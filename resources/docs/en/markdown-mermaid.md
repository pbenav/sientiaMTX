# Comprehensive Guide to Markdown & Mermaid Diagrams in Sientia.MTX

Sientia.MTX includes a rich drafting engine based on **Standard Markdown** and native support for **interactive vector diagrams with Mermaid**. This enables teams to organize proposals, tasks, administrative cases, and digital signature circuits with visual elegance, structure, and clarity, both in the web interface and in official PDF exports.

---

## 1. Markdown Essentials

Markdown is a lightweight markup language that applies formatting using plain text characters.

### 1.1 Headers
```markdown
# Level 1 Heading (Main Title)
## Level 2 Heading (Section)
### Level 3 Heading (Subsection)
#### Level 4 Heading (Sub-item)
```

### 1.2 Text Formatting
* **Bold**: `**bold text**`
* *Italic*: `*italic text*`
* ~~Strikethrough~~: `~~strikethrough text~~`
* Inline Code: `` `identifier_or_command` ``

### 1.3 Lists & Checklists
```markdown
* Standard bullet item
  * Sub-bullet item
* Another item

1. First operational step
2. Second review step
3. Final decision

- [x] Identity verification completed
- [x] Digital certificate validated
- [ ] Technical approval report
```

### 1.4 Tables
```markdown
| Phase | Assigned Officer | SLA / Deadline | Status |
| :--- | :--- | :---: | :--- |
| Intake | Citizen Service Center | 24 hours | Completed |
| Review | Case Manager | 10 days | In Progress |
| Resolution | Department Director | 5 days | Pending |
```

---

## 2. Mermaid Visual Diagrams

Mermaid compiles plain text descriptions into vector graphics (SVG). Enclose any diagram inside ```` ```mermaid ```` code fences to render it in Sientia.MTX.

### 2.1 Flowcharts & Decision Trees (`graph` / `flowchart`)
```mermaid
graph TD
    A([Case Intake]) --> B[Registration & Document Check]
    B --> C{Complete Requirements?}
    C -- Yes --> D[Assign Technical Officer]
    C -- No --> E[Issue Rectification Notice]
    E --> F{Submitted within 10 days?}
    F -- Yes --> D
    F -- No --> G([Case Dismissal & Archival])
    D --> H[Draft Positive Assessment]
    H --> I[Digital Signature via AutoFirma]
    I --> J([Case Closure & Public Registry])
```

---

### 2.2 Sequence Diagrams (`sequenceDiagram`)
```mermaid
sequenceDiagram
    autonumber
    actor U as Signer / Officer
    participant S as Sientia.MTX Electronic Seat
    participant A as AutoFirma Client
    participant T as Time Stamping Authority

    U->>S: 1. Request document signing
    S->>S: 2. Calculate document SHA-256 hash
    S->>A: 3. Invoke AutoScript with hash and layout coords
    A->>U: 4. Prompt digital certificate selection
    U-->>A: 5. Select certificate
    A->>A: 6. Generate cryptographic PAdES-BES signature
    A-->>S: 7. Deliver signed package
    S->>T: 8. Request qualified timestamp
    T-->>S: 9. Timestamp sealed and registered
    S-->>U: 10. Display signed PDF with CSV & QR code
```

---

### 2.3 State Diagrams (`stateDiagram-v2`)
```mermaid
stateDiagram-v2
    [*] --> Draft : Creation
    Draft --> InProcess : Submit Circuit
    
    state InProcess {
        [*] --> PendingReview
        PendingReview --> ApprovedStep : Sign Step
        PendingReview --> RequestedChanges : Remarks
        RequestedChanges --> PendingReview : Resubmit
    }

    InProcess --> FullyApproved : Final Signature Complete
    InProcess --> Rejected : Negative Veto
    
    FullyApproved --> Published : Generate Public CSV & QR
    Published --> [*]
    Rejected --> [*]
```

---

### 2.4 Gantt Charts (`gantt`)
```mermaid
gantt
    title Implementation & Circuit Milestones
    dateFormat YYYY-MM-DD
    section Phase 1: Planning
    Process Mapping             :done, p1, 2026-11-01, 7d
    Template Parameterization   :active, p2, after p1, 8d
    section Phase 2: Rollout
    AutoFirma Integration       :r1, 2026-11-18, 5d
    Pilot Cases & Time Stamping :r2, after r1, 6d
    section Phase 3: Production
    Staff Training              :t1, after r2, 5d
    Go-Live Final Launch        :crit, after t1, 2d
```

---

### 2.5 Distribution Pie Charts (`pie`)
```mermaid
pie title Quarterly Administrative Cases
    "Approved & Digitally Sealed" : 65
    "Under Rectification" : 20
    "Pending Technical Review" : 10
    "Dismissed" : 5
```

---

## 3. Editor Features in Sientia.MTX

1. **Toolbar Shortcut**: Click the **`MERMAID`** button next to the **`MARKDOWN`** cheat sheet in the editor.
2. **One-Click Insert**: Explore ready-to-use templates and click **`⚡ Insert into Document`** to drop code directly at the cursor position.
3. **Live Preview**: Switch to the **Preview** tab to review rendered diagrams before saving.
4. **PDF Output**: Sientia.MTX automatically renders and embeds clean, high-resolution diagram graphics in official PDF reports and signed circuit documents.
