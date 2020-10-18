<?php

// Require composer's autoloader.
require_once 'vendor/autoload.php';

use Garden\Cli\Cli;
use Illuminate\Database\Capsule\Manager as Capsule;

// Show help menu
$cli = new Cli;
$cli
  ->opt('file', '[csv file name] – this is the name of the CSV to be parsed')
  ->opt('create_table', 'this will cause the MySQL users table to be built (and no further action will be taken)')
  ->opt('dry_run', "this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered")
  ->opt('db_host:h', 'MySQL host', true) 
  ->opt('db_username:u', 'MySQL username', true) 
  ->opt('db_password:p', 'MySQL password', true);

// Parse and return cli args.
$args = $cli->parse($argv, true);

// Capture user inputs
$DB_HOST = $args->getOpt('db_host') == 'localhost' ? '127.0.0.1' : $args->getOpt('db_host');
$DB_USERNAME = $args->getOpt('db_username');
$DB_PASSWORD = $args->getOpt('db_password');

$DB_NAME = 'CatalystIT';
$DB_TABLE_NAME = 'Users';

// Bootstrap database if not exists
function createDb()
{
  global $DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB_NAME;
  $conn = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD);
  if ($conn->connect_error) 
  {
    die("Connection failed: " . $conn->connect_error);
  }

  // Create database
  if ($conn->query("CREATE DATABASE IF NOT EXISTS $DB_NAME") !== TRUE) 
  {
    die("Error creating database: " . $conn->error);
  }
  $conn->close();
}
createDb();

// Init  Illuminate database package for easy data manipulation
$capsule = new Capsule;
$capsule->addConnection([
  'driver' => 'mysql',
  'host' => $DB_HOST,
  'database' => $DB_NAME,
  'username' => $DB_USERNAME,
  'password' => $DB_PASSWORD
]);

// bootstrap eloquent
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Get connection 
$dbConnection = $capsule->getConnection();
$schema = Capsule::schema();

// Check if user invokes create_table opt 
if ($args->hasOpt('create_table') && !$schema->hasTable($DB_TABLE_NAME))
{
  $schema->create($DB_TABLE_NAME, function ($table) {
    $table->increments('id');
    $table->string('email')->unique();
    $table->timestamps();
  });
}

/* $user = Capsule::table('users')->where('id', 1)->get(); */
