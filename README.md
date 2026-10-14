# ERP Fidoma

Database-first ERP application built with Laravel, Filament and MySQL.

## Development stack

- Laravel 13
- Filament 5
- PHP 8.5
- MySQL 8.4
- Laravel Sail / Docker Compose
- phpMyAdmin
- Node.js 24 and Vite

All application runtimes and database services run in Docker. Only Docker Engine
and Docker Compose are required on the host.

## Start the application

```bash
./vendor/bin/sail up -d
```

The local services are then available at:

- Application: <http://localhost:8000>
- Filament panel: <http://localhost:8000/admin>
- phpMyAdmin: <http://localhost:8081>
- MySQL: `127.0.0.1:3306`

The default local database credentials are:

```text
Database: laravel
Username: sail
Password: password
```

These credentials are intended for local development only.

## Create the first Filament user

```bash
./vendor/bin/sail artisan make:filament-user
```

## Common commands

```bash
# Stop the containers
./vendor/bin/sail down

# Run migrations
./vendor/bin/sail artisan migrate

# Run tests
./vendor/bin/sail test

# Build frontend assets
./vendor/bin/sail npm run build

# Open the MySQL command line
./vendor/bin/sail mysql
```

After cloning the repository, create the local environment and install dependencies:

```bash
cp .env.example .env
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$PWD:/app" \
    -w /app \
    laravelsail/php84-composer:latest \
    composer install
./vendor/bin/sail up -d --build
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```
