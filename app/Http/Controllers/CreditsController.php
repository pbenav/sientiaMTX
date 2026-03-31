<?php

namespace App\Http\Controllers;

class CreditsController extends Controller
{
    public function index()
    {
        $credits = [
            [
                'category' => __('credits.category_backend'),
                'icon' => '⚙️',
                'color' => 'from-red-500 to-orange-500',
                'items' => [
                    ['name' => 'Laravel', 'version' => '12.x', 'description' => __('credits.laravel_desc'), 'url' => 'https://laravel.com', 'license' => 'MIT'],
                    ['name' => 'PHP', 'version' => '8.4', 'description' => __('credits.php_desc'), 'url' => 'https://www.php.net', 'license' => 'PHP License'],
                    ['name' => 'Laravel Breeze', 'version' => '2.x', 'description' => __('credits.breeze_desc'), 'url' => 'https://github.com/laravel/breeze', 'license' => 'MIT'],
                    ['name' => 'Laravel Tinker', 'version' => '2.x', 'description' => __('credits.tinker_desc'), 'url' => 'https://github.com/laravel/tinker', 'license' => 'MIT'],
                    ['name' => 'Google API Client', 'version' => '2.x', 'description' => __('credits.google_api_desc'), 'url' => 'https://github.com/googleapis/google-api-php-client', 'license' => 'Apache 2.0'],
                    ['name' => 'Laravel WebPush', 'version' => '10.x', 'description' => __('credits.webpush_desc'), 'url' => 'https://github.com/laravel-notification-channels/webpush', 'license' => 'MIT'],
                ],
            ],
            [
                'category' => __('credits.category_frontend'),
                'icon' => '🎨',
                'color' => 'from-blue-500 to-cyan-500',
                'items' => [
                    ['name' => 'Tailwind CSS', 'version' => '3.x', 'description' => __('credits.tailwind_desc'), 'url' => 'https://tailwindcss.com', 'license' => 'MIT'],
                    ['name' => 'Alpine.js', 'version' => '3.x', 'description' => __('credits.alpine_desc'), 'url' => 'https://alpinejs.dev', 'license' => 'MIT'],
                    ['name' => 'Vite', 'version' => '7.x', 'description' => __('credits.vite_desc'), 'url' => 'https://vitejs.dev', 'license' => 'MIT'],
                    ['name' => 'SweetAlert2', 'version' => '11.x', 'description' => __('credits.sweetalert_desc'), 'url' => 'https://sweetalert2.github.io', 'license' => 'MIT'],
                    ['name' => 'Axios', 'version' => '1.x', 'description' => __('credits.axios_desc'), 'url' => 'https://axios-http.com', 'license' => 'MIT'],
                    ['name' => '@tailwindcss/typography', 'version' => '0.5.x', 'description' => __('credits.typography_desc'), 'url' => 'https://github.com/tailwindlabs/tailwindcss-typography', 'license' => 'MIT'],
                ],
            ],
            [
                'category' => __('credits.category_fonts'),
                'icon' => '✍️',
                'color' => 'from-purple-500 to-violet-500',
                'items' => [
                    ['name' => 'Inter', 'version' => null, 'description' => __('credits.inter_desc'), 'url' => 'https://rsms.me/inter/', 'license' => 'OFL-1.1'],
                    ['name' => 'Space Grotesk', 'version' => null, 'description' => __('credits.space_grotesk_desc'), 'url' => 'https://fonts.floriankarsten.com/space-grotesk', 'license' => 'OFL-1.1'],
                ],
            ],
            [
                'category' => __('credits.category_services'),
                'icon' => '🌐',
                'color' => 'from-emerald-500 to-teal-500',
                'items' => [
                    ['name' => 'Telegram Bot API', 'version' => null, 'description' => __('credits.telegram_desc'), 'url' => 'https://core.telegram.org/bots/api', 'license' => __('credits.license_free')],
                    ['name' => 'Google Calendar API', 'version' => 'v3', 'description' => __('credits.gcal_desc'), 'url' => 'https://developers.google.com/calendar', 'license' => __('credits.license_free')],
                    ['name' => 'Google Fonts', 'version' => null, 'description' => __('credits.gfonts_desc'), 'url' => 'https://fonts.google.com', 'license' => __('credits.license_free')],
                ],
            ],
            [
                'category' => __('credits.category_infra'),
                'icon' => '🖥️',
                'color' => 'from-gray-500 to-slate-600',
                'items' => [
                    ['name' => 'Nginx', 'version' => null, 'description' => __('credits.nginx_desc'), 'url' => 'https://nginx.org', 'license' => 'BSD-2-Clause'],
                    ['name' => 'MySQL / MariaDB', 'version' => '8.x / 10.x', 'description' => __('credits.mysql_desc'), 'url' => 'https://mariadb.org', 'license' => 'GPL-2.0'],
                    ['name' => 'Supervisor', 'version' => '4.x', 'description' => __('credits.supervisor_desc'), 'url' => 'http://supervisord.org', 'license' => __('credits.license_free')],
                    ['name' => 'Composer', 'version' => '2.x', 'description' => __('credits.composer_desc'), 'url' => 'https://getcomposer.org', 'license' => 'MIT'],
                    ['name' => 'Node.js + NPM', 'version' => '18+', 'description' => __('credits.node_desc'), 'url' => 'https://nodejs.org', 'license' => 'MIT'],
                ],
            ],
        ];

        return view('credits.index', compact('credits'));
    }
}
