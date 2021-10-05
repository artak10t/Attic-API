<?php
// FQDN name of server
define('FQDN', 'attic.com');

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

// How many times retry the same transaction on deadlock
define('DEADLOCK_RETRIES', 2);

// How long wait after each deadlock retry in microseconds
define('DEADLOCK_SLEEP', 1000); // milliseconds = 1sec

// Max count of fetched records per page
define('MAX_RECORDS_PER_PAGE', 100);

// Max count of pages to examine for count after last selected page
// if more records available API will return flag "$more_pages_available" = 1 (true)
define('MAX_CONTROL_PAGES_COUNT', 10);

// If true sends invitations through email.
// If false only admins can directly create account, erset lost password or email.
define('USE_EMAIL_SUBSYSTEM', true);

// If true only admins allowed to send invites
// If false any user can send invite.
// Requires Email subsystem to be enabled.
define('ADMIN_INVITE_ONLY', true);

// Accept sharing from remote attics
define('ACCEPT_REMOTE_SHARING', true);

// Allow sharing from local attic.
define('ENABLE_LOCAL_SHARING', true);

// Timeout to wait for remote attic API to connect and respond.
define('REMOTE_ATTIC_API_TIMEOUT', 30);

?>
