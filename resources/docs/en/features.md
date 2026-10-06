# Sientia MTX: Feature Master Guide (v1.2.0)

SientiaMTX is a high-performance productivity ecosystem that merges the Eisenhower methodology with advanced Artificial Intelligence, collaborative document management, and citizen appointments.

---

## 🚀 1. High-Impact Operational Dashboard

The command center where team effort and service pulse converge.

### 📊 The Eisenhower Matrix
*   **Intelligent Visualization**: Automatic classification of tasks into Q1 (Urgent/Important), Q2 (Planning), Q3 (Delegation), and Q4 (Elimination).
*   **Focus on Value**: Optimized design to reduce visual fatigue and prioritize work that generates real impact.

### 🛡️ Sentinel: Collaborative Monitoring
*   **Service Pulse**: Real-time monitoring of critical tools with visual status indicators (🟢, 🟡, 🔴).
*   **Sentinel Bonus**: Gamified incentive system for members who report and validate service outages.

### 🔋 Energy Management (Flow)
*   **Vital Energy**: Each user has an energy level that is consumed based on the cognitive load of tasks, preventing burnout through a morning "Fresh Start" system.

---

## 💬 2. Real-Time Communication and AI

### 💬 Sientia Chat and Integrated Videoconferencing
*   **Instant Chat**: Direct instant messaging system between collaborators integrated into the sidebar with an ultra-modern *Glassmorphism* design and optimized scrollbars.
*   **History Clearing 🧹**: Advanced secure erase feature that completely wipes the conversation history between two collaborators with SweetAlert2 visual confirmation.
*   **One-Click Videocall Suites**:
    *   **Sientia Meet (Jitsi) 🎥**: Unlimited instant videoconferences without accounts or registrations of any kind, integrated on the fly.
    *   **Rapid Google Meet 🌐**: Creation of rooms with **intelligent link sanitization** (allows pasting URLs, shortened links, or simple 10-letter codes and automatically expands them).
*   **Immersive Alerts & Reactive Notifications**:
    *   **In-Focus Calls**: Incoming calls display a centered, screen-wide interactive SweetAlert with the sender's avatar, audio-frequency synthesized chime, and blinking tab title.
    *   **Non-Intrusive Messages**: Ordinary messages are notified via interactive Toasts with a click-to-reply *"Click to respond"* option.
    *   **Pending Bouncing Badges ✉️**: A global state store (`Alpine.store`) synchronizes unread messages and displays dynamic red envelope badges bouncing on avatars in the "Active Network" list until the chat is opened.

### 🏛️ Nested Forums (Threads)
*   **Deep Discussions**: Support for nested conversation threads, direct citations, and user mentions.
*   **Premium Preview**: Preview system before publishing with full rendering of Markdown and images.

### 🤖 Ax.ia: Your Productivity Copilot
*   **AI Everywhere**: Integrated into tasks, forums, and quick notes with Gemini 2.0 Flash and 1.5 Pro model support.
*   **Voice to Text**: Automatic transcription of voice notes in seconds.
*   **AI Content Transfer**: Inject summaries, minutes, and breakdowns directly into descriptions, team notes, document chapters, or forum threads.

---

## 📋 3. Universal Activities & Case Management (Expedientes)

### ⚡ The 7 Activity Types
*   **Tasks (`task`)**: Operational execution, hierarchical subtasks, and exact completion timestamp (`completed_at`).
*   **Documents (`document`)**: Live OnlyOffice office editing and Filerobot graphic manipulation.
*   **Notes (`note`)**: Rich markdown notes with voice dictation and pin support (`pinned`).
*   **Links (`link`)**: Web reference cards with automatic Open Graph metadata fetching.
*   **Agreements (`agreement`)**: Formal records of team decisions with rationale, alternatives, impact levels, and internal digital approvals.
*   **Meetings (`meeting`)**: In-person sessions, Jitsi rooms, or Google Meet with agendas and recorded minutes.
*   **Reminders (`reminder`)**: Multi-channel alerts (Telegram, WhatsApp, Mail, Push) with live countdown and interactive toggle switches.

### 📂 Case Management & Deep Privacy
*   **Official Sequential Code**: Unique identifier `EXP-YYYY-NNNN`.
*   **Strict Deep Privacy**: Private files remain strictly confidential to the creator and assigned members, shielded even from non-participating administrators.
*   **Cross-Dossier Relations**: Bidirectional linking between related case files.

---

## ⏱️ 4. Time Tracking & Workday Governance

*   **Real-Time Stopwatch**: High-precision display (hours, minutes, seconds) with a sticky top banner across all views.
*   **Effort Accounting Widget**: Audit time invested per activity and member with strict privacy filtering.
*   **Anomalous Workday Detection**: Automatically identifies forgotten stopwatches with a 10-hour hard cap and computes normalized `effectiveMinutes` (`timelogs:mark-anomalous`).

---

## 📆 5. Citizen Appointments & Public Booking

*   **Public Booking Portal**: Self-service scheduling with modality selection (in-person, video) and real-time slot capacity counters.
*   **Unique Locator**: Simplified code (e.g., `25C-B4A1`) for appointment verification or self-service cancellation.
*   **Attention Desk**: "Attend Now" button shifts appointments to the current moment, launches the consultation stopwatch, and records session notes.
*   **Duration Analytics**: 30-day KPI cards tracking minimum, average, mode, and maximum consultation lengths.

---

## 🎮 6. Gamification: The Skill Tree

*   **Professional Evolution**: Work activities feed a skill tree (Support, Development, Systems, etc.), reflecting the authentic growth of each team member.
*   **Resilience Points**: Special recognition for taking on challenges outside primary specialty areas.

---

**Sientia MTX: Collective Intelligence for High-Performance Teams.**
