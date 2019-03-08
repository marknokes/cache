<?php

class db {

	public $connection;

	private $db_connection_info;

	public function __construct( $config = array() )
	{
		foreach( $config as $key => $value )

			$this->$key = $value;

		$this->connection = new mysqli( $this->Server, $this->db_connection_info["UID"], $this->db_connection_info["PWD"], $this->db_connection_info["Database"] );

		if ( $this->connection->connect_error )
			
		    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
	}
}