# SyntaxCore

SyntaxCore is a lightweight, elegant PHP MVC Framework designed with a clear separation between **Core** (framework engine) and **Application** (domain logic).

---

## 🚀 Quickstart with Docker

SyntaxCore comes with a fully-configured Docker Compose environment including:
- **PHP 8.3 FPM** (`app`) with `pdo_mysql`, `opcache`, and Composer pre-installed
- **Nginx Alpine** (`web`) web server routing all HTTP requests to the front controller
- **MySQL 8.0** (`db`) database with automatic schema initialization

### 1. Requirements
- [Docker](https://docs.docker.com/get-docker/) (20.10+)
- [Docker Compose](https://docs.docker.com/compose/) (v2+)

### 2. Environment Setup
Copy the example environment configuration:
```bash
cp .env.example .env
```

### 3. Build & Run Containers
Start all services in detached mode:
```bash
docker compose up -d --build
```

The application will be accessible at:
- **Web Application / Home**: [http://localhost:8080](http://localhost:8080)
- **API Status**: [http://localhost:8080/api/v1/status](http://localhost:8080/api/v1/status)
- **Admin Panel**: [http://localhost:8080/admin](http://localhost:8080/admin)
- **Admin Login**: [http://localhost:8080/admin/login](http://localhost:8080/admin/login)

MySQL is accessible on host port `3306` (or `${FORWARD_DB_PORT}`):
- **Host**: `127.0.0.1` (or `db` from inside containers)
- **Database**: `syntaxcore`
- **Username**: `syntaxcore` (or `root`)
- **Password**: `secret` (or root password `root`)

---

## 📦 Useful Docker Commands

### Seed Administrator Account
Populate the database with a default administrator user (`admin@syntaxcore.com` / `admin123`):
```bash
docker compose exec app composer seed
# or
docker compose exec app php database/seed.php
```

### Run Architecture & Integration Tests
Execute the native 32-test integration suite:
```bash
docker compose exec app composer test
# or
docker compose exec app php tests/run.php
```

### Open Container Shell
Access the PHP application container shell:
```bash
docker compose exec app bash
```

### View Logs
```bash
# All logs
docker compose logs -f

# App / PHP-FPM logs
docker compose logs -f app

# Web / Nginx logs
docker compose logs -f web

# MySQL database logs
docker compose logs -f db
```

### Stop Containers
```bash
docker compose down
```
To remove volumes and reset the database:
```bash
docker compose down -v
```

---

## 📂 Project Architecture

```text
syntaxcore/
├── app/                  # Application domain logic
│   ├── Controllers/      # Web, API, and Admin controllers
│   ├── Middleware/       # App-specific middlewares (CSRF, Auth, Guest)
│   ├── Models/           # Database Models (Active Record pattern)
│   └── Services/         # Business logic services (e.g. AuthService)
├── bootstrap/            # Application bootstrap & dependency injection
├── config/               # App and database configuration
├── core/                 # SyntaxCore Engine (passive & stable)
├── database/             # Schema definitions and seed scripts
│   ├── schema.sql        # Initial MySQL schema (auto-mounted in Docker)
│   └── seed.php          # CLI seeder for default users
├── docker/               # Docker configuration files
│   ├── entrypoint.sh     # Container startup script (permissions & autoload)
│   ├── nginx/            # Nginx virtual host configuration
│   └── php/              # Custom PHP & PHP-FPM pool configuration
├── public/               # Web server document root
│   ├── assets/           # Compiled / browser-accessible assets
│   ├── .htaccess         # Apache rewrite configuration
│   └── index.php         # Application front controller
├── resources/            # Source assets and views
│   ├── assets/           # Source CSS and JS
│   └── views/            # MVC Views (Web and Admin)
├── routes/               # Route definitions (web.php, api.php, admin.php)
├── storage/              # Cache, logs, and user uploads
├── tests/                # Native architectural & integration test suite
├── docker-compose.yml    # Docker Compose multi-container configuration
├── Dockerfile            # Application PHP container image definition
└── composer.json         # Autoloading and scripts definition
```

---

## 📄 License
MIT License