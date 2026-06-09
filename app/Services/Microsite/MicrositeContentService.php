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
/* Scaffold premium Ax.ia — valores por defecto; la IA puede sobrescribir con variables propias */
.ms-root {
  --ms-primary: #4f46e5;
  --ms-primary-dark: #3730a3;
  --ms-bg: #ffffff;
  --ms-surface: #f8fafc;
  --ms-text: #0f172a;
  --ms-text-muted: #64748b;
  --ms-accent: #ec4899;
  --ms-radius: 1rem;
  --ms-shadow: 0 10px 40px -10px rgba(15, 23, 42, 0.15);
  box-sizing: border-box;
  color: var(--ms-text);
  background-color: var(--ms-bg);
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  line-height: 1.65;
  -webkit-font-smoothing: antialiased;
  width: 100%;
}
.ms-root *, .ms-root *::before, .ms-root *::after { box-sizing: border-box; }
.ms-root img, .ms-root video, .ms-root iframe { max-width: 100%; height: auto; }
.ms-root a { color: var(--ms-primary); }
CSS;
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
