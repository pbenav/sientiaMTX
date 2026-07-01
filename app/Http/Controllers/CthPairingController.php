<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CthPairingController extends Controller
{
    /**
     * Empareja la cuenta de MTX con CTH mediante inicio de sesión API (Sanctum).
     */
    public function pair(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'cth_url' => 'required|url',
        ]);

        $user = auth()->user();
        $apiUrl = rtrim($request->input('cth_url'), '/');
        
        // Limpiar sufijos /api o /api/v1 si el usuario los introdujo por error
        if (str_ends_with($apiUrl, '/api/v1')) {
            $apiUrl = substr($apiUrl, 0, -7);
        } elseif (str_ends_with($apiUrl, '/api')) {
            $apiUrl = substr($apiUrl, 0, -4);
        }

        try {
            $response = Http::timeout(10)->acceptJson()->post($apiUrl . '/api/v1/login', [
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'device_name' => 'MTX S2S Integration (' . request()->getHost() . ')',
            ]);

            if ($response->successful()) {
                $token = $response->json('data.token');
                $cthUserCode = $response->json('data.user.code');
                
                if (!$token) {
                    return response()->json(['success' => false, 'message' => 'El servidor CTH no devolvió un token válido.'], 400);
                }

                // Recuperar el centro de trabajo actual consultando el estado
                $statusResponse = Http::timeout(5)->withToken($token)->acceptJson()->post($apiUrl . '/api/v1/status');
                $workCenterCode = null;
                if ($statusResponse->successful()) {
                    $workCenterCode = $statusResponse->json('data.current_work_center_code');
                }

                $user->update([
                    'cth_api_url' => $apiUrl,
                    'cth_api_token' => $token,
                    'sync_with_cth' => true,
                    'cth_user_code' => $cthUserCode,
                    'cth_work_center_code' => $workCenterCode,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '¡Cuenta vinculada con éxito a CTH!',
                ]);
            }

            $msg = $response->json('message') ?: 'Credenciales inválidas o el servidor CTH rechazó la conexión.';
            return response()->json(['success' => false, 'message' => $msg], 401);
        } catch (\Exception $e) {
            Log::error('CTH Pairing Error: ' . $e->getMessage(), ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => 'No se ha podido conectar con el servidor CTH. Verifica la URL.',
            ], 500);
        }
    }

    /**
     * Desvincula la cuenta de CTH eliminando el token.
     */
    public function unpair(Request $request)
    {
        $user = auth()->user();
        
        $user->update([
            'cth_api_token' => null,
            'sync_with_cth' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta desvinculada de CTH correctamente.',
        ]);
    }
}
