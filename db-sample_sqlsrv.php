<?php

class db {

	public $connection;

	public $errors = array();

	private $db_connection_info;

	public function __construct( $config = array() )
	{
		foreach( $config as $key => $value )

			$this->$key = $value;

		$this->connection = @sqlsrv_connect(
			$this->Server,
			$this->db_connection_info
		);

		if( !$this->connection )
		{
			array_push( $this->errors, sqlsrv_errors() );
			
			return $this;
		}
	}

	public function close_connection()
	{
		@sqlsrv_close( $this->connection );
	}
}