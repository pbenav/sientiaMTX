<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceReport;
use App\Models\Team;
use App\Traits\AwardsGamification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    use AwardsGamification;
    public function store(Request $request, Team $team)
    {
        if (!$team->isCoordinator(auth()->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url',
            'icon' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $team->services()->create($validated);

        return back()->with('success', __('Servicio añadido correctamente.'));
    }

    public function report(Request $request, Team $team, Service $service)
    {
        $validated = $request->validate([
            'type' => 'required|in:up,down',
            'details' => 'nullable|string',
        ]);

        // Check for cooldown (1 report per hour per service per user)
        $exists = ServiceReport::where('service_id', $service->id)
            ->where('user_id', auth()->id())
            ->where('type', $validated['type'])
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if ($exists) {
            return back()->with('error', __('Ya has reportado este estado recientemente.'));
        }

        DB::transaction(function () use ($service, $validated, $team) {
            $report = ServiceReport::create([
                'service_id' => $service->id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'details' => $validated['details'] ?? null,
            ]);

            // Simple status update logic
            $recentDown = ServiceReport::where('service_id', $service->id)
                ->where('type', 'down')
                ->where('created_at', '>=', now()->subHours(2))
                ->count();

            $recentUp = ServiceReport::where('service_id', $service->id)
                ->where('type', 'up')
                ->where('created_at', '>=', now()->subHour())
                ->count();

            $oldStatus = $service->status;

            if ($validated['type'] === 'down') {
                if ($recentDown >= 3) {
                    $service->update(['status' => 'down', 'status_updated_at' => now()]);
                    
                    // Gamification: First reporter gets a bonus if verified
                    if ($recentDown === 3) {
                        $firstReport = ServiceReport::where('service_id', $service->id)
                            ->where('type', 'down')
                            ->where('created_at', '>=', now()->subHours(2))
                            ->orderBy('created_at', 'asc')
                            ->first();
                        
                        if ($firstReport && $firstReport->user) {
                            $this->awardServiceReportingPoints(
                                $firstReport->user, 
                                $team->id, 
                                __('Bono Centinela: Reporte de caída verificado en :service', ['service' => $service->name])
                            );
                        }
                    }
                } else {
                    $service->update(['status' => 'unstable', 'status_updated_at' => now()]);
                }
            } else {
                // To restore status to 'up', we need at least 2 confirmations of 'up'
                if ($recentUp >= 2) {
                    $service->update(['status' => 'up', 'status_updated_at' => now()]);
                }
            }
        });

        return back()->with('success', __('Estado reportado.¡Gracias por colaborar!'));
    }

    public function destroy(Team $team, Service $service)
    {
        if (!$team->isCoordinator(auth()->user())) {
            abort(403);
        }

        $service->delete();

        return back()->with('success', __('Servicio eliminado.'));
    }
}
