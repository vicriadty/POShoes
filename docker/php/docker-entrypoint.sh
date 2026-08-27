#!/bin/sh
set -e

# Entrypoint untuk semua target app (fpm, worker, scheduler).
# Menjalankan perintah wajib sebelum layanan utama.

if [ "${COMMAND_FRESH}" = "1" ]; then
  echo "Running database migrations + setup..."
  php artisan key:generate
  php artisan migrate --force
fi

exec "$@"
