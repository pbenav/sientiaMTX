<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Traits;

use App\Services\DemoModeService;

/**
 * HasDemoMasking
 *
 * Eloquent trait that intercepts attribute reads and JSON serialisation to
 * transparently mask sensitive fields when Demo Mode is active.
 *
 * ─── USAGE ───────────────────────────────────────────────────────────────────
 *
 *   class User extends Authenticatable
 *   {
 *       use HasDemoMasking;
 *
 *       // Define which attributes are sensitive and how they should be masked.
 *       // Keys = attribute names; Values = mask type accepted by DemoModeService::mask()
 *       protected array $demoSensitiveAttributes = [
 *           'name'  => 'name',
 *           'email' => 'email',
 *           'phone' => 'phone',
 *       ];
 *   }
 *
 * ─── IMPORTANT ───────────────────────────────────────────────────────────────
 *
 *  - The real database values are NEVER modified; masking is read-only.
 *  - When a model is being SAVED (isDirty / saving event), the trait does NOT
 *    touch anything — the underlying raw value goes to the DB untouched.
 *  - Use getRawOriginal($key) or the model's internal $attributes directly
 *    when you need to bypass the mask (e.g. in Observers, API controllers
 *    that mutate data, or queued jobs).
 */
trait HasDemoMasking
{
    /**
     * Sensitive attributes definition.
     * Override in the model class to customise which fields are masked and how.
     * MUST be declared in the model (not here) to avoid PHP 8.2 composition conflicts.
     *
     * Format:  'attribute_name' => 'mask_type'
     * Types:   'name' | 'email' | 'phone' | 'text' | 'token' | 'id' | 'url'
     */
    // protected array $demoSensitiveAttributes = []; // Declare this in your model!

    /**
     * Helper: returns the list of sensitive attributes defined in the model.
     * Falls back to an empty array if the model doesn't declare the property,
     * making the trait safe to use on any model without PHP 8.2 composition errors.
     */
    protected function getSensitiveAttributes(): array
    {
        return property_exists($this, 'demoSensitiveAttributes')
            ? $this->demoSensitiveAttributes
            : [];
    }

    /**
     * Override Eloquent's getAttribute so masked values are returned
     * transparently on property access ($model->attribute).
     *
     * The raw value is still accessible via $model->getRawOriginal('attribute').
     */
    public function getAttribute($key): mixed
    {
        $rawValue = parent::getAttribute($key);

        // Only intercept when demo mode is active AND the attribute is listed as sensitive
        $sensitive = $this->getSensitiveAttributes();
        if ($rawValue !== null && isset($sensitive[$key])) {
            /** @var DemoModeService $demo */
            $demo = app(DemoModeService::class);

            if ($demo->isActive()) {
                return $demo->mask((string) $rawValue, $sensitive[$key]);
            }
        }

        return $rawValue;
    }

    /**
     * Override toArray() / toJson() so API responses and Blade loops also get
     * masked values when demo mode is on.
     */
    public function toArray(): array
    {
        /** @var DemoModeService $demo */
        $demo = app(DemoModeService::class);

        $array = parent::toArray();

        $sensitive = $this->getSensitiveAttributes();
        if ($demo->isActive() && !empty($sensitive)) {
            foreach ($sensitive as $attr => $type) {
                if (array_key_exists($attr, $array) && $array[$attr] !== null) {
                    $array[$attr] = $demo->mask((string) $array[$attr], $type);
                }
            }
        }

        return $array;
    }
}
