<?php

// Require composer's autoloader.
require_once 'vendor/autoload.php';

use Garden\Cli\Cli;
use Illuminate\Database\Capsule\Manager as Capsule;

$cli = new Cli;
$cli
  ->opt('file', '[csv file name] – this is the name of the CSV to be parsed')
  ->opt('create_table', 'this will cause the MySQL users table to be built (and no further action will be taken)')
  ->opt('dry_run', "this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered")
  ->opt('db-host:h', 'MySQL host', true) 
  ->opt('db-username:u', 'MySQL username', true) 
  ->opt('db-password:p', 'MySQL password', true);

// Parse and return cli args.
$args = $cli->parse($argv, true);

$DB_HOST = $args['db-host'];
$DB_USERNAME = $args['db-username'];
$DB_PASSWORD = $args['db-password'];
$DB_NAME = 'CatalystIT';
$DB_TABLE_NAME = 'Users';

$capsule = new Capsule;

$capsule->addConnection([
  'driver' => 'mysql',
  'host' => $DB_HOST,,
  'database' => $DB_NAME,
  'username' => $DB_USERNAME,,
  'password' => $DB_PASSWORD
]);

$capsule->setAsGlobal();

// Setup the Eloquent ORM.
$capsule->bootEloquent();

// Init database if not existed
$capsule->getConnection()->statement("CREATE DATABASE IF NOT EXISTS $DB_NAME");

/* $user = Capsule::table('users')->where('id', 1)->get(); */
