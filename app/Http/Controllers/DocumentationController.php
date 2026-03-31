<?php

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
        $contentHtml = Str::markdown($contentMd);

        // Define the menu structure
        $menu = [
            'es' => [
                'installation' => 'Instalación',
                'telegram' => 'Configuración Telegram',
                'user-manual' => 'Manual de Usuario',
                'admin-manual' => 'Manual de Administrador',
            ],
            'en' => [
                'installation' => 'Installation',
                'telegram' => 'Telegram Setup',
                'user-manual' => 'User Manual',
                'admin-manual' => 'Admin Manual',
            ]
        ];

        $currentMenu = $menu[$lang] ?? $menu[$fallbackLang];

        return view('docs.index', [
            'content' => $contentHtml,
            'slug' => $slug,
            'menu' => $currentMenu,
        ]);
    }
}
