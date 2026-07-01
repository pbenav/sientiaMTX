<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class OnlyOfficeController extends Controller
{
    /**
     * Display the document editor.
     */
    public function edit(TaskAttachment $attachment)
    {
        $startTime = microtime(true);
        Log::info("[OnlyOffice Profiling] Iniciando edit() para TaskAttachment {$attachment->id}");

        // Ensure the file exists locally
        if ($attachment->storage_provider === 'google') {
            return back()->with('error', 'La edición en OnlyOffice no soporta archivos en Google Drive.');
        }

        // Ensure the user can access it
        // For absolute security, check team membership
        $team = $attachment->getTeam();
        if (!$team || !$attachment->canBeAccessedBy(auth()->user(), $team)) {
            abort(403, 'No autorizado.');
        }
        
        Log::info("[OnlyOffice Profiling] Auth validado en " . round((microtime(true) - $startTime) * 1000, 2) . "ms");

        // Define supported extensions and resolve document type
        $ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
        $docType = $this->getDocumentType($ext);

        if (!$docType) {
            return back()->with('error', 'Este tipo de archivo no es compatible con el editor online.');
        }

        $configUrl = config('onlyoffice.url');
        $apiUrl = rtrim($configUrl, '/') . '/web-apps/apps/api/documents/api.js';

        // Generate a persistent Key for tracking collaborative revisions
        // Using a combination of attachment ID and its updated_at makes sure 
        // if the file changes externally, a new session starts.
        $key = md5($attachment->id . '_' . $attachment->updated_at->timestamp);

        // Construir las URLs que OnlyOffice usará para descargar y hacer callback.
        // Usa la IP interna si está configurada para que OnlyOffice conecte directamente por LAN.
        // IMPORTANTE: Usa config() no env() para que funcione con config:cache activo.
        $baseUrl = rtrim(config('onlyoffice.internal_app_url', config('app.url')), '/');
        $downloadUrl = $baseUrl . '/onlyoffice/download/' . $attachment->id . '?v=' . $key;
        $callbackUrl = $baseUrl . '/onlyoffice/callback/' . $attachment->id;



        $config = [
            'document' => [
                'fileType' => $ext,
                'key' => $key,
                'title' => $attachment->file_name,
                'url' => $downloadUrl,
                'permissions' => [
                    'comment' => true,
                    'download' => true,
                    'edit' => true,
                    'print' => true,
                    'review' => true,
                ],
            ],
            'documentType' => $docType,
            'editorConfig' => [
                'callbackUrl' => $callbackUrl,
                'lang' => 'es',
                'mode' => 'edit',
                'user' => [
                    'id' => (string)auth()->id(),
                    'name' => auth()->user()->name,
                ],
                'customization' => [
                    'autosave' => true,
                    'chat' => true,
                    'comments' => true,
                    'forcesave' => true, // set to true if you want explicit save button triggered sync
                    'logo' => [
                        'image' => asset('img/logo.png'), // optional
                        'url' => config('app.url'),
                    ],
                ]
            ],
        ];

        // Sign with JWT if Secret exists (RECOMMENDED)
        $secret = config('onlyoffice.secret');
        $token = null;
        if (!empty($secret)) {
            $payload = $config;
            $payload['iat'] = time();
            $payload['exp'] = time() + (60 * 60); // 1 hour
            $token = JWT::encode($payload, $secret, 'HS256');
            // In newer versions, the token MUST also be inside the object if explicitly passed
            $config['token'] = $token;
        }

        Log::info("[OnlyOffice Profiling] edit() JWT y payload generados. Renderizando vista. Tiempo total: " . round((microtime(true) - $startTime) * 1000, 2) . "ms");

        return view('onlyoffice.editor', compact('apiUrl', 'config', 'attachment', 'token'));
    }

    /**
     * Create a new empty document, save it as a task attachment, and open the editor.
     */
    public function createDocument(Request $request, Team $team, Task $task)
    {
        $this->authorize('update', $task);

        $type = $request->input('type', 'docx'); // docx | xlsx | pptx
        $allowedTypes = ['docx', 'xlsx', 'pptx'];
        if (!in_array($type, $allowedTypes)) {
            abort(422, 'Tipo de documento no soportado.');
        }

        // Map of minimal valid empty Office Open XML files (base64-encoded stubs)
        // These are the smallest valid files OnlyOffice can open and edit.
        $stubs = [
            'docx' => base64_decode(
                'UEsDBBQABgAIAAAAIQDfpNJsWgEAACAFAAATAAAAW0NvbnRlbnRfVHlwZXNdLnhtbKyUy07DMBBF9'
                . '5X8D5G3KHFBCKEkBb6AJdqkjT3jVqrG9njx92XaIkQklF2k7Mw9d8bHk9V6N0y9DyihJJPgVEi'
                . 'QJolNBPWL1DCXY3pJAw2IxMzYoGJ2gT2eHbp5KL5aMCB4g0GjF7NiIDApJFCBq0WnSEoYdqiE'
                . 'oBsG6LBQQ2IoDsMiT6lqEhJYISxHT6XbDEBJB+YdBQAA'
            ),
            'xlsx' => base64_decode(
                'UEsDBBQABgAIAAAAIQCj/R0tGQEAAMkBAAATAAAAW0NvbnRlbnRfVHlwZXNdLnhtbIyRQU7DMBBF9'
                . 'yn8D5G3KHFBKYqSVUJKoQmHkKFrY40Txx6PvDe8XdIilQ1kN++9P+O/F4vdaDI/GQVAqZlOSp'
                . 'qKMWzEqBKgCIBqHhbAJOkCPPeDwgCJKxFOBIQ3D2TuCM1gCN+YhBQAA'
            ),
            'pptx' => base64_decode(
                'UEsDBBQABgAIAAAAIQDw94q6GQEAAIEBAAATAAAAdGhlbWUvdGhlbWUxLnhtbKyRQU7DMBBF9yn8'
                . 'D5G3KHFBKYqSVUJKoQmHkKGjY00cxx6PvHe8JdIilQ1kN++9P+O/F4vdaDI/GQVAqZlOSoqK'
                . 'MVzEqBKgCIBqHhbAJOkCPPeDwgCJKxFOBIQ3D2TuCM1gCN+YhBQAA'
            ),
        ];

        // Build file name: YYYY-MM-DD-{taskslug}.{ext}
        $date    = now()->format('Y-m-d');
        $slug    = Str::slug($task->title, '-');
        $fileName = "{$date}-{$slug}.{$type}";

        // Store the file in the task's attachment folder
        $directory = 'attachments/tasks/' . $task->id;
        $filePath  = $directory . '/' . $fileName;
        Storage::disk('public')->makeDirectory($directory);

        // Write minimal valid file content
        // If a stub is not valid for opening, OnlyOffice will still create a new doc.
        $emptyContent = match($type) {
            'docx'  => $this->minimalDocx(),
            'xlsx'  => $this->minimalXlsx(),
            'pptx'  => $this->minimalPptx(),
            default => '',
        };

        Storage::disk('public')->put($filePath, $emptyContent);

        $mimeMap = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        // Register it as a TaskAttachment
        $attachment = TaskAttachment::create([
            'attachable_type'  => Task::class,
            'attachable_id'    => $task->id,
            'user_id'          => auth()->id(),
            'file_name'        => $fileName,
            'file_path'        => $filePath,
            'file_size'        => strlen($emptyContent),
            'mime_type'        => $mimeMap[$type],
            'storage_provider' => 'local',
        ]);

        Log::info("[OnlyOffice] Nuevo documento '{$fileName}' creado y vinculado a Tarea ID {$task->id}.");

        // Redirect straight to the editor
        return redirect()->route('onlyoffice.edit', $attachment);
    }

    /** Returns a minimal but valid empty .docx (Word) file content */
    private function minimalDocx(): string
    {
        // Minimal valid OOXML .docx with empty body
        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'docx_');
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:cx="http://schemas.microsoft.com/office/drawing/2014/chartex" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" xmlns:w16cex="http://schemas.microsoft.com/office/word/2018/wordml/cex" xmlns:w16cid="http://schemas.microsoft.com/office/word/2016/wordml/cid" xmlns:w16="http://schemas.microsoft.com/office/word/2018/wordml" xmlns:w16sdtdh="http://schemas.microsoft.com/office/word/2020/wordml/sdtdatahash" xmlns:w16se="http://schemas.microsoft.com/office/word/2015/wordml/symex" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 w15 w16se w16cid w16 w16cex w16sdtdh wp14"><w:body><w:p><w:pPr><w:rPr><w:lang w:val="es-ES"/></w:rPr></w:pPr></w:p><w:sectPr/></w:body></w:document>');
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
        $zip->close();
        $content = file_get_contents($tmp);
        unlink($tmp);
        return $content;
    }

    /** Returns a minimal but valid empty .xlsx (Excel) file content */
    private function minimalXlsx(): string
    {
        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Hoja1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData/></worksheet>');
        $zip->close();
        $content = file_get_contents($tmp);
        unlink($tmp);
        return $content;
    }

    /** Returns a minimal but valid empty .pptx (PowerPoint) file content */
    private function minimalPptx(): string
    {
        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'pptx_');
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/><Override PartName="/ppt/slides/slide1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/><Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/><Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/></Relationships>');
        $zip->addFromString('ppt/presentation.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentation xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" saveSubsetFonts="1"><p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst><p:sldSz cx="9144000" cy="6858000"/><p:notesSz cx="6858000" cy="9144000"/><p:sldIdLst><p:sldId id="256" r:id="rId2"/></p:sldIdLst></p:presentation>');
        $zip->addFromString('ppt/_rels/presentation.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide1.xml"/></Relationships>');
        $zip->addFromString('ppt/slides/slide1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:cSld><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>');
        $zip->addFromString('ppt/slides/_rels/slide1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/></Relationships>');
        $zip->addFromString('ppt/slideLayouts/slideLayout1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1"><p:cSld name="En blanco"><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sldLayout>');
        $zip->addFromString('ppt/slideLayouts/_rels/slideLayout1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/></Relationships>');
        $zip->addFromString('ppt/slideMasters/slideMaster1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:cSld><p:bg><p:bgRef idx="1001"><a:schemeClr val="bg1"/></p:bgRef></p:bg><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/><p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst><p:txStyles><p:titleStyle><a:lvl1pPr algn="ctr" defTabSz="914400" rtl="0" eaLnBrk="1" latinLnBrk="0" hangingPunct="1"><a:spcBef><a:spcPct val="0"/></a:spcBef><a:buNone/><a:defRPr lang="es-ES" sz="4400" kern="1200"><a:solidFill><a:schemeClr val="tx1"/></a:solidFill><a:latin typeface="+mj-lt"/><a:ea typeface="+mj-ea"/><a:cs typeface="+mj-cs"/></a:defRPr></a:lvl1pPr></p:titleStyle><p:bodyStyle/><p:otherStyle/></p:txStyles></p:sldMaster>');
        $zip->addFromString('ppt/slideMasters/_rels/slideMaster1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/></Relationships>');
        $zip->close();
        $content = file_get_contents($tmp);
        unlink($tmp);
        return $content;
    }

    /**
     * Secured endpoint for OnlyOffice Server to download the raw file.
     */
    public function downloadFile(Request $request, TaskAttachment $attachment)
    {
        $startTime = microtime(true);
        Log::info("[OnlyOffice Profiling] Iniciando downloadFile() para TaskAttachment {$attachment->id} desde IP {$request->ip()}");

        // Validar que la petición viene de la IP de OnlyOffice o tiene firma válida.
        $onlyOfficeIp = parse_url(config('onlyoffice.internal_server_url', ''), PHP_URL_HOST);
        $clientIp = $request->ip();

        $isAuthorized = $request->hasValidSignature() || ($onlyOfficeIp && $clientIp === $onlyOfficeIp);

        if (!$isAuthorized) {
            Log::warning("[OnlyOffice] Acceso denegado a descarga. IP: {$clientIp}, esperada: {$onlyOfficeIp}");
            abort(403, 'No autorizado.');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Archivo no encontrado en disco.');
        }

        Log::info("[OnlyOffice Profiling] Iniciando descarga binaria en downloadFile(). Tiempo hasta aquí: " . round((microtime(true) - $startTime) * 1000, 2) . "ms");

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Callback receiver for OnlyOffice saving cycles.
     */
    public function callback(Request $request, TaskAttachment $attachment)
    {
        // Read request payload (Sent as application/json body)
        $body = $request->json()->all();

        // Check JWT Signature if configured (Crucial for security!)
        $secret = config('onlyoffice.secret');
        if (!empty($secret)) {
            // OnlyOffice envía el token en el Body 'token', en la cabecera configurada (Authorization) o en 'X-CDES-JWT'
            $token = $body['token'] ?? $request->header('Authorization') ?? $request->header('X-CDES-JWT');
            
            if (!$token) {
                Log::warning("[OnlyOffice] Invalid callback received: No token provided.");
                return response()->json(['error' => 1, 'message' => 'Authentication required']);
            }

            try {
                // Decode and strip "Bearer " if sent in header
                $jwtStr = Str::startsWith($token, 'Bearer ') ? substr($token, 7) : $token;
                $decoded = (array) JWT::decode($jwtStr, new \Firebase\JWT\Key($secret, 'HS256'));
                // The actual body might be nested in $decoded['payload'] depending on OnlyOffice settings
                // but standard behavior overrides body if correct.
            } catch (\Exception $e) {
                Log::error("[OnlyOffice] JWT Decoded fail: " . $e->getMessage());
                return response()->json(['error' => 1, 'message' => 'Invalid token signature']);
            }
        }

        // Check status
        // 2 = Ready for saving
        // 6 = Editing ended, being saved automatically
        $status = (int) ($body['status'] ?? 0);

        if ($status === 2 || $status === 6) {
            $downloadUri = $body['url'] ?? null;
            if (!$downloadUri) {
                Log::error("[OnlyOffice] Status 2 but no download URL received for Attachment {$attachment->id}");
                return response()->json(['error' => 1, 'message' => 'No download URL provided by editor']);
            }

            try {
                // OPTIMIZACIÓN PARA RED INTERNA:
                // Si Laravel también debe conectar internamente a OnlyOffice para guardar cambios
                $internalServerUrl = config('onlyoffice.internal_server_url'); // Ej: http://192.168.10.152
                if (!empty($internalServerUrl)) {
                    $publicServerUrl = rtrim(config('onlyoffice.url'), '/');
                    $downloadUri = str_replace($publicServerUrl, rtrim($internalServerUrl, '/'), $downloadUri);
                }

                // Download the modified file from OnlyOffice server temporary storage
                $newFileContent = file_get_contents($downloadUri);
                if ($newFileContent === false) {
                    throw new \Exception("Could not retrieve file from {$downloadUri}");
                }

                // Update existing file in our Storage
                Storage::disk('public')->put($attachment->file_path, $newFileContent);
                
                // Update metadata: file size
                $attachment->update([
                    'file_size' => strlen($newFileContent),
                    'updated_at' => now()
                ]);

                Log::info("[OnlyOffice] Attachment ID {$attachment->id} ({$attachment->file_name}) updated and saved successfully via callback.");
                
                // Trigger audit log if module exists
                \App\Models\AttachmentLog::create([
                    'attachment_id' => $attachment->id,
                    'user_id' => $body['users'][0] ?? null, // Could use current editor ID if passed
                    'action' => 'edited',
                    'details' => 'Edición completada mediante OnlyOffice.',
                ]);

            } catch (\Exception $e) {
                Log::error("[OnlyOffice] Error updating file for Attachment {$attachment->id}: " . $e->getMessage());
                return response()->json(['error' => 1, 'message' => 'Internal write failure']);
            }
        }

        // Always return {"error": 0} to tell OnlyOffice to keep calm.
        return response()->json(['error' => 0]);
    }

    private function getDocumentType($ext): ?string
    {
        $map = config('onlyoffice.extensions');
        if (in_array($ext, $map['word'])) return 'word';
        if (in_array($ext, $map['cell'])) return 'cell';
        if (in_array($ext, $map['slide'])) return 'slide';
        return null;
    }

    /**
     * Display the document editor for Activity v2.
     */
    public function editActivity(ActivityAttachment $attachment)
    {
        $startTime = microtime(true);
        Log::info("[OnlyOffice Profiling] Iniciando editActivity() para ActivityAttachment {$attachment->id}");

        // Ensure the user can access it
        $activity = $attachment->activity;
        if (!$activity || auth()->user()->cannot('view', $activity)) {
            abort(403, 'No autorizado.');
        }

        Log::info("[OnlyOffice Profiling] Auth validado en " . round((microtime(true) - $startTime) * 1000, 2) . "ms");

        // Define supported extensions and resolve document type
        $ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
        $docType = $this->getDocumentType($ext);

        if (!$docType) {
            return back()->with('error', 'Este tipo de archivo no es compatible con el editor online.');
        }

        $configUrl = config('onlyoffice.url');
        $apiUrl = rtrim($configUrl, '/') . '/web-apps/apps/api/documents/api.js';

        $key = md5($attachment->id . '_' . $attachment->updated_at->timestamp);

        $baseUrl = rtrim(config('onlyoffice.internal_app_url', config('app.url')), '/');
        // Usamos las nuevas rutas de actividad
        $downloadUrl = $baseUrl . '/onlyoffice/activity-download/' . $attachment->id . '?v=' . $key;
        $callbackUrl = $baseUrl . '/onlyoffice/activity-callback/' . $attachment->id;

        $config = [
            'document' => [
                'fileType' => $ext,
                'key' => $key,
                'title' => $attachment->file_name,
                'url' => $downloadUrl,
                'permissions' => [
                    'comment' => true,
                    'download' => true,
                    'edit' => true,
                    'print' => true,
                    'review' => true,
                ],
            ],
            'documentType' => $docType,
            'editorConfig' => [
                'callbackUrl' => $callbackUrl,
                'lang' => 'es',
                'mode' => 'edit',
                'user' => [
                    'id' => (string)auth()->id(),
                    'name' => auth()->user()->name,
                ],
                'customization' => [
                    'autosave' => true,
                    'chat' => true,
                    'comments' => true,
                    'forcesave' => true,
                    'logo' => [
                        'image' => asset('img/logo.png'),
                        'url' => config('app.url'),
                    ],
                ]
            ],
        ];

        $secret = config('onlyoffice.secret');
        $token = null;
        if (!empty($secret)) {
            $payload = $config;
            $payload['iat'] = time();
            $payload['exp'] = time() + (60 * 60);
            $token = JWT::encode($payload, $secret, 'HS256');
            $config['token'] = $token;
        }

        Log::info("[OnlyOffice Profiling] editActivity() JWT y payload generados. Renderizando vista. Tiempo total: " . round((microtime(true) - $startTime) * 1000, 2) . "ms");

        return view('onlyoffice.editor', compact('apiUrl', 'config', 'attachment', 'token'));
    }

    /**
     * Create a new empty document for Activity v2.
     */
    public function createActivityDocument(Request $request, Team $team, Activity $activity)
    {
        $this->authorize('update', $activity);

        $type = $request->input('type', 'docx');
        $allowedTypes = ['docx', 'xlsx', 'pptx'];
        if (!in_array($type, $allowedTypes)) {
            abort(422, 'Tipo de documento no soportado.');
        }

        $date    = now()->format('Y-m-d');
        $slug    = Str::slug($activity->title, '-');
        $fileName = "{$date}-{$slug}.{$type}";

        $directory = 'attachments/activities/' . $activity->id;
        $filePath  = $directory . '/' . $fileName;
        Storage::disk('public')->makeDirectory($directory);

        $emptyContent = match($type) {
            'docx'  => $this->minimalDocx(),
            'xlsx'  => $this->minimalXlsx(),
            'pptx'  => $this->minimalPptx(),
            default => '',
        };

        Storage::disk('public')->put($filePath, $emptyContent);

        $mimeMap = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        // Register it as an ActivityAttachment
        $attachment = ActivityAttachment::create([
            'activity_id'      => $activity->id,
            'uploaded_by_id'   => auth()->id(),
            'file_name'        => $fileName,
            'file_path'        => $filePath,
            'file_size'        => strlen($emptyContent),
            'mime_type'        => $mimeMap[$type],
            'disk'             => 'public',
        ]);

        Log::info("[OnlyOffice] Nuevo documento '{$fileName}' creado y vinculado a Activity ID {$activity->id}.");

        return redirect()->route('onlyoffice.activity.edit', $attachment);
    }

    /**
     * Secured endpoint for OnlyOffice Server to download the raw Activity file.
     */
    public function downloadActivityFile(Request $request, ActivityAttachment $attachment)
    {
        $startTime = microtime(true);
        Log::info("[OnlyOffice Profiling] Iniciando downloadActivityFile() para ActivityAttachment {$attachment->id} desde IP {$request->ip()}");

        $onlyOfficeIp = parse_url(config('onlyoffice.internal_server_url', ''), PHP_URL_HOST);
        $clientIp = $request->ip();

        $isAuthorized = $request->hasValidSignature() || ($onlyOfficeIp && $clientIp === $onlyOfficeIp);

        if (!$isAuthorized) {
            Log::warning("[OnlyOffice] Acceso denegado a descarga Activity. IP: {$clientIp}, esperada: {$onlyOfficeIp}");
            abort(403, 'No autorizado.');
        }

        if (!Storage::disk($attachment->disk ?? 'public')->exists($attachment->file_path)) {
            abort(404, 'Archivo no encontrado en disco.');
        }

        Log::info("[OnlyOffice Profiling] Iniciando descarga binaria en downloadActivityFile(). Tiempo hasta aquí: " . round((microtime(true) - $startTime) * 1000, 2) . "ms");

        return Storage::disk($attachment->disk ?? 'public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Callback receiver for OnlyOffice saving cycles on Activity.
     */
    public function activityCallback(Request $request, ActivityAttachment $attachment)
    {
        $body = $request->json()->all();

        $secret = config('onlyoffice.secret');
        if (!empty($secret)) {
            $token = $body['token'] ?? $request->header('Authorization') ?? $request->header('X-CDES-JWT');
            if (!$token) {
                return response()->json(['error' => 1, 'message' => 'Authentication required']);
            }
            try {
                $jwtStr = Str::startsWith($token, 'Bearer ') ? substr($token, 7) : $token;
                JWT::decode($jwtStr, new \Firebase\JWT\Key($secret, 'HS256'));
            } catch (\Exception $e) {
                return response()->json(['error' => 1, 'message' => 'Invalid token signature']);
            }
        }

        $status = (int) ($body['status'] ?? 0);

        if ($status === 2 || $status === 6) {
            $downloadUri = $body['url'] ?? null;
            if (!$downloadUri) {
                return response()->json(['error' => 1, 'message' => 'No download URL provided by editor']);
            }

            try {
                $internalServerUrl = config('onlyoffice.internal_server_url');
                if (!empty($internalServerUrl)) {
                    $publicServerUrl = rtrim(config('onlyoffice.url'), '/');
                    $downloadUri = str_replace($publicServerUrl, rtrim($internalServerUrl, '/'), $downloadUri);
                }

                $newFileContent = file_get_contents($downloadUri);
                if ($newFileContent === false) {
                    throw new \Exception("Could not retrieve file from {$downloadUri}");
                }

                Storage::disk($attachment->disk ?? 'public')->put($attachment->file_path, $newFileContent);
                
                $attachment->update([
                    'file_size' => strlen($newFileContent),
                    'updated_at' => now()
                ]);

                Log::info("[OnlyOffice] ActivityAttachment ID {$attachment->id} ({$attachment->file_name}) updated.");
                
                $attachment->activity->histories()->create([
                    'user_id' => $body['users'][0] ?? null,
                    'action' => 'edited',
                    'details' => json_encode(['note' => 'Edición completada mediante OnlyOffice.']),
                ]);

            } catch (\Exception $e) {
                Log::error("[OnlyOffice] Error updating Activity file for Attachment {$attachment->id}: " . $e->getMessage());
                return response()->json(['error' => 1, 'message' => 'Internal write failure']);
            }
        }

        return response()->json(['error' => 0]);
    }
}
