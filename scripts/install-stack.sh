#!/usr/bin/env bash
# For new Ubuntu 24.04 installations.
# Usage:
#   sudo APP_DIR=/var/www/m.pbsys.co.ke APP_USER=timon APP_PASS='set-a-strong-password' bash scripts/install-stack.sh

set -euo pipefail
set +H

export DEBIAN_FRONTEND=noninteractive
export LC_ALL=C.UTF-8
export LANG=C.UTF-8

APP_USER="${APP_USER:-timon}"
APP_DIR="${APP_DIR:-/var/www/m.pbsys.co.ke}"
APP_NAME_VALUE="${APP_NAME:-Laravel}"
APP_URL_VALUE="${APP_URL:-https://m.pbsys.co.ke}"
DB_DATABASE_VALUE="${DB_DATABASE:-mpbsys_mpesa}"
DB_USERNAME_VALUE="${DB_USERNAME:-${APP_USER}}"
DB_PASSWORD_VALUE="${DB_PASSWORD:-${APP_PASS:-}}"
REDIS_PASSWORD_VALUE="${REDIS_PASSWORD:-${APP_PASS:-}}"
RABBITMQ_USER_VALUE="${RABBITMQ_USER:-${APP_USER}}"
RABBITMQ_PASSWORD_VALUE="${RABBITMQ_PASSWORD:-${APP_PASS:-}}"
RABBITMQ_C2B_QUEUE_VALUE="${RABBITMQ_C2B_QUEUE:-pbsys.mpesa.c2b.notifications}"
RABBITMQ_TRANSACTION_STATUS_QUEUE_VALUE="${RABBITMQ_TRANSACTION_STATUS_QUEUE:-pbsys.mpesa.transaction_status.results}"
MPESA_TRANSACTION_STATUS_INITIATOR_VALUE="${MPESA_TRANSACTION_STATUS_INITIATOR:-}"
MPESA_TRANSACTION_STATUS_CREDENTIAL_VALUE="${MPESA_TRANSACTION_STATUS_CREDENTIAL:-}"
MPESA_TRANSACTION_STATUS_IDENTIFIER_VALUE="${MPESA_TRANSACTION_STATUS_IDENTIFIER:-shortcode}"
MPESA_TRANSACTION_STATUS_REMARKS_VALUE="${MPESA_TRANSACTION_STATUS_REMARKS:-C2B notification enrichment}"
MPESA_TRANSACTION_STATUS_OCCASION_VALUE="${MPESA_TRANSACTION_STATUS_OCCASION:-C2B notification enrichment}"
MPESA_TRANSACTION_STATUS_WAIT_SECONDS_VALUE="${MPESA_TRANSACTION_STATUS_WAIT_SECONDS:-15}"

if [ -z "${APP_PASS:-}" ]; then
  printf "Password for MySQL, Redis, and RabbitMQ user '%s': " "${APP_USER}"
  stty -echo
  read -r APP_PASS
  stty echo
  printf "\n"
fi

if [ -z "${APP_PASS}" ]; then
  echo "APP_PASS cannot be empty."
  exit 1
fi

DB_PASSWORD_VALUE="${DB_PASSWORD:-${APP_PASS}}"
REDIS_PASSWORD_VALUE="${REDIS_PASSWORD:-${APP_PASS}}"
RABBITMQ_PASSWORD_VALUE="${RABBITMQ_PASSWORD:-${APP_PASS}}"

if [ "$(id -u)" -ne 0 ]; then
  echo "Run this script with sudo or as root."
  exit 1
fi

escape_mysql_string() {
  printf "%s" "$1" | sed "s/'/''/g"
}

MYSQL_APP_USER="$(escape_mysql_string "${APP_USER}")"
MYSQL_APP_PASS="$(escape_mysql_string "${APP_PASS}")"

escape_sed_replacement() {
  printf "%s" "$1" | sed -e 's/[\/&|\\]/\\&/g'
}

set_env_value() {
  env_file="$1"
  key="$2"
  value="$3"
  escaped_value="$(escape_sed_replacement "$value")"

  if grep -qE "^${key}=" "$env_file"; then
    sed -i "s|^${key}=.*|${key}=${escaped_value}|" "$env_file"
  else
    printf "%s=%s\n" "$key" "$value" >> "$env_file"
  fi
}

sync_project_env() {
  if [ ! -d "$APP_DIR" ]; then
    echo "App directory '${APP_DIR}' does not exist yet; skipping .env sync."
    return
  fi

  if [ ! -f "$APP_DIR/.env.example" ]; then
    echo "No .env.example found in '${APP_DIR}'; skipping .env sync."
    return
  fi

  if [ ! -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    chown "${APP_USER}:${APP_USER}" "$APP_DIR/.env" 2>/dev/null || true
    chmod 640 "$APP_DIR/.env" 2>/dev/null || true
  fi

  env_file="$APP_DIR/.env"

  set_env_value "$env_file" APP_NAME "$APP_NAME_VALUE"
  set_env_value "$env_file" APP_ENV production
  set_env_value "$env_file" APP_DEBUG false
  set_env_value "$env_file" APP_URL "$APP_URL_VALUE"
  set_env_value "$env_file" APP_DIR "$APP_DIR"
  set_env_value "$env_file" LOG_CHANNEL stack

  set_env_value "$env_file" DB_CONNECTION mysql
  set_env_value "$env_file" DB_HOST 127.0.0.1
  set_env_value "$env_file" DB_PORT 3306
  set_env_value "$env_file" DB_DATABASE "$DB_DATABASE_VALUE"
  set_env_value "$env_file" DB_USERNAME "$DB_USERNAME_VALUE"
  set_env_value "$env_file" DB_PASSWORD "$DB_PASSWORD_VALUE"

  set_env_value "$env_file" BROADCAST_DRIVER log
  set_env_value "$env_file" CACHE_DRIVER file
  set_env_value "$env_file" QUEUE_CONNECTION sync
  set_env_value "$env_file" SESSION_DRIVER file
  set_env_value "$env_file" SESSION_LIFETIME 120

  set_env_value "$env_file" RABBITMQ_HOST 127.0.0.1
  set_env_value "$env_file" RABBITMQ_PORT 5672
  set_env_value "$env_file" RABBITMQ_USER "$RABBITMQ_USER_VALUE"
  set_env_value "$env_file" RABBITMQ_PASSWORD "$RABBITMQ_PASSWORD_VALUE"
  set_env_value "$env_file" RABBITMQ_VHOST /
  set_env_value "$env_file" RABBITMQ_C2B_QUEUE "$RABBITMQ_C2B_QUEUE_VALUE"
  set_env_value "$env_file" RABBITMQ_TRANSACTION_STATUS_QUEUE "$RABBITMQ_TRANSACTION_STATUS_QUEUE_VALUE"

  set_env_value "$env_file" MPESA_TRANSACTION_STATUS_INITIATOR "$MPESA_TRANSACTION_STATUS_INITIATOR_VALUE"
  set_env_value "$env_file" MPESA_TRANSACTION_STATUS_CREDENTIAL "$MPESA_TRANSACTION_STATUS_CREDENTIAL_VALUE"
  set_env_value "$env_file" MPESA_TRANSACTION_STATUS_IDENTIFIER "$MPESA_TRANSACTION_STATUS_IDENTIFIER_VALUE"
  set_env_value "$env_file" MPESA_TRANSACTION_STATUS_REMARKS "$MPESA_TRANSACTION_STATUS_REMARKS_VALUE"
  set_env_value "$env_file" MPESA_TRANSACTION_STATUS_OCCASION "$MPESA_TRANSACTION_STATUS_OCCASION_VALUE"
  set_env_value "$env_file" MPESA_TRANSACTION_STATUS_WAIT_SECONDS "$MPESA_TRANSACTION_STATUS_WAIT_SECONDS_VALUE"

  set_env_value "$env_file" REDIS_HOST 127.0.0.1
  set_env_value "$env_file" REDIS_PASSWORD "$REDIS_PASSWORD_VALUE"
  set_env_value "$env_file" REDIS_PORT 6379

  set_env_value "$env_file" MAIL_MAILER "${MAIL_MAILER:-smtp}"
  set_env_value "$env_file" MAIL_HOST "${MAIL_HOST:-smtp.mailtrap.io}"
  set_env_value "$env_file" MAIL_PORT "${MAIL_PORT:-2525}"
  set_env_value "$env_file" MAIL_USERNAME "${MAIL_USERNAME:-null}"
  set_env_value "$env_file" MAIL_PASSWORD "${MAIL_PASSWORD:-null}"
  set_env_value "$env_file" MAIL_ENCRYPTION "${MAIL_ENCRYPTION:-null}"
  set_env_value "$env_file" MAIL_FROM_ADDRESS "${MAIL_FROM_ADDRESS:-null}"
  set_env_value "$env_file" MAIL_FROM_NAME "${MAIL_FROM_NAME:-${APP_NAME_VALUE}}"

  set_env_value "$env_file" AWS_ACCESS_KEY_ID "${AWS_ACCESS_KEY_ID:-}"
  set_env_value "$env_file" AWS_SECRET_ACCESS_KEY "${AWS_SECRET_ACCESS_KEY:-}"
  set_env_value "$env_file" AWS_DEFAULT_REGION "${AWS_DEFAULT_REGION:-us-east-1}"
  set_env_value "$env_file" AWS_BUCKET "${AWS_BUCKET:-}"

  set_env_value "$env_file" PUSHER_APP_ID "${PUSHER_APP_ID:-}"
  set_env_value "$env_file" PUSHER_APP_KEY "${PUSHER_APP_KEY:-}"
  set_env_value "$env_file" PUSHER_APP_SECRET "${PUSHER_APP_SECRET:-}"
  set_env_value "$env_file" PUSHER_APP_CLUSTER "${PUSHER_APP_CLUSTER:-mt1}"
  set_env_value "$env_file" MIX_PUSHER_APP_KEY '${PUSHER_APP_KEY}'
  set_env_value "$env_file" MIX_PUSHER_APP_CLUSTER '${PUSHER_APP_CLUSTER}'

  if [ -f "$APP_DIR/artisan" ] && [ -f "$APP_DIR/vendor/autoload.php" ] && grep -qE '^APP_KEY=$' "$env_file"; then
    (cd "$APP_DIR" && php artisan key:generate --force)
  fi

  echo "Synced Laravel .env keys in '${env_file}'."
}

apt update
apt install -y \
  ca-certificates \
  curl \
  gnupg \
  lsb-release \
  software-properties-common \
  apache2 \
  mysql-server \
  redis-server \
  rabbitmq-server

systemctl enable --now apache2 mysql redis-server rabbitmq-server

add-apt-repository -y ppa:ondrej/php
apt update
apt install -y \
  php7.4 \
  php7.4-cli \
  php7.4-common \
  php7.4-mysql \
  php7.4-xml \
  php7.4-curl \
  php7.4-mbstring \
  php7.4-zip \
  php7.4-gd \
  php7.4-intl \
  php7.4-bcmath \
  php7.4-soap \
  php7.4-readline \
  php7.4-redis \
  libapache2-mod-php7.4

if [ -x /usr/sbin/a2dismod ]; then
  a2dismod php8.3 >/dev/null 2>&1 || true
fi

a2enmod rewrite
a2enmod php7.4

cat >/etc/apache2/conf-available/rewrite-override.conf <<'EOF'
<Directory /var/www/>
    AllowOverride All
    Require all granted
</Directory>
EOF

a2enconf rewrite-override
systemctl restart apache2

update-alternatives --set php /usr/bin/php7.4 || true

mysql <<SQL
CREATE USER IF NOT EXISTS '${MYSQL_APP_USER}'@'%' IDENTIFIED BY '${MYSQL_APP_PASS}';
ALTER USER '${MYSQL_APP_USER}'@'%' IDENTIFIED BY '${MYSQL_APP_PASS}';
GRANT ALL PRIVILEGES ON *.* TO '${MYSQL_APP_USER}'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL

touch /var/lib/redis/users.acl
chown redis:redis /var/lib/redis/users.acl
chmod 640 /var/lib/redis/users.acl

if grep -qE '^\s*aclfile\s+' /etc/redis/redis.conf; then
  sed -i 's|^\s*aclfile\s\+.*|aclfile /var/lib/redis/users.acl|' /etc/redis/redis.conf
else
  printf '\naclfile /var/lib/redis/users.acl\n' >> /etc/redis/redis.conf
fi

systemctl restart redis-server

redis-cli ACL SETUSER "${APP_USER}" on ">${APP_PASS}" "~*" "&*" "+@all"
redis-cli ACL SETUSER default off
redis-cli --user "${APP_USER}" -a "${APP_PASS}" ACL SAVE

rabbitmq-plugins enable rabbitmq_management

if rabbitmqctl list_users | awk '{print $1}' | grep -qx "${APP_USER}"; then
  rabbitmqctl change_password "${APP_USER}" "${APP_PASS}"
else
  rabbitmqctl add_user "${APP_USER}" "${APP_PASS}"
fi

rabbitmqctl set_user_tags "${APP_USER}" administrator
rabbitmqctl set_permissions -p / "${APP_USER}" ".*" ".*" ".*"

if rabbitmqctl list_users | awk '{print $1}' | grep -qx guest; then
  rabbitmqctl delete_user guest
fi

systemctl restart rabbitmq-server
sync_project_env

echo
echo "Done."
echo "Apache:  enabled with mod_rewrite"
echo "PHP:     $(php -v | head -n1)"
echo "MySQL:   user '${APP_USER}'@'%' created"
echo "Redis:   ACL user '${APP_USER}' created, default user disabled"
echo "RabbitMQ user '${APP_USER}' created, management UI on :15672"
echo "App dir: ${APP_DIR}"
echo "App env: synced when '${APP_DIR}/.env.example' exists"
echo
echo "Checks:"
echo "  apache2ctl -M | grep rewrite"
echo "  php -v"
echo "  mysql -u ${APP_USER} -p -e 'SELECT VERSION();'"
echo "  redis-cli --user ${APP_USER} -a '***' PING"
echo "  rabbitmqctl list_users"
