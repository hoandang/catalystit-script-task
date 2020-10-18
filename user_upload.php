<?php

// Require composer's autoloader.
require_once 'vendor/autoload.php';

use Garden\Cli\Cli;
use League\Csv\Reader;

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

// Init db connection
$conn = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD);
if ($conn->connect_error) 
{
  die("Connection failed: " . $conn->connect_error);
}

// Bootstrap database if not exists
if ($conn->query("CREATE DATABASE IF NOT EXISTS $DB_NAME") !== TRUE) 
{
  die("Error creating database: " . $conn->error);
}

$conn->select_db($DB_NAME);

// build users table if create_table opt is invoked
if ($args->hasOpt('create_table'))
{
  $sql = <<<EOD
CREATE TABLE IF NOT EXISTS $DB_TABLE_NAME (
  name VARCHAR(30),
  surname VARCHAR(30),
  email VARCHAR(50) NOT NULL,
  UNIQUE(email)
)
EOD;
  if (!$conn->query($sql)) die("Error creating table: " . $conn->error);
}

$csv = Reader::createFromPath('users.csv')->setHeaderOffset(0);
$headers = array_map('trim', $csv->getHeader());
$users = array_map(function($user) {
  return [
    'name' => filter_var(trim(ucfirst(strtolower($user['name'])), FILTER_SANITIZE_STRING)),
    'surname' => filter_var(trim(ucfirst(strtolower($user['surname'])), FILTER_SANITIZE_STRING)),
    'email' => filter_var(trim(strtolower($user['email'])), FILTER_SANITIZE_STRING)
  ];
}, iterator_to_array($csv->getRecords($headers)));

$statement = $conn->prepare("INSERT INTO $DB_TABLE_NAME (name, surname, email) VALUES (?, ?, ?)");
foreach($users as $user)
{
  if (filter_var($user['email'], FILTER_VALIDATE_EMAIL))
  {
    try
    {
      $statement->bind_param("sss", $user['name'], $user['surname'], $user['email']);
      $statement->execute();
    }
    catch(mysqli_sql_exception $e)
    {
      echo $e->getMessage();
    }
  }
  else
  {
    echo 'Invalid email' . $user['email'] . ', no data added'.PHP_EOL;
  }
}

$conn->close();
