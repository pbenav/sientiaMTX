<?php

namespace App\Services;

use App\Traits\HandlesPersistentFilters;
use Illuminate\Http\Request;

class PersistentFilterService
{
    use HandlesPersistentFilters;

    public function getFilters(Request $request, string $prefix, array $keys, array $defaults = []): array
    {
        return $this->getPersistentFilters($request, $prefix, $keys, $defaults);
    }
}
