<?php

class db {

	public $connection;

	public $errors = array();

	private $db_connection_info;

	public function __construct( $config = array() )
	{
		foreach( $config as $key => $value )

			$this->$key = $value;

		$this->connection = @new mysqli(
			$this->Server,
			$this->db_connection_info["UID"],
			$this->db_connection_info["PWD"],
			$this->db_connection_info["Database"]
		);

		if ( $this->connection->connect_error )
		{
			array_push( $this->errors, array( $this->connection->connect_errno => $this->connection->connect_error ) );

			return $this;
		}
	}

	public function close_connection()
	{
		@$this->connection->close();
	}
}