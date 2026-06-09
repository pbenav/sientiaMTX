<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class LegalSettingsController extends Controller
{
    /**
     * Show the legal settings form.
     */
    public function edit()
    {
        $privacy = Setting::get('legal_privacy');
        $terms = Setting::get('legal_terms');
        $cookies = Setting::get('legal_cookies');

        // Pre-load defaults if empty
        if (empty($privacy)) {
            $privacy = '<h1>Política de Privacidad</h1><p>En <strong>' . config('app.name', 'Sientia Open Source Lab') . '</strong>, nos tomamos muy en serio la privacidad de tus datos. Esta Política de Privacidad describe cómo recopilamos, utilizamos y protegemos tu información personal.</p><h2>1. Identificación del Responsable</h2><p>El responsable del tratamiento de tus datos es <strong>[Nombre del Responsable / Empresa]</strong>.</p><h2>2. Datos que Recopilamos</h2><p>Recopilamos información de registro (nombre, email), datos de uso de la plataforma y preferencias de usuario.</p><h2>3. Finalidad del Tratamiento</h2><p>Tus datos se utilizan para gestionar tu acceso, sincronizar servicios autorizados y mejorar la experiencia de usuario.</p><h2>4. Tus Derechos</h2><p>Puedes ejercer tus derechos de acceso, rectificación, supresión y portabilidad en cualquier momento a través de tu perfil.</p><div class="bg-blue-50 p-4 rounded-xl">Última actualización: ' . now()->format('d/m/Y') . '</div>';
        }

        if (empty($terms)) {
            $terms = '<h1>Términos de Servicio</h1><p>Bienvenido a <strong>' . config('app.name', 'Sientia Open Source Lab') . '</strong>. Al utilizar nuestra plataforma, aceptas estos términos y condiciones.</p><h2>1. Registro</h2><p>Debes proporcionar información veraz y mantener la confidencialidad de tu cuenta.</p><h2>2. Normas de Uso</h2><p>No se permite el uso de la plataforma para actividades ilícitas o que interfieran con el servicio.</p><h2>3. Propiedad Intelectual</h2><p>El software y contenidos son propiedad de <strong>[Nombre del Responsable / Empresa]</strong>.</p><h2>4. Limitación de Responsabilidad</h2><p>SientiaMTX se ofrece "tal cual", sin garantías explícitas sobre la disponibilidad ininterrumpida.</p><div class="bg-blue-50 p-4 rounded-xl">Última actualización: ' . now()->format('d/m/Y') . '</div>';
        }

        if (empty($cookies)) {
            $cookies = '<h1>Política de Cookies</h1><p>Utilizamos cookies propias y de terceros para mejorar tu experiencia.</p><h2>1. ¿Qué son las Cookies?</h2><p>Son pequeños archivos que se guardan en tu navegador para recordar tus preferencias.</p><h2>2. Cookies que usamos</h2><ul><li><strong>Técnicas:</strong> Necesarias para el funcionamiento.</li><li><strong>Personalización:</strong> Para recordar tus ajustes de tema e idioma.</li><li><strong>Terceros:</strong> Gestionadas por servicios como Google OAuth.</li></ul><h2>3. Gestión</h2><p>Puedes bloquear las cookies desde los ajustes de tu navegador.</p><div class="bg-blue-50 p-4 rounded-xl">Última actualización: ' . now()->format('d/m/Y') . '</div>';
        }

        return view('settings.legal', [
            'privacy' => $privacy,
            'terms' => $terms,
            'cookies' => $cookies,
        ]);
    }

    /**
     * Update the legal settings in the database.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'legal_privacy' => 'nullable|string',
            'legal_terms' => 'nullable|string',
            'legal_cookies' => 'nullable|string',
            'notify_changes' => 'nullable|boolean',
        ]);

        foreach (['legal_privacy', 'legal_terms', 'legal_cookies'] as $key) {
            if (isset($validated[$key])) {
                Setting::set($key, $validated[$key]);
            }
        }

        if ($request->has('notify_changes')) {
            Setting::set('legal_updated_at', now()->toDateTimeString());
        }

        return back()->with('success', __('notifications.config_saved'));
    }
}
