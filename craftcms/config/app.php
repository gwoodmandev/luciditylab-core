<?php
/**
 * Yii Application Config
 *
 * The array returned by this file gets merged with
 * vendor/craftcms/cms/src/config/app.php and app.[web|console].php.
 *
 * Read more about application configuration:
 * @link https://craftcms.com/docs/5.x/reference/config/app.html
 */

use craft\helpers\App;

$config = [
    'id' => App::env('CRAFT_APP_ID') ?: 'CraftCMS',
];

// ---------------------------------------------------------------------------
// Redis
//
// Only wired up when REDIS_HOSTNAME is set, so an install without Redis (or a
// host that doesn't provide it) falls back to Craft's file-based cache and
// PHP's default session handler with no config changes.
//
// Redis-backed sessions become necessary as soon as more than one PHP
// container serves the site, since file sessions are per-container.
// ---------------------------------------------------------------------------

$redisHostname = App::env('REDIS_HOSTNAME');

if ($redisHostname) {
    $redisPort = (int)(App::env('REDIS_PORT') ?: 6379);
    $redisPassword = App::env('REDIS_PASSWORD') ?: null;

    // sessions and cache use separate databases so clearing caches can never
    // log everyone out
    $sessionDb = (int)(App::env('REDIS_DEFAULT_DB') ?? 0);
    $cacheDb = (int)(App::env('REDIS_CRAFT_DB') ?? 1);

    $connection = [
        'hostname' => $redisHostname,
        'port' => $redisPort,
        'password' => $redisPassword,
    ];

    $config['components']['redis'] = $connection + [
        'class' => yii\redis\Connection::class,
        'database' => $sessionDb,
    ];

    // the redis property needs a full component config (including class) or a
    // component ID — a bare host/port array is not instantiable
    $config['components']['cache'] = [
        'class' => yii\redis\Cache::class,
        'redis' => $connection + [
            'class' => yii\redis\Connection::class,
            'database' => $cacheDb,
        ],
        'defaultDuration' => 86400,
        'keyPrefix' => App::env('CRAFT_APP_ID') ?: 'CraftCMS',
    ];

    // Merge Redis into Craft's own session config rather than replacing it —
    // Craft attaches a SessionBehavior plus flash/auth params that it relies on.
    $config['components']['session'] = function() use ($connection, $sessionDb) {
        $sessionConfig = App::sessionConfig();
        $sessionConfig['class'] = yii\redis\Session::class;
        $sessionConfig['redis'] = $connection + [
            'class' => yii\redis\Connection::class,
            'database' => $sessionDb,
        ];

        return Craft::createObject($sessionConfig);
    };
}

return $config;
