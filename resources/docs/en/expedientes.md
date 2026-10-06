# 📂 Case Management (Expedientes) — SientiaMTX (v1.2.0)

In corporate organizations, legal chambers, and high-performance teams, work rarely happens in isolation. **Expedientes (Dossiers / Case Files)** in SientiaMTX function as master work folders and operational containers, centralizing all activities, appointments, notes, and file attachments associated with a particular case or project.

---

## 🏛️ 1. Concept & Anatomy of an Expediente

A dossier unifies the entire lifecycle of a procedure under a single roof:

```
┌─────────────────────────────────────────────────────────────┐
│                    EXPEDIENTE (EXP-2026-0014)               │
├─────────────────────────────────────────────────────────────┤
│ ├── Linked Tasks & Activities                               │
│ ├── Documents (OnlyOffice) & Images (Filerobot)             │
│ ├── Associated Citizen/Client Appointments                  │
│ ├── Internal notes & follow-up log                          │
│ ├── Bidirectional links to Related Expedientes              │
│ └── Authorized Member Registry (Deep Privacy)               │
└─────────────────────────────────────────────────────────────┘
```

### Official Sequential Code (`EXP-YYYY-NNNN`)
Each dossier is automatically assigned an official sequential identifier by the system formatted as:
$$\text{EXP} - \text{YEAR} - \text{NUMBER}$$
*(e.g., `EXP-2026-0001`, `EXP-2026-0002`)*. This code is immutable, guarantees administrative audit trails, and prevents duplicate entries.

---

## 🔒 2. Deep Privacy Architecture

One of SientiaMTX's most rigorous security pillars is its **Deep Privacy** enforcement:

### Public Dossiers
- Visible and accessible to all active members of the workspace team.
- Ideal for transversal projects, open operational procedures, and collective reference materials.

### Private Dossiers (*Strict Deep Privacy*)
- Engineered for strictly confidential matters (human resources, legal audits, complaints, medical cases, or sensitive trade secrets).
- **Strict Isolation Principle**: **Only the creator, the primary assignee, and explicitly invited members/groups can view, search, or access the dossier**.
- **Admin Hierarchy Shield**: Neither Team Owners, Team Coordinators, nor Global System Administrators can view private dossiers unless they have been explicitly added as collaborators.
- **Access Transparency**: The dossier details view clearly documents each user's authorized access reason:
  - `Creator`: The user who created the file.
  - `Assignee`: The primary responsible member.
  - `Assigned`: Direct individual collaborator.
  - `Group`: User with inherited access via an assigned team group.
  - `Task`: User assigned to an internal task linked to this file.

---

## 🛠️ 3. Control Toolbar & Real-Time Search

The dossiers view features a unified toolbar designed for visual consistency with the Forum and Activities interfaces:

- **Reactive Search**: Instant filtering by official code (`EXP-...`), keywords in the title, or descriptive summary terms.
- **Status Lifecycle Filters**:
  - `Draft / Initiated`: Preliminary gathering of case materials.
  - `In Progress`: Active processing.
  - `On Hold / Waiting`: Awaiting external documentation or administrative resolution.
  - `Resolved / Closed`: Successfully completed case.
  - `Archived`: Inactive historical record retained for audit purposes.
- **Priority Classification**: Triage levels including Low, Medium, High, and Urgent.
- **Harmonized Empty States**: Clear visual cues and direct call-to-actions when no dossiers match active filters.

---

## 🔗 4. Bidirectional Inter-Dossier Relations

Dossiers can be linked bidirectionally (`relatedExpedientes`):
- When *Dossier A* is linked to *Dossier B*, both files automatically display reciprocal direct links.
- Enables tracking dependencies between parent files, related litigation, or cross-departmental operations.

---

## 📆 5. Citizen Appointments Integration

When a client or citizen books a consultation through the public portal or is received in an office:
- The appointment can be directly attached to an existing dossier from the attention desk.
- Operators can view the complete consultation history within the context of the dossier, tracking cumulative face-to-face or video time alongside session notes.

