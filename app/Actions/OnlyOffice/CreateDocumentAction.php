<?php

namespace App\Actions\OnlyOffice;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\OnlyOfficeService;

class CreateDocumentAction
{
    public function __construct(protected OnlyOfficeService $onlyOfficeService) {}

    public function execute($model, string $type, string $fileNameRaw, string $directory): array
    {
        $allowedTypes = ['docx', 'xlsx', 'pptx'];
        if (!in_array($type, $allowedTypes)) {
            abort(422, 'Tipo de documento no soportado.');
        }

        $date    = now()->format('Y-m-d');
        $slug    = Str::slug(pathinfo($fileNameRaw, PATHINFO_FILENAME), '-');
        $fileName = "{$date}-{$slug}.{$type}";

        $filePath  = $directory . '/' . Str::uuid() . '_' . $fileName;
        Storage::disk('public')->makeDirectory($directory);

        $emptyContent = match($type) {
            'docx'  => $this->onlyOfficeService->minimalDocx(),
            'xlsx'  => $this->onlyOfficeService->minimalXlsx(),
            'pptx'  => $this->onlyOfficeService->minimalPptx(),
            default => '',
        };

        Storage::disk('public')->put($filePath, $emptyContent);

        $mimeMap = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => strlen($emptyContent),
            'mime_type' => $mimeMap[$type],
        ];
    }
}
