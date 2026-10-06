# 📆 Citizen Appointments Portal — SientiaMTX (v1.2.0)

The **Appointments (Cita Previa)** module in SientiaMTX connects your team's agenda with citizens, customers, and external visitors. It delivers an intuitive self-service public booking portal, granular capacity control, and an operational desk equipped with a live attention stopwatch.

---

## 🌐 1. Public Self-Service Booking Portal

Each team or professional gets a public booking portal accessible without requiring visitors to create an account in the system:

```
[Public Portal] ➔ 1. Select Service ➔ 2. Modality ➔ 3. Date & Time ➔ 4. Personal Info & ID ➔ [Unique Locator]
```

### Key Booking Features:
- **Responsive & Accessible Design**: Crafted for mobile phones, tablets, and desktop workstations.
- **Real-Time Capacity Badges**: Time slot buttons visually indicate available openings (e.g., `09:30 (3 available)`).
- **Anti-Fraud & Duplicate Prevention**:
  - Automated normalization of National ID / Passport numbers and emails (stripping whitespaces, case normalization).
  - If a visitor attempts to submit duplicate concurrent bookings for the same service, the system detects it and offers a direct link to check or manage their existing booking using their locator code.
- **Simplified Unique Locator**:
  - Upon completing the booking, the visitor receives a memorable locator code (e.g., `25C-B4A1`) displayed on screen and sent via email confirmation.
  - Using this code and their email address, visitors can verify their booking status or cancel on their own within policy limits.

---

## ⚙️ 2. Service Configuration (`AppointmentService`)

Coordinators and administrators can create customized public services with independent policies:

| Setting | Description |
|---|---|
| **Modalities** | `In-Person` (on-premise), `Google Meet` (with dynamic link generation), or `Sientia Meet` (embedded Jitsi without external accounts). |
| **Standard Duration** | Nominal consultation length in minutes (e.g., 15, 30, 60 min). |
| **Capacity per Slot (`max_per_slot`)** | Maximum concurrent bookings permitted within each time slot. |
| **Custom Form Fields** | Configure custom visitor questions (free text, case numbers, reasons, drop-down menus). |
| **Pricing & Transparency** | Define service fee and toggle public pricing visibility on or off. |
| **Cloud Synchronization** | Optional bidirectional synchronization with Google Calendar and Google Tasks. |
| **GDPR / Privacy Consent** | Dedicated legal disclosure text in Markdown format that visitors must review and accept before booking. |

---

## ⏰ 3. Availability Schedules & Shifts

The availability engine dynamically calculates open slots across multiple calendar layers:

1. **Standard Weekly Schedules (`AppointmentSchedule`)**: Morning and afternoon shifts configured per day of the week, with customizable buffer times.
2. **Calendar Blocks (`AppointmentBlock`)**: Holidays, vacations, or scheduled maintenance periods.
3. **Advance Notice Requirements**: Prevent last-minute bookings by enforcing a minimum notice window (in hours or days).
4. **Extraordinary Administrative Overrides**:
   - Ability to book consultations in past dates/times to record walk-in visits or unplanned phone consultations.
   - Authorize extraordinary time slots without modifying the recurring schedule template.

---

## 🖥️ 4. Attention Desk & Operations Workflow

The internal appointments panel gives front-desk staff and professionals complete control over visitor flow:

- **Grouped Time-Block View**: Appointments are grouped visually by time segments and dates, with elevated color contrast for fast reading in reception areas.
- **"Attend Now" (Atender Ahora) Button**:
  - If a visitor arrives earlier or later than scheduled, clicking **"Attend Now"** automatically shifts the appointment start time to the exact current moment.
- **Live Consultation Stopwatch**:
  - Measures consultation duration in real time with second-level precision.
  - Automatically records spent time into effort accounting analytics.
- **Dossier & Activity Linkage**:
  - Link the appointment to an existing dossier (`EXP-...`) or spawn follow-up tasks with a single click.
- **Consultation Notes**: Private operational notes recorded by the practitioner (`member_notes`).

---

## 📈 5. Duration Analytics & KPI Dashboard

At the top of the Appointments workspace, an analytics card displays real-time statistics covering the **last 30 days**:

- **Total Period Volume**: Confirmed, completed, canceled, and *No Show* consultations.
- **Consultation Duration Breakdown**:
  - **Minimum Duration**: Shortest consultation recorded.
  - **Average Duration**: Mean consultation time.
  - **Mode (Most Frequent)**: The most frequent consultation duration.
  - **Maximum Duration**: Longest consultation recorded.

These metrics allow operations managers to fine-tune `duration_minutes` to match real-world service requirements, eliminating bottleneck queues.

---

## 🛡️ 6. Data Protection & Right to Erasure (GDPR Art. 17)

SientiaMTX implements strict European data protection standards:

- **Isolated Visitor Registry (`AppointmentVisitor`)**: Personal identification data is structured and isolated from system users.
- **Definitive Anonymization**: Fulfill Right to Erasure requests (GDPR Art. 17) by irreversibly scrubbing all personal identifiers (name, ID number, phone, email) while preserving operational statistical counts for public service reporting.

