```md
# Tasks & Improvements Todo List

## Navigation
- [ ] Add a "Retour / Back" button in every page and form step.
- [ ] Keep navigation history working correctly between pages.
- [ ] Redirect user to the previous page after save/update when needed.

---


---

## Teacher Assignment Improvements
### Assign Secteur + Filière
Current problem:
Teacher can teach multiple filières in multiple secteurs.

### Required improvement
- [ ] First select `Secteur`.
- [ ] After selecting secteur, load only filières related to this secteur.
- [ ] Use multi-select for filières.
- [ ] Add button/icon: "Ajouter une autre filière".
- [ ] Allow adding another secteur + filière combination dynamically.
- [ ] Prevent duplicate filière assignments.
- [ ] Save all selected assignments correctly in database.

### Example UI Flow
1. Select Secteur
2. Load Filières dropdown dynamically
3. Select one or multiple Filières
4. Click "+" to add another Secteur/Filière block
5. Repeat as needed

---

## Stagiaire Edit
### Admin Edit Form
- [ ] When admin edits a stagiaire, load old data automatically in form fields.
- [ ] Pre-fill:
  - nom
  - prénom
  - etc.
- [ ] Keep old values if no new value selected.

---

## Confirmation Modals / Prompts
### Required for ALL actions
Before executing actions, show confirmation popup/modal.

### Actions requiring confirmation
- [ ] Delete
- [ ] Edit
- [ ] Add
- [ ] Absence
- [ ] Update
- [ ] Remove assignment
- [ ] Archive
- [ ] Restore

### Example Messages
- "Are you sure you want to delete this item?"
- "Confirm absence for this stagiaire?"
- "Save changes?"

---

## UX Improvements
- [ ] Add loading spinner during requests.
- [ ] Show success toast notifications.
- [ ] Show error alerts with clear messages.
- [ ] Disable submit button while request is processing.
- [ ] Improve responsive design for mobile/tablet.

---

## Security
- [ ] Protect routes by role:
  - Admin
  - Teacher
  - Student
- [ ] Prevent unauthorized access from frontend and backend.
- [ ] Validate all requests on backend.

---

## Backend Checks
- [ ] Validate teacher assignments before saving.
- [ ] Ensure filière belongs to selected secteur.
- [ ] Prevent duplicate records.
- [ ] Add soft delete if needed.

---

## Testing
- [ ] Test all CRUD operations.
- [ ] Test role permissions.
- [ ] Test dynamic filière loading.
- [ ] Test edit forms with existing data.
- [ ] Test confirmation prompts.
- [ ] Test responsive UI.

---

## Priority Order
### High Priority
- [ ] Role permissions
- [ ] Edit stagiaire load old data
- [ ] Confirmation prompts
- [ ] Dynamic secteur/filière assignment

### Medium Priority
- [ ] UX improvements
- [ ] Responsive design

```
