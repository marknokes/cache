<?php

// Make a connection to your database here if you like
include "db-sample.php";

// Store your database settings in an .ini file somewhere
$db_config = parse_ini_file( "db-config-sample.ini", true );

// Instantiate the database object
$db = new db( $db_config['ini_section'] );

// Be sure to comment out or remove the caching method you don't need!
$config = array(
	
	/* file, wincache, apcu, sqlsrv, mysqli  */
	"cache_type" 		 => "file",

	/* Optional string in minutes, up to 59. Default is "15 minutes" */
	"cache_age"			 => "25 minutes",

	/* For database caching */
	"db_connection" 	 => $db->connection, // See db-sample.php

	/* For file caching */
	"cache_path" 		 => "C:\\diff\\path\\from\\default", // Default is "C:\\tmp\\"
	"cache_prefix" 		 => "my-prefix-", // Only applies to file caching. Default is "feed-cache-"

	/* No cache, update options */
	"no_cache"			 => false, // Default is false
	"force_update_cache" => false // Default is false
);