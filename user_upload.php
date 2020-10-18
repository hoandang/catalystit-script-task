<?php

function sanitizeData($data)
{
  return trim(filter_var($data, FILTER_SANITIZE_STRING));
}

function transformName($data)
{
  return ucfirst(strtolower($data));
}

function showInvalidEmailUser($user)
{
  echo "Invalid Email: {$user['email']}\n";
}

function reformatUserData($user)
{
  return [
    'name' => transformName(sanitizeData($user['name'])),
    'surname' => transformName(sanitizeData($user['surname'])),
    'email' => sanitizeData(strtolower($user['email']))
  ];
}

function isInvalidEmailUser($user)
{
  return !filter_var($user['email'], FILTER_VALIDATE_EMAIL);
}

// Require composer's autoloader.
require_once 'vendor/autoload.php';

use Garden\Cli\Cli;
use League\Csv\Reader;

// Show help menu
$cli = new Cli;
$cli
  ->opt('file', '[csv file name] – this is the name of the CSV to be parsed', true)
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

$HAS_CREATE_TABLE_OPTION = $args->hasOpt('create_table');
$HAS_DRY_RUN_OPTION = $args->hasOpt('dry_run');
$FILE = $args->getOpt('file');

$DB_NAME = 'CatalystIT';
$DB_TABLE_NAME = 'Users';

// Init db connection
$conn = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD);
if ($conn->connect_error) 
{
  die("Connection failed: $conn->connect_error");
}

// Bootstrap database if not exists
if ($conn->query("CREATE DATABASE IF NOT EXISTS $DB_NAME") !== TRUE) 
{
  die("Error creating database: $conn->error");
}

$conn->select_db($DB_NAME);

// build users table if create_table opt is invoked
if ($HAS_CREATE_TABLE_OPTION)
{
  $sql = <<<EOD
CREATE TABLE IF NOT EXISTS $DB_TABLE_NAME (
  name VARCHAR(30),
  surname VARCHAR(30),
  email VARCHAR(50) NOT NULL,
  UNIQUE(email)
)
EOD;
  if (!$conn->query($sql)) die("Error creating table: $conn->error");
}

// Load csv data
$csv = null;
try 
{
  $csv = Reader::createFromPath($FILE)->setHeaderOffset(0);
}
catch(Exception $e)
{
  die($e->getMessage().PHP_EOL);
}

// Trim csv header
$headers = array_map('trim', $csv->getHeader());

// Reformat data
$users = array_map('reformatUserData', iterator_to_array($csv->getRecords($headers)));

// Extract invalid email users
$invalidEmailUsers = array_filter($users, 'isInvalidEmailUser');

// Remove invalid email users from the user list
$users = array_filter($users, function($user) use($invalidEmailUsers) {
  return $invalidEmailUsers['email'] != $user['email'];
});

// Echo out list of invalid email users
array_walk($invalidEmailUsers, 'showInvalidEmailUser');

// If there is no dry_run opt, then trigger the db insertion
if (!$HAS_DRY_RUN_OPTION)
{
  // Prepare for sql insertion
  $statement = $conn->prepare("INSERT INTO $DB_TABLE_NAME (name, surname, email) VALUES (?, ?, ?)");
  foreach($users as $user)
  {
    try
    {
      $statement->bind_param('sss', $user['name'], $user['surname'], $user['email']);
      $statement->execute();
    }
    catch(mysqli_sql_exception $e)
    {
      echo $e->getMessage();
    }
  }
}

$conn->close();
