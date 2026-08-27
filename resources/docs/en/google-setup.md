# Google API Setup Guide

For task synchronization with Google Calendar to work, you need to obtain a **Client ID** and a **Client Secret** from the Google Cloud Console.

### 1. Create a Project in Google Cloud
1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. In the top left, click the project selector and then the **"New Project"** button.
3. Enter a name (e.g., `sientiaMTX`) and click **Create**.

### 2. Enable the Necessary APIs
1. In the left menu, go to **APIs & Services > Library**.
2. Search for and enable the following APIs:
   - **Google Calendar API**
   - **Gmail API**

### 3. Configure the OAuth Consent Screen
*This step is mandatory before creating credentials.*
1. Go to **APIs & Services > OAuth consent screen**.
2. Choose **User Type: External** (if you do not have a Google Workspace organization).
3. Fill in the required fields:
   - **App name:** `sientiaMTX`
   - **User support email:** Your email.
   - **Developer contact info:** Your email.
4. In **Scopes**, add:
   - `.../auth/calendar.events.readonly`
   - `.../auth/gmail.readonly`
   - `.../auth/userinfo.email`
5. In **Test users**, add your own email address (the one you will use for testing). *This is crucial because while the app is in "Testing" mode, only the email addresses listed here will be able to log in.*

### 4. Create Credentials (Client ID)
1. Go to **APIs & Services > Credentials**.
2. Click **+ Create Credentials > OAuth client ID**.
3. **Application type:** Web application.
4. **Name:** `sientiaMTX Web Client`.
5. **Authorized redirect URIs:**
   - Add: `http://localhost:8000/auth/google/callback` (for local testing).
   - Add your staging/production server URL (e.g., `https://yourserver.com/auth/google/callback`).

### 5. Configure in SientiaMTX
1. Once created, copy the **Client ID** and **Client Secret**.
2. Go to the application **Settings** in the side menu.
3. Paste the credentials into the Google section.
4. Ensure that the URLs in `.env` match the ones you entered in the Google Console.
