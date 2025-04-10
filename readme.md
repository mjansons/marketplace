# Marketplace

A Symfony 7.2 project for managing classified ads (cars, computers, phones) with user accounts, admin panel, and product lifecycle workflows.
Includes filtering, publishing, and automated expiry of listings.

---

## 🧱 Requirements

- PHP 8.2+
- Composer
- Docker or local MySQL setup

---

## ⚙️ Project Setup

Clone the repository:

```bash
git clone https://github.com/yourname/mjansons-marketplace.git
cd mjansons-marketplace
```

### 1. Environment Configuration

Create your `.env` file:

```env
APP_ENV=dev
APP_SECRET=
DATABASE_URL="mysql://root:root@db:3306/marketplace_db?/app?serverVersion=8.0.32&charset=utf8mb4"
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

For development, also create `.env.dev`:

```env
APP_SECRET=12345678yuuenhdtbsrj654scv
```

For testing, use `.env.test`:

```env
KERNEL_CLASS='App\\Kernel'
APP_SECRET='87654fstvbknm743wnhdhh'
SYMFONY_DEPRECATIONS_HELPER=999999
DATABASE_URL="mysql://root:root@db:3306/marketplace_db?serverVersion=8.0.32&charset=utf8mb4"
```

### 2. Install dependencies

```bash
composer install
```

### 3. Docker Setup (optional)

If you're using Docker:
```bash
docker compose up -d
```

Make sure your database service is named `db` for correct connection with `DATABASE_URL`.

### 4. Database Setup

#### Regular DB:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

#### Test DB:
```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
```

### 5. Load Fixtures (optional)

You can load fixtures by group:

```bash
php bin/console doctrine:fixtures:load --group=users
php bin/console doctrine:fixtures:load --group=products --append
php bin/console doctrine:fixtures:load --group=car --append
```

Note: If you run fixtures without `--append`, the database will be purged.

**Default login credentials after running user fixtures:**

- Regular User:
    - **Email:** `basicuser@basicuser.basicuser`
    - **Password:** `basicuser`
- Admin User:
    - **Email:** `adminuser@adminuser.adminuser`
    - **Password:** `adminuser`

---

## 🧪 Running Tests

Run all tests:
```bash
./vendor/bin/phpunit --testdox tests
```

---

## 📬 Messenger Setup

To start consuming delayed messages:

```bash
php bin/console messenger:setup-transports
php bin/console messenger:consume async
```

If you want more control:
```bash
# Limit to 10 messages
php bin/console messenger:consume async --limit=10

# Set a memory limit of 128MB
php bin/console messenger:consume async --memory-limit=128M

# Restart automatically every 5 minutes
php bin/console messenger:consume async --time-limit=300
```

💡 Keep this command running in the background or via a supervisor if you want it to always listen.

---

## 🔑 Admin Panel

This project uses [EasyAdmin](https://github.com/EasyCorp/EasyAdminBundle) for admin management.

Access the admin panel at:
```
http://localhost:8080/admin
```

---

## 🧹 Notable Packages

- Symfony 7.2 (full stack)
- Doctrine ORM & Migrations
- Messenger component for async processing
- EasyAdmin for admin UI
- PHPUnit + LiipFixturesBundle for functional testing
- Twig templates

---

## 📁 Project Structure

```bash
mjansons-marketplace/
├── bin/                  # Console commands
├── config/               # Symfony configuration
├── migrations/           # Doctrine migrations
├── public/               # Web root
├── src/                  # Application code
│   ├── Controller/       # App + Admin controllers
│   ├── DataFixtures/     # Doctrine fixture classes
│   ├── Entity/           # Doctrine entities
│   ├── Form/             # Symfony form types
│   ├── Message/          # Async message classes
│   ├── Repository/       # Doctrine repositories
│   └── Service/          # App services (e.g. ProductHandler)
├── templates/            # Twig templates
├── tests/                # Unit & Functional tests
├── translations/         # Translation files
├── .env, .env.test, etc  # Environment configs
├── composer.json         # PHP dependencies
└── docker-compose.yaml   # Optional Docker setup
```