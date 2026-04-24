<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HandlesPersistentFilters
{
    /**
     * Retrieves filters from request or session, ensuring persistence.
     * 
     * @param Request $request
     * @param string $prefix Prefix for session keys (e.g., 'tasks', 'forum')
     * @param array $keys List of filter keys to handle
     * @param array $defaults Default values for filters
     * @return array
     */
    protected function getPersistentFilters(Request $request, string $prefix, array $keys, array $defaults = []): array
    {
        $sessionKeyBase = "persistent_filter_{$prefix}_";
        $filters = [];

        // 1. Reset logic
        if ($request->has('reset_filters')) {
            foreach ($keys as $key) {
                session()->forget($sessionKeyBase . $key);
                $filters[$key] = $defaults[$key] ?? null;
            }
            return $filters;
        }

        // 2. Process each key
        foreach ($keys as $key) {
            if ($request->has($key)) {
                // Update session with new value
                $value = $request->get($key);
                
                // If value is empty/null, we might want to keep it or clear it.
                // Usually, if a user explicitly clears a filter in the UI, we should clear it in the session too.
                if ($value === null || $value === '') {
                    session()->forget($sessionKeyBase . $key);
                    $filters[$key] = $defaults[$key] ?? null;
                } else {
                    session()->put($sessionKeyBase . $key, $value);
                    $filters[$key] = $value;
                }
            } else {
                // Retrieve from session or use default
                $filters[$key] = session($sessionKeyBase . $key, $defaults[$key] ?? null);
            }
        }

        return $filters;
    }
}
