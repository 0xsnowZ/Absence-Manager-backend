```md
# Absence Management Improvements – Tasks

## Important
- The database schema already exists.
- Only add the required improvements to the existing system.
- Do NOT recreate the whole absence module.

---

# Tasks

## 1. Track absence creator

When a teacher creates an absence:

- automatically save:
  - creator user id
  - creation datetime

Requirements:
- teacher does not choose the status manually
- default status = `non_justifie`

Expected behavior:
- new absence appears directly in red
- status automatically set from backend

---

## 2. Track absence updates

When an absence is modified:

- save:
  - updated_by
  - updated_at

This must work for:
- administration modifications
- justification changes
- status changes

---

## 3. Teacher consultation improvements

When teacher consults absences:

Display:
- absence status
- who created the absence
- creation date/time
- last modification date/time

Example information:
- Created by: Admin
- Created at: 14/05/2026 10:20
- Status: non_justifie

---

## 4. Administration consultation improvements

Administration must be able to:

- consult all absences
- see who created them
- see creation datetime
- see last update datetime
- see current status


---

## 5. Administration status management

Administration can update absence status:

Possible statuses:
- non_justifie
- justifie
- retard
- absence_excusee

When status becomes `justifie`:
- save justification datetime
- save updated_by

---



## 8. Absence colors in frontend

Display colors depending on status:

| Status | Color |
|---|---|
| non_justifie | Red |
| justifie | Green |
| retard | Orange |
| absence_excusee | Blue |

Important:
- justified absences must appear in green

---

## 9. Default absence behavior

When teacher creates absence:
- status automatically = `non_justifie`
- absence cell immediately becomes red

Teacher should NOT manually select the status.

---

## 10. Consultation popup / modal

When clicking an absence:

Display:
- trainee name
- session
- date
- status
- created by
- created at
- updated by
- updated at

---

## 11. Admin edit popup

Administration can:
- change status
- save changes

---

## 12. Permissions

Rules:
- teacher can create absences
- teacher can consult absences
- administration can modify statuses
- teacher cannot justify absences
- teacher cannot edit already justified absences

---

## 13. Backend API updates

Update existing endpoints to support:
- created_by
- updated_by
- status management
- justification upload
- justification note

---

## 14. Frontend improvements

Improve absence UI:
- better colors
- hover tooltip
- status badges
- cleaner consultation modal

Tooltip example:
- Created by Ahmed
- 14/05/2026 09:30
- Status: justifie

---

# Final Goal

The system must allow:
- complete absence tracking
- creator tracking
- modification history
- justification management
- administration control
- visual status colors
- easier absence consultation
```
