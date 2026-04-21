# Gamification and Vital Energy System

This document details the calculation logic for experience points (XP), resilience, and the management of Vital Energy for members on the platform.

## 1. Fundamental Concepts

### Cognitive Load (Multiplier)
Each task has a **Cognitive Load** (from 1 to 5) assigned. This value acts as the base multiplier for almost all rewards and energy consumption.
- **Load 1:** Routine task / low concentration.
- **Load 5:** Critical task / maximum concentration.

---

## 2. Experience Calculation (XP)

Base XP for completing a task is calculated as follows:
`Base XP = 10 * Multiplier`

### Additional Bonuses:
1. **Backstage Bonus:** If the task is marked as "Backstage" (preparation), an extra **+5 * Multiplier** is added.
2. **Out of Skill Tree:** If the task does not belong to the user's primary specialties, base XP is reduced to **5 * Multiplier**, but is compensated with Resilience points.

### Master Plan Race Bonus:
To incentivize agility in shared tasks or those derived from a Master Plan, bonuses are awarded by order of completion:
- **1st Place:** +15 XP
- **2nd Place:** +10 XP
- **3rd Place:** +5 XP

---

## 3. Resilience Challenge
When a user completes a task that is **outside their skill tree**, the system recognizes it as a "Resilience Challenge":
- **Resilience Points:** `20 * Multiplier`
- **Objective:** To reward adaptability and the effort of stepping out of the technical comfort zone.

---

## 4. Vital Energy Management (Flow)
Energy is a dynamic resource that ranges between 0 and 100. The calculation has been redesigned to avoid suffocating exhaustion and reward "closing the circle".

### The Net Balance:
Instead of being based on real time (which punishes forgotten timers), the system uses Cognitive Load to predict wear:

1. **Effort Drain:** `Cognitive Load * 2` (Energy consumed while working).
2. **Closing Reward:** `+5` fixed points when marked as completed.
3. **High Load Bonus:** If Cognitive Load is > 3, an extra `+2` reward points are added.

**Flow example (Load 5):**
- Drain: `-10` (5 * 2)
- Reward: `+7` (5 + 2)
- **Result:** Net loss of `-3` energy.

---

## 5. Skill Progression (Leveling)
Each task grants XP to the associated specialties (Skills). The leveling system for each skill follows this progression:

| Level | Accumulated XP Required |
| :--- | :--- |
| **Level 1** | 0 XP |
| **Level 2** | 30 XP |
| **Level 3** | 100 XP |
| **Level 4** | 300 XP |
| **Level 5** | 1000 XP |

---

## 6. Sentinel Bonus (Monitoring)
To reward the commitment to team availability, points are awarded for service reporting:
- **Action:** Being the first member to report a service outage that is later verified by other members.
- **Reward:** +20 XP and +5 Vital Energy points.
- **Limitation:** Applies once per hour per service to avoid abuse.
