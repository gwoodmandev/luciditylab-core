# 🧪 Lucidity Lab Core

This repository serves as the foundational starting point for all client website builds. It provides a standardised project structure, shared configurations, and best-practice conventions to ensure consistency across projects and reduce setup time.

**Stack:** Craft CMS · Docker · Nginx · MySQL · Redis · ESBuild

## 🍴 Forking this core project

1. Create a new project repository in GitHub.
2. Open your terminal in your projects directory and run the following, replacing `userName` and `newRepo` with your project name matching the repository you just created.
```bash
git clone https://github.com/gwoodmandev/luciditylab-core.git newRepo
cd newRepo
git remote set-url origin https://github.com/userName/newRepo.git
git remote add upstream https://github.com/gwoodmandev/luciditylab-core.git
git push origin main
git push --all
```
3. Replace any reference to `luciditylab-core` with your new project name in this `README.md` file.
4. Set your project ports. Open `craftcms/docker-compose.yml` and assign unique port numbers for this project to avoid conflicts with other locally running projects.
```yaml
# Frontend & Control Panel
ports:
  - "8000:80"   # Change 8000 to your chosen port

# phpMyAdmin
ports:
  - "8080:80"   # Change 8080 to your chosen port
```

5. Once set, update the access URLs in the [Access Your Site](#6-access-your-site) section of this README **AND** the `PRIMARY_SITE_URL` & `FILESYSTEM_URL_PUBLIC` URLs in `craftcms/.env.example.dev` to reflect your chosen ports. 
6. Remove this entire section [Forking this core project](#forking-this-core-project).
7. Commit and push these changes with this optional commit message: `chore(setup): new project setup complete` 
8. Branch off into `develop`. (This is where you'll be building out from. This branch should reflect anything on staging prior to release)

## 📁 Project Structure

```
luciditylab-core/
├── craftcms/                    # Craft CMS backend
│   ├── config/                  # Craft configuration files
│   ├── docker/                  # Docker configuration
│   │   ├── nginx/
│   │   │   └── default.conf     # Nginx server configuration
│   │   └── php/
│   │       └── Dockerfile       # PHP-FPM with Craft requirements
│   ├── modules/                 # Custom Craft modules
│   ├── storage/                 # Craft storage (logs, runtime)
│   ├── templates/               # Twig templates
│   ├── vendor/                  # PHP dependencies
│   ├── web/                     # Public web root
│   │   ├── assets/              # Compiled frontend assets
│   │   ├── cpresources/         # Craft CP resources
│   │   └── index.php            # Entry point
│   ├── .env                     # Environment variables
│   ├── composer.json            # PHP dependencies
│   └── docker-compose.yml       # Docker services configuration
└── frontend/                    # Frontend assets & build tools
    ├── src/                     # Source files
    │   ├── css/                 # Stylesheets
    │   ├── js/                  # JavaScript
    │   └── images/              # Images
    ├── package.json             # Node dependencies
    └── esbuild.config.js        # ESBuild configuration
```

## ✅ Prerequisites

Before you begin, ensure you have the following installed:

- [Docker Desktop](https://www.docker.com/products/docker-desktop) (Windows, Mac, or Linux)
- [Git](https://git-scm.com/downloads)
- [Composer](https://getcomposer.org/download/) (optional - can run via Docker)
- [Node.js](https://nodejs.org/) (for frontend build tools)

## 🚀 Getting Started

### 1. Clone the Repository

```bash
git clone https://github.com/gwoodmandev/luciditylab-core.git
cd luciditylab-core
```

### 2. Set Up Environment Variables

Navigate to the `craftcms` directory and create your `.env` file:

```bash
cd craftcms
cp .env.example.dev .env
```

Edit the `.env` file and generate the required security keys:

**Generate CRAFT_SECURITY_KEY:**
```bash
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```

**Generate CRAFT_APP_ID:**
```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

Update your `.env` file with these values:
```env
CRAFT_SECURITY_KEY=your-generated-key-here
CRAFT_APP_ID=your-generated-app-id-here
CRAFT_DB_DATABASE=luciditylab-core
CRAFT_DB_USER=craft
CRAFT_DB_PASSWORD=craft
```

> ⚠️ **Security note:** The credentials above are for local development only. Never use default or weak credentials in staging or production environments. Always use strong, unique passwords and store them securely — ideally via a secrets manager or environment-level config rather than committing them to your `.env` file.

### 3. Start Docker Containers

From the `craftcms` directory:

```bash
docker-compose up -d --build
```

This will:
- Build the custom PHP container with all Craft CMS requirements
- Start Nginx web server
- Start MySQL database
- Start Redis for caching
- Start phpMyAdmin for database management

**Wait for containers to fully start** (check with `docker-compose ps`)

### 4. Install Craft CMS

If Craft isn't already installed, run:

```bash
docker-compose exec php composer install
docker-compose exec php php craft install
```

Follow the prompts to set up:
- Site name
- Site URL: `http://localhost:8000`
- Admin username and password
- Admin email

### 5. Set Up Frontend Build Tools (Optional)

Navigate to the `frontend` directory:

```bash
cd ../frontend
npm install
```

Run the build process:

```bash
npm run build
# Or for development with watch mode:
npm run dev
```

### 6. Access Your Site

- 🌐 **Frontend:** http://localhost:8000
- 🛠️ **Control Panel:** http://localhost:8000/admin
- 🗄️ **phpMyAdmin:** http://localhost:8080

## 🛠️ Development

### Useful Docker Commands

**View running containers:**
```bash
docker-compose ps
```

**View logs:**
```bash
docker-compose logs -f
# Or for a specific service:
docker-compose logs -f web
```

**Stop containers:**
```bash
docker-compose down
```

**Restart containers:**
```bash
docker-compose restart
```

**Access PHP container shell:**
```bash
docker-compose exec php bash
```

**Run Craft console commands:**
```bash
docker-compose exec php php craft <command>
```

### Common Craft Commands

**Clear caches:**
```bash
docker-compose exec php php craft clear-caches/all
```

**Run migrations:**
```bash
docker-compose exec php php craft migrate/all
```

**Create a new section:**
```bash
docker-compose exec php php craft sections/create
```

**Backup database:**
```bash
docker-compose exec php php craft db/backup
```

## 🔧 Troubleshooting

### Port Already in Use

If port 8000 is already in use, update the port mapping in `docker-compose.yml`:

```yaml
ports:
  - "8001:80"  # Change 8000 to any available port
```

### Permission Issues

If you encounter permission issues with storage or web directories:

```bash
docker-compose exec php chown -R www-data:www-data storage web/cpresources
```

### Database Connection Issues

Verify your database credentials in `.env` match those in `docker-compose.yml`:
- `DB_SERVER` should be `db` (the Docker service name)
- `DB_DATABASE`, `DB_USER`, and `DB_PASSWORD` should match the MySQL environment variables

### Nginx 404 Errors

Ensure your volume mounts are correct in `docker-compose.yml` and that `web/index.php` exists.

## 🌍 Production Deployment

This Docker setup is designed for local development. For production deployment:

1. Use environment-specific `.env` files
2. Set `CRAFT_ENVIRONMENT=production`
3. Disable debug mode and dev mode
4. Use proper SSL certificates
5. Configure proper database backups
6. Use production-grade secrets management
7. Consider using Docker Swarm or Kubernetes for orchestration

> ⚠️ **Security reminder:** Ensure all default credentials have been replaced before deploying to any public-facing environment. Review your `.env` file carefully and ensure it is never committed to version control.

## ⚙️ Technologies Used

- **Backend:** Craft CMS 4.x
- **Web Server:** Nginx
- **PHP:** 8.2 with FPM
- **Database:** MySQL 8.0
- **Caching:** Redis
- **Frontend Build:** ESBuild
- **Containerization:** Docker & Docker Compose

Project Link: [https://github.com/gwoodmandev/luciditylab-core](https://github.com/gwoodmandev/luciditylab-core)