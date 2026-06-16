#!/bin/bash
set -e

# Wait for database connection
echo "Waiting for database to be ready..."
until php -r "
try {
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '5432';
    $dbName = getenv('DB_DATABASE') ?: 'vms';
    $username = getenv('DB_USERNAME') ?: 'vms_user';
    $password = getenv('DB_PASSWORD') ?: 'vms_password';
    new PDO(\"pgsql:host=$host;port=$port;dbname=$dbName\", $username, $password);
    exit(0);
} catch (Exception $e) {
    exit(1);
}
"; do
    echo "Database is unavailable - sleeping..."
    sleep 2
done
echo "Database is up!"

# Run standard Laravel optimizations
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Seed the database if VMS_SEED is set to true
if [ "$VMS_SEED" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force
fi

exec "$@"
