<?php
// MySQL server address
define('MYSQL_ADDRESS', '127.0.0.1');

// MySQL server port
define('MYSQL_PORT', 3306);

// MySQL database name
define('MYSQL_DATABASE', 'attic');

// MySQL server username
define('MYSQL_USERNAME', 'myuser');

// MySQL server password
define('MYSQL_PASSWORD', 'mypassword');

// How many times retry the same query on deadlock
define('DEADLOCK_RETRIES', 10);

// How long wait after each deadlock retry in microseconds
define('DEADLOCK_SLEEP', 1000); // milliseconds = 1sec

// If true sends invitation and activation tokens through email.
// If false sends invitation and activation tokens directly in data.
// Email subsystem has to be setted up, otherwise email will not be sent.
define('USE_EMAIL_SUBSYSTEM', true);
?>
