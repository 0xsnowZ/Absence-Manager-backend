<div align="center">

# 🎓 Absence Manager — Backend API

**REST API powering the ISTA Inezgane Absence Management Platform**

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![SQLite](https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://sqlite.org)
[![Railway](https://img.shields.io/badge/Deployed_on-Railway-0B0D0E?style=for-the-badge&logo=railway&logoColor=white)](https://railway.app)
[![License](https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge)](LICENSE)

[**Live API**](https://web-production-09c0f.up.railway.app/api) · [**Frontend Repo**](https://github.com/0xsnowZ/Absence-Manager-frontend) · [**Report Bug**](https://github.com/0xsnowZ/Absence-Manager-backend/issues)

</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [API Reference](#-api-reference)
- [Getting Started](#-getting-started)
- [Environment Variables](#-environment-variables)
- [Database Schema](#-database-schema)
- [Deployment](#-deployment)
- [Roadmap](#-roadmap)

---

## 🌟 Overview

The **Absence Manager Backend** is a RESTful API built with Laravel 11 for **ISTA Inezgane** (Institut Spécialisé de Technologie Appliquée), part of the OFPPT network. It handles:

- 🔐 Token-based authentication (Laravel Sanctum)
- 👥 Stagiaire & programme management
- 📅 Session-based attendance recording
- 📊 Real-time absence statistics
- 📁 Bulk Excel import for stagiaires
- 🌍 CORS-secured cross-origin access for the Vercel frontend

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 |
| Language | PHP 8.3 |
| Auth | Laravel Sanctum (Bearer token) |
| Database | SQLite (production) |
| Deployment | Railway + Nixpacks |
| API Style | RESTful JSON |

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────┐
│               React Frontend (Vercel)           │
│         https://absence-app-one.vercel.app      │
└───────────────────┬─────────────────────────────┘
                    │ HTTPS + Bearer Token
                    ▼
┌─────────────────────────────────────────────────┐
│           Laravel 11 API (Railway)              │
│    https://web-production-09c0f.up.railway.app  │
├─────────────────────────────────────────────────┤
│  Auth  │  Stagiaires  │  Sessions  │  Absences  │
├─────────────────────────────────────────────────┤
│              SQLite Database                    │
└─────────────────────────────────────────────────┘
```

---

## 📡 API Reference

### Authentication
| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/login` | Login → returns Bearer token |
| `DELETE` | `/api/logout` | Revoke current token |
| `GET` | `/api/me` | Get authenticated user |

### Stagiaires
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/stagiaires` | List all stagiaires (paginated) |
| `POST` | `/api/stagiaires` | Create a stagiaire |
| `PUT` | `/api/stagiaires/{id}` | Update a stagiaire |
| `DELETE` | `/api/stagiaires/{id}` | Delete a stagiaire |
| `POST` | `/api/stagiaires/upsert-from-excel` | Bulk import from Excel |
| `GET` | `/api/stagiaires/{id}/attendance-stats` | Per-stagiaire stats |

### Sessions & Attendances
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/sessions` | List sessions |
| `POST` | `/api/attendances/bulk` | Record attendance for a full session |
| `PATCH` | `/api/attendances/{id}/status` | Update justification status |
| `GET` | `/api/attendances/stats/by-stagiaire` | Stats grouped by stagiaire |
| `GET` | `/api/attendances/stats/by-programme` | Stats grouped by programme |

> All routes except `/api/login` require `Authorization: Bearer {token}` header.

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.3+
- Composer 2.x

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/0xsnowZ/Absence-Manager-backend.git
cd Absence-Manager-backend

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Create the SQLite database
touch database/database.sqlite

# 6. Run migrations + seed
php artisan migrate --seed

# 7. Start the development server
php artisan serve
```

The API will be available at `http://localhost:8000/api`.

**Default admin credentials (from seeder):**
```
Email:    admin@school.ma
Password: password
```

---

## ⚙️ Environment Variables

| Variable | Required | Description |
|---|---|---|
| `APP_KEY` | ✅ | Laravel encryption key |
| `APP_ENV` | ✅ | `local` / `production` |
| `APP_URL` | ✅ | Full URL of the backend |
| `DB_CONNECTION` | ✅ | `sqlite` |
| `FRONTEND_URL` | ✅ | Frontend URL (used by CORS) |
| `SESSION_DRIVER` | — | Use `file` in production |
| `CACHE_STORE` | — | Use `file` in production |

---

## 🗄 Database Schema

```
users          → id, name, email, password, role
stagiaires     → id, matricule, nom, prenom, sexe, cin, telephone
programmes     → id, code_diplome, libelle_long, filiere_id
filieres       → id, code, libelle
seances        → id, programme_id, time_block_id, date_session
attendances    → id, stagiaire_id, session_id, type_absence_id, status, justification
type_absences  → id, code (PRESENT/ABSENT/RETARD), libelle
time_blocks    → id, code (TB1–TB4), heure_debut, heure_fin
```

---

## 🚂 Deployment (Railway)

The project auto-deploys to Railway on every push to `main`. The deployment is configured via:

| File | Purpose |
|---|---|
| `railway.toml` | Builder config, start command, health check |
| `nixpacks.toml` | PHP 8.3 + extensions installation |
| `start.sh` | Migrate → seed → serve on `$PORT` |

Railway environment variables to set:

```env
APP_ENV=production
APP_KEY=base64:...
APP_URL=https://your-app.up.railway.app
FRONTEND_URL=https://your-frontend.vercel.app
DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file
```

---

## 🗺 Roadmap

- [x] Bearer token authentication (Sanctum)
- [x] Full CRUD for stagiaires, programmes, sessions
- [x] Bulk attendance recording
- [x] Excel import for stagiaires
- [x] CORS support for Vercel frontend
- [ ] Email/SMS notifications on absence threshold
- [ ] Justification document upload & approval workflow
- [ ] QR code attendance recording
- [ ] Multi-établissement (multi-tenant) support
- [ ] Arabic RTL support

---

## 👨‍💻 Author

**elgarouani** — ISTA Inezgane · OFPPT

---

<div align="center">
  <sub>Built with ❤️ for ISTA Inezgane · <a href="https://github.com/0xsnowZ/Absence-Manager-backend">GitHub</a></sub>
</div>
