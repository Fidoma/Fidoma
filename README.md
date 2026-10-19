# Fidoma

<div align="center">

### Modern, Self-Hosted ERP & Business Management

**An open-source ERP platform built with Laravel, Filament and MySQL.**

Manage your business workflows, customers, invoices and financial processes from one central application.

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-5-FDAE4B)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://docker.com)

</div>

---

## Overview

**Fidoma** is a modern, self-hosted Enterprise Resource Planning (ERP) application designed to simplify everyday business operations.

Built on a modern PHP technology stack, Fidoma aims to provide a clean, intuitive interface for managing business processes without unnecessary complexity.

The project focuses on modularity, extensibility and full control over your own data.

### Application Preview

<!-- TODO: Add a real screenshot of the Fidoma dashboard -->
<!-- Expected path: docs/screenshots/dashboard.png -->

**📸 Screenshot coming soon — Dashboard**

The dashboard provides a central overview of your business activities.

---

## Features

Fidoma is being developed as a modular business management platform.

> **Development note:** The following feature areas should be reviewed against the current implementation. Some functionality may still be under active development.

| Module | Description |
|---|---|
| Dashboard | Central overview of business information |
| Customer Management | Manage customers and business contacts |
| Quotations | Create and manage quotations |
| Invoicing | Manage invoices and billing workflows |
| Financial Management | Organize financial records and transactions |
| Document Management | Organize business-related documents |
| Contract Management | Manage contracts and reusable templates |

Additional modules and improvements are planned as development continues.

---

## Screenshots

Explore the Fidoma interface and its core workflows.

### Dashboard

<!-- TODO: docs/screenshots/dashboard.png -->

**📸 Screenshot missing — Dashboard**

### Invoice Management

<!-- TODO: docs/screenshots/invoices.png -->

**📸 Screenshot missing — Invoice Management**

### Customer Management

<!-- TODO: docs/screenshots/customers.png -->

**📸 Screenshot missing — Customer Management**

### Financial Management

<!-- TODO: docs/screenshots/finance.png -->

**📸 Screenshot missing — Financial Management**

### Document Management

<!-- TODO: docs/screenshots/documents.png -->

**📸 Screenshot missing — Document Management**

Once screenshots are available, replace the placeholders with Markdown image references, for example:

`![Fidoma Dashboard](docs/screenshots/dashboard.png)`

---

## Technology Stack

Fidoma is built using modern, actively maintained technologies.

| Technology | Version | Purpose |
|---|---|---|
| Laravel | 13 | Backend framework |
| Filament | 5 | Administration interface |
| PHP | 8.5 | Application runtime |
| MySQL | 8.4 | Relational database |
| Laravel Sail | — | Docker development environment |
| Docker Compose | — | Container orchestration |
| Node.js | 24 | Frontend tooling |
| Vite | — | Asset bundling |
| phpMyAdmin | — | Database administration |

All application runtimes and database services run inside Docker containers.

Only Docker Engine and Docker Compose are required on the host.

---

## Getting Started

### Prerequisites

Make sure the following tools are installed:

- Docker Engine
- Docker Compose
- Git

### 1. Clone the Repository

```bash
git clone https://github.com/Fidoma/Fidoma.git
cd Fidoma
```

### 2. Configure the Environment

Create your local environment configuration:

```bash
cp .env.example .env
```

### 3. Install Dependencies

Install Composer dependencies using Docker:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$PWD:/app" \
    -w /app \
    laravelsail/php84-composer:latest \
    composer install
```

### 4. Start Docker Containers

```bash
./vendor/bin/sail up -d --build
```

### 5. Initialize the Application

Generate the application key and run database migrations:

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

### 6. Install Frontend Dependencies

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### 7. Create an Administrator Account

```bash
./vendor/bin/sail artisan make:filament-user
```

Follow the interactive instructions to create your first administrator account.

### 8. Open Fidoma

After installation, the following services are available:

| Service | URL |
|---|---|
| Application | http://localhost:8000 |
| Admin Panel | http://localhost:8000/admin |
| phpMyAdmin | http://localhost:8081 |
| MySQL | `127.0.0.1:3306` |

### Default Development Database

```text
Database: laravel
Username: sail
Password: password
```

> ⚠️ These credentials are intended for local development only. Never use default credentials in production.

---

## Development

### Common Commands

Start the application:

```bash
./vendor/bin/sail up -d
```

Stop the containers:

```bash
./vendor/bin/sail down
```

Run database migrations:

```bash
./vendor/bin/sail artisan migrate
```

Run automated tests:

```bash
./vendor/bin/sail test
```

Build frontend assets:

```bash
./vendor/bin/sail npm run build
```

Open the MySQL command line:

```bash
./vendor/bin/sail mysql
```

---

## Project Structure

Fidoma follows the standard Laravel project structure.

```text
Fidoma/
├── app/              # Application logic
├── bootstrap/        # Application bootstrap
├── config/           # Configuration
├── database/         # Migrations and seeders
├── docs/             # Documentation
│   └── screenshots/  # Application screenshots
├── public/           # Public assets
├── resources/        # Frontend resources
├── routes/           # Application routes
├── storage/          # Application storage
├── tests/            # Automated tests
├── compose.yaml      # Docker services
└── README.md
```

---

## Roadmap

Fidoma is under active development.

The long-term goal is to provide a flexible and extensible ERP platform that brings essential business operations together in one application.

Planned improvements and upcoming releases will be documented here as development progresses.

For current development activity, see the [GitHub repository](https://github.com/Fidoma/Fidoma).

---

## Contributing

Contributions, suggestions and bug reports are welcome.

If you would like to contribute:

1. Open an issue to discuss your idea or report a problem.
2. Fork the repository.
3. Create a feature branch.
4. Implement and test your changes.
5. Submit a pull request.

Please keep changes focused and follow the existing project conventions.

---

## Support & Feedback

Found a bug or have a feature suggestion?

Please open an issue in the [GitHub Issue Tracker](https://github.com/Fidoma/Fidoma/issues).

Your feedback helps improve Fidoma.

---

<div align="center">

### Built with Laravel & Filament

**Fidoma — Modern Business Management, Self-Hosted.**

[GitHub Repository](https://github.com/Fidoma/Fidoma) · [Report an Issue](https://github.com/Fidoma/Fidoma/issues)

</div>