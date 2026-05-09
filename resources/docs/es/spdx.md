# 🛡️ Compatibilidad y Cumplimiento con SPDX

**Software Package Data Exchange® (SPDX)** es un estándar abierto (ISO/IEC 5230) diseñado para comunicar información sobre la factura de materiales de software (SBOM), incluyendo componentes, licencias, derechos de autor y referencias de seguridad.

SientiaMTX ha sido desarrollado bajo los más altos estándares de rigor de código abierto, garantizando la rastreabilidad legal y técnica total de todo su núcleo lógico.

---

## 🏛️ Fundamentos del Cumplimiento

La adopción de SPDX en SientiaMTX obedece a una estrategia de transparencia y seguridad en la cadena de suministro de software (*Software Supply Chain Security*). Esto permite que las organizaciones puedan auditar la aplicación mediante herramientas automatizadas (SCA - Software Composition Analysis) con fiabilidad absoluta.

### 1. Identificadores Cortos en Código Fuente
Cada archivo del núcleo lógico de SientiaMTX incluye una cabecera estandarizada SPDX que permite a los escáneres identificar de forma unívoca la licencia que rige dicho fichero sin ambigüedad.

Ejemplo de cabecera en archivos `.php`:
```php
<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>
```

### 2. Rastreo de Dependencias (SBoM)
El sistema gestiona su árbol de dependencias de manera modular y versionada a través de los ecosistemas líderes del mercado, permitiendo la generación instantánea de manifiestos SPDX:
*   **Ecosistema PHP (Composer):** Mediante el bloqueo exacto en `composer.lock`.
*   **Ecosistema Frontend (NPM):** Bloqueado determinantemente en `package-lock.json`.

---

## 📊 Beneficios para Entornos Corporativos

Implementar la metodología SPDX en la base de código aporta ventajas críticas para la adopción de SientiaMTX en entornos de alta confidencialidad:

| Beneficio | Impacto en SientiaMTX |
|---|---|
| **Garantía Legal** | Evita el "contagio" o brechas de propiedad intelectual, definiendo claramente la licencia Copyleft fuerte (AGPL-3.0). |
| **Automatización del Auditing** | Los departamentos de IT pueden pasar herramientas como *FOSSology* o *Black Duck* y certificar el código en segundos. |
| **Gestión de Vulnerabilidades** | Facilita el mapeo de componentes ante bases de datos de vulnerabilidades (CVE), localizando exactamente qué versión del software está en uso. |

---

## 🔧 Generación de Manifiestos

Para los administradores de sistemas que deseen generar un SBOM (Software Bill of Materials) completo de la instalación actual de SientiaMTX, se recomienda el uso de herramientas compatibles con CycloneDX o SPDX CLI:

```bash
# Ejemplo generando un reporte de licencias mediante Composer
composer license
```

> [!IMPORTANT]
> SientiaMTX mantiene un compromiso de tolerancia cero frente a dependencias obsoletas o con licencias conflictivas, manteniendo el manifiesto SPDX siempre íntegro y verificable.
