<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class OnlyOfficeService
{
        public function buildConfig($attachment, $user, string $callbackUrl, string $downloadUrl, string $title, string $mode = 'edit'): array
    {
        $ext = pathinfo($attachment->file_name, PATHINFO_EXTENSION);
        $docType = $this->getDocumentType($ext);
        
        if (!$docType) {
            // Fallback for missing extension in DB if we know the mime type
            $mime = $attachment->mime_type ?? $attachment->file_type ?? '';
            if (str_contains($mime, 'wordprocessingml')) { $ext = 'docx'; $docType = 'word'; }
            elseif (str_contains($mime, 'spreadsheetml')) { $ext = 'xlsx'; $docType = 'cell'; }
            elseif (str_contains($mime, 'presentationml')) { $ext = 'pptx'; $docType = 'slide'; }
            else {
                abort(422, "El archivo '{$attachment->file_name}' no tiene una extensión válida ni un tipo MIME compatible con OnlyOffice.");
            }
        }

        $secret = config('onlyoffice.secret');

        $key = md5($attachment->id . '_' . $attachment->updated_at->timestamp);

        $config = [
            'document' => [
                'fileType' => strtolower($ext),
                'key' => $key,
                'title' => $title,
                'url' => $downloadUrl,
                'permissions' => [
                    'edit' => $mode === 'edit',
                    'download' => true,
                ],
            ],
            'documentType' => $docType,
            'editorConfig' => [
                'callbackUrl' => $callbackUrl,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'mode' => $mode,
                'lang' => app()->getLocale(),
                'customization' => [
                    'forcesave' => true,
                    'autosave' => true,
                ],
            ]
        ];

        if (!empty($secret)) {
            $payload = $config;
            $payload['iat'] = time();
            $payload['exp'] = time() + (60 * 60);
            $config['token'] = JWT::encode($payload, $secret, 'HS256');
        }

        return $config;
    }

    public function handleCallback(Request $request, $attachment, callable $logActivity)
    {
        $body = $request->json()->all();

        $secret = config('onlyoffice.secret');
        if (!empty($secret)) {
            $token = $body['token'] ?? $request->header('Authorization') ?? $request->header('X-CDES-JWT');
            
            if (!$token) {
                Log::warning("[OnlyOffice] Invalid callback received: No token provided.");
                return ['error' => 1, 'message' => 'Authentication required'];
            }

            try {
                $jwtStr = Str::startsWith($token, 'Bearer ') ? substr($token, 7) : $token;
                JWT::decode($jwtStr, new \Firebase\JWT\Key($secret, 'HS256'));
            } catch (\Exception $e) {
                Log::error("[OnlyOffice] JWT Decoded fail: " . $e->getMessage());
                return ['error' => 1, 'message' => 'Invalid token signature'];
            }
        }

        $status = (int) ($body['status'] ?? 0);

        if ($status === 2 || $status === 6) {
            $downloadUri = $body['url'] ?? null;
            if (!$downloadUri) {
                Log::error("[OnlyOffice] Status 2 but no download URL received for Attachment {$attachment->id}");
                return ['error' => 1, 'message' => 'No download URL provided by editor'];
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

                Storage::disk('public')->put($attachment->file_path, $newFileContent);
                
                $attachment->update([
                    'file_size' => strlen($newFileContent),
                    'updated_at' => now()
                ]);

                Log::info("[OnlyOffice] Attachment ID {$attachment->id} updated successfully via callback.");
                
                $userId = $body['users'][0] ?? null;
                $logActivity($userId);

            } catch (\Exception $e) {
                Log::error("[OnlyOffice] Error updating file for Attachment {$attachment->id}: " . $e->getMessage());
                return ['error' => 1, 'message' => 'Internal write failure'];
            }
        }

        return ['error' => 0];
    }

    public function minimalDocx(): string
    {
        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'docx_');
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t></w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        $fileContent = file_get_contents($tmp);
        unlink($tmp);
        return $fileContent;
    }

    public function minimalXlsx(): string
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
        $fileContent = file_get_contents($tmp);
        unlink($tmp);
        return $fileContent;
    }

    public function minimalPptx(): string
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
        $fileContent = file_get_contents($tmp);
        unlink($tmp);
        return $fileContent;
    }

    public function getDocumentType($ext): ?string
    {
        $word = ['doc', 'docx', 'rtf', 'odt', 'txt'];
        $cell = ['xls', 'xlsx', 'ods', 'csv'];
        $slide = ['ppt', 'pptx', 'odp'];

        $e = strtolower($ext);

        if (in_array($e, $word)) return 'word';
        if (in_array($e, $cell)) return 'cell';
        if (in_array($e, $slide)) return 'slide';

        return null;
    }
}
