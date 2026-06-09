<?php

namespace App\Services\Microsite;

use App\Models\Microsite;
use App\Models\TaskAttachment;
use App\Models\Team;

class MicrositeContentService
{
    /**
     * Corrige URLs de adjuntos rotas o inventadas por la IA en el HTML del micrositio.
     */
    public function fixAttachmentUrls(string $html, Team $team): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $html = preg_replace_callback(
            '#(?:https?://[^"\'\s]+)?/files/([^"\'\s<>]+)#i',
            fn (array $matches) => $this->resolveBrokenFileUrl($matches[1], $team) ?? $matches[0],
            $html
        );

        $html = preg_replace_callback(
            '#(?:https?://[^"\'\s]+)?/storage/attachments/[^"\'\s<>]+#i',
            function (array $matches) use ($team) {
                $match = $matches[0];
                $path = parse_url($match, PHP_URL_PATH) ?? $match;
                $path = ltrim(str_replace('/storage/', '', $path), '/');
                $attachment = $this->findAttachmentByStoragePath($path, $team);

                return $attachment?->getPublicEmbedUrl() ?? $match;
            },
            $html
        );

        return $html;
    }

    public function fixMicrositeContent(Microsite $microsite): string
    {
        return $this->prepareMicrosite($microsite)['html'];
    }

    /**
     * Prepara HTML y CSS del micrositio para renderizado público premium y funcional.
     *
     * @return array{html: string, css: string, uses_tailwind: bool}
     */
    public function prepareMicrosite(Microsite $microsite): array
    {
        $html = $this->fixAttachmentUrls($microsite->html_content ?? '', $microsite->team);
        $html = $this->ensureRootWrapper($html);
        $html = $this->enhancePdfEmbeds($html);
        $css = $this->enhanceCss($microsite->css_content ?? '');

        return [
            'html' => $html,
            'css' => $css,
            'uses_tailwind' => $this->usesTailwindClasses($html),
        ];
    }

    public function ensureRootWrapper(string $html): string
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return '<div class="ms-root"></div>';
        }

        if (preg_match('/\bms-root\b/', $trimmed)) {
            return $trimmed;
        }

        return '<div class="ms-root">' . $trimmed . '</div>';
    }

    public function usesTailwindClasses(string $html): bool
    {
        return (bool) preg_match(
            '/\bclass="[^"]*\b(bg-|text-|flex|grid|items-|justify-|gap-|p-\d|px-|py-|m-\d|mx-|my-|rounded-|shadow-|from-|to-|via-|border-|w-|h-|max-w-|min-h-|space-|font-|leading-|tracking-)/',
            $html
        );
    }

    public function enhanceCss(string $css): string
    {
        $scaffold = $this->getBaseScaffoldCss();

        if (str_contains($css, '.ms-root')) {
            return $scaffold . "\n" . $css;
        }

        return $scaffold . "\n" . $css;
    }

    public function getBaseScaffoldCss(): string
    {
        return <<<'CSS'
/* ── Design system Ax.ia para micrositios (premium por defecto) ── */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:wght@600;700&display=swap');

.ms-root {
  --ms-primary: #4338ca;
  --ms-primary-dark: #312e81;
  --ms-bg: #fafafa;
  --ms-surface: #ffffff;
  --ms-text: #0f172a;
  --ms-text-muted: #64748b;
  --ms-accent: #db2777;
  --ms-radius: 1.25rem;
  --ms-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.12);
  --ms-shadow-sm: 0 4px 14px rgba(15, 23, 42, 0.08);
  --ms-max-w: 72rem;
  box-sizing: border-box;
  color: var(--ms-text);
  background: linear-gradient(165deg, var(--ms-bg) 0%, #eef2ff 45%, var(--ms-bg) 100%);
  font-family: 'DM Sans', system-ui, sans-serif;
  line-height: 1.7;
  -webkit-font-smoothing: antialiased;
  width: 100%;
  min-height: 60vh;
}
.ms-root *, .ms-root *::before, .ms-root *::after { box-sizing: border-box; }
.ms-root img, .ms-root video { max-width: 100%; height: auto; display: block; }
.ms-root a { color: var(--ms-primary); text-decoration: none; transition: color 0.2s; }
.ms-root a:hover { color: var(--ms-accent); }

.ms-container { max-width: var(--ms-max-w); margin: 0 auto; padding: 0 1.5rem; }

.ms-hero {
  padding: 4rem 1.5rem 3rem;
  text-align: center;
  background: linear-gradient(135deg, var(--ms-primary) 0%, var(--ms-primary-dark) 100%);
  color: #ffffff;
}
.ms-hero h1 {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: clamp(2rem, 5vw, 3.25rem);
  font-weight: 700;
  line-height: 1.15;
  margin: 0 0 1rem;
  letter-spacing: -0.02em;
}
.ms-hero p { color: rgba(255,255,255,0.88); font-size: 1.125rem; max-width: 40rem; margin: 0 auto; }

.ms-section { padding: 3rem 0; }
.ms-section--alt { background: var(--ms-surface); }

.ms-card {
  background: var(--ms-surface);
  border-radius: var(--ms-radius);
  box-shadow: var(--ms-shadow-sm);
  padding: 1.75rem;
  border: 1px solid rgba(15, 23, 42, 0.06);
}

.ms-btn {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem 1.5rem; border-radius: 9999px; font-weight: 600;
  font-size: 0.9375rem; border: none; cursor: pointer;
  transition: transform 0.15s, box-shadow 0.2s;
}
.ms-btn:hover { transform: translateY(-1px); box-shadow: var(--ms-shadow-sm); }
.ms-btn--primary { background: var(--ms-primary); color: #fff; }
.ms-btn--primary:hover { background: var(--ms-primary-dark); color: #fff; }
.ms-btn--ghost {
  background: rgba(255,255,255,0.15); color: inherit;
  border: 1px solid rgba(255,255,255,0.35);
}
.ms-btn--outline {
  background: transparent; color: var(--ms-primary);
  border: 2px solid var(--ms-primary);
}

/* Visor PDF premium con pantalla completa */
.ms-pdf-viewer {
  background: var(--ms-surface);
  border-radius: var(--ms-radius);
  box-shadow: var(--ms-shadow);
  overflow: hidden;
  border: 1px solid rgba(15, 23, 42, 0.08);
  margin: 2rem 0;
}
.ms-pdf-viewer:fullscreen {
  border-radius: 0; margin: 0; width: 100vw; height: 100vh;
  display: flex; flex-direction: column;
}
.ms-pdf-viewer:fullscreen .ms-pdf-frame { flex: 1; min-height: 0; height: auto; }
.ms-pdf-toolbar {
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  background: linear-gradient(90deg, var(--ms-primary-dark), var(--ms-primary));
  color: #fff;
}
.ms-pdf-title { font-weight: 600; font-size: 0.9375rem; opacity: 0.95; }
.ms-pdf-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.ms-pdf-actions .ms-btn { font-size: 0.8125rem; padding: 0.5rem 1rem; }
.ms-pdf-frame {
  width: 100%; min-height: 75vh; height: 80vh; border: none; display: block;
  background: #1e293b;
}

@media (max-width: 768px) {
  .ms-hero { padding: 2.5rem 1rem; }
  .ms-pdf-frame { min-height: 60vh; height: 65vh; }
}
CSS;
    }

    /**
     * Envuelve iframes PDF en el visor premium con toolbar y pantalla completa.
     */
    public function enhancePdfEmbeds(string $html): string
    {
        if (!preg_match('/<iframe/i', $html)) {
            return $html;
        }

        return preg_replace_callback(
            '/<iframe([^>]*)\ssrc=(["\'])([^"\']+)\2([^>]*)><\/iframe>/i',
            function (array $m) {
                $src = $m[3];
                if (!$this->looksLikePdfEmbed($src, $m[0])) {
                    return $m[0];
                }
                if (str_contains($m[0], 'ms-pdf-viewer')) {
                    return $m[0];
                }

                $title = 'Documento PDF';
                if (preg_match('/title=(["\'])([^"\']*)\1/i', $m[0], $t)) {
                    $title = htmlspecialchars($t[2], ENT_QUOTES, 'UTF-8');
                }

                $srcEsc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
                $classAttr = preg_match('/\bclass=(["\'])([^"\']*)\1/i', $m[0], $c)
                    ? $c[2] . ' ms-pdf-frame'
                    : 'ms-pdf-frame';

                return <<<HTML
<div class="ms-pdf-viewer">
  <div class="ms-pdf-toolbar">
    <span class="ms-pdf-title">{$title}</span>
    <div class="ms-pdf-actions">
      <button type="button" class="ms-btn ms-btn--ghost" data-ms-fullscreen aria-label="Pantalla completa">⛶ Pantalla completa</button>
      <a href="{$srcEsc}" target="_blank" rel="noopener" class="ms-btn ms-btn--ghost">↗ Abrir en pestaña</a>
    </div>
  </div>
  <iframe class="{$classAttr}" src="{$srcEsc}" title="{$title}" allowfullscreen loading="lazy"></iframe>
</div>
HTML;
            },
            $html
        );
    }

    private function looksLikePdfEmbed(string $src, string $fullTag): bool
    {
        if (preg_match('/\.pdf(\?|#|$)/i', $src)) {
            return true;
        }
        if (str_contains($src, '/embed/files/')) {
            return true;
        }
        if (preg_match('/type=(["\'])application\/pdf\1/i', $fullTag)) {
            return true;
        }

        return false;
    }

    private function resolveBrokenFileUrl(string $rawFilename, Team $team): ?string
    {
        $filename = urldecode($rawFilename);
        $attachment = $this->findAttachmentByFileName($filename, $team);

        return $attachment?->getPublicEmbedUrl();
    }

    private function findAttachmentByFileName(string $filename, Team $team): ?TaskAttachment
    {
        $basename = basename($filename);
        $normalized = $this->normalizeFilename($basename);

        $candidates = TaskAttachment::query()
            ->whereHas('task', fn ($query) => $query->where('team_id', $team->id))
            ->latest()
            ->get();

        return $candidates->first(function (TaskAttachment $attachment) use ($filename, $basename, $normalized) {
            if (in_array($attachment->file_name, [$filename, $basename], true)) {
                return true;
            }

            if (str_ends_with($attachment->file_path ?? '', $basename)) {
                return true;
            }

            return $this->normalizeFilename($attachment->file_name) === $normalized;
        });
    }

    private function normalizeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $name) ?? '');
    }

    private function findAttachmentByStoragePath(string $path, Team $team): ?TaskAttachment
    {
        return TaskAttachment::query()
            ->where('file_path', $path)
            ->whereHas('task', fn ($query) => $query->where('team_id', $team->id))
            ->first();
    }
}
