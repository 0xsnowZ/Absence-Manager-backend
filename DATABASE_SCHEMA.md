# Database Schema — Trainee Attendance System

This document describes the database tables, columns, primary keys, foreign keys and relationships. Use this as the schema reference when preparing Excel import files.

---

## Tables

### `secteurs`
- id: BIGINT UNSIGNED PK (auto-increment)
- code: string (unique)
- nom: string (nullable)
- created_at, updated_at

Relations:
- `secteurs` hasMany `filieres` (filieres.secteur_id → secteurs.id)

---

### `niveau_formations`
- id: BIGINT UNSIGNED PK
- code: string (unique)
- nom: string (nullable)
- created_at, updated_at

Relations:
- `niveau_formations` hasMany `programmes` (programmes.niveau_id → niveau_formations.id)

---

### `filieres`
- id: BIGINT UNSIGNED PK
- code: string (unique)
- nom: string
- secteur_id: BIGINT UNSIGNED FK → `secteurs(id)`
- created_at, updated_at

Relations:
- `filieres` belongsTo `secteurs`
- `filieres` hasMany `programmes`

---

### `programmes`
- id: BIGINT UNSIGNED PK
- code_diplome: string (unique)
- libelle_long: text (nullable)
- filiere_id: BIGINT UNSIGNED FK → `filieres(id)`
- niveau_id: BIGINT UNSIGNED FK → `niveau_formations(id)`
- annee: integer (nullable)
- saison: integer (nullable)
- is_cds: boolean (default false)
- created_at, updated_at

Relations:
- `programmes` belongsTo `filieres` and `niveau_formations`
- `programmes` hasMany `sessions`
- `programmes` hasMany `inscriptions`
- `programmes` belongsToMany `stagiaires` via `inscriptions`

---

### `stagiaires`
- id: BIGINT UNSIGNED PK
- matricule: unsigned big integer (unique)
- nom: string
- prenom: string
- sexe: char(1) (nullable)
- date_naissance: date (nullable)
- cin: string (nullable, unique)
- telephone: string (nullable)
- created_at, updated_at

Relations:
- `stagiaires` hasMany `inscriptions`
- `stagiaires` belongsToMany `programmes` via `inscriptions`
- `stagiaires` hasMany `attendances`

---

### `inscriptions`
- id: BIGINT UNSIGNED PK
- stagiaire_id: BIGINT UNSIGNED FK → `stagiaires(id)`
- programme_id: BIGINT UNSIGNED FK → `programmes(id)`
- date_inscription: date (nullable)
- date_dossier_complet: date (nullable)
- created_at, updated_at
- UNIQUE(stagiaire_id, programme_id)

Relations:
- pivot between `stagiaires` and `programmes`

---

### `type_absences`
- id: BIGINT UNSIGNED PK
- code: string (unique) — e.g. PRESENT, ABSENT, EXCUSED, SICK, PERMIT, LATE, PARTIAL
- libelle: string
- created_at, updated_at

Relations:
- `type_absences` hasMany `attendances`

---

### `sessions`
- id: BIGINT UNSIGNED PK
- programme_id: BIGINT UNSIGNED FK → `programmes(id)`
- date_session: date
- heure_debut: time (nullable) — kept temporarily for migration safety
- heure_fin: time (nullable) — kept temporarily for migration safety
- time_block_id: BIGINT UNSIGNED FK (nullable) → `time_blocks(id)`
- lieu: string (nullable)
- created_by: string (nullable)
- created_at, updated_at

Relations:
- `sessions` belongsTo `programmes`
- `sessions` belongsTo `time_blocks` (optional)
- `sessions` hasMany `attendances`

Notes:
- New preferred workflow: set `time_block_id` to a fixed block and remove `heure_debut`/`heure_fin` after validation.

---

### `time_blocks`
- id: BIGINT UNSIGNED PK
- code: string (unique) — e.g. `TB1`, `TB2`, ...
- label: string — human readable (e.g. `08:30 → 11:00`)
- heure_debut: time
- heure_fin: time
- created_at, updated_at

Seeded blocks (default):
1. TB1 — 08:30:00 → 11:00:00
2. TB2 — 11:00:00 → 13:15:00
3. TB3 — 13:30:00 → 16:00:00
4. TB4 — 16:00:00 → 18:30:00
5. TB5 — 19:00:00 → 21:00:00 (CDS)

Relations:
- `time_blocks` hasMany `sessions`

---

### `attendances`
- id: BIGINT UNSIGNED PK
- session_id: (unsigned integer or unsigned big integer) FK → `sessions(id)`
- stagiaire_id: (unsigned integer or unsigned big integer) FK → `stagiaires(id)`
- type_absence_id: (unsigned integer or unsigned big integer) FK → `type_absences(id)`
- justification: text (nullable)
- recorded_by: string (nullable)
- recorded_at: datetime (nullable)
- created_at, updated_at
- UNIQUE(session_id, stagiaire_id)

Relations:
- `attendances` belongsTo `sessions`, `stagiaires`, `type_absences`

---

## Excel import mapping guidance

- Prepare one or more Excel sheets mapped to destination tables.
- Key sheet suggestions:
  - `stagiaires` sheet: columns -> `matricule`, `nom`, `prenom`, `sexe`, `date_naissance`, `cin`, `telephone`
  - `programmes` sheet: `code_diplome`, `libelle_long`, `filiere_code`, `niveau_code`, `annee`, `saison`, `is_cds`
  - `inscriptions` sheet: `matricule`, `code_diplome` (use matricule & programme to resolve FK)
  - `sessions` sheet: `code_diplome`, `date_session`, `time_block_code` (preferred) OR `heure_debut` + `heure_fin` (fallback), `lieu`, `created_by`
  - `attendances` sheet: `session_identifier` (id or programme+date+block), `matricule`, `type_absence_code`, `justification`, `recorded_by`

Mapping tips:
- Use unique natural keys to resolve FKs (e.g., `matricule` for `stagiaires`, `code_diplome` for `programmes`, `code` for `time_blocks`).
- When importing `sessions`, prefer `time_block_code` (map to `time_blocks.id`) instead of raw times.
- Provide a small lookup sheet (`lookups`) with `time_blocks` codes if needed.

---

## Importing steps (recommended)
1. Ensure `time_blocks` table is seeded:

```bash
php artisan db:seed --class="\\Database\\Seeders\\TimeBlockSeeder"
```

2. Import `secteurs`, `niveau_formations`, `filieres` (in this order).
3. Import `programmes` (link to `filieres` + `niveau_formations`).
4. Import `stagiaires`.
5. Import `inscriptions` (link `stagiaire` → `programme`).
6. Import `sessions` using `time_block_code` mapped to `time_blocks.id`.
7. Import `attendances` (use `session` id or resolved session key).

For complex imports use a command (example) `php artisan import:stagiaires file.xlsx` and follow the project command template.

---

## Notes & best practices
- Keep raw `heure_debut`/`heure_fin` until you validate all sessions mapped to `time_blocks`, then drop them in a safe migration.
- Use transactions when importing bulk data and log unmatched rows for manual review.
- Prefer `updateOrCreate` for idempotent imports to avoid duplicates.

---

If you provide the Excel file(s) (or headings), I can produce a concrete mapping file or a Laravel import command skeleton to consume those sheets directly and perform FK resolution.