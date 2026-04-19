@props(['team' => null])

@php
    // Helper to get setting with hierarchy: Team -> Global -> Default
    $getStyle = function($key, $default) use ($team) {
        if ($team && isset($team->settings['markdown_' . $key])) {
            return $team->settings['markdown_' . $key];
        }
        return \App\Models\Setting::get('markdown_' . $key, $default);
    };

    $h1_size = $getStyle('h1_size', '1.875rem');
    $h1_weight = $getStyle('h1_weight', '800');
    $h2_size = $getStyle('h2_size', '1.5rem');
    $h2_weight = $getStyle('h2_weight', '700');
    $h3_size = $getStyle('h3_size', '1.25rem');
    $h3_weight = $getStyle('h3_weight', '600');
    
    $text_size = $getStyle('text_size', '1rem');
    $accent = $getStyle('accent_color', '#4f46e5');
    
    $bq_color = $getStyle('bq_color', $accent);
    $bq_width = $getStyle('bq_width', '4px');
    
    $bullet_color = $getStyle('bullet_color', $accent);
@endphp

<style>
    /* Global Markdown & Prose Styles - Generated for {{ $team ? 'Team: ' . $team->name : 'Global' }} */
    :root {
        --md-h1-size: {{ $h1_size }};
        --md-h1-weight: {{ $h1_weight }};
        --md-h2-size: {{ $h2_size }};
        --md-h2-weight: {{ $h2_weight }};
        --md-h3-size: {{ $h3_size }};
        --md-h3-weight: {{ $h3_weight }};
        --md-text-size: {{ $text_size }};
        --md-accent: {{ $accent }};
        --md-bq-color: {{ $bq_color }};
        --md-bq-width: {{ $bq_width }};
        --md-bullet-color: {{ $bullet_color }};
    }

    .markdown-content, .prose {
        font-size: var(--md-text-size) !important;
        line-height: 1.6;
    }

    .markdown-content h1, .prose h1,
    .prose :where(h1):not(:where([class~="not-prose"], [class~="not-prose"] *)) {
        font-size: var(--md-h1-size) !important;
        font-weight: var(--md-h1-weight) !important;
        line-height: 1.2;
        margin-top: 2.5rem;
        margin-bottom: 1.5rem;
        color: var(--md-accent);
        font-family: 'Space Grotesk', sans-serif;
    }

    .markdown-content h2, .prose h2,
    .prose :where(h2):not(:where([class~="not-prose"], [class~="not-prose"] *)) {
        font-size: var(--md-h2-size) !important;
        font-weight: var(--md-h2-weight) !important;
        line-height: 1.3;
        margin-top: 2rem;
        margin-bottom: 1.25rem;
        font-family: 'Space Grotesk', sans-serif;
    }

    .markdown-content h3, .prose h3,
    .prose :where(h3):not(:where([class~="not-prose"], [class~="not-prose"] *)) {
        font-size: var(--md-h3-size) !important;
        font-weight: var(--md-h3-weight) !important;
        line-height: 1.4;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }

    .markdown-content a, .prose a {
        color: var(--md-accent) !important;
        text-decoration: underline;
        text-underline-offset: 4px;
        font-weight: 600;
        transition: opacity 0.2s;
    }

    .markdown-content a:hover, .prose a:hover {
        opacity: 0.8;
    }

    .markdown-content ul, .prose ul {
        list-style-type: disc !important;
        padding-left: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .markdown-content ul li::marker, .prose ul li::marker {
        color: var(--md-bullet-color);
    }

    .markdown-content ol, .prose ol {
        list-style-type: decimal !important;
        padding-left: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .markdown-content blockquote, .prose blockquote {
        border-left: var(--md-bq-width) solid var(--md-bq-color) !important;
        padding-left: 1.5rem;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        font-style: italic;
        background: rgba(0,0,0,0.02);
        border-radius: 0 0.5rem 0.5rem 0;
        color: #4b5563;
        margin: 2rem 0;
    }

    .dark .markdown-content blockquote, .dark .prose blockquote {
        background: rgba(255,255,255,0.03);
        color: #9ca3af;
    }

    .markdown-content code, .prose code {
        background: #f3f4f6;
        padding: 0.3rem 0.5rem;
        border-radius: 0.4rem;
        font-size: 0.9em;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: var(--md-accent);
    }

    /* Remove default backticks from prose code if needed */
    .prose code::before, .prose code::after { content: "" !important; }

    .dark .markdown-content code, .dark .prose code {
        background: #1e293b;
        color: #e2e8f0;
    }

    .markdown-content pre, .prose pre {
        background: #0f172a;
        color: #f8fafc;
        padding: 1.5rem;
        border-radius: 1.25rem;
        margin-bottom: 2rem;
        overflow-x: auto;
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
    }

    .markdown-content img, .prose img {
        max-width: 100%;
        height: auto;
        border-radius: 1.25rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        margin: 2.5rem auto;
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: block;
    }

    .dark .markdown-content img, .dark .prose img {
        border-color: rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
    }
</style>
