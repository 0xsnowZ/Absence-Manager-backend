Migration plan: move session times to time_blocks

Goal
- Map existing `sessions.heure_debut`/`heure_fin` into `time_blocks` and populate `sessions.time_block_id` safely.

Assumptions
- You have already run: `php artisan migrate` to create `time_blocks` and add `time_block_id` column (nullable).
- `TimeBlockSeeder` has been run or will be run to populate the five blocks.
- The existing `sessions` table still contains `heure_debut` and `heure_fin` columns during migration.

Steps (safe, reversible)

1) Seed the time blocks (if not already):

```bash
php artisan db:seed --class="\Database\Seeders\TimeBlockSeeder"
```

2) Inspect distinct times present in `sessions`:

```sql
SELECT DISTINCT heure_debut, heure_fin FROM sessions;
```

3) Map existing times to time_block ids.
You can run SQL UPDATE statements to set `time_block_id` based on matching times. Example:

```sql
-- TB1 08:30 -> 11:00
UPDATE sessions SET time_block_id = (SELECT id FROM time_blocks WHERE code = 'TB1')
WHERE heure_debut = '08:30:00' AND heure_fin = '11:00:00';

-- TB2 11:00 -> 13:15
UPDATE sessions SET time_block_id = (SELECT id FROM time_blocks WHERE code = 'TB2')
WHERE heure_debut = '11:00:00' AND heure_fin = '13:15:00';

-- TB3 13:30 -> 16:00
UPDATE sessions SET time_block_id = (SELECT id FROM time_blocks WHERE code = 'TB3')
WHERE heure_debut = '13:30:00' AND heure_fin = '16:00:00';

-- TB4 16:00 -> 18:30
UPDATE sessions SET time_block_id = (SELECT id FROM time_blocks WHERE code = 'TB4')
WHERE heure_debut = '16:00:00' AND heure_fin = '18:30:00';

-- TB5 19:00 -> 21:00
UPDATE sessions SET time_block_id = (SELECT id FROM time_blocks WHERE code = 'TB5')
WHERE heure_debut = '19:00:00' AND heure_fin = '21:00:00';
```

4) Identify sessions that didn't match any block and decide manually:

```sql
SELECT id, date_session, heure_debut, heure_fin FROM sessions WHERE time_block_id IS NULL;
```

For any unmatched rows, either set the closest block manually or create a new `time_block` entry if needed.

5) Backfill application usage: update any code that reads `heure_debut`/`heure_fin` to prefer `time_block`.

6) Optional: After sufficient verification (no nulls, app uses time_block), remove old columns in a migration:

```php
Schema::table('sessions', function (Blueprint $table) {
    $table->dropColumn(['heure_debut', 'heure_fin']);
});
```

Prefer to keep these columns for a short validation window before dropping them.

Rollback
- To undo mapping, you can set `time_block_id` back to NULL.

```sql
UPDATE sessions SET time_block_id = NULL WHERE time_block_id IS NOT NULL;
```

Notes
- Do not drop `heure_debut`/`heure_fin` until `time_block_id` usage is fully enabled and tested.
- If you have sessions spanning different start/end times not matching fixed blocks, consider creating extra `time_blocks` or keeping the raw times for flexibility.

Example API JSON for creating a session (preferred):

{
  "programme_id": 1,
  "date_session": "2025-10-05",
  "time_block_id": 2,
  "lieu": "Salle 101",
  "created_by": "M. JEAN"
}

Example API JSON (fallback using raw times):

{
  "programme_id": 1,
  "date_session": "2025-10-05",
  "heure_debut": "08:30",
  "heure_fin": "11:00",
  "lieu": "Salle 101",
  "created_by": "M. JEAN"
}
