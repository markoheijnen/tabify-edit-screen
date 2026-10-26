#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 3 ]; then
	printf 'Usage: %s <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-db-create]\n' "$0" >&2
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4:-localhost}
WP_VERSION=${5:-latest}
SKIP_DB_CREATE=${6:-false}
WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress}
TASK_TMP=$(mktemp -d "${TMPDIR:-/tmp}/tabify-wp-tests.XXXXXX")
trap 'rm -rf "$TASK_TMP"' EXIT

case "$WP_VERSION" in
	latest) ARCHIVE_URL='https://wordpress.org/latest.tar.gz' ;;
	*[!0-9.]*|'') printf 'Use latest or a numbered WordPress release.\n' >&2; exit 1 ;;
	*) ARCHIVE_URL="https://wordpress.org/wordpress-$WP_VERSION.tar.gz" ;;
esac

mkdir -p "$WP_CORE_DIR" "$WP_TESTS_DIR"
curl --fail --silent --show-error --location "$ARCHIVE_URL" --output "$TASK_TMP/wordpress.tar.gz"
tar -xzf "$TASK_TMP/wordpress.tar.gz" --strip-components=1 -C "$WP_CORE_DIR"
INSTALLED_VERSION=$(php -r 'require $argv[1] . "/wp-includes/version.php"; echo $wp_version;' "$WP_CORE_DIR")

for directory in includes data; do
	svn export --quiet --force "https://develop.svn.wordpress.org/tags/$INSTALLED_VERSION/tests/phpunit/$directory/" "$WP_TESTS_DIR/$directory"
done
curl --fail --silent --show-error --location "https://develop.svn.wordpress.org/tags/$INSTALLED_VERSION/wp-tests-config-sample.php" --output "$TASK_TMP/wp-tests-config.php"

php "$(dirname "$0")/configure-wp-tests.php" "$TASK_TMP/wp-tests-config.php" "$WP_TESTS_DIR/wp-tests-config.php" "$WP_CORE_DIR" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST"

if [ "$SKIP_DB_CREATE" != true ]; then
	if [[ ! "$DB_NAME" =~ ^[a-zA-Z0-9_]+$ ]]; then
		printf 'Database names may contain only letters, numbers, and underscores.\n' >&2
		exit 1
	fi
	DB_ARGS=(--user="$DB_USER")
	if [[ "$DB_HOST" == *:* ]]; then
		DB_ARGS+=(--host="${DB_HOST%%:*}")
		DB_PORT_OR_SOCKET=${DB_HOST#*:}
		if [[ "$DB_PORT_OR_SOCKET" =~ ^[0-9]+$ ]]; then
			DB_ARGS+=(--port="$DB_PORT_OR_SOCKET" --protocol=tcp)
		else
			DB_ARGS+=(--socket="$DB_PORT_OR_SOCKET")
		fi
	else
		DB_ARGS+=(--host="$DB_HOST")
	fi
	MYSQL_PWD="$DB_PASS" mysql "${DB_ARGS[@]}" --execute="CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;"
fi
printf 'WordPress %s tests installed in %s\n' "$INSTALLED_VERSION" "$WP_TESTS_DIR"
