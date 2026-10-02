<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use App\Models\ActivityAttachment;
use App\Models\Task;
use App\Models\Team;
use App\Models\Expediente;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\OnlyOfficeService;
use App\Actions\OnlyOffice\CreateDocumentAction;

class OnlyOfficeController extends Controller
{
    public function __construct(
        protected OnlyOfficeService $onlyOfficeService,
        protected CreateDocumentAction $createDocumentAction
    ) {}

    public function edit(TaskAttachment $attachment)
    {
        $this->authorize('view', $attachment->task->team ?? $attachment->attachable->team);

        $serverUrl = rtrim(config('onlyoffice.url'), '/');
        if (empty($serverUrl)) {
            abort(500, 'Servidor de OnlyOffice no configurado.');
        }
        $apiUrl = $serverUrl . '/web-apps/apps/api/documents/api.js';

        $baseUrl = rtrim(config('onlyoffice.internal_app_url', config('app.url')), '/');
        $downloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'onlyoffice.download',
            now()->addHours(2),
            ['attachment' => $attachment->id]
        );
        $appUrl = rtrim(config('app.url'), '/');
        if ($baseUrl !== $appUrl) {
            $downloadUrl = str_replace($appUrl, $baseUrl, $downloadUrl);
        }
        $callbackUrl = $baseUrl . '/onlyoffice/callback/' . $attachment->id;

        $config = $this->onlyOfficeService->buildConfig(
            $attachment,
            auth()->user(),
            $callbackUrl,
            $downloadUrl,
            $attachment->file_name,
            'edit'
        );
        $token = $config['token'] ?? null;

        return view('onlyoffice.editor', [
            'config' => $config,
            'apiUrl' => $apiUrl,
            'serverUrl' => $serverUrl,
            'attachment' => $attachment,
            'token' => $token,
            'backUrl' => url()->previous()
        ]);
    }

    public function createDocument(Request $request, Team $team, Task $task)
    {
        $request->validate([
            'file_name' => 'required|string|max:200',
            'type'      => 'required|in:docx,xlsx,pptx',
        ]);

        $fileData = $this->createDocumentAction->execute($task, $request->type, $request->file_name, "teams/{$team->id}/tasks/{$task->id}");

        $attachment = $task->attachments()->create([
            'file_name' => $fileData['file_name'],
            'file_path' => $fileData['file_path'],
            'file_type' => $fileData['mime_type'],
            'file_size' => $fileData['file_size'],
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('onlyoffice.edit', $attachment);
    }

    public function createExpedienteDocument(Request $request, Team $team, Expediente $expediente)
    {
        $this->authorize('update', $expediente);

        $request->validate([
            'file_name' => 'nullable|string|max:200',
            'type'      => 'nullable|in:docx,xlsx,pptx',
        ]);

        $type = $request->input('type', 'docx');
        $fileNameRaw = $request->input('file_name', $expediente->title);

        $fileData = $this->createDocumentAction->execute($expediente, $type, $fileNameRaw, "attachments/expedientes/{$expediente->id}");

        $attachment = TaskAttachment::create([
            'attachable_type'  => Expediente::class,
            'attachable_id'    => $expediente->id,
            'user_id'          => auth()->id(),
            'file_name'        => $fileData['file_name'],
            'file_path'        => $fileData['file_path'],
            'file_size'        => $fileData['file_size'],
            'mime_type'        => $fileData['mime_type'],
            'storage_provider' => 'local',
        ]);

        Log::info("[OnlyOffice] Nuevo documento '{$fileData['file_name']}' creado y vinculado a Expediente ID {$expediente->id}.");

        return redirect()->route('onlyoffice.edit', $attachment);
    }

    public function downloadFile(Request $request, TaskAttachment $attachment)
    {
        $onlyOfficeIp = parse_url(config('onlyoffice.internal_server_url', ''), PHP_URL_HOST);
        $isAuthorized = $request->hasValidSignature() || ($onlyOfficeIp && $request->ip() === $onlyOfficeIp);

        if (!$isAuthorized) {
            abort(403, 'No autorizado.');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Archivo no encontrado en disco.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function callback(Request $request, TaskAttachment $attachment)
    {
        $result = $this->onlyOfficeService->handleCallback($request, $attachment, function($userId) use ($attachment) {
            \App\Models\AttachmentLog::create([
                'attachment_id' => $attachment->id,
                'user_id' => $userId,
                'action' => 'edited',
                'details' => 'Edición completada mediante OnlyOffice.',
            ]);
        });

        return response()->json($result);
    }

    public function editActivity(ActivityAttachment $attachment)
    {
        $this->authorize('view', $attachment->activity->team);

        $serverUrl = rtrim(config('onlyoffice.url'), '/');
        if (empty($serverUrl)) {
            abort(500, 'Servidor de OnlyOffice no configurado.');
        }
        $apiUrl = $serverUrl . '/web-apps/apps/api/documents/api.js';

        $baseUrl = rtrim(config('onlyoffice.internal_app_url', config('app.url')), '/');
        $downloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'onlyoffice.activity.download',
            now()->addHours(2),
            ['attachment' => $attachment->id]
        );
        $appUrl = rtrim(config('app.url'), '/');
        if ($baseUrl !== $appUrl) {
            $downloadUrl = str_replace($appUrl, $baseUrl, $downloadUrl);
        }
        $callbackUrl = $baseUrl . '/onlyoffice/activity-callback/' . $attachment->id;

        $config = $this->onlyOfficeService->buildConfig(
            $attachment,
            auth()->user(),
            $callbackUrl,
            $downloadUrl,
            $attachment->file_name,
            'edit'
        );
        $token = $config['token'] ?? null;

        return view('onlyoffice.editor', [
            'config' => $config,
            'apiUrl' => $apiUrl,
            'serverUrl' => $serverUrl,
            'attachment' => $attachment,
            'token' => $token,
            'backUrl' => url()->previous()
        ]);
    }

    public function createActivityDocument(Request $request, Team $team, Activity $activity)
    {
        $this->authorize('update', $activity);

        $type = $request->input('type', 'docx');
        $fileNameRaw = $request->input('file_name', $activity->title);

        $fileData = $this->createDocumentAction->execute($activity, $type, $fileNameRaw, "attachments/activities/{$activity->id}");

        $attachment = ActivityAttachment::create([
            'activity_id'      => $activity->id,
            'uploaded_by_id'   => auth()->id(),
            'file_name'        => $fileData['file_name'],
            'file_path'        => $fileData['file_path'],
            'file_size'        => $fileData['file_size'],
            'mime_type'        => $fileData['mime_type'],
            'disk'             => 'public',
        ]);

        Log::info("[OnlyOffice] Nuevo documento '{$fileData['file_name']}' creado y vinculado a Activity ID {$activity->id}.");

        return redirect()->route('onlyoffice.activity.edit', $attachment);
    }

    public function downloadActivityFile(Request $request, ActivityAttachment $attachment)
    {
        $onlyOfficeIp = parse_url(config('onlyoffice.internal_server_url', ''), PHP_URL_HOST);
        $isAuthorized = $request->hasValidSignature() || ($onlyOfficeIp && $request->ip() === $onlyOfficeIp);

        if (!$isAuthorized) {
            abort(403, 'No autorizado.');
        }

        if (!Storage::disk($attachment->disk ?? 'public')->exists($attachment->file_path)) {
            abort(404, 'Archivo no encontrado en disco.');
        }

        return Storage::disk($attachment->disk ?? 'public')->download($attachment->file_path, $attachment->file_name);
    }

    public function activityCallback(Request $request, ActivityAttachment $attachment)
    {
        $result = $this->onlyOfficeService->handleCallback($request, $attachment, function($userId) use ($attachment) {
            $attachment->activity->histories()->create([
                'user_id' => $userId,
                'action' => 'edited',
                'details' => json_encode(['note' => 'Edición completada mediante OnlyOffice.']),
            ]);
        });

        return response()->json($result);
    }
}
