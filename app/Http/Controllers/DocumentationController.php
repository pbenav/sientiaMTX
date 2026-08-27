<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocumentationController extends Controller
{
    /**
     * Display the documentation page.
     */
    public function index($slug = 'installation')
    {
        $lang = app()->getLocale();
        $fallbackLang = 'es';
        
        $path = resource_path("docs/{$lang}/{$slug}.md");

        // Fallback to default lang if not found in current lang
        if (!File::exists($path)) {
            $path = resource_path("docs/{$fallbackLang}/{$slug}.md");
        }

        // If still not found, return 404
        if (!File::exists($path)) {
            abort(404, "Documentation file not found: {$slug}");
        }

        $contentMd = File::get($path);
        
        // Convert Markdown to HTML using Laravel's built-in Str::markdown (powered by CommonMark)
        $contentHtml = Str::markdown($contentMd, ['html_input' => 'strip']);

        // Define the menu structure
        $menu = [
            'es' => [
                'installation'      => 'Instalación',
                'custom-domains'    => 'Dominios Personalizados (White-label)',
                'axia'              => 'Configuración Ax.ia (Gemini API)',
                'telegram'          => 'Configuración Telegram',
                'whatsapp'          => 'Configuración WhatsApp',
                'google-setup'      => 'Configuración Google API',
                'onlyoffice-laravel' => 'OnlyOffice — Laravel (MTX)',
                'onlyoffice-server'  => 'OnlyOffice — Servidor',
                'user-manual'       => 'Manual de Usuario',
                'admin-manual'      => 'Manual de Administrador',
                'gamification'      => 'Sistema de Gamificación',
                'wellness-metrics'  => 'Métricas de Bienestar (Wellness)',
                'spdx'              => 'Compatibilidad SPDX',
                'ens'               => 'Cumplimiento ENS',
            ],
            'en' => [
                'installation'      => 'Installation',
                'custom-domains'    => 'Custom Domains (White-label)',
                'axia'              => 'Ax.ia Configuration (Gemini API)',
                'telegram'          => 'Telegram Setup',
                'whatsapp'          => 'WhatsApp Setup',
                'google-setup'      => 'Google API Setup',
                'onlyoffice-laravel' => 'OnlyOffice — Laravel (MTX)',
                'onlyoffice-server'  => 'OnlyOffice — Server',
                'user-manual'       => 'User Manual',
                'admin-manual'      => 'Admin Manual',
                'gamification'      => 'Gamification System',
                'wellness-metrics'  => 'Wellness Metrics',
                'spdx'              => 'SPDX Compatibility',
                'ens'               => 'ENS Compliance',
            ]
        ];

        $currentMenu = $menu[$lang] ?? $menu[$fallbackLang];

        return view('docs.index', [
            'content' => $contentHtml,
            'slug'    => $slug,
            'menu'    => $currentMenu,
        ]);
    }

    /**
     * Render a standalone, print-optimized page for a documentation document.
     * Opens directly in a new tab — no popups, no JavaScript tricks.
     */
    public function print($slug = 'installation')
    {
        $lang         = app()->getLocale();
        $fallbackLang = 'es';
        $path         = resource_path("docs/{$lang}/{$slug}.md");

        if (!File::exists($path)) {
            $path = resource_path("docs/{$fallbackLang}/{$slug}.md");
        }
        if (!File::exists($path)) {
            abort(404, "Documentation file not found: {$slug}");
        }

        $contentHtml = Str::markdown(File::get($path), ['html_input' => 'strip']);

        $titles = [
            'es' => [
                'installation'       => 'Guía de Instalación SientiaMTX',
                'custom-domains'     => 'Dominios Personalizados (White-label)',
                'axia'               => 'Configuración Ax.ia (Gemini API)',
                'telegram'           => 'Configuración de Telegram',
                'whatsapp'           => 'Configuración de WhatsApp',
                'google-setup'       => 'Guía de Configuración de Google API',
                'onlyoffice-laravel' => 'OnlyOffice — Laravel (MTX)',
                'onlyoffice-server'  => 'OnlyOffice — Servidor',
                'user-manual'        => 'Manual de Usuario',
                'admin-manual'       => 'Manual de Administrador',
                'gamification'       => 'Sistema de Gamificación',
                'wellness-metrics'   => 'Métricas de Bienestar (Wellness)',
                'spdx'               => 'Compatibilidad SPDX',
                'ens'                => 'Cumplimiento ENS',
            ],
            'en' => [
                'installation'       => 'SientiaMTX Installation Guide',
                'custom-domains'     => 'Custom Domains (White-label)',
                'axia'               => 'Ax.ia Configuration (Gemini API)',
                'telegram'           => 'Telegram Setup',
                'whatsapp'           => 'WhatsApp Setup',
                'google-setup'       => 'Google API Setup Guide',
                'onlyoffice-laravel' => 'OnlyOffice — Laravel (MTX)',
                'onlyoffice-server'  => 'OnlyOffice — Server',
                'user-manual'        => 'User Manual',
                'admin-manual'       => 'Admin Manual',
                'gamification'       => 'Gamification System',
                'wellness-metrics'   => 'Wellness Metrics',
                'spdx'               => 'SPDX Compatibility',
                'ens'                => 'ENS Compliance',
            ],
        ];

        $title = $titles[$lang][$slug] ?? $titles[$fallbackLang][$slug] ?? 'Documentación SientiaMTX';

        return view('docs.print', [
            'content' => $contentHtml,
            'title'   => $title,
            'slug'    => $slug,
        ]);
    }
}
