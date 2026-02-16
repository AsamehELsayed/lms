# E-LMS Docker Development Environment

This guide will help you set up and run the E-LMS Laravel application using Docker.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Docker Services](#docker-services)
- [Directory Structure](#directory-structure)
- [Common Commands](#common-commands)
- [Development Workflow](#development-workflow)
- [Troubleshooting](#troubleshooting)
- [Database Management](#database-management)

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- Docker Desktop (latest version)
  - **macOS**: [Download Docker Desktop for Mac](https://www.docker.com/products/docker-desktop)
  - **Windows**: [Download Docker Desktop for Windows](https://www.docker.com/products/docker-desktop)
  - **Linux**: [Install Docker Engine](https://docs.docker.com/engine/install/)
- Docker Compose (included with Docker Desktop)
- At least 4GB of available RAM for Docker
- At least 10GB of free disk space

## Quick Start

### First Time Setup

1. **Clone the repository** (if you haven't already):

    ```bash
    cd /path/to/lms
    ```

2. **Run the automated setup script**:

    ```bash
    ./docker-setup.sh
    ```

    This script will:

    - Create the `.env` file from `.env.docker` template
    - Build all Docker containers
    - Start all services
    - Install Composer dependencies
    - Install NPM dependencies
    - Build frontend assets
    - Set up Laravel (generate key, link storage, clear caches)
    - Import the database from `elms.sql`

3. **Access your application**:

    - **Application**: <http://localhost:8000>
    - **Live Reload (BrowserSync)**: <http://localhost:3000> (recommended for development)
    - **PHPMyAdmin**: <http://localhost:8080>
        - Username: `root`
        - Password: `root`

    **Note**: For development with live reload, use <http://localhost:3000>. This will automatically refresh your browser when you make changes to PHP files, Blade templates, CSS, or JavaScript.

### Daily Development

For subsequent runs, simply use:

```bash
./docker-start.sh
```

To stop the containers:

```bash
./docker-stop.sh
```

## Docker Services

The `docker-compose.yml` file defines the following services:

| Service        | Container Name  | Description                                  | Port            |
| -------------- | --------------- | -------------------------------------------- | --------------- |
| **app**        | elms_app        | PHP 8.3-FPM with Laravel application         | Internal (9000) |
| **nginx**      | elms_nginx      | Nginx web server                             | 8000            |
| **mix**        | elms_mix        | Laravel Mix with BrowserSync for live reload | 3000            |
| **db**         | elms_db         | MySQL 8.0 database                           | 3306            |
| **redis**      | elms_redis      | Redis cache and session store                | 6379            |
| **phpmyadmin** | elms_phpmyadmin | Database management interface                | 8080            |

### Service Details

#### App Container (PHP-FPM)

- **Base Image**: php:8.3-fpm
- **PHP Extensions**: PDO, MySQL, mbstring, exif, pcntl, bcmath, GD, zip, intl, opcache
- **Includes**: Composer, Node.js 20.x, NPM
- **Working Directory**: `/var/www/html`

#### Database Container (MySQL)

- **Version**: MySQL 8.0
- **Database Name**: `elms`
- **Root Password**: `root`
- **User**: `elms_user`
- **Password**: `secret`
- **Auto-imports**: `elms.sql` on first run

## Directory Structure

```bash
lms/
├── docker/                   # Docker configuration files
│   ├── nginx/
│   │   └── nginx.conf       # Nginx server configuration
│   └── php/
│       └── local.ini        # PHP custom settings
├── Dockerfile               # PHP-FPM container definition
├── docker-compose.yml       # Docker services orchestration
├── .dockerignore           # Files to exclude from Docker context
├── .env.docker             # Docker environment template
├── docker-setup.sh         # First-time setup script
├── docker-start.sh         # Daily start script
├── docker-stop.sh          # Stop script
└── elms.sql                # Database dump (auto-imported)
```

## Common Commands

### Container Management

```bash
# Start all containers
docker compose up -d

# Stop all containers
docker compose stop

# Restart all containers
docker compose restart

# View container status
docker compose ps

# Remove all containers (preserves database volume)
docker compose down

# Remove all containers and volumes (CAUTION: deletes database)
docker compose down -v

# View logs for all services
docker compose logs -f

# View logs for specific service
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f db
```

### Laravel Commands

Execute Laravel artisan commands inside the app container:

```bash
# Run artisan commands
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:list

# Run tinker
docker compose exec app php artisan tinker

# Create a new controller
docker compose exec app php artisan make:controller MyController

# Run tests
docker compose exec app php artisan test
```

### Composer Commands

```bash
# Install dependencies
docker compose exec app composer install

# Update dependencies
docker compose exec app composer update

# Add a new package
docker compose exec app composer require vendor/package

# Remove a package
docker compose exec app composer remove vendor/package

# Dump autoload
docker compose exec app composer dump-autoload
```

### NPM Commands

```bash
# Install dependencies
docker compose exec app npm install

# Run development build
docker compose exec app npm run dev

# Run production build
docker compose exec app npm run production

# Watch for changes
docker compose exec app npm run watch
```

### Database Commands

```bash
# Access MySQL CLI
docker compose exec db mysql -uroot -proot elms

# Import SQL file
docker compose exec -T db mysql -uroot -proot elms < backup.sql

# Export database
docker compose exec db mysqldump -uroot -proot elms > backup.sql

# Reset database with fresh import
docker compose exec -T db mysql -uroot -proot elms < elms.sql
```

### File Permissions

If you encounter permission issues:

```bash
# Fix storage and cache permissions
docker compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

## Development Workflow

### Live Reload with BrowserSync

The Docker setup includes **BrowserSync** for automatic browser refresh when you make changes:

1. **Access your app via BrowserSync**: Open <http://localhost:3000> in your browser
2. **Make changes**: Edit any PHP files, Blade templates, CSS, or JavaScript
3. **Automatic refresh**: BrowserSync will automatically detect changes and refresh your browser

**Watched files**:

- `app/**/*.php` - All PHP files in the app directory
- `resources/views/**/*.php` - View files
- `resources/views/**/*.blade.php` - Blade templates
- `public/css/**/*.css` - CSS files
- `public/js/**/*.js` - JavaScript files

**Ports**:

- **Port 8000** (Nginx): Direct access without BrowserSync
- **Port 3000** (BrowserSync): Development with live reload (recommended)

The `mix` service runs `npm run watch` automatically, so you don't need to manually start the watcher.

### Making Code Changes

1. Edit files on your host machine using your favorite IDE
2. Changes are automatically reflected in the container (via volume mounting)
3. BrowserSync will automatically refresh your browser if you're using port 3000
4. For manual asset compilation:

    ```bash
    docker compose exec app npm run dev
    ```

### Running Migrations

```bash
# Run new migrations
docker compose exec app php artisan migrate

# Rollback last migration
docker compose exec app php artisan migrate:rollback

# Refresh database (WARNING: destroys all data)
docker compose exec app php artisan migrate:fresh

# Refresh and seed
docker compose exec app php artisan migrate:fresh --seed
```

### Working with Queues

```bash
# Run queue worker
docker compose exec app php artisan queue:work

# Run queue worker in background (daemon)
docker compose exec -d app php artisan queue:work

# Clear failed jobs
docker compose exec app php artisan queue:flush
```

### Clearing Caches

```bash
# Clear all caches
docker compose exec app php artisan optimize:clear

# Or individually:
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
```

## Troubleshooting

### Containers Won't Start

```bash
# Check for port conflicts
lsof -i :8000
lsof -i :3306
lsof -i :8080

# View detailed logs
docker compose logs

# Rebuild containers
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Database Connection Issues

1. Ensure the database container is running:

    ```bash
    docker compose ps db
    ```

2. Check database logs:

    ```bash
    docker compose logs db
    ```

3. Verify `.env` configuration:

    ```env
    DB_HOST=db
    DB_PORT=3306
    DB_DATABASE=elms
    DB_USERNAME=root
    DB_PASSWORD=root
    ```

### Permission Denied Errors

```bash
# Fix ownership
docker compose exec app chown -R www-data:www-data /var/www/html

# Fix permissions
docker compose exec app chmod -R 775 /var/www/html/storage
docker compose exec app chmod -R 775 /var/www/html/bootstrap/cache
```

### 502 Bad Gateway

This usually means PHP-FPM is not running:

```bash
# Restart the app container
docker compose restart app

# Check app logs
docker compose logs app
```

### Composer/NPM Issues

```bash
# Clear Composer cache
docker compose exec app composer clear-cache
docker compose exec app rm -rf vendor
docker compose exec app composer install

# Clear NPM cache
docker compose exec app npm cache clean --force
docker compose exec app rm -rf node_modules
docker compose exec app npm install
```

### Application Key Missing

```bash
docker compose exec app php artisan key:generate
```

### Storage Link Not Found

```bash
docker compose exec app php artisan storage:link
```

## Database Management

### Using PHPMyAdmin

1. Open <http://localhost:8080> in your browser
2. Login with:
    - **Server**: `db`
    - **Username**: `root`
    - **Password**: `root`
3. Select the `elms` database

### Backup Database

```bash
# Create backup
docker compose exec db mysqldump -uroot -proot elms > backup-$(date +%Y%m%d-%H%M%S).sql
```

### Restore Database

```bash
# Restore from backup
docker compose exec -T db mysql -uroot -proot elms < backup-20241229-120000.sql
```

### Re-import Original Database

```bash
# Drop and recreate database, then import
docker compose exec db mysql -uroot -proot -e "DROP DATABASE IF EXISTS elms; CREATE DATABASE elms;"
docker compose exec -T db mysql -uroot -proot elms < elms.sql
```

## Performance Tips

1. **Use Docker Desktop Resources**: Allocate sufficient CPU and RAM in Docker Desktop settings

    - Recommended: 4+ CPUs, 4GB+ RAM

2. **Enable File Sharing**: Ensure your project directory is in Docker's file sharing list

3. **Use Redis**: Redis is configured for cache and sessions for better performance

4. **Optimize Composer**: Use `--optimize-autoloader --classmap-authoritative` in production

5. **Asset Compilation**: Use `npm run production` for optimized assets

## Environment Variables

Key environment variables in `.env`:

```env
# Application
APP_URL=http://localhost:8000
APP_DEBUG=true

# Database
DB_HOST=db              # Container name
DB_DATABASE=elms
DB_USERNAME=root
DB_PASSWORD=root

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=redis        # Container name
```

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Laravel Documentation](https://laravel.com/docs)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

## Support

For issues or questions:

1. Check the [Troubleshooting](#troubleshooting) section
2. Review Docker logs: `docker compose logs -f`
3. Ensure all prerequisites are met
4. Try rebuilding containers: `docker compose down && docker compose up --build -d`

Happy coding!
