#!/bin/bash

# 🛡️ SientiaMTX - Generador SBOM (SPDX) de Emergencia/Local
# Este script descarga temporalmente Syft y genera el manifiesto SPDX sin ensuciar tu sistema.

set -e

echo "🔍 Iniciando generación de SBOM para SientiaMTX..."

# Directorio temporal
TEMP_BIN="/tmp/syft"

if ! command -v syft &> /dev/null; then
    echo "📦 Syft no detectado localmente. Obteniendo binario temporal..."
    curl -sSfL https://raw.githubusercontent.com/anchore/syft/main/install.sh | sh -s -- -b /tmp &> /dev/null
    SYFT_PATH="/tmp/syft"
else
    SYFT_PATH="syft"
fi

OUTPUT_FILE="sientiamtx-sbom.spdx.json"

echo "⚙️  Escaneando dependencias de Laravel y Node.js..."
$SYFT_PATH . -o spdx-json > "$OUTPUT_FILE"

if [ -f "$OUTPUT_FILE" ]; then
    SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo "✅ Éxito: Manifiesto SBOM generado correctamente en '$OUTPUT_FILE' ($SIZE)"
    echo "💡 Puedes subir este archivo a herramientas de auditoría como Dependency-Track o enviarlo a clientes B2B."
else
    echo "❌ Error: No se pudo generar el archivo de manifiesto."
    exit 1
fi
