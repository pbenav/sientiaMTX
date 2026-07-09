# Team Wellness and Performance Metrics

In SientiaMTX, the Wellness Dashboard provides a comprehensive view of the team's health, stress levels, and workload. Unlike pure productivity metrics, this dashboard aims to prevent professional burnout and encourage a healthy Work-Life Balance.

## Where is the data extracted from?

To ensure data privacy and reliability, the dashboard's metrics **are not magically auto-generated**. The system requires active cooperation from team members:

### 1. Mood, Stress, and Energy (Daily Check-in)
The **Heatmap**, **Stress Trend**, **Energy Trend**, and **Burnout Risk** charts are calculated based on daily user logs (`user_mood_logs`).
*   **What the team must do:** Use the *"Daily Check-in" / "How are you feeling today"* widget on a daily basis. They will need to indicate their overall mood (Excellent, Good, Neutral, Bad, Terrible) and rate their energy level on a scale from 1 to 5.
*   **Impact:** The system cross-references these inputs. Stress is calculated inversely to mood. If a user logs a "Bad" mood for several consecutive days along with a high workload, the *Burnout Risk* indicator will turn red and alert managers.

### 2. Overtime and Work-Life Balance
The **Weekly Overtime** chart and the **Work-Life Balance** radar depend on time tracking or Activity time logs (`time_logs`).
*   **What the team must do:** Clock in and out of their shift, or actively use the task timer.
*   **Impact:** The system compares recorded times with the official working hours configured in each member's profile (e.g., 8:00 AM to 6:00 PM). 
    * If time is tracked outside those hours, it automatically counts as "Overtime".
    * If time is tracked during the weekend (Saturday/Sunday), it will negatively impact the Work-Life Balance index.

### 3. Workload Distribution and Productivity
The **Workload Box Plot** and the **Mood vs. Productivity Correlation** charts are extracted from activity volume.
*   **What the team must do:** Create, assign, and update Activities within their normal workflow.

## Privacy Notes
Managerial dashboards display **aggregated** metrics by default. However, if severe overload risks are detected (active Burnout alerts), managers can view individual profiles to schedule follow-up meetings (1-on-1s) with the affected team member and redistribute the operational load.

---
> **Important:** If no mood check-ins are submitted or the task timer goes unused, the charts on this dashboard will display an *"Insufficient Data"* warning. It is highly recommended to encourage daily check-ins across the team.
