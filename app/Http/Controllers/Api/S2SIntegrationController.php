<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class S2SIntegrationController extends Controller
{
    public function syncWorkday(Request $request)
    {
        $secret = config('services.cth.secret');
        if (!$secret || $request->header('X-S2S-Secret') !== $secret) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $email = $request->input('email');
        $action = $request->input('action'); // 'start' or 'stop'
        $timestampStr = $request->input('timestamp');
        $timestamp = $timestampStr ? \Carbon\Carbon::parse($timestampStr)->setTimezone(config('app.timezone')) : now();

        $user = User::where('email', $email)->first();
        if (!$user || !$user->sync_with_cth) {
            return response()->json(['message' => 'User not found or sync disabled'], 404);
        }

        try {
            $activeLog = $user->activeWorkdayLog();

            if ($action === 'delete_active' && $activeLog) {
                $activeLog->delete();
                $activeTaskLog = $user->activeTaskLog();
                if ($activeTaskLog) {
                    $activeTaskLog->update(['end_at' => now()]);
                }
            } elseif ($action === 'stop' && $activeLog) {
                // If the stop timestamp is before the active log started, the event was likely deleted in CTH
                if ($timestampStr && $timestamp->lt(\Carbon\Carbon::parse($activeLog->start_at))) {
                    $activeLog->delete();
                    $activeTaskLog = $user->activeTaskLog();
                    if ($activeTaskLog) {
                        $activeTaskLog->update(['end_at' => now()]);
                    }
                } else {
                    $activeLog->update(['end_at' => $timestamp]);
                    
                    // Auto-stop active task if workday ends
                    $activeTaskLog = $user->activeTaskLog();
                    if ($activeTaskLog) {
                        $activeTaskLog->update(['end_at' => $timestamp]);
                    }
                }
            } elseif ($action === 'start' && !$activeLog) {
                $user->timeLogs()->create([
                    'type' => 'workday',
                    'start_at' => $timestamp,
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('S2S MTX Sync Error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }
}
