<?php

namespace App\Services\Mermaid;

use Illuminate\Support\Facades\Log;

class MermaidRendererService
{
    /**
     * Cache en memoria de diagramas ya renderizados en la petición actual.
     */
    protected array $cache = [];

    /**
     * Detecta y sustituye los bloques de código Mermaid en el HTML por imágenes PNG base64
     * optimizadas para incrustación directa en documentos DomPDF.
     */
    public function renderMermaidBlocksInHtml(string $html): string
    {
        if (!str_contains($html, 'mermaid')) {
            return $html;
        }

        // Patrón para capturar bloques Markdown estándar: <pre><code class="language-mermaid">...</code></pre>
        $pattern = '/<pre\b[^>]*><code\b[^>]*class=["\'][^"\']*(?:language-mermaid|lang-mermaid|mermaid)[^"\']*["\'][^>]*>(.*?)<\/code><\/pre>/si';

        return preg_replace_callback($pattern, function ($matches) {
            $rawCode = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (empty($rawCode)) {
                return $matches[0];
            }

            $pngBase64 = $this->renderDiagramToPng($rawCode);
            if (!$pngBase64) {
                return $matches[0];
            }

            return '<div class="mermaid-diagram" style="text-align: center; margin: 18px auto; page-break-inside: avoid;">'
                . '<img src="data:image/png;base64,' . $pngBase64 . '" style="max-width: 92%; max-height: 460px; height: auto;" alt="Diagrama Mermaid">'
                . '</div>';
        }, $html);
    }

    /**
     * Renderiza un bloque de código Mermaid a PNG base64 recortado usando el motor headless Chromium local.
     */
    public function renderDiagramToPng(string $mermaidCode): ?string
    {
        $hash = md5($mermaidCode);
        if (isset($this->cache[$hash])) {
            return $this->cache[$hash];
        }

        $mermaidJs = base_path('node_modules/mermaid/dist/mermaid.min.js');
        if (!file_exists($mermaidJs)) {
            Log::warning('[MermaidRendererService] mermaid.min.js no encontrado en node_modules.');
            return null;
        }

        // Detectar binario de chromium
        $chromium = null;
        foreach (['/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'] as $bin) {
            if (file_exists($bin) && is_executable($bin)) {
                $chromium = $bin;
                break;
            }
        }

        if (!$chromium) {
            return null;
        }

        $tmpHtml = tempnam(sys_get_temp_dir(), 'mmd_') . '.html';
        $tmpPng = tempnam(sys_get_temp_dir(), 'mmd_') . '.png';

        $escapedCode = htmlspecialchars($mermaidCode, ENT_QUOTES, 'UTF-8');
        $htmlDocument = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<script src="file://{$mermaidJs}"></script>
<style>
  body { margin: 0; padding: 25px; background: white; display: inline-block; }
  .mermaid { font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif; }
</style>
</head>
<body>
<div id="diag" class="mermaid">
{$escapedCode}
</div>
</body>
</html>
HTML;

        file_put_contents($tmpHtml, $htmlDocument);

        try {
            $cmd = escapeshellcmd($chromium)
                . ' --headless --disable-gpu --no-sandbox --allow-file-access-from-files'
                . ' --window-size=1400,900 --run-all-compositor-stages-before-draw --virtual-time-budget=2000'
                . ' --screenshot=' . escapeshellarg($tmpPng) . ' ' . escapeshellarg($tmpHtml)
                . ' 2>/dev/null';

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($tmpPng) && filesize($tmpPng) > 0) {
                // Recortar márgenes blancos sobrantes con GD si está disponible
                if (function_exists('imagecropauto') && function_exists('imagecreatefrompng')) {
                    $im = @imagecreatefrompng($tmpPng);
                    if ($im) {
                        $cropped = @imagecropauto($im, IMG_CROP_WHITE);
                        if ($cropped) {
                            @imagepng($cropped, $tmpPng);
                            @imagedestroy($cropped);
                        }
                        @imagedestroy($im);
                    }
                }

                $pngData = file_get_contents($tmpPng);
                if ($pngData) {
                    $b64 = base64_encode($pngData);
                    $this->cache[$hash] = $b64;
                    return $b64;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[MermaidRendererService] Error renderizando diagrama: ' . $e->getMessage());
        } finally {
            @unlink($tmpHtml);
            @unlink($tmpPng);
        }

        return null;
    }
}
