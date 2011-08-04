<?php

// There should be one of these files for each of systems you're running the site on.
// This is an example development config.
// The file MUST be named as the ServerName from your apache conf, followed by ".conf.php" on the end

//  Remove this line for production conf
define('DEV', true);

// While this is true, any emails the system sends (if it does send any) will instead
// go to a log file mail.log in the project root. Remove the line if you want emails to be
// sent instead.
define('DISABLE_EMAILS', true);

define('SITE_EMAIL', 'your@email.address');
define('HOST_NAME', 'your.own.hostname');

// Feel free to change these before running the initdb script if you like
define('MYSQL_DB', 'mollusc');
define('MYSQL_USER', "mollusc");
define('MYSQL_PASSWORD', "");

// You might need to change the path to your socket or comment this out and
// use the host and port settings depending on your mysql setup
define('MYSQL_SOCKET', '/tmp/mysql.sock');
// define('MYSQL_HOST', 'localhost');
// define('MYSQL_PORT', '3306');

define('OYSTER_USERNAME', 'oyster_username');
define('OYSTER_PASSWORD', 'oyster_username');
// You can leave this null if you only have one card on your account
define('OYSTER_CARD', null);
