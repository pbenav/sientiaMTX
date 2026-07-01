<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Contracts;

/**
 * Contrato para actividades exportables e importables bajo el esquema v2.
 * Permite registrar subtipos dinámicos con metadatos específicos (specs).
 */
interface ExportableActivityInterface
{
    /**
     * Define la estructura y reglas (esquema) de los metadatos específicos de la actividad.
     * 
     * @return array
     */
    public static function getSpecsSchema(): array;

    /**
     * Extrae los metadatos específicos del modelo para ser empaquetados en el JSON v2 (sección 'specs').
     *
     * @return array
     */
    public function exportSpecs(): array;

    /**
     * Recibe los metadatos de la sección 'specs' del JSON v2 y los asigna o mergea de forma limpia en 'metadata'.
     *
     * @param array $specs
     * @return void
     */
    public function importSpecs(array $specs): void;
}
