# Contributing to Fidoma

Thank you for helping improve Fidoma. Contributions should preserve existing business workflows, feature boundaries and data compatibility.

## Before you start

1. Search existing issues and pull requests to avoid duplicate work.
2. For substantial features or changes to data structures, open an issue first and describe the use case.
3. Never include credentials, `.env` files, customer data or production documents in issues, commits or test fixtures.
4. Report vulnerabilities through the process in [SECURITY.md](SECURITY.md), not in a public issue.

The repository currently has no published license file. Before making a significant contribution, review the [license note](README.md#license) and ask the maintainers if the contribution terms are unclear.

## Development setup

Fidoma uses PHP 8.5 in the development container, Laravel 13, Filament 5, Livewire, MySQL 8.4, Vite 8 and Tailwind CSS 4. The project uses PHPUnit, not Pest.

```bash
git clone https://github.com/Fidoma/Fidoma.git
cd Fidoma
cp .env.example .env
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$PWD:/app" -w /app \
  laravelsail/php85-composer:latest composer install
vendor/bin/sail up -d --build
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
vendor/bin/sail npm install
vendor/bin/sail npm run build
```

Create an administrator with `vendor/bin/sail artisan make:filament-user --panel=admin` and open `/admin`.

## Architecture and coding guidelines

- Put domain code under the relevant `app/Features/<Feature>` directory.
- Reuse existing models, relations, enums, actions, queries and services before adding new abstractions.
- Keep Filament pages and Livewire components thin; put reusable write logic in actions and read logic in queries.
- Use existing amount and calculator logic for money. Do not introduce float-based calculations.
- Add German and English strings to the feature-local `lang` directories.
- Prefer Filament, Livewire and existing project components over custom markup, CSS or JavaScript.
- Do not rename `ip_` tables or keys without a compelling compatibility reason.
- Do not add dependencies unless the existing Laravel/Filament stack cannot reasonably solve the problem.

More detail is available in the [architecture](https://fidoma.github.io/Fidoma/developers/architecture.html) and [module development](https://fidoma.github.io/Fidoma/developers/modules.html) guides.

## Tests and formatting

Add regression coverage for behavior changes and important error cases. Start with the smallest relevant test, then run formatting and the full suite when the change crosses feature boundaries.

```bash
vendor/bin/sail artisan test tests/Feature/path/to/RelevantTest.php
vendor/bin/sail bin pint --dirty
vendor/bin/sail artisan test
```

For frontend or documentation changes, also run the relevant build:

```bash
vendor/bin/sail npm run build
vendor/bin/sail npm run docs:build
```

## Pull requests

Keep a pull request focused and include:

- the user or business problem;
- the implemented behavior and affected feature;
- screenshots for visible UI changes;
- migrations, configuration or compatibility considerations;
- exact tests and checks run;
- known limitations or follow-up work.

Do not mix unrelated formatting or refactoring into the same pull request. Update the relevant user or developer documentation when behavior, setup or configuration changes.
