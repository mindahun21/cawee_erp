# Cawee ERP

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

## 🌐 Deploying on cPanel (without PostgreSQL)

This application can be deployed on cPanel hosting environments that don't have PostgreSQL/pgvector support. The AI Intelligence Hub module will be automatically disabled, while all other ERP modules function normally.

### Prerequisites for cPanel Deployment

- PHP 8.2 or higher
- MySQL database
- SSH access (for running composer and artisan commands)
- Node.js & NPM (for building assets)

### Deployment Steps

1. **Upload the code** to your cPanel hosting via Git or FTP

2. **Create a MySQL database** in cPanel and note the credentials

3. **Configure environment** - Copy `.env.example` to `.env` and update:
   ```bash
   cp .env.example .env
   ```

4. **Edit `.env` file** with your cPanel settings:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   
   # Disable AI Intelligence module (no PostgreSQL on cPanel)
   ENABLED_MODULES=hr,recruitment,procurement,finance,donor_fundraising,brt,monitoring_evaluation,inventory,vehicle_management,file_sharing,car_rent
   ```

5. **Install dependencies** via SSH:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   npm install
   npm run build
   ```

6. **Set permissions**:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

7. **Configure web server** - Point your domain's document root to the `public` directory

### What Works Without PostgreSQL

✅ All core ERP modules:
- Human Resources
- Recruitment
- Procurement
- Finance
- Donor & Fundraising
- Beneficiary Registry & Project Tracking
- Monitoring & Evaluation
- Inventory & Asset Management
- Vehicle Management
- File Sharing
- Car Rent Management

❌ AI Intelligence Hub (requires PostgreSQL with pgvector extension)

### Enabling AI Features Later

If you move to a VPS or hosting with PostgreSQL support:

1. **Install PostgreSQL with pgvector extension**

2. **Update `.env` with vector database credentials**:
   ```env
   VECTOR_DB_HOST=127.0.0.1
   VECTOR_DB_PORT=5432
   VECTOR_DB_DATABASE=elisoft_vectors
   VECTOR_DB_USERNAME=vector_user
   VECTOR_DB_PASSWORD=your_password
   ```

3. **Add `ai_intelligence` to ENABLED_MODULES**:
   ```env
   ENABLED_MODULES=hr,recruitment,procurement,finance,donor_fundraising,brt,monitoring_evaluation,inventory,vehicle_management,file_sharing,car_rent,ai_intelligence
   ```

4. **Run migrations and index documents**:
   ```bash
   php artisan migrate
   php artisan ai:index-documents
   ```

5. **Check AI status**:
   ```bash
   php artisan ai:check
   ```

### Troubleshooting

**Problem**: Setup fails with "Vector database unavailable"  
**Solution**: This is expected on cPanel. The setup will continue and skip AI features automatically.

**Problem**: AI Analytics Hub appears in navigation  
**Solution**: Ensure `ai_intelligence` is NOT in your `ENABLED_MODULES` list in `.env`

**Problem**: Application crashes on startup  
**Solution**: Run `php artisan ai:check` to diagnose AI configuration issues

**Problem**: Migrations fail  
**Solution**: Check database credentials in `.env` and ensure MySQL is accessible

For more help, run the diagnostic command:
```bash
php artisan ai:check
```

## 📄 License
This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
