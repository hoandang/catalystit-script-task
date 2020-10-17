<?php

// Require composer's autoloader.
require_once 'vendor/autoload.php';

$cli = new Garden\Cli\Cli();
$cli
  ->opt('file', '[csv file name] – this is the name of the CSV to be parsed')
  ->opt('create_table', 'this will cause the MySQL users table to be built (and no further action will be taken)')
  ->opt('dry_run', "this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered")
  ->opt('h', 'MySQL host') 
  ->opt('u', 'MySQL username') 
  ->opt('p', 'MySQL password');

// Parse and return cli args.
$args = $cli->parse($argv, true);

/* var_dump($args); */
