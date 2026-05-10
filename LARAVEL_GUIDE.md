# 📚 Laravel Implementation Guide

## Système de Gestion des Stagiaires & Suivi d'Absences

---

## 📑 Table des Matières

1. [Installation](#installation)
2. [Structure du Projet](#structure-du-projet)
3. [Migrations](#migrations)
4. [Models (Eloquent)](#models-eloquent)
5. [Controllers](#controllers)
6. [Routes API](#routes-api)
7. [Exemples d'Utilisation](#exemples-dutilisation)
8. [Guide d'Import Excel](#guide-dimport-excel)

---

## Installation

### 1. Créer un nouveau projet Laravel

```bash
composer create-project laravel/laravel stagiaires-app
cd stagiaires-app
```

### 2. Copier les fichiers

**Migrations :** Copier les fichiers de `/laravel_migrations/` vers `database/migrations/`

```bash
cp laravel_migrations/*.php database/migrations/
```

**Models :** Copier les fichiers de `/laravel_models/` vers `app/Models/`

```bash
mkdir -p app/Models
cp laravel_models/*.php app/Models/
```

**Controllers :** Copier les fichiers de `/laravel_controllers/` vers `app/Http/Controllers/Api/`

```bash
mkdir -p app/Http/Controllers/Api
cp laravel_controllers/*.php app/Http/Controllers/Api/
```

**Seeders :** Copier les fichiers de `/laravel_seeders/` vers `database/seeders/`

```bash
cp laravel_seeders/*.php database/seeders/
```

**Routes :** Remplacer `routes/api.php` par le fichier `/laravel_routes/api.php`

```bash
cp laravel_routes/api.php routes/api.php
```

### 3. Configurer la Base de Données

```bash
# .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stagiaires_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Exécuter les Migrations

```bash
php artisan migrate
```

### 5. Remplir les Données Initiales

```bash
php artisan db:seed --class=TypeAbsenceSeeder
```

### 6. Démarrer l'Application

```bash
php artisan serve
# L'API sera disponible à http://localhost:8000/api
```

---

## Structure du Projet

```
stagiaires-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── StagiaireController.php
│   │   │       ├── ProgrammeController.php
│   │   │       ├── SessionController.php
│   │   │       └── AttendanceController.php
│   │   └── Requests/              (optionnel : form requests)
│   └── Models/
│       ├── Secteur.php
│       ├── Filiere.php
│       ├── NiveauFormation.php
│       ├── Programme.php
│       ├── Stagiaire.php
│       ├── Inscription.php
│       ├── TypeAbsence.php
│       ├── Session.php
│       └── Attendance.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000100_create_secteurs_table.php
│   │   ├── 2025_01_01_000200_create_niveau_formations_table.php
│   │   ├── 2025_01_01_000300_create_filieres_table.php
│   │   ├── 2025_01_01_000400_create_programmes_table.php
│   │   ├── 2025_01_01_000500_create_stagiaires_table.php
│   │   ├── 2025_01_01_000600_create_inscriptions_table.php
│   │   ├── 2025_01_01_000700_create_type_absences_table.php
│   │   ├── 2025_01_01_000800_create_sessions_table.php
│   │   └── 2025_01_01_000900_create_attendances_table.php
│   └── seeders/
│       └── TypeAbsenceSeeder.php
└── routes/
    └── api.php
```

---

## Migrations

Les migrations créent la structure de la base de données dans l'ordre correct (respectant les dépendances).

### Ordre d'Exécution

1. `secteurs` (no deps)
2. `niveau_formations` (no deps)
3. `filieres` (dépend de secteurs)
4. `programmes` (dépend de filieres + niveau_formations)
5. `stagiaires` (no deps)
6. `inscriptions` (dépend de stagiaires + programmes)
7. `type_absences` (no deps)
8. `sessions` (dépend de programmes)
9. `attendances` (dépend de sessions + stagiaires + type_absences)

**Exécuter les migrations :**

```bash
php artisan migrate                    # Exécuter toutes les migrations
php artisan migrate:refresh            # Reset et re-migrer
php artisan migrate:rollback           # Annuler la dernière batch
php artisan migrate:reset              # Reset toutes
```

---

## Models (Eloquent)

Tous les models incluent :
- Les relationships (hasMany, belongsTo, belongsToMany)
- Les fillable fields
- Les casts de types
- Les scopes utiles (query helpers)

### Exemple : Relationships

```php
// Un Secteur a plusieurs Filieres
$secteur->filieres();

// Une Filiere appartient à un Secteur
$filiere->secteur();

// Un Programme a plusieurs Sessions
$programme->sessions();

// Une Session a plusieurs Attendances
$session->attendances();

// Un Stagiaire a plusieurs Inscriptions
$stagiaire->inscriptions();

// Un Stagiaire est inscrit dans plusieurs Programmes
$stagiaire->programmes();
```

### Exemple : Scopes

```php
// Obtenir tous les stagiaires d'un programme
Stagiaire::inProgramme($programmeId)->get();

// Obtenir les stagiaires d'une saison
Stagiaire::inSaison(2025)->get();

// Obtenir les sessions futures
Session::future()->get();

// Obtenir les absences non justifiées
Attendance::unjustified()->get();
```

---

## Controllers

### StagiaireController

```
GET    /api/stagiaires                    → Tous les stagiaires
POST   /api/stagiaires                    → Créer un stagiaire
GET    /api/stagiaires/{id}               → Détails d'un stagiaire
PUT    /api/stagiaires/{id}               → Modifier un stagiaire
DELETE /api/stagiaires/{id}               → Supprimer un stagiaire

GET    /api/stagiaires/{id}/programmes    → Programmes du stagiaire
GET    /api/stagiaires/{id}/attendance-stats → Statistiques de présence

POST   /api/stagiaires/upsert-from-excel  → UPSERT (pour import Excel)
```

### ProgrammeController

```
GET    /api/programmes                    → Tous les programmes
POST   /api/programmes                    → Créer un programme
GET    /api/programmes/{id}               → Détails d'un programme
PUT    /api/programmes/{id}               → Modifier un programme
DELETE /api/programmes/{id}               → Supprimer un programme

GET    /api/programmes/by-code/{code}     → Trouver par code_diplome
GET    /api/programmes/by-libelle/{libelle} → Trouver par LibelleLong

GET    /api/programmes/{id}/stagiaires    → Stagiaires du programme
GET    /api/programmes/{id}/sessions      → Sessions du programme
GET    /api/programmes/{id}/attendance-summary → Résumé des absences
```

### SessionController

```
GET    /api/sessions                      → Toutes les sessions
POST   /api/sessions                      → Créer une session
GET    /api/sessions/{id}                 → Détails d'une session
PUT    /api/sessions/{id}                 → Modifier une session
DELETE /api/sessions/{id}                 → Supprimer une session

GET    /api/sessions/{id}/summary         → Résumé de l'appel
GET    /api/sessions/{id}/roster          → Feuille d'appel (tous les stagiaires)

POST   /api/programmes/{id}/sessions/create-multiple → Créer plusieurs sessions
GET    /api/sessions/programme/{code}/upcoming → Prochaines sessions
```

### AttendanceController

```
GET    /api/attendances                   → Tous les enregistrements
POST   /api/attendances                   → Enregistrer une présence
GET    /api/attendances/{id}              → Détails d'un enregistrement
PUT    /api/attendances/{id}              → Modifier une présence
DELETE /api/attendances/{id}              → Supprimer une présence

POST   /api/attendances/bulk              → Enregistrer l'appel en masse

GET    /api/attendances/unjustified/list  → Absences non justifiées (alertes)

GET    /api/attendances/stats/by-stagiaire → Stats par stagiaire
GET    /api/attendances/stats/by-programme → Stats par programme
```

---

## Routes API

Les routes sont organisées par ressource principale. Importer le fichier `/laravel_routes/api.php` dans `routes/api.php`.

**Base URL :** `http://localhost:8000/api`

---

## Exemples d'Utilisation

### 1. Créer un Stagiaire

```bash
curl -X POST http://localhost:8000/api/stagiaires \
  -H "Content-Type: application/json" \
  -d '{
    "matricule": 12345,
    "nom": "DUBOIS",
    "prenom": "Jean",
    "sexe": "H",
    "date_naissance": "2005-03-15",
    "cin": "AB123456",
    "telephone": "0612345678"
  }'
```

**Réponse :**
```json
{
  "success": true,
  "message": "Stagiaire créé avec succès",
  "data": {
    "id": 1,
    "matricule": 12345,
    "nom": "DUBOIS",
    "prenom": "Jean",
    "sexe": "H",
    "date_naissance": "2005-03-15",
    "cin": "AB123456",
    "telephone": "0612345678",
    "created_at": "2025-05-10T12:00:00Z",
    "updated_at": "2025-05-10T12:00:00Z"
  }
}
```

### 2. Créer une Session

```bash
curl -X POST http://localhost:8000/api/sessions \
  -H "Content-Type: application/json" \
  -d '{
    "programme_id": 1,
    "date_session": "2025-10-05",
    "heure_debut": "09:00",
    "heure_fin": "11:00",
    "lieu": "Salle 101",
    "created_by": "M. JEAN"
  }'
```

### 3. Enregistrer la Présence d'un Stagiaire

```bash
curl -X POST http://localhost:8000/api/attendances \
  -H "Content-Type: application/json" \
  -d '{
    "session_id": 1,
    "stagiaire_id": 1,
    "type_absence_id": 1,
    "recorded_by": "M. JEAN"
  }'
```

**Type Absence IDs :**
- `1` = PRESENT
- `2` = ABSENT
- `3` = EXCUSED
- `4` = SICK
- `5` = PERMIT
- `6` = LATE
- `7` = PARTIAL

### 4. Enregistrer l'Appel en Masse

```bash
curl -X POST http://localhost:8000/api/attendances/bulk \
  -H "Content-Type: application/json" \
  -d '{
    "session_id": 1,
    "recorded_by": "M. JEAN",
    "attendances": [
      {
        "stagiaire_id": 1,
        "type_absence_id": 1
      },
      {
        "stagiaire_id": 2,
        "type_absence_id": 2,
        "justification": "Malade"
      },
      {
        "stagiaire_id": 3,
        "type_absence_id": 6,
        "justification": "Retard de 30 min"
      }
    ]
  }'
```

### 5. Obtenir le Roster d'une Session

```bash
curl http://localhost:8000/api/sessions/1/roster
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "session": {
      "id": 1,
      "date": "2025-10-05",
      "heure_debut": "09:00:00",
      "heure_fin": "11:00:00",
      "programme": "EB101"
    },
    "total_enrolled": 30,
    "total_marked": 30,
    "roster": [
      {
        "stagiaire_id": 1,
        "matricule": 12345,
        "nom": "DUBOIS",
        "prenom": "Jean",
        "telephone": "0612345678",
        "attendance_id": 1,
        "absence_code": "PRESENT",
        "absence_libelle": "Présent",
        "justification": null,
        "recorded_by": "M. JEAN",
        "recorded_at": "2025-10-05T09:15:00Z"
      },
      ...
    ]
  }
}
```

### 6. Obtenir les Statistiques d'un Stagiaire

```bash
curl "http://localhost:8000/api/stagiaires/1/attendance-stats?saison=2025&programme_id=1"
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "total_sessions": 20,
    "presents": 18,
    "absents": 2,
    "justified": 0,
    "attendance_rate": 90.0
  }
}
```

### 7. Obtenir les Absences Non Justifiées

```bash
curl "http://localhost:8000/api/attendances/unjustified/list?days_back=7"
```

**Réponse :**
```json
{
  "success": true,
  "count": 3,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 45,
        "session": {
          "id": 12,
          "date_session": "2025-10-04",
          "programme": {
            "code_diplome": "EB101"
          }
        },
        "stagiaire": {
          "id": 5,
          "matricule": 12348,
          "nom": "MARTIN",
          "prenom": "Paul",
          "telephone": "0698765432"
        },
        "typeAbsence": {
          "code": "ABSENT",
          "libelle": "Absent non justifié"
        },
        "justification": null,
        "recorded_by": "Mlle SOPHIE",
        "recorded_at": "2025-10-04T11:30:00Z"
      }
    ]
  }
}
```

---

## Guide d'Import Excel

### Étape 1 : Préparer les Données

L'Excel doit avoir le format original du fichier `lister_*.xls` avec les colonnes :
- `id_inscriptionsessionprogramme`
- `MatriculeEtudiant`
- `Nom`, `Prenom`
- `Sexe`
- `LibelleLong`
- `CodeDiplome`
- `DateNaissance`, `DateInscription`, `DateDossierComplet`
- `CIN`, `NTelelephone`

### Étape 2 : Créer un Command Laravel

```php
// app/Console/Commands/ImportStagiairesCommand.php

namespace App\Console\Commands;

use App\Models\{Stagiaire, Inscription, Programme, Filiere, NiveauFormation, Secteur};
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportStagiairesCommand extends Command
{
    protected $signature = 'import:stagiaires {file}';
    protected $description = 'Importer les stagiaires depuis un fichier Excel';

    public function handle()
    {
        $filepath = $this->argument('file');
        
        if (!file_exists($filepath)) {
            $this->error("Fichier non trouvé : $filepath");
            return;
        }

        $data = Excel::toArray([], $filepath);
        $rows = $data[0]; // Première feuille

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                // Parser LibelleLong
                $parsed = $this->parseLibelleLong($row['libellelong'] ?? '');

                // Créer/mettre à jour le stagiaire (UPSERT)
                $stagiaire = Stagiaire::updateOrCreate(
                    ['matricule' => $row['matriculeetudiant']],
                    [
                        'nom' => strtoupper($row['nom']),
                        'prenom' => ucfirst($row['prenom']),
                        'sexe' => strtoupper($row['sexe'])[0],
                        'date_naissance' => $this->parseDate($row['datenaissance'] ?? null),
                        'cin' => $row['cin'] ?? null,
                        'telephone' => $row['ntelephone'] ?? null,
                    ]
                );

                // Créer l'inscription
                $programme = $this->getOrCreateProgramme($parsed, $row['codediplome']);

                Inscription::firstOrCreate(
                    [
                        'stagiaire_id' => $stagiaire->id,
                        'programme_id' => $programme->id,
                    ],
                    [
                        'date_inscription' => $this->parseDate($row['dateinscription'] ?? null),
                        'date_dossier_complet' => $this->parseDate($row['datedossiercomplet'] ?? null),
                    ]
                );

                $created++;
            } catch (\Exception $e) {
                $this->error("Erreur ligne : " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("Import complété : $created insérés/mis à jour, $errors erreurs");
    }

    protected function parseLibelleLong($libelle)
    {
        preg_match(
            '/^([^_]+)_([^_]+)_([^_]+)_(?:RCDS_)?(\dA)-(.+?)\s*\((\d+A)\)-(\d{4})$/',
            $libelle,
            $matches
        );

        return [
            'secteur' => $matches[1] ?? '',
            'filiere' => $matches[2] ?? '',
            'niveau' => $matches[3] ?? '',
            'annee' => $matches[4] ?? '',
            'nom_filiere' => trim($matches[5] ?? ''),
            'saison' => (int) ($matches[7] ?? date('Y')),
            'is_cds' => strpos($libelle, 'RCDS') !== false,
        ];
    }

    protected function getOrCreateProgramme($parsed, $codeDiplome)
    {
        // Créer secteur
        $secteur = Secteur::firstOrCreate(['code' => $parsed['secteur']]);

        // Créer filière
        $filiere = Filiere::firstOrCreate(
            ['code' => $parsed['filiere']],
            ['secteur_id' => $secteur->id, 'nom' => $parsed['nom_filiere']]
        );

        // Créer niveau
        $niveau = NiveauFormation::firstOrCreate(['code' => $parsed['niveau']]);

        // Créer/trouver programme
        return Programme::firstOrCreate(
            ['code_diplome' => $codeDiplome],
            [
                'filiere_id' => $filiere->id,
                'niveau_id' => $niveau->id,
                'annee' => $parsed['annee'],
                'saison' => $parsed['saison'],
                'is_cds' => $parsed['is_cds'],
            ]
        );
    }

    protected function parseDate($value)
    {
        if (!$value) return null;
        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
```

### Étape 3 : Installer Maatwebsite Excel (optionnel)

```bash
composer require maatwebsite/excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

### Étape 4 : Exécuter l'Import

```bash
php artisan import:stagiaires lister_2025.xls
```

---

## API Response Format

Toutes les réponses suivent ce format :

```json
{
  "success": true|false,
  "message": "Description du résultat",
  "data": {...}
}
```

**Codes HTTP :**
- `200` OK
- `201` Created
- `400` Bad Request
- `404` Not Found
- `422` Unprocessable Entity (validation error)
- `500` Server Error

---

## Authentification (Optionnel)

Pour ajouter l'authentification (Laravel Sanctum) :

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Dans routes/api.php, envelopper les routes :
Route::middleware('auth:sanctum')->group(function () {
    // ... vos routes
});
```

---

## Performance & Optimisations

### Eager Loading

```php
// Mauvais (N+1 queries)
$stagiaires = Stagiaire::all();
foreach ($stagiaires as $s) {
    $s->programmes;
}

// Bon
$stagiaires = Stagiaire::with('programmes')->get();
```

### Indexing

Toutes les migrations incluent les indexes appropriés sur les colonnes de recherche et les clés étrangères.

### Pagination

```php
// Paginer automatiquement
$stagiaires = Stagiaire::paginate(15);

// Utiliser dans les requêtes
?per_page=30
```

---

## Troubleshooting

### Erreur de migration

```bash
php artisan migrate:fresh        # Reset complet
php artisan migrate --step       # Voir chaque étape
```

### Model not found

Vérifier que les models sont dans `app/Models/` et que le namespace est correct.

### CORS errors (frontend)

Dans `config/cors.php` :

```php
'allowed_origins' => ['http://localhost:3000', 'http://localhost:8080'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
```

---

## Conclusion

Cette implémentation Laravel fournit une API REST complète pour gérer les stagiaires, les programmes, les sessions et les absences. Elle est prête pour une application web ou mobile frontend.

Consultez la documentation DOCUMENTATION.md pour plus de contexte sur le schéma et les cas d'usage.
