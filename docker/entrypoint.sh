#!/bin/sh

set -eu

port="${PORT:-8080}"

case "$port" in
    ''|*[!0-9]*)
        echo "PORT must be a valid TCP port." >&2
        exit 1
        ;;
esac

if [ "$port" -lt 1 ] || [ "$port" -gt 65535 ]; then
    echo "PORT must be between 1 and 65535." >&2
    exit 1
fi

sed -i "s/^Listen 80$/Listen $port/; s/8080/$port/g" \
    /etc/apache2/ports.conf \
    /etc/apache2/sites-enabled/tasks.conf

# These caches are local to each immutable container instance. They do not run
# migrations or perform any database mutation.
su -s /bin/sh www-data -c 'php artisan config:cache'
su -s /bin/sh www-data -c 'php artisan route:cache'
su -s /bin/sh www-data -c 'php artisan view:cache'

exec "$@"
