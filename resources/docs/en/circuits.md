# Electronic Workflow Circuits, Digital Signature & Public Verification (CSV / QR)

**sientia.MTX** incorporates a comprehensive collegial governance and administrative electronic workflow engine designed to provide proposals, documents, and resolutions with full legal validity in compliance with **Law 39/2015** and the European **eIDAS Regulation (EU 910/2014)**.

---

## 🏛️ 1. Concept and Circuit Architecture

A **Circuit Execution** is a sequential or structured review and sign-off workflow applied to any entity in the system (**Activities**, documents, cases, or proposals).

```
   [Activity / Document / Expediente]
                     │
                     ▼
       Step 1: Initial Endorsement ────────► Conformity CSV / Signature
                     │
                     ▼
       Step 2: Technical Justification ────► Report & Motivation
                     │
                     ▼
       Step 3: Definitive Approval ────────► AutoFirma (PAdES) / Seal
                     │
                     ▼
       [Consolidated PDF Diligence] ───────► CSV + QR + Public Edict Board
```

### Key Highlights
- **Granular Role Assignment**: Steps can be assigned to individual users, coordinators, item owners, or specific roles (e.g. Legal Counsel, Comptroller, Executive Director).
- **Decision Gates**: Participants can **Approve**, **Return for Submediation** (specifying the target step and detailed feedback), or **Reject**.
- **Immutability and Audit Log**: Captures IP address, user-agent, ISO 8601 timestamps, SHA-256 evidence hashes, and official notes.
- **Pending Tasks Inbox**: Direct access via `/teams/{team}/circuitos/pendientes` to centralize all signatures and reviews awaiting the user's action.
- **Template Designer**: Create and configure reusable workflow templates with sequential order, titles, descriptions, and required signature type per phase (`/teams/{team}/circuitos/plantillas`).
- **Auto-Start / Manual Initiation**: Circuits can be launched manually from any Activity or Expediente, or automatically upon task creation when linked to a template.

---

## ✍️ 2. Dual Signature Modalities

The system supports a dual-tier signature architecture adapted to each procedural phase:

| Modality | Scope of Application | Technical Mechanism | Legal Stance |
|---|---|---|---|
| **Digital Signature AutoFirma (PAdES)** | Official resolutions, formal acts, definitive approvals | X.509 digital certificate via the native AutoFirma client | Recognized / Qualified Electronic Signature (eIDAS / Law 39/2015) |
| **Accredited Conformity (CSV)** | Preliminary technical checks, internal endorsements | Deterministic Secure Verification Code (CSV) with SHA-256 evidence hashing | Legally accredited internal conformity |
| **External / Manual Signature** | Contingency when local AutoFirma client is unavailable | Download generated PDF, sign with desktop tool, upload signed PDF | Preserves external PAdES cryptographic container |

---

## 🔒 3. Consolidated Official PDF Diligence

Every completed circuit generates a tamper-evident consolidated PDF diligence:
- **Institutional Header**: Organization badge, case number, team, and canonical CSV code.
- **Workflow Itinerary**: Summary of all stages, actors, decisions, and signatures (`✓ Firmado`).
- **Signature Boxes with Dynamic QR Codes**: Green institutional frames with signer credentials, complete 64-character SHA-256 cryptographic hashes, and high-resolution QR codes linking to public verification.
- **Pre-synchronized Signing**: AutoFirma downloads the document pre-stamped in the approved state, guaranteeing total synchronization.

---

## 🌐 4. Public Document Verification Portal (`/verificar/{csv}`)

Any citizen or auditing body can verify authentic copies without requiring system credentials:
- Open URL: `/verificar/{csv?}`
- Scan QR code from mobile devices to view certificate of authenticity.
- Direct download of authentic consolidated PDF (`/verificar/{csv}/descargar`).

---

## 📢 5. Public Edict Notice Board (`/tablon/{team}`)

Concluded workflows can be officially published on the institutional **Public Edict Board**:
- **Internal Publishing Management**: From `/teams/{team}/circuitos/tablon`, team coordinators can publish or unpublish edicts, set active timeframes, and inspect visitor statistics.
- **Official Edict Reference**: Sequential official code assignment (e.g., `EDICTO-2026-0001`).
- **Public Visibility Window**: Definitive start and auto-expiration dates.
- **Public Citizen Portal (`/tablon/{team}`)**:
  - Open citizen access without requiring login.
  - Search engine and filtering by year and active/expired status.
  - Detailed edict view (`/tablon/doc/{slug}`) with procedural metadata.
  - Direct download of signed official PDF diligence with CSV (`/tablon/doc/{slug}/descargar` and `/tablon/doc/{slug}/pdf`) for total administrative transparency.

---

## 🧭 6. Workflow Integration and Navigation

The circuit module connects seamlessly across team views:
- **Team Circuit Hub**: Access general workflow index, pending reviews, template manager, and edict board.
- **Active Circuit Banner**: Top banner across Activities and Expedientes showing completion percentage, current step, and immediate signing link.
- **Visual Stepper & Audit Widget**: Step-by-step progress tracker with color coding (green approved, purple in progress, gray pending, red returned) and access to step notes and attachments.

