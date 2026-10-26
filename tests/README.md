# Tests

## Static analysis and coding standards

Install the pinned development tools with `composer install`, then run:

```sh
composer check     # WordPress PHP coding standards and PHPStan
composer phpcs     # Coding standards only
composer phpcbf    # Apply automatic coding-standard fixes
composer phpstan   # Static analysis only
```

Both checks cover the plugin entry point, `inc/`, and `features/`. PHPStan runs at level 5, targets PHP 8.0, and uses the WordPress extension and stubs without loading a WordPress installation. There is no error baseline. Defensive checks remain in place for malformed options and filter values, even when upstream PHPDoc promises a valid type.

PHPCS uses the full `WordPress` standard, including documentation, escaping, request validation, and translations. Existing class-file names are retained to preserve include paths. Small inline exceptions document read-only navigation, trusted HTML renderers, settings-group sanitizers, and legacy core/plugin integration behavior. Errors and warnings fail the check.

GitHub Actions runs `composer check` in a dedicated job without a database or Docker. Integration tests and browser tests run separately. A failed job reports a failing check; requiring it before merges is a repository branch-protection setting.

## WordPress integration tests

Install development dependencies with `composer install`. The suite uses WordPress's own test library and a **disposable database**:

```sh
bash bin/install-wp-tests.sh tabify_test root '' 127.0.0.1 latest
composer test
```

The installer downloads the matching core and test library over HTTPS. Set `WP_CORE_DIR` and `WP_TESTS_DIR` to use dedicated directories. Pass `true` as its sixth argument when the database already exists. The test suite drops tables in the configured test database; use a separate database from any working site.

CI runs the suite with MySQL, PHP 8.0–8.5, and WordPress 6.9/latest. Tests cover live detection before tabs are enabled, expired and malformed cache entries, environment invalidation, nonce/capability checks, language and user isolation, cached box restoration, permissions, and settings rendering.

When Docker/MySQL is unavailable, the official SQLite Database Integration drop-in can run the suite locally. Put the drop-in in the temporary WordPress installation's `wp-content/db.php`, and define `FQDB` in its `wp-tests-config.php` to point to a disposable SQLite file. This provides local WordPress integration coverage; CI retains MySQL coverage.

## Browser tests

```sh
npm --prefix tests/browser ci
cd tests/browser
npx playwright install chromium
npm test
```

To use an installed Chrome instead, run `PLAYWRIGHT_CHANNEL=chrome npm --prefix tests/browser test` from the repository root.

These tests run the production JavaScript with jQuery and jQuery UI in a settings-page fixture. Network responses are controlled so success, failure, retry, duplicate requests, and concurrent post-type requests are reproducible. They also exercise dragging boxes, sorting tabs with role restrictions, and adding/deleting tabs. PHP integration tests separately verify the generated controls and detection responses.

## WPML / ACF

Language context is simulated through WPML's documented `wpml_current_language` filter. This verifies that one language cannot reuse another language's detected boxes and that detection links retain the selected language. Saving settings also preserves configured IDs that are currently unavailable.

Issue #69 still needs an end-to-end check against current WPML and ACF releases. Translated field groups may use different meta-box IDs; cache isolation does not map those IDs automatically. Keep that issue open until a real translated-field-group installation is tested.
