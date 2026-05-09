# 🛡️ SPDX Compatibility and Compliance

**Software Package Data Exchange® (SPDX)** is an open standard (ISO/IEC 5230) designed to communicate software bill of materials (SBOM) information, including components, licenses, copyrights, and security references.

SientiaMTX has been developed under the highest standards of open-source rigor, guaranteeing total legal and technical traceability of its entire logical core.

---

## 🏛️ Compliance Fundamentals

The adoption of SPDX in SientiaMTX adheres to a strategy of transparency and security in the software supply chain (*Software Supply Chain Security*). This allows organizations to audit the application using automated SCA (Software Composition Analysis) tools with absolute reliability.

### 1. Short-Form Identifiers in Source Code
Every file in the SientiaMTX logical core includes a standardized SPDX header, allowing scanners to uniquely and unambiguously identify the license governing the file.

Example header in `.php` files:
```php
<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>
```

### 2. Dependency Tracking (SBoM)
The system manages its dependency tree modularly and in versioned manner through leading market ecosystems, enabling instant generation of SPDX manifests:
*   **PHP Ecosystem (Composer):** Through precise locking in `composer.lock`.
*   **Frontend Ecosystem (NPM):** Deterministically locked in `package-lock.json`.

---

## 📊 Benefits for Corporate Environments

Implementing the SPDX methodology in the codebase provides critical advantages for adopting SientiaMTX in high-confidentiality environments:

| Benefit | Impact on SientiaMTX |
|---|---|
| **Legal Guarantee** | Prevents intellectual property breaches, clearly defining the strong Copyleft license (AGPL-3.0). |
| **Automated Auditing** | IT departments can use tools like *FOSSology* or *Black Duck* to certify the code in seconds. |
| **Vulnerability Management** | Facilitates component mapping to vulnerability databases (CVE), accurately identifying exactly which software version is in use. |

---

## 🔧 Generating Manifests

System administrators wishing to generate a complete SBOM (Software Bill of Materials) for the current SientiaMTX installation are encouraged to use tools compatible with CycloneDX or SPDX CLI:

```bash
# Example generating a license report through Composer
composer license
```

> [!IMPORTANT]
> SientiaMTX maintains a zero-tolerance policy against obsolete dependencies or conflicting licenses, keeping the SPDX manifest always integral and verifiable.
