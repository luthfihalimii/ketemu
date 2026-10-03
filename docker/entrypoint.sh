#!/bin/sh
set -e

# Tunggu DB siap (max ~60 detik) sebelum migrate.
i=0
until php artisan db:show --database=mysql >/dev/null 2>&1 || [ $i -ge 30 ]; do
  i=$((i+1))
  sleep 2
done

if [ "$SKIP_MIGRATIONS" != "1" ]; then
  php artisan migrate --force
fi

php artisan storage:link >/dev/null 2>&1 || true

exec "$@"
