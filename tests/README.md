# Tests

## WordPress integration tests

Install development dependencies with `composer install`. The suite uses WordPress's own test library and a **disposable database**:

```sh
bash bin/install-wp-tests.sh tabify_test root '' 127.0.0.1 latest
composer test
```

The installer downloads the matching core and test library over HTTPS. Set `WP_CORE_DIR` and `WP_TESTS_DIR` to use dedicated directories. Pass `true` as its sixth argument when the database already exists. The test suite drops tables in the configured test database; use a separate database from any working site.

CI runs the suite with MySQL, PHP 8.0–8.5, and WordPress 6.9/latest. Tests cover PHP compatibility, permissions, and settings rendering.

When Docker/MySQL is unavailable, the official SQLite Database Integration drop-in can run the suite locally. Put the drop-in in the temporary WordPress installation's `wp-content/db.php`, and define `FQDB` in its `wp-tests-config.php` to point to a disposable SQLite file. This provides local WordPress integration coverage; CI retains MySQL coverage.

## Browser tests

```sh
npm --prefix tests/browser ci
cd tests/browser
npx playwright install chromium
npm test
```

To use an installed Chrome instead, run `PLAYWRIGHT_CHANNEL=chrome npm --prefix tests/browser test` from the repository root.

These tests run the production JavaScript with jQuery and jQuery UI in a settings-page fixture. They exercise moving meta boxes between tabs; PHP integration tests verify settings rendering.
