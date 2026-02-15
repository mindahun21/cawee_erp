# Elisoft ERP

A modern Enterprise Resource Planning (ERP) solution built with the latest Laravel ecosystem.

## 🚀 Tech Stack

- **Backend:** [Laravel 12+](https://laravel.com)
- **Admin Panel:** [Filament 5](https://filamentphp.com)
- **Frontend/Styling:** [Tailwind CSS 4](https://tailwindcss.com) & [Vite](https://vitejs.dev)
- **Runtime:** PHP 8.2+
- **Database:** SQLite (default for development)

## 🛠️ Installation & Setup

We have included a streamlined setup script to get you up and running quickly.

### 1. Prerequisites
Ensure you have the following installed:
- PHP 8.2 or higher
- Composer
- Node.js & NPM

### 2. Fast Setup
Run the following command to install dependencies, set up your `.env` file, generate an app key, and run migrations:

```bash
composer setup
```

This command automatically:
- Installs PHP dependencies (`composer install`)
- Creates `.env` from `.env.example` (if not exists)
- Generates the `APP_KEY`
- Runs database migrations
- Installs NPM packages and builds assets

## 💻 Development Workflow

### Start Development Server
To start the application and assets watcher concurrently:

```bash
composer dev
```
This runs the Laravel server, Vite, and the queue listener in one terminal.

### Accessing the Admin Panel
Once the server is running, you can access the Filament admin panel at:
`http://localhost:8000/admin`

### Running Tests
To run the project's test suite:
```bash
composer test
```

## 📂 Project Structure

- `app/Filament`: Custom Filament resources, pages, and widgets (accessible via `AdminPanelProvider`).
- `app/Models`: Core business logic and database entities.
- `database/migrations`: Database schema definitions.
- `resources/css`: Tailwind CSS 4 configuration and styles.

## 📄 License
This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
