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
     * Retorna la estructura jerárquica de categorías y manuales organizada
     * con una línea lógica: Usuarios finales -> Coordinadores -> Integraciones -> Sysadmins.
     */
    protected function getCategories(): array
    {
        return [
            'es' => [
                'users' => [
                    'label' => 'Manuales de Usuario y Operativa',
                    'icon'  => 'users',
                    'items' => [
                        'user-manual'      => 'Manual de Usuario',
                        'activities'       => 'Actividades y Flujo de Trabajo',
                        'expedientes'      => 'Gestión de Expedientes',
                        'circuits'         => 'Circuitos y Firma Digital',
                        'appointments'     => 'Cita Previa y Atención Ciudadana',
                        'axia'             => 'Asistente Ax.ia (Gemini IA)',
                        'gamification'     => 'Sistema de Gamificación',
                        'wellness-metrics' => 'Métricas de Bienestar (Wellness)',
                    ],
                ],
                'coordination' => [
                    'label' => 'Gestión y Coordinación',
                    'icon'  => 'shield',
                    'items' => [
                        'admin-manual'   => 'Manual de Administrador',
                        'custom-domains' => 'Dominios Personalizados (White-label)',
                    ],
                ],
                'integrations' => [
                    'label' => 'Integraciones y Canales',
                    'icon'  => 'puzzle',
                    'items' => [
                        'google-setup'       => 'Configuración Google API',
                        'telegram'           => 'Integración con Telegram',
                        'whatsapp'           => 'Integración con WhatsApp',
                        'onlyoffice-laravel' => 'OnlyOffice — Integración MTX',
                        'onlyoffice-server'  => 'OnlyOffice — Servidor Documental',
                    ],
                ],
                'sysadmin' => [
                    'label' => 'Sistemas, Despliegue y Seguridad',
                    'icon'  => 'server',
                    'items' => [
                        'installation'   => 'Guía de Instalación y Despliegue',
                        'ens'            => 'Cumplimiento ENS (Esquema Nacional)',
                        'spdx'           => 'Compatibilidad y Licencias SPDX',
                    ],
                ],
            ],
            'en' => [
                'users' => [
                    'label' => 'User Manuals & Daily Operations',
                    'icon'  => 'users',
                    'items' => [
                        'user-manual'      => 'User Manual',
                        'activities'       => 'Activities & Workflow',
                        'expedientes'      => 'Case Management (Expedientes)',
                        'circuits'         => 'Circuits & Digital Signature',
                        'appointments'     => 'Appointments Portal',
                        'axia'             => 'Ax.ia Assistant (Gemini AI)',
                        'gamification'     => 'Gamification System',
                        'wellness-metrics' => 'Wellness Metrics',
                    ],
                ],
                'coordination' => [
                    'label' => 'Management & Coordination',
                    'icon'  => 'shield',
                    'items' => [
                        'admin-manual'   => 'Admin Manual',
                        'custom-domains' => 'Custom Domains (White-label)',
                    ],
                ],
                'integrations' => [
                    'label' => 'Integrations & Services',
                    'icon'  => 'puzzle',
                    'items' => [
                        'google-setup'       => 'Google API Setup',
                        'telegram'           => 'Telegram Setup',
                        'whatsapp'           => 'WhatsApp Setup',
                        'onlyoffice-laravel' => 'OnlyOffice — MTX Integration',
                        'onlyoffice-server'  => 'OnlyOffice — Server Setup',
                    ],
                ],
                'sysadmin' => [
                    'label' => 'Sysadmin, Deployment & Security',
                    'icon'  => 'server',
                    'items' => [
                        'installation'   => 'Installation Guide',
                        'ens'            => 'ENS Compliance',
                        'spdx'           => 'SPDX Compatibility',
                    ],
                ],
            ],
        ];
    }

    /**
     * Retorna el menú plano de documentos para compatibilidad y búsquedas directas.
     */
    protected function getFlatMenu(?string $lang = null): array
    {
        $lang = $lang ?? app()->getLocale();
        $fallbackLang = 'es';
        $categories = $this->getCategories();
        $currentCategories = $categories[$lang] ?? $categories[$fallbackLang];

        $flat = [];
        foreach ($currentCategories as $cat) {
            foreach ($cat['items'] as $k => $v) {
                $flat[$k] = $v;
            }
        }
        return $flat;
    }

    /**
     * Muestra la vista de documentación principal.
     */
    public function index($slug = 'user-manual')
    {
        $lang = app()->getLocale();
        $fallbackLang = 'es';

        $path = resource_path("docs/{$lang}/{$slug}.md");

        if (!File::exists($path)) {
            $path = resource_path("docs/{$fallbackLang}/{$slug}.md");
        }

        if (!File::exists($path)) {
            abort(404, "Documentation file not found: {$slug}");
        }

        $contentMd = File::get($path);
        $contentHtml = Str::markdown($contentMd, ['html_input' => 'strip']);
        $contentHtml = preg_replace('/((?:[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2B50}\x{2B55}\x{231A}-\x{231B}\x{23ED}-\x{23EF}\x{23F0}\x{23F3}\x{25FD}-\x{25FE}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{3297}\x{3299}\x{3030}\x{303D}\x{00A9}\x{00AE}\x{2122}\x{2139}]|\p{Extended_Pictographic})(?:\x{FE0F}|\x{FE0E})?)/u', '<span class="emoji-icon">$1</span>', $contentHtml);

        $categories = $this->getCategories();
        $currentCategories = $categories[$lang] ?? $categories[$fallbackLang];
        $flatMenu = $this->getFlatMenu($lang);

        return view('docs.index', [
            'content'    => $contentHtml,
            'slug'       => $slug,
            'categories' => $currentCategories,
            'menu'       => $flatMenu,
        ]);
    }

    /**
     * Renderiza una página independiente para imprimir un único documento de documentación.
     */
    public function print($slug = 'user-manual')
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
        $contentHtml = preg_replace('/((?:[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2B50}\x{2B55}\x{231A}-\x{231B}\x{23ED}-\x{23EF}\x{23F0}\x{23F3}\x{25FD}-\x{25FE}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{3297}\x{3299}\x{3030}\x{303D}\x{00A9}\x{00AE}\x{2122}\x{2139}]|\p{Extended_Pictographic})(?:\x{FE0F}|\x{FE0E})?)/u', '<span class="emoji-icon">$1</span>', $contentHtml);

        $titles = $this->getFlatMenu($lang);
        $title = $titles[$slug] ?? Str::title(str_replace('-', ' ', $slug));

        return view('docs.print', [
            'content' => $contentHtml,
            'title'   => $title,
            'slug'    => $slug,
        ]);
    }

    /**
     * Compila y renderiza el libro de documentación oficial.
     * Permite incluir todos los manuales o una selección de ellos
     * con portada corporativa, índice de contenidos (TOC) y paginación estilo libro.
     */
    public function printBook(Request $request)
    {
        $lang = app()->getLocale();
        $fallbackLang = 'es';

        $categories = $this->getCategories();
        $currentCategories = $categories[$lang] ?? $categories[$fallbackLang];
        $allTitles = $this->getFlatMenu($lang);

        // Obtener selección de documentos
        $selected = $request->input('docs');
        if (is_string($selected)) {
            $selected = explode(',', $selected);
        }
        $selected = is_array($selected) ? array_filter($selected) : [];

        // Si no se especifica selección, incluir todos los manuales en orden
        if (empty($selected)) {
            $selected = array_keys($allTitles);
        }

        $title = $request->input('title', 'Manual Oficial SientiaMTX');
        $subtitle = $request->input('subtitle', 'Compendio de Usuario, Gobernanza Colegiada y Administración');
        $version = $request->input('version', 'v' . config('app.version', '1.3.0'));
        $includeCover = $request->boolean('cover', true);
        $includeToc = $request->boolean('toc', true);

        // Mapear qué categoría tiene cada slug para mostrarlo en el libro
        $slugToCategory = [];
        foreach ($currentCategories as $catKey => $catData) {
            foreach ($catData['items'] as $itemSlug => $itemTitle) {
                $slugToCategory[$itemSlug] = $catData['label'];
            }
        }

        // Compilar capítulos
        $chapters = [];
        $chapterNumber = 1;

        foreach ($selected as $docSlug) {
            $docSlug = trim($docSlug);
            $path = resource_path("docs/{$lang}/{$docSlug}.md");
            if (!File::exists($path)) {
                $path = resource_path("docs/{$fallbackLang}/{$docSlug}.md");
            }
            if (!File::exists($path)) {
                continue;
            }

            $contentMd = File::get($path);
            $contentHtml = Str::markdown($contentMd, ['html_input' => 'strip']);
            $contentHtml = preg_replace('/((?:[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2B50}\x{2B55}\x{231A}-\x{231B}\x{23ED}-\x{23EF}\x{23F0}\x{23F3}\x{25FD}-\x{25FE}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{3297}\x{3299}\x{3030}\x{303D}\x{00A9}\x{00AE}\x{2122}\x{2139}]|\p{Extended_Pictographic})(?:\x{FE0F}|\x{FE0E})?)/u', '<span class="emoji-icon">$1</span>', $contentHtml);

            $chapters[] = [
                'number'   => $chapterNumber++,
                'slug'     => $docSlug,
                'title'    => $allTitles[$docSlug] ?? Str::title(str_replace('-', ' ', $docSlug)),
                'category' => $slugToCategory[$docSlug] ?? 'Documentación',
                'html'     => $contentHtml,
            ];
        }

        return view('docs.book', [
            'title'        => $title,
            'subtitle'     => $subtitle,
            'version'      => $version,
            'chapters'     => $chapters,
            'includeCover' => $includeCover,
            'includeToc'   => $includeToc,
        ]);
    }
}
