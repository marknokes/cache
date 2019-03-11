<?php

$db_options = array(
	"db_config_ini" 				=> "/path/to/db-config.ini",
	"sqlsrv"						=> array(
		"db_config_ini_section" 	=> "sqlsrv",
		"db_class_path" 			=> "/path/to/db_sqlsrv.php",
	),
	"mysqli"						=> array(
		"db_config_ini_section" 	=> "mysqli",
		"db_class_path" 			=> "/path/to/db_mysqli.php"
	)
);

$date_format = "F d, Y h:i:s A";