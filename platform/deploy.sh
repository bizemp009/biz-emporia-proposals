#!/usr/bin/env bash
# One-step install / update for Hostinger shared hosting (Premium / Business).
#
#   First install:  bash deploy.sh proposals.yourdomain.com
#   Update later:   cd ~/biz-emporia-proposals && git pull && bash platform/deploy.sh proposals.yourdomain.com
#
# The admin password is asked for on first install and written only to the
# server's .env file - it is never stored in git.
set -euo pipefail

HOST="${1:-}"
if [ -z "$HOST" ] || [ "${HOST#*.}" = "$HOST" ]; then
  echo "Usage: bash deploy.sh proposals.yourdomain.com"; exit 1
fi
SUB="${HOST%%.*}"
ROOT_DOMAIN="${HOST#*.}"
APP_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$APP_DIR"

step() { printf '\n==> %s\n' "$1"; }

# ---------------------------------------------------------------- PHP 8.3+
step "Finding PHP 8.3 or newer"
PHP=""
for c in php /opt/alt/php85/usr/bin/php /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php; do
  if command -v "$c" >/dev/null 2>&1 && "$c" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' 2>/dev/null; then
    PHP="$(command -v "$c")"; break
  fi
done
if [ -z "$PHP" ]; then
  echo "PHP 8.3+ not found. In hPanel open Advanced > PHP Configuration, choose PHP 8.3, then run this again."; exit 1
fi
"$PHP" -v | head -1

# ---------------------------------------------------------------- Composer packages
step "Installing PHP packages (takes a minute)"
if [ ! -f composer.phar ]; then
  curl -sS https://getcomposer.org/installer | "$PHP" -- --quiet
fi
"$PHP" composer.phar install --no-dev --optimize-autoloader --no-interaction

# ---------------------------------------------------------------- .env (first install only)
set_env() {  # set_env KEY VALUE  - replaces or appends one line
  grep -v "^$1=" .env > .env.tmp || true
  printf '%s=%s\n' "$1" "$2" >> .env.tmp
  mv .env.tmp .env
}

FIRST_INSTALL=0
if [ ! -f .env ]; then
  FIRST_INSTALL=1
  step "Creating settings (.env)"
  cp .env.example .env
  while :; do
    read -rsp "Choose the admin login password: " PASS; echo
    read -rsp "Type it again: " PASS2; echo
    [ -n "$PASS" ] && [ "$PASS" = "$PASS2" ] && break
    echo "Passwords were empty or did not match - try again."
  done
  case "$PASS" in *'"'*|*'\'*|*'$'*) echo 'Please avoid the characters " \ $ in the password.'; rm .env; exit 1;; esac
  set_env APP_ENV production
  set_env APP_DEBUG false
  set_env APP_URL "https://$HOST"
  set_env LOG_LEVEL error
  set_env ADMIN_PASSWORD "\"$PASS\""
  "$PHP" artisan key:generate --force
fi

# ---------------------------------------------------------------- database
step "Setting up the database"
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite
"$PHP" artisan config:clear
if [ "$FIRST_INSTALL" = 1 ]; then
  "$PHP" artisan migrate --seed --force
else
  "$PHP" artisan migrate --force
fi

# ---------------------------------------------------------------- files & permissions
step "Linking uploads folder and fixing permissions"
# (plain ln: Hostinger disables PHP's exec(), which artisan storage:link may need)
mkdir -p storage/app/public
[ -e public/storage ] || ln -s "$APP_DIR/storage/app/public" public/storage
chmod -R u+rwX,g+rwX storage bootstrap/cache database

# ---------------------------------------------------------------- point the subdomain at /public
step "Pointing $HOST at the app"
if [ -e "$HOME/domains/$HOST/public_html" ]; then
  # A website of its own (e.g. a free *.hostingersite.com temporary domain)
  WEB="$HOME/domains/$HOST/public_html"
elif [ -d "$HOME/domains/$ROOT_DOMAIN/public_html" ]; then
  # A subdomain living inside its parent domain's folder
  WEB="$HOME/domains/$ROOT_DOMAIN/public_html/$SUB"
else
  echo "Could not find a website folder for $HOST."
  echo "Folders that exist:"; ls "$HOME/domains" 2>/dev/null || true
  echo "Copy the site name exactly as listed above and run this again."; exit 1
fi
if [ -d "$WEB" ] && [ ! -L "$WEB" ]; then
  mv "$WEB" "$WEB.old-$(date +%Y%m%d%H%M%S)"
fi
ln -sfn "$APP_DIR/public" "$WEB"

# ---------------------------------------------------------------- caches
step "Optimising"
"$PHP" artisan optimize

cat <<DONE

============================================================
 Done!  Open:  https://$HOST
 Login: admin@bizemporia.com  (password: the one you chose)

 Last step - add this Cron Job in hPanel (Advanced > Cron Jobs,
 Custom, every minute) so proposals expire automatically:

   cd $APP_DIR && $PHP artisan schedule:run
============================================================
DONE
