# 🤖 Ax.ia AI Assistant (Google Gemini API) — SientiaMTX (v1.2.0)

**Ax.ia** is the native Artificial Intelligence copilot embedded throughout the SientiaMTX ecosystem. Powered by Google Gemini foundation models, Ax.ia helps teams draft documentation, transcribe voice memos, make structured decisions, and transfer outputs directly into active workspace items.

---

## 🔑 1. Obtaining a Gemini API Key

To activate Ax.ia, you need a Google Gemini API Key:

1. Sign in to the [Google AI Studio](https://aistudio.google.com/) console using your Google credentials.
2. In the navigation sidebar, click on **"Get API key"**.
3. Click the **"Create API key"** button.
4. Select an existing Google Cloud project or provision a new one to bind the key.
5. Copy your newly created API key and store it securely.

---

## ⚙️ 2. Key Configuration Hierarchy

SientiaMTX supports a three-tier API key resolution order:

### Tier 1: Global System Key (`.env`)
Recommended for organizations funding centralized AI access for all corporate users:
1. Edit the `.env` file at the root of your SientiaMTX deployment:
   ```env
   GEMINI_API_KEY="your_google_ai_studio_api_key"
   ```
2. Clear Laravel configuration cache:
   ```bash
   php artisan config:clear
   ```

### Tier 2: Team-Specific Workspace Key
Teams can isolate their API quota by storing an encrypted team key under **Team Settings ➔ Artificial Intelligence**.

### Tier 3: Personal User Key
Any individual member can supply their own key under **Profile ➔ AI Integrations**, enabling Ax.ia with their personal quota regardless of system-level settings.

---

## 🧠 3. Supported Models & Fallback Architecture

SientiaMTX implements intelligent model selection to balance speed, cost, and rate-limit resilience:

| Model | Primary Purpose | Profile |
|---|---|---|
| **Gemini 2.0 Flash** / **1.5 Flash** | Default engine for general chat, fast breakdowns, and real-time completions. | Ultra-fast, minimal latency |
| **Gemini 1.5 Pro** | Complex multi-step reasoning, exhaustive document analysis, and meeting minutes synthesis. | High context window |

If the Google API encounters transient rate limits (HTTP 429), SientiaMTX automatically invokes the secondary fallback chain to maintain continuous operation.

---

## 🚀 4. Smart AI Content Transfer

Unlike isolated chat tools, Ax.ia is connected to SientiaMTX's core domain models:

```
[Ax.ia Conversation]
         │
         ├──▶ Transfer to Activity Description
         ├──▶ Inject as Internal Team Note
         ├──▶ Append as New Chapter in Document (OnlyOffice/Markdown)
         └──▶ Post Thread to Discussion Forum
```

- **Document Injection**: Generate complex long-form sections and insert them as structured chapters within any Document activity.
- **Activity Generation**: Ax.ia extracts titles, descriptions, urgency levels, and due dates to generate actionable tasks automatically.
- **Instant Audio Transcription**: Upload or record voice memos; Gemini transcribes the audio into formatted text immediately.

---

## 🛠️ 5. Verification & Health Check

To test your Ax.ia configuration:
1. Open the Ax.ia assistant drawer from the floating icon in the bottom corner.
2. Send a prompt (e.g., *"Generate 3 subtasks to organize a quarterly product launch"*).
3. Click the transfer button to confirm that output injects smoothly into the target item.
