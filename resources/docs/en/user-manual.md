# 📖 SientiaMTX — User Manual (v1.2.0)

SientiaMTX is a **high-performance productivity ecosystem** blending the Eisenhower Matrix methodology with advanced Artificial Intelligence, collaborative document management, and robust team operations.

---

## 🔐 1. Access & Profile

### Login & Authentication
Sign in using your corporate email and password. If your organization enables Google Workspace, you can sign in or link your account with one click to synchronize calendars and tasks.

### Profile Settings
From the user dropdown (top-right corner) ➔ **Profile**:
- **Personal Information**: Full name and email address.
- **Timezone & Language**: Seamless toggle between Spanish and English for both application interfaces and system documentation.
- **AI Integrations**: Configure your personal Google Gemini API key if your team does not provide a centralized enterprise key.

### 🛡️ Multi-Factor Authentication (2FA / MFA)
SientiaMTX protects accounts with two-factor authentication compliant with European security standards (ENS):
1. Go to **Profile ➔ Security Settings**.
2. Enable 2FA by confirming your current password.
3. Choose between **Email Verification Code** or **Authenticator App (TOTP)** (Google Authenticator, Authy, etc.).

> [!NOTE]
> **Client-Side Privacy:** QR code generation for TOTP activation takes place 100% locally in your browser. No secret keys or seeds are ever transmitted to third-party APIs.

---

## 📋 2. The Eisenhower Matrix

The methodological foundation of SientiaMTX prioritizes genuine value over artificial urgency:

| Quadrant | Classification | Recommended Action | Focus |
|---|---|---|---|
| **Q1 — Do First** | Urgent & Important | Resolve immediately | Crises, imminent hard deadlines |
| **Q2 — Schedule** | Not Urgent but Important | Block focused calendar time | Strategy, innovation, prevention |
| **Q3 — Delegate** | Urgent but Not Important | Delegate or automate | Disruptions, secondary operations |
| **Q4 — Eliminate** | Not Urgent & Not Important | Discard or postpone | Distractions, non-essential clutter |

> [!TIP]
> High-performance teams spend upwards of 65% of their working hours in **Q2**. A board flooded with Q1 items indicates reactive rather than proactive planning.

---

## ⚡ 3. Unified Activities & Operational Workflow

Starting in v1.2.0, all work is organized under the universal **Activities (Items)** architecture:

1. **Tasks (`task`)**: Actionable tasks with subtask nesting, deadlines, and completion timestamps (`completed_at`).
2. **Documents (`document`)**: Live multi-user office editing via **OnlyOffice** and graphic asset manipulation using the **Filerobot** image editor.
3. **Notes (`note`)**: Rich Markdown knowledge notes with voice memo recording transcribed automatically by Ax.ia.
4. **Links (`link`)**: Smart bookmarks with automatic Open Graph metadata fetching (`og:title`, `og:image`).
5. **Agreements (`agreement`)**: Formal consensus records with rationale, alternative analysis, impact levels, and internal digital approvals.
6. **Meetings (`meeting`)**: In-person sessions, instant video rooms on **Sientia Meet (Jitsi)**, or **Google Meet** with automatic URL sanitation, agendas, and minutes.
7. **Reminders (`reminder`)**: Alarms with live countdown timers, completion toggle switches, and dispatch via Telegram, WhatsApp, Email, or Web Push.

> [!NOTE]
> For complete technical schemas and detailed capabilities, refer to the [Activities & Workflow Guide](activities.md).

---

## ⏱️ 4. Time Tracking & Real-Time Stopwatch

1. **Live Stopwatch**: Launch time tracking on any activity by clicking its stopwatch icon. Tracks hours, minutes, and seconds with precision.
2. **Sticky Top Banner**: A persistent banner keeps you informed of your running activity across the app, enabling one-click pauses or task switching.
3. **Effort Accounting Widget**: Audit time invested per activity and member directly on your dashboard, with deep privacy safeguards for confidential files.

---

## 📂 5. Case Management (Expedientes) & Deep Privacy

**Expedientes (Dossiers)** organize activities, documents, notes, and citizen consultations under a sequential identifier `EXP-YYYY-NNNN`.

- **Public Dossiers**: Visible to all workspace team members.
- **Private Dossiers (*Strict Deep Privacy*)**: Strictly confidential. Accessible exclusively to the creator and explicitly assigned members or groups. Team Owners, Coordinators, and System Admins cannot view private dossiers unless explicitly invited.

> [!NOTE]
> Review the [Case Management (Expedientes) Guide](expedientes.md) for cross-dossier linking and permission management details.

---

## 💬 6. Communication: Chat, Video Calls & Forums

### Sientia Chat & Video Meetings
- **Direct Messaging**: Instant teammate chat in the sidebar with a modern *Glassmorphism* aesthetic.
- **Immersive Incoming Call Alerts**: Full-screen call modal with acoustic alert and caller avatar.
- **Bouncing Unread Badges**: Unread messages dynamically pulse over user avatars in the Active Network.
- **Secure History Wipe**: Erase private chat history between two users with confirmed safety prompts.
- **One-Click Video Conferencing**:
  - **Sientia Meet**: Instant Jitsi video rooms with zero time limits and no external sign-ups.
  - **Smart Google Meet**: Automatic sanitation of room URLs and raw 10-character room codes.

### Nested Discussion Forums
- **Threaded Discussions**: Context-preserved discussions organized into clear threads.
- **Citations & Mentions**: Quote specific replies and tag colleagues (`@user`) for immediate notifications.
- **Real-Time Preview**: Live rendering of Markdown and media attachments before posting.

---

## 📊 7. Workspace Views

- **Eisenhower Matrix**: Daily tactical triage for Tasks and Agreements.
- **Kanban Board**: Drag & drop workflow columns with automated completion percentage calculations.
- **Gantt (Roadmap)**: Interactive scheduling timeline. Open-ended activities render with a **faded gradient and dashed border**; drag the right handle to set due dates on the fly.
- **Active Network**: Real-time presence telemetry showing teammate activity: 🟢 Active, 🔴 In Labor, 🟡 Away.

---

## 🤖 8. Ax.ia: Your AI Productivity Copilot (Gemini)

Ax.ia (powered by **Gemini 2.0 Flash** and **Gemini 1.5 Pro**) is integrated into your entire workspace:

- **Goal Breakdown**: Ask Ax.ia to analyze a complex milestone and suggest subtasks.
- **Voice-to-Text**: Dictate audio memos; Ax.ia transcribes them accurately in seconds.
- **Content Transfer**: Inject AI conversation outputs directly into activity descriptions, team notes, or as new chapters in documents.

---

## 📆 9. Appointments & Public Booking

- **Public Portal**: Share your booking link so citizens or clients can select services, modalities, and time slots.
- **Unique Locator**: Bookings generate a locator code (e.g., `25C-B4A1`) for easy tracking and self-service cancellation.
- **"Attend Now" Desk Action**: Update appointment start time to the current second when visitors arrive early or late, launching the consultation stopwatch.

---

## 📡 10. Sentinel: Collaborative Service Monitoring

- **Service Pulse**: Check the health status of critical infrastructure tools (🟢 Operational, 🟡 Degraded, 🔴 Down).
- **Incident Reporting**: Flag service outages to alert your teammates.
- **Sentinel Rewards**: Earn XP points and recover Vital Energy by participating in system monitoring.

---

**SientiaMTX: Elevating productivity and collaboration through state-of-the-art design and technology.**
