### DEPENDENCIES
- league/csv version 9.6 for loading csv
- vanilla/garden-cli version 2.0 for handling command line input

Please cd to the root folder and run `composer install` to fetch all the necessary packages.


### DIRECTIVES USAGE
- Mysql host, username, password (-h, -u, -p) options are required.
- File option (--file) is also required

### Examples

```
// This command will create users table, fetch users from csv, validate the data and insert them to db
php user_upload.php -h localhost -u root -p foobar --file users.csv --create_table

// This command will only fetch users from csv and validate the data
php user_upload.php -h localhost -u root -p foobar --file users.csv --dry_run
```

### IMPLEMENTATION
As I didn't see database creation mentioned in the doc so I wrote the script to 
automatically create a database name ***CatalystIT*** on the first run of the script.