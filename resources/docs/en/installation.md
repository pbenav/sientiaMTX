# 🚀 SientiaMTX Installation Guide

Welcome to the official installation guide for **SientiaMTX**, an intelligent task management system based on the Eisenhower Matrix with AI integration and Real-Time Notifications.

## 📋 Prerequisites

Before starting, ensure your server meets the following requirements:

- **PHP 8.2** or higher (8.3/8.4 recommended).
- **MySQL 8.0** or MariaDB 10.4+.
- **Composer** (PHP dependency manager).
- **Node.js 18+** and **NPM** (For frontend assets).
- **Web Server**: Apache or Nginx.
- **Supervisor**: Required for processing notification queues in production.

---

## 🛠️ Installation Steps

Follow these commands in order to get the application up and running:

### 1. Clone the repository
```bash
git clone https://github.com/pbenav/sientiaMTX.git
cd sientiaMTX
```

### 2. Install dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install
```

### 3. Configure the environment
Copy the example file and generate the application key:
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database
Edit your `.env` file with your database credentials and run the migrations:
```bash
# In .env:
# DB_DATABASE=sientia_mtx
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

php artisan migrate --force
```

### 5. Compile Frontend Assets
```bash
npm run build
```

---

## ⚙️ Critical Production Configuration

### Notification Queues
SientiaMTX uses queues so that sending messages to Telegram doesn't slow down the application. In your `.env`, make sure you have:
```env
QUEUE_CONNECTION=database
```

### Supervisor (Persistence)
To keep notifications working 24/7, you must configure **Supervisor**. Create a file at `/etc/supervisor/conf.d/sientiamtx.conf`:
```ini
[program:sientiamtx-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sientiaMTX/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sientiaMTX/storage/logs/worker.log
stopwaitsecs=3600
```
Then restart Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## ✅ You're Done!
The application should be accessible at your domain. The next step is to configure the **Telegram Bot** to activate the intelligent alert system.
