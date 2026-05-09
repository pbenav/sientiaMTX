# 🔐 Compliance with the National Security Framework (ENS)

The **National Security Framework (ENS)** regulates the security conditions necessary to ensure trust in the use of electronic means in the Spanish Public Administration and its supply chain.

**SientiaMTX** has been conceived from its design (*Security by Design*) to offer the highest level of technological sovereignty and confidentiality, architecturally prepared to fulfill the ENS requirements.

---

## 🛡️ Foundational Compliance Pillars

The SientiaMTX architecture satisfies ENS demands in multiple key dimensions, enabling its deployment in infrastructures handling Medium or High-level data.

### 1. Data Sovereignty and On-Premise Deployment
Unlike SaaS solutions hosted in third-party clouds, SientiaMTX favors self-hosted container deployments.
*   **Physical Location:** Data resides on client-controlled servers (e.g., local Proxmox LXC).
*   **Network Isolation:** Designed to operate behind NAT without exposing inbound ports, thanks to its secure outbound deployment pipeline.

### 2. Authentication and Access Control (MFA)
Rigorously complying with the principle of prevention and access control:
*   **Robust MFA:** Implements mandatory Two-Factor Authentication (2FA/TOTP).
*   **Offline Privacy:** Unlike other systems, pairing QR code generation occurs **locally in the browser**, eliminating dependencies on third-party APIs (like Google Charts) and preventing cryptographic secret leakage over the network.

### 3. Cryptography and Communications
*   **Encryption at Rest:** Sensitive credentials, such as AI API keys, Google Tokens, or TOTP secrets, are stored using robust symmetric encryption (AES-256-CBC) governed by the `APP_KEY` master key.
*   **Secure Channels:** Native enforced support for TLS 1.3 in all web interactions and API notifications.

### 4. Traceability and Auditing
SientiaMTX maintains a detailed record of actions impacting system security:
*   Detailed log of logins and access IPs.
*   Immutable history of task state changes and assignments.
*   Session isolation in third-party integrations (Isolated multi-session in WhatsApp Bridge).

---

## ⚖️ ENS Adequacy Matrix

| ENS Measure | SientiaMTX Implementation | Assurance Level |
|---|---|---|
| **[op.acc.1]** Identification & Authentication | 2FA Integration (Local-Offline) and Bcrypt hashed passwords (work-factor 12+). | HIGH |
| **[op.pl.1]** Capacity Planning | Active disk quota monitoring per team to prevent resource exhaustion attacks. | MEDIUM |
| **[mp.info.1]** Information Protection | SQL Database backups and storage can be managed locally without internet connectivity. | HIGH |
| **[mp.com.1]** Communications Protection | API Integration based on secure Webhooks and end-to-end SSL/TLS support. | HIGH |

---

## 💼 Adequacy for Confidential Environments

Thanks to this paranoid approach to security, SientiaMTX is ideal for:
1.  **Strategic Companies:** Sensitive task management without exposing data to commercial clouds.
2.  **Local Governments:** Immediate compliance with GDPR regulations and local security schemes.
3.  **R&D:** Environments where competitive data leakage poses an existential risk.

> [!TIP]
> To maximize ENS compliance, it is recommended to keep SientiaMTX updated regularly through the authorized CI/CD pipeline and host it on operating systems with active security support (LTS).
