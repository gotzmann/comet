<?php

// TODO Replace Phinx migrations with something simpler, maybe "invented here"

/*
    How to process all migrations?
    CLI: php vendor/robmorgan/phinx/bin/phinx migrate

    How to create new migration?
    CLI: php vendor/robmorgan/phinx/bin/phinx create NewMigration
*/

// load our environment files - used to store credentials & configuration
//(new Dotenv\Dotenv(__DIR__))->load();

// FIXME Determine .env name automatically (if there env.dev then get it, if no - grab .env)
//$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, './env.dev');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (getenv('DB_TYPE') == '' || getenv('DB_NAME') == '')
    die("\n[ERR] Environment has no vars for DB settings!");

return
    [
        'paths' => [
            'migrations' => __DIR__ . '/migrations',
        ],
        'environments' =>
            [
                'default_environment' => 'development',
                //'default_database' => 'development',
                'default_migration_table' => 'migrations',
                'development' =>
                    [
                        'adapter' => getenv('DB_TYPE'),
                        'host' => getenv('DB_HOST'),
                        'name' => getenv('DB_NAME'),
                        'user' => getenv('DB_USER'),
                        'pass' => getenv('DB_PASSWORD'),
                        'port' => getenv('DB_PORT'),
                        'charset' => 'utf8'
                    ],
                'production' =>
                    [
                        'adapter' => getenv('DB_TYPE'),
                        'host' => getenv('DB_HOST'),
                        'name' => getenv('DB_NAME'),
                        'user' => getenv('DB_USER'),
                        'pass' => getenv('DB_PASSWORD'),
                        'port' => getenv('DB_PORT'),
                        'charset' => 'utf8'
                    ],
            ],
    ];
