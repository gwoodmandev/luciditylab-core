<?php
/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here. You can see a
 * list of the available settings in vendor/craftcms/cms/src/config/GeneralConfig.php.
 *
 * @see \craft\config\GeneralConfig
 * @link https://craftcms.com/docs/5.x/reference/config/general.html
 */

use craft\config\GeneralConfig;
use craft\helpers\App;

return GeneralConfig::create()
    // Set the default week start day for date pickers (0 = Sunday, 1 = Monday, etc.)
    ->defaultWeekStartDay(1)
    // Prevent generated URLs from including "index.php"
    ->omitScriptNameInUrls()
    // Preload Single entries as Twig variables
    ->preloadSingles()
    // Prevent user enumeration attacks
    ->preventUserEnumeration()
    // Set the @webroot alias so the clear-caches command knows where to find CP resources
    ->aliases([
        '@webroot' => dirname(__DIR__) . '/web',
    ])
    // The PHP image ships the MariaDB client, which verifies TLS by default and
    // so rejects MySQL 8's self-signed certificate. Both commands are otherwise
    // Craft's defaults, with --skip-ssl added.
    ->backupCommand(
        'mysqldump --skip-ssl --add-drop-table --comments --create-options --dump-date ' .
        '--no-autocommit --routines --set-charset --triggers --no-tablespaces --single-transaction ' .
        '--host={server} --port={port} --user={user} --password={password} {database} > {file}'
    )
    ->restoreCommand(
        'mysql --skip-ssl --host={server} --port={port} --user={user} --password={password} {database} < {file}'
    )
;
