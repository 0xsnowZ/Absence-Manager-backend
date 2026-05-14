# Database Schema — Trainee Attendance System

This document lists the tables, columns, primary keys, foreign keys, and the main relationships in the current Laravel app.

## Table Map

Some Eloquent models use a different table name than the model class:

- `Programme` model -> `classes` table
- `Session` model -> `seances` table
- `TimeBlock` model -> `time_blocks` table
- `User` model -> `users` table

## Tables

### `users`
- `id`: BIGINT UNSIGNED PK, auto-increment
- `name`: string
- `email`: string, unique
- `email_verified_at`: timestamp, nullable
- `password`: string
- `remember_token`: string, nullable
- `created_at`, `updated_at`

Extra column added by migration:
- `role`: enum(`admin`, `prof`), default `admin`

Relations:
- `users` belongsToMany `classes` via `user_classes`

---

### `password_reset_tokens`
- `email`: string, primary key
- `token`: string
- `created_at`: timestamp, nullable

---

### `sessions`  
Laravel auth/session storage table, not the attendance session table.
- `id`: string, primary key
- `user_id`: BIGINT UNSIGNED, nullable, indexed
- `ip_address`: string(45), nullable
- `user_agent`: text, nullable
- `payload`: longText
- `last_activity`: integer, indexed

---

### `secteurs`
- `id`: BIGINT UNSIGNED PK
- `code`: string, unique
- `nom`: string, nullable
- `created_at`, `updated_at`

Relations:
- `secteurs` hasMany `filieres`

---

### `niveau_formations`
- `id`: BIGINT UNSIGNED PK
- `code`: string, unique
- `nom`: string, nullable
- `created_at`, `updated_at`

Relations:
- `niveau_formations` hasMany `classes`

---

### `filieres`
- `id`: BIGINT UNSIGNED PK
- `code`: string, unique
- `nom`: string
- `secteur_id`: BIGINT UNSIGNED FK -> `secteurs(id)`
- `created_at`, `updated_at`

Relations:
- `filieres` belongsTo `secteurs`
- `filieres` hasMany `classes`

---

### `classes`
This is the table used by the `Programme` model.
- `id`: BIGINT UNSIGNED PK
- `code_diplome`: string, unique
- `libelle_long`: text, nullable
- `filiere_id`: BIGINT UNSIGNED FK -> `filieres(id)`
- `niveau_id`: BIGINT UNSIGNED FK -> `niveau_formations(id)`
- `annee`: integer, nullable
- `saison`: integer, nullable
- `is_cds`: boolean, default `false`
- `created_at`, `updated_at`

Relations:
- `classes` belongsTo `filieres`
- `classes` belongsTo `niveau_formations`
- `classes` hasMany `seances`
- `classes` hasMany `inscriptions`
- `classes` belongsToMany `users` via `user_classes`

---

### `stagiaires`
- `id`: BIGINT UNSIGNED PK
- `matricule`: BIGINT UNSIGNED, unique
- `nom`: string
- `prenom`: string
- `sexe`: char(1), nullable
- `date_naissance`: date, nullable
- `lieu_naissance`: string, nullable
- `cin`: string, nullable, unique
- `telephone`: string, nullable
- `created_at`, `updated_at`

Relations:
- `stagiaires` hasMany `inscriptions`
- `stagiaires` belongsToMany `classes` via `inscriptions`
- `stagiaires` hasMany `attendances`

---

### `inscriptions`
- `id`: BIGINT UNSIGNED PK
- `stagiaire_id`: BIGINT UNSIGNED FK -> `stagiaires(id)`
- `programme_id`: BIGINT UNSIGNED FK -> `classes(id)`
- `date_inscription`: date, nullable
- `date_dossier_complet`: date, nullable
- `created_at`, `updated_at`
- unique(`stagiaire_id`, `programme_id`)

Relations:
- pivot table between `stagiaires` and `classes`

---

### `type_absences`
- `id`: BIGINT UNSIGNED PK
- `code`: string, unique
- `libelle`: string
- `created_at`, `updated_at`

Relations:
- `type_absences` hasMany `attendances`

---

### `time_blocks`
- `id`: BIGINT UNSIGNED PK
- `code`: string, unique
- `label`: string
- `heure_debut`: time
- `heure_fin`: time
- `created_at`, `updated_at`

Relations:
- `time_blocks` hasMany `seances`

Default seeded blocks:
- `TB1` - 08:30:00 to 11:00:00
- `TB2` - 11:00:00 to 13:15:00
- `TB3` - 13:30:00 to 16:00:00
- `TB4` - 16:00:00 to 18:30:00
- `TB5` - 19:00:00 to 21:00:00

---

### `seances`
This is the attendance/session table used by the `Session` model.
- `id`: BIGINT UNSIGNED PK
- `classe_id`: BIGINT UNSIGNED FK -> `classes(id)`
- `date_session`: date
- `heure_debut`: time, nullable
- `heure_fin`: time, nullable
- `time_block_id`: BIGINT UNSIGNED FK, nullable -> `time_blocks(id)`
- `lieu`: string, nullable
- `created_by`: string, nullable
- `created_at`, `updated_at`

Relations:
- `seances` belongsTo `classes`
- `seances` belongsTo `time_blocks` (optional)
- `seances` hasMany `attendances`

---

### `attendances`
- `id`: BIGINT UNSIGNED PK
- `session_id`: BIGINT UNSIGNED FK -> `seances(id)`
- `stagiaire_id`: BIGINT UNSIGNED FK -> `stagiaires(id)`
- `type_absence_id`: BIGINT UNSIGNED FK -> `type_absences(id)`
- `justification`: text, nullable
- `recorded_by`: string, nullable
- `recorded_at`: datetime, nullable
- `created_at`, `updated_at`
- unique(`session_id`, `stagiaire_id`)

Relations:
- `attendances` belongsTo `seances`
- `attendances` belongsTo `stagiaires`
- `attendances` belongsTo `type_absences`

---

### `personal_access_tokens`
Laravel Sanctum token table.
- `id`: BIGINT UNSIGNED PK
- `tokenable_type`: string
- `tokenable_id`: BIGINT UNSIGNED
- `name`: text
- `token`: string(64), unique
- `abilities`: text, nullable
- `last_used_at`: timestamp, nullable
- `expires_at`: timestamp, nullable, indexed
- `created_at`, `updated_at`

---

### `user_classes`
Pivot table between users and classes.
- `id`: BIGINT UNSIGNED PK
- `user_id`: BIGINT UNSIGNED FK -> `users(id)`
- `classe_id`: BIGINT UNSIGNED FK -> `classes(id)`
- `created_at`, `updated_at`
- unique(`user_id`, `classe_id`)

Relations:
- `user_classes` belongsTo `users`
- `user_classes` belongsTo `classes`

## Import order

If you are importing seed or Excel data, use this order:

1. `secteurs`
2. `niveau_formations`
3. `filieres`
4. `classes`
5. `stagiaires`
6. `inscriptions`
7. `type_absences`
8. `time_blocks`
9. `seances`
10. `attendances`
11. `users` / `user_classes` if needed

## Notes

- Use `classes` in code when working with the `Programme` model.
- Use `seances` in code when working with the `Session` model.
- Keep `heure_debut` and `heure_fin` in `seances` until all sessions are fully mapped to `time_blocks`.
- If you need a separate table map for Excel import, I can generate a CSV/Markdown mapping section next.