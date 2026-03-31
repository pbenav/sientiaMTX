<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class LegalController extends Controller
{
    /**
     * Display the privacy policy.
     */
    public function privacy()
    {
        return view('legal.privacy', [
            'content' => Setting::get('legal_privacy')
        ]);
    }

    /**
     * Display the terms of service.
     */
    public function terms()
    {
        return view('legal.terms', [
            'content' => Setting::get('legal_terms')
        ]);
    }

    /**
     * Display the cookie policy.
     */
    public function cookies()
    {
        return view('legal.cookies', [
            'content' => Setting::get('legal_cookies')
        ]);
    }

    /**
     * Show the mandatory re-consent form.
     */
    public function reconsent()
    {
        $privacy = Setting::get('legal_privacy');
        $terms = Setting::get('legal_terms');
        $cookies = Setting::get('legal_cookies');

        // Fallbacks if empty
        if (empty($privacy)) {
            $privacy = '<h1>Política de Privacidad</h1><p>En <strong>' . config('app.name', 'Sientia') . '</strong>, nos tomamos muy en serio la privacidad de tus datos...</p><h2>1. Identificación del Responsable</h2><p>El responsable es <strong>[Nombre del Responsable / Empresa]</strong>.</p>';
        }

        if (empty($terms)) {
            $terms = '<h1>Términos de Servicio</h1><p>Bienvenido. Al usar <strong>' . config('app.name', 'Sientia') . '</strong> aceptas estos términos.</p>';
        }

        if (empty($cookies)) {
            $cookies = '<h1>Política de Cookies</h1><p>Usamos cookies para mejorar tu experiencia.</p>';
        }

        return view('legal.reconsent', [
            'privacy' => $privacy,
            'terms' => $terms,
            'cookies' => $cookies,
        ]);
    }

    /**
     * Handle the legal re-consent submission.
     */
    public function acceptConsent(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'accept' => 'required|accepted',
        ], [
            'accept.accepted' => __('Debes aceptar los términos legales para continuar.'),
        ]);

        $user = auth()->user();
        $user->update([
            'privacy_policy_accepted_at' => now(),
            'terms_accepted_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', __('¡Gracias por aceptar los nuevos términos!'));
    }
}
