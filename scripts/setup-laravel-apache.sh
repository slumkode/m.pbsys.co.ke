#!/usr/bin/env bash
# Configure Apache, MySQL, Composer, Laravel, and optional HTTPS for this app.
# For a new server, run scripts/install-stack.sh first, then this script.
#
# Example:
#   sudo DOMAIN=m.pbsys.co.ke PROJECT_DIR=/var/www/m.pbsys.co.ke DB_PASS='set-a-strong-password' bash scripts/setup-laravel-apache.sh

set -Eeuo pipefail
set +H

############################
# Runtime configuration
############################

DOMAIN="${DOMAIN:-m.pbsys.co.ke}"
PROJECT_DIR="${PROJECT_DIR:-${APP_DIR:-/var/www/m.pbsys.co.ke}}"
APP_DIR="${APP_DIR:-${PROJECT_DIR}}"
PHP_VERSION="${PHP_VERSION:-7.4}"

SSL_EMAIL="${SSL_EMAIL:-}"
ENABLE_SSL="${ENABLE_SSL:-0}"
DISABLE_DEFAULT_SITE="${DISABLE_DEFAULT_SITE:-1}"

DB_NAME="${DB_NAME:-mlp_pbsys}"
DB_USER="${DB_USER:-mlp_pbsys}"
DB_PASS="${DB_PASS:-}"
DB_IMPORT_FILE="${DB_IMPORT_FILE:-}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-1}"
RUN_SEEDER="${RUN_SEEDER:-0}"

WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"

APP_ENV_VALUE="${APP_ENV:-production}"
APP_DEBUG_VALUE="${APP_DEBUG:-false}"
APP_NAME_VALUE="${APP_NAME:-MLP pbsys}"
APP_URL_VALUE="${APP_URL:-https://${DOMAIN}}"

BROADCAST_DRIVER_VALUE="${BROADCAST_DRIVER:-log}"
CACHE_DRIVER_VALUE="${CACHE_DRIVER:-file}"
QUEUE_CONNECTION_VALUE="${QUEUE_CONNECTION:-sync}"
SESSION_DRIVER_VALUE="${SESSION_DRIVER:-file}"
SESSION_LIFETIME_VALUE="${SESSION_LIFETIME:-120}"

RABBITMQ_HOST_VALUE="${RABBITMQ_HOST:-127.0.0.1}"
RABBITMQ_PORT_VALUE="${RABBITMQ_PORT:-5672}"
RABBITMQ_USER_VALUE="${RABBITMQ_USER:-${DB_USER}}"
RABBITMQ_PASSWORD_VALUE="${RABBITMQ_PASSWORD:-${DB_PASS}}"
RABBITMQ_VHOST_VALUE="${RABBITMQ_VHOST:-/}"
RABBITMQ_C2B_QUEUE_VALUE="${RABBITMQ_C2B_QUEUE:-pbsys.mpesa.c2b.notifications}"
RABBITMQ_TRANSACTION_STATUS_QUEUE_VALUE="${RABBITMQ_TRANSACTION_STATUS_QUEUE:-pbsys.mpesa.transaction_status.results}"

MPESA_TRANSACTION_STATUS_INITIATOR_VALUE="${MPESA_TRANSACTION_STATUS_INITIATOR:-}"
MPESA_TRANSACTION_STATUS_CREDENTIAL_VALUE="${MPESA_TRANSACTION_STATUS_CREDENTIAL:-}"
MPESA_TRANSACTION_STATUS_IDENTIFIER_VALUE="${MPESA_TRANSACTION_STATUS_IDENTIFIER:-shortcode}"
MPESA_TRANSACTION_STATUS_REMARKS_VALUE="${MPESA_TRANSACTION_STATUS_REMARKS:-C2B notification enrichment}"
MPESA_TRANSACTION_STATUS_OCCASION_VALUE="${MPESA_TRANSACTION_STATUS_OCCASION:-C2B notification enrichment}"
MPESA_TRANSACTION_STATUS_WAIT_SECONDS_VALUE="${MPESA_TRANSACTION_STATUS_WAIT_SECONDS:-15}"

REDIS_HOST_VALUE="${REDIS_HOST:-127.0.0.1}"
REDIS_PASSWORD_VALUE="${REDIS_PASSWORD:-${DB_PASS}}"
REDIS_PORT_VALUE="${REDIS_PORT:-6379}"

MAIL_MAILER_VALUE="${MAIL_MAILER:-smtp}"
MAIL_HOST_VALUE="${MAIL_HOST:-smtp.mailtrap.io}"
MAIL_PORT_VALUE="${MAIL_PORT:-2525}"
MAIL_USERNAME_VALUE="${MAIL_USERNAME:-null}"
MAIL_PASSWORD_VALUE="${MAIL_PASSWORD:-null}"
MAIL_ENCRYPTION_VALUE="${MAIL_ENCRYPTION:-null}"
MAIL_FROM_ADDRESS_VALUE="${MAIL_FROM_ADDRESS:-null}"
MAIL_FROM_NAME_VALUE="${MAIL_FROM_NAME:-${APP_NAME_VALUE}}"

AWS_ACCESS_KEY_ID_VALUE="${AWS_ACCESS_KEY_ID:-}"
AWS_SECRET_ACCESS_KEY_VALUE="${AWS_SECRET_ACCESS_KEY:-}"
AWS_DEFAULT_REGION_VALUE="${AWS_DEFAULT_REGION:-us-east-1}"
AWS_BUCKET_VALUE="${AWS_BUCKET:-}"

PUSHER_APP_ID_VALUE="${PUSHER_APP_ID:-}"
PUSHER_APP_KEY_VALUE="${PUSHER_APP_KEY:-}"
PUSHER_APP_SECRET_VALUE="${PUSHER_APP_SECRET:-}"
PUSHER_APP_CLUSTER_VALUE="${PUSHER_APP_CLUSTER:-mt1}"

COMPOSER_FLAGS="${COMPOSER_FLAGS:---no-dev --prefer-dist --optimize-autoloader --no-interaction}"

SYNC_PROJECT="${SYNC_PROJECT:-0}"
SOURCE_SSH_USER="${SOURCE_SSH_USER:-root}"
SOURCE_SSH_HOST="${SOURCE_SSH_HOST:-OLD_SERVER_IP}"
SOURCE_SSH_PORT="${SOURCE_SSH_PORT:-22}"
SOURCE_DIR="${SOURCE_DIR:-/var/www/mpesa}"

APACHE_CONF="/etc/apache2/sites-available/${DOMAIN}.conf"
ENV_FILE="${PROJECT_DIR}/.env"

log() {
  printf '\n[%s] %s\n' "$(date '+%F %T')" "$*"
}

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

require_root() {
  [ "${EUID:-$(id -u)}" -eq 0 ] || fail "Run as root."
}

require_dir() {
  [ -d "$1" ] || fail "Directory not found: $1"
}

require_file() {
  [ -f "$1" ] || fail "File not found: $1"
}

require_secret_inputs() {
  if [ -z "${DB_PASS}" ]; then
    printf "Database password for '%s': " "${DB_USER}"
    stty -echo
    read -r DB_PASS
    stty echo
    printf "\n"
  fi

  [ -n "${DB_PASS}" ] || fail "DB_PASS cannot be empty."

  RABBITMQ_PASSWORD_VALUE="${RABBITMQ_PASSWORD:-${DB_PASS}}"
  REDIS_PASSWORD_VALUE="${REDIS_PASSWORD:-${DB_PASS}}"
}

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

dotenv_quote() {
  printf "'%s'" "$(printf '%s' "$1" | sed "s/'/'\\\\''/g")"
}

set_env_value() {
  local key="$1"
  local value="$2"
  local file="$3"
  local escaped_key escaped_val

  escaped_key="$(printf '%s' "$key" | sed 's/[][\/.^$*]/\\&/g')"
  escaped_val="$(printf '%s' "$value" | sed 's/[\/&]/\\&/g')"

  if grep -qE "^${escaped_key}=" "$file"; then
    sed -i "s/^${escaped_key}=.*/${key}=${escaped_val}/" "$file"
  else
    printf '%s=%s\n' "$key" "$value" >> "$file"
  fi
}

install_packages() {
  log "Installing system packages"
  apt update

  apt install -y \
    apache2 \
    mysql-server \
    certbot \
    python3-certbot-apache \
    git \
    unzip \
    curl \
    ca-certificates \
    gnupg \
    lsb-release \
    rsync \
    software-properties-common

  if ! apt-cache show "php${PHP_VERSION}" >/dev/null 2>&1; then
    add-apt-repository -y ppa:ondrej/php
    apt update
  fi

  apt install -y \
    "php${PHP_VERSION}" \
    "php${PHP_VERSION}-cli" \
    "libapache2-mod-php${PHP_VERSION}" \
    "php${PHP_VERSION}-common" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-soap" \
    "php${PHP_VERSION}-readline" \
    "php${PHP_VERSION}-redis"

  systemctl enable --now apache2 mysql
  update-alternatives --set php "/usr/bin/php${PHP_VERSION}" || true
}

sync_project() {
  mkdir -p "${PROJECT_DIR}"

  if [ "${SYNC_PROJECT}" != "1" ]; then
    log "Skipping project sync"
    return 0
  fi

  log "Syncing project from old server"
  rsync -az --delete \
    -e "ssh -p ${SOURCE_SSH_PORT}" \
    --exclude=".env" \
    --exclude=".git/" \
    --exclude=".vscode/" \
    --exclude="vendor/" \
    --exclude="node_modules/" \
    --exclude="storage/logs/*" \
    --exclude="storage/framework/cache/*" \
    --exclude="storage/framework/sessions/*" \
    --exclude="storage/framework/views/*" \
    "${SOURCE_SSH_USER}@${SOURCE_SSH_HOST}:${SOURCE_DIR}/" \
    "${PROJECT_DIR}/"
}

validate_project_files() {
  log "Validating Laravel project files"
  require_dir "${PROJECT_DIR}"
  require_file "${PROJECT_DIR}/composer.json"
  require_file "${PROJECT_DIR}/artisan"
  require_dir "${PROJECT_DIR}/public"
}

configure_apache() {
  log "Configuring Apache vhost"

  if [ -x /usr/sbin/a2dismod ]; then
    a2dismod php8.3 >/dev/null 2>&1 || true
  fi

  a2enmod rewrite ssl headers "php${PHP_VERSION}"

  cat > "${APACHE_CONF}" <<EOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAdmin ${SSL_EMAIL:-webmaster@localhost}
    DocumentRoot ${PROJECT_DIR}/public

    <Directory ${PROJECT_DIR}/public>
        AllowOverride All
        Require all granted
        Options FollowSymLinks
        DirectoryIndex index.php index.html
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}-access.log combined
</VirtualHost>
EOF

  a2ensite "${DOMAIN}.conf"

  if [ "${DISABLE_DEFAULT_SITE}" = "1" ]; then
    a2dissite 000-default.conf || true
  fi

  apache2ctl configtest
  systemctl reload apache2

  if command -v ufw >/dev/null 2>&1; then
    ufw allow 'Apache Full' >/dev/null 2>&1 || true
  fi
}

prepare_env_file() {
  log "Preparing .env"

  cd "${PROJECT_DIR}"

  if [ ! -f "${ENV_FILE}" ] && [ -f "${PROJECT_DIR}/.env.example" ]; then
    cp "${PROJECT_DIR}/.env.example" "${ENV_FILE}"
  fi

  [ -f "${ENV_FILE}" ] || touch "${ENV_FILE}"

  set_env_value "APP_NAME" "$(dotenv_quote "${APP_NAME_VALUE}")" "${ENV_FILE}"
  set_env_value "APP_ENV" "${APP_ENV_VALUE}" "${ENV_FILE}"
  set_env_value "APP_DEBUG" "${APP_DEBUG_VALUE}" "${ENV_FILE}"
  set_env_value "APP_URL" "$(dotenv_quote "${APP_URL_VALUE}")" "${ENV_FILE}"
  set_env_value "APP_DIR" "$(dotenv_quote "${APP_DIR}")" "${ENV_FILE}"
  set_env_value "LOG_CHANNEL" "stack" "${ENV_FILE}"

  set_env_value "DB_CONNECTION" "mysql" "${ENV_FILE}"
  set_env_value "DB_HOST" "127.0.0.1" "${ENV_FILE}"
  set_env_value "DB_PORT" "3306" "${ENV_FILE}"
  set_env_value "DB_DATABASE" "$(dotenv_quote "${DB_NAME}")" "${ENV_FILE}"
  set_env_value "DB_USERNAME" "$(dotenv_quote "${DB_USER}")" "${ENV_FILE}"
  set_env_value "DB_PASSWORD" "$(dotenv_quote "${DB_PASS}")" "${ENV_FILE}"

  set_env_value "BROADCAST_DRIVER" "${BROADCAST_DRIVER_VALUE}" "${ENV_FILE}"
  set_env_value "CACHE_DRIVER" "${CACHE_DRIVER_VALUE}" "${ENV_FILE}"
  set_env_value "QUEUE_CONNECTION" "${QUEUE_CONNECTION_VALUE}" "${ENV_FILE}"
  set_env_value "SESSION_DRIVER" "${SESSION_DRIVER_VALUE}" "${ENV_FILE}"
  set_env_value "SESSION_LIFETIME" "${SESSION_LIFETIME_VALUE}" "${ENV_FILE}"

  set_env_value "RABBITMQ_HOST" "${RABBITMQ_HOST_VALUE}" "${ENV_FILE}"
  set_env_value "RABBITMQ_PORT" "${RABBITMQ_PORT_VALUE}" "${ENV_FILE}"
  set_env_value "RABBITMQ_USER" "$(dotenv_quote "${RABBITMQ_USER_VALUE}")" "${ENV_FILE}"
  set_env_value "RABBITMQ_PASSWORD" "$(dotenv_quote "${RABBITMQ_PASSWORD_VALUE}")" "${ENV_FILE}"
  set_env_value "RABBITMQ_VHOST" "${RABBITMQ_VHOST_VALUE}" "${ENV_FILE}"
  set_env_value "RABBITMQ_C2B_QUEUE" "${RABBITMQ_C2B_QUEUE_VALUE}" "${ENV_FILE}"
  set_env_value "RABBITMQ_TRANSACTION_STATUS_QUEUE" "${RABBITMQ_TRANSACTION_STATUS_QUEUE_VALUE}" "${ENV_FILE}"

  set_env_value "MPESA_TRANSACTION_STATUS_INITIATOR" "$(dotenv_quote "${MPESA_TRANSACTION_STATUS_INITIATOR_VALUE}")" "${ENV_FILE}"
  set_env_value "MPESA_TRANSACTION_STATUS_CREDENTIAL" "$(dotenv_quote "${MPESA_TRANSACTION_STATUS_CREDENTIAL_VALUE}")" "${ENV_FILE}"
  set_env_value "MPESA_TRANSACTION_STATUS_IDENTIFIER" "${MPESA_TRANSACTION_STATUS_IDENTIFIER_VALUE}" "${ENV_FILE}"
  set_env_value "MPESA_TRANSACTION_STATUS_REMARKS" "$(dotenv_quote "${MPESA_TRANSACTION_STATUS_REMARKS_VALUE}")" "${ENV_FILE}"
  set_env_value "MPESA_TRANSACTION_STATUS_OCCASION" "$(dotenv_quote "${MPESA_TRANSACTION_STATUS_OCCASION_VALUE}")" "${ENV_FILE}"
  set_env_value "MPESA_TRANSACTION_STATUS_WAIT_SECONDS" "${MPESA_TRANSACTION_STATUS_WAIT_SECONDS_VALUE}" "${ENV_FILE}"

  set_env_value "REDIS_HOST" "${REDIS_HOST_VALUE}" "${ENV_FILE}"
  set_env_value "REDIS_PASSWORD" "$(dotenv_quote "${REDIS_PASSWORD_VALUE}")" "${ENV_FILE}"
  set_env_value "REDIS_PORT" "${REDIS_PORT_VALUE}" "${ENV_FILE}"

  set_env_value "MAIL_MAILER" "${MAIL_MAILER_VALUE}" "${ENV_FILE}"
  set_env_value "MAIL_HOST" "${MAIL_HOST_VALUE}" "${ENV_FILE}"
  set_env_value "MAIL_PORT" "${MAIL_PORT_VALUE}" "${ENV_FILE}"
  set_env_value "MAIL_USERNAME" "${MAIL_USERNAME_VALUE}" "${ENV_FILE}"
  set_env_value "MAIL_PASSWORD" "${MAIL_PASSWORD_VALUE}" "${ENV_FILE}"
  set_env_value "MAIL_ENCRYPTION" "${MAIL_ENCRYPTION_VALUE}" "${ENV_FILE}"
  set_env_value "MAIL_FROM_ADDRESS" "${MAIL_FROM_ADDRESS_VALUE}" "${ENV_FILE}"
  set_env_value "MAIL_FROM_NAME" "$(dotenv_quote "${MAIL_FROM_NAME_VALUE}")" "${ENV_FILE}"

  set_env_value "AWS_ACCESS_KEY_ID" "${AWS_ACCESS_KEY_ID_VALUE}" "${ENV_FILE}"
  set_env_value "AWS_SECRET_ACCESS_KEY" "${AWS_SECRET_ACCESS_KEY_VALUE}" "${ENV_FILE}"
  set_env_value "AWS_DEFAULT_REGION" "${AWS_DEFAULT_REGION_VALUE}" "${ENV_FILE}"
  set_env_value "AWS_BUCKET" "${AWS_BUCKET_VALUE}" "${ENV_FILE}"

  set_env_value "PUSHER_APP_ID" "${PUSHER_APP_ID_VALUE}" "${ENV_FILE}"
  set_env_value "PUSHER_APP_KEY" "${PUSHER_APP_KEY_VALUE}" "${ENV_FILE}"
  set_env_value "PUSHER_APP_SECRET" "${PUSHER_APP_SECRET_VALUE}" "${ENV_FILE}"
  set_env_value "PUSHER_APP_CLUSTER" "${PUSHER_APP_CLUSTER_VALUE}" "${ENV_FILE}"
  set_env_value "MIX_PUSHER_APP_KEY" '"${PUSHER_APP_KEY}"' "${ENV_FILE}"
  set_env_value "MIX_PUSHER_APP_CLUSTER" '"${PUSHER_APP_CLUSTER}"' "${ENV_FILE}"
}

create_mysql_database_and_user() {
  log "Creating MySQL database and app user"

  local db_name_esc db_user_esc db_pass_esc
  db_name_esc="$(sql_escape "${DB_NAME}")"
  db_user_esc="$(sql_escape "${DB_USER}")"
  db_pass_esc="$(sql_escape "${DB_PASS}")"

  mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${db_name_esc}\`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${db_user_esc}'@'localhost' IDENTIFIED BY '${db_pass_esc}';
ALTER USER '${db_user_esc}'@'localhost' IDENTIFIED BY '${db_pass_esc}';
GRANT ALL PRIVILEGES ON \`${db_name_esc}\`.* TO '${db_user_esc}'@'localhost';

CREATE USER IF NOT EXISTS '${db_user_esc}'@'127.0.0.1' IDENTIFIED BY '${db_pass_esc}';
ALTER USER '${db_user_esc}'@'127.0.0.1' IDENTIFIED BY '${db_pass_esc}';
GRANT ALL PRIVILEGES ON \`${db_name_esc}\`.* TO '${db_user_esc}'@'127.0.0.1';

FLUSH PRIVILEGES;
SQL
}

install_local_composer() {
  log "Installing project-local Composer PHAR"

  cd "${PROJECT_DIR}"
  rm -f composer-setup.php

  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --2.2 --filename=composer.phar
  rm -f composer-setup.php
  chmod +x composer.phar
}

install_composer_dependencies() {
  log "Installing Composer dependencies"

  cd "${PROJECT_DIR}"
  export COMPOSER_ALLOW_SUPERUSER=1

  if [ -f "${PROJECT_DIR}/composer.phar" ]; then
    php "${PROJECT_DIR}/composer.phar" install ${COMPOSER_FLAGS}
  else
    install_local_composer
    php "${PROJECT_DIR}/composer.phar" install ${COMPOSER_FLAGS}
  fi
}

fix_laravel_permissions() {
  log "Fixing Laravel permissions"

  cd "${PROJECT_DIR}"

  mkdir -p bootstrap/cache
  mkdir -p storage/framework/cache
  mkdir -p storage/framework/sessions
  mkdir -p storage/framework/views
  mkdir -p storage/logs

  chown -R "${WEB_USER}:${WEB_GROUP}" "${PROJECT_DIR}"
  chmod -R ug+rwX "${PROJECT_DIR}/storage" "${PROJECT_DIR}/bootstrap/cache"
}

generate_app_key_if_needed() {
  log "Ensuring APP_KEY exists"

  cd "${PROJECT_DIR}"

  if ! grep -qE '^APP_KEY=.+$' "${ENV_FILE}"; then
    php artisan key:generate --force
  fi
}

optional_db_import() {
  cd "${PROJECT_DIR}"

  if [ -n "${DB_IMPORT_FILE}" ]; then
    log "Importing database dump: ${DB_IMPORT_FILE}"
    require_file "${DB_IMPORT_FILE}"

    case "${DB_IMPORT_FILE}" in
      *.sql)
        mysql "${DB_NAME}" < "${DB_IMPORT_FILE}"
        ;;
      *.sql.gz)
        gunzip -c "${DB_IMPORT_FILE}" | mysql "${DB_NAME}"
        ;;
      *)
        fail "Unsupported DB_IMPORT_FILE format. Use .sql or .sql.gz"
        ;;
    esac
  fi
}

laravel_finalize() {
  log "Running Laravel finalize commands"

  cd "${PROJECT_DIR}"

  php artisan optimize:clear || true
  php artisan storage:link || true

  if [ "${RUN_MIGRATIONS}" = "1" ]; then
    php artisan migrate --force
  fi

  if [ "${RUN_SEEDER}" = "1" ]; then
    php artisan db:seed --force
  fi
}

enable_https() {
  [ "${ENABLE_SSL}" = "1" ] || return 0
  [ -n "${SSL_EMAIL}" ] || fail "SSL_EMAIL is required when ENABLE_SSL=1"

  log "Requesting/installing Let's Encrypt certificate"

  certbot --apache \
    --non-interactive \
    --agree-tos \
    --keep-until-expiring \
    --redirect \
    -m "${SSL_EMAIL}" \
    -d "${DOMAIN}"
}

show_summary() {
  cat <<EOF

Done.

Domain:        ${DOMAIN}
Project dir:   ${PROJECT_DIR}
Public dir:    ${PROJECT_DIR}/public
Apache conf:   ${APACHE_CONF}
DB name:       ${DB_NAME}
DB user:       ${DB_USER}
HTTPS:         $( [ "${ENABLE_SSL}" = "1" ] && echo enabled || echo skipped )

Useful checks:
  ls -la ${PROJECT_DIR}
  php -v
  php ${PROJECT_DIR}/composer.phar --version
  apache2ctl -S
  systemctl status apache2 mysql
EOF
}

main() {
  require_root
  require_secret_inputs
  install_packages
  sync_project
  validate_project_files
  configure_apache
  prepare_env_file
  create_mysql_database_and_user
  install_composer_dependencies
  fix_laravel_permissions
  generate_app_key_if_needed
  optional_db_import
  laravel_finalize
  enable_https
  show_summary
}

main "$@"
