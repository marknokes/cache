<?php

class Cache
{
	/**
    * Timestamp representing the current run time
    *
    * @var int
    */
    private $time = 0;

    /**
    * Timestamp representing the last time the content was retrieved. This value is stored in the cache with the content
    *
    * @var int
    */
    private $last_run = 0;

    /**
    * Display a string containing date/time of current cached content.
    *
    * @var str
    */
    public $info = "";

    /**
    * The content to cache.
    *
    * @var str
    */
    private $content = "";

     /**
    * The content to deliver.
    *
    * @var str
    */
    private $cache_content = "";

    /**
    * Options: file, mysqli, sqlsrv, wincache, apcu
    *
    * @var str
    */
    public $cache_type = "";

    /**
    * Should the content be cached? This option should be set in the content HTML as a data parameter, i.e., no-cache="true"
    *
    * @var bool 
    */
	private $no_cache = false;

	/**
    * Should the content be cached? This option should be set in the content HTML as a data parameter, i.e., no-cache="true"
    *
    * @var bool 
    */
	private $force_update_cache = false;

	/**
	* The full path to the cache file, if sql server is not used for persistance. This option may be overridden in config.php
	*
	* @var str
	*/
	private $cache_path = "";

	/**
	* The age of the cache data set as human readable time string in minutes, up to 59. For example: 25 minutes". This option may be overridden in config.php
	*
	* @var str
	*/
    private $cache_age = "15 minutes";

    /**
	* Prefix to be prepended to the cache filename. This option may be overridden in config.php
	*
	* @var str
	*/
    private $cache_prefix = "cache-";

    /**
	* The full path to the cache file including the cache filename, if sql server is not used for persistance. Configured in $this->init() and includes options from config.php
	*
	* @var str
	*/
	private $cache_file = "";

	/**
	* If sql server is used for persistance, the database id of the cache data. Configured in $this->init() as an md5 hash
	*
	* @var str
	*/
	private $id = "";

    /**
    * Instance of the database connection object if sql server persistance is used
    *
    * @var obj
    */
	private $db_connection;

	/**
	* Database query formats
	*
	* @var array
	*/
	private $queries = array(
		"sqlsrv" => array(
			"get" 				=> "SELECT * FROM cache_data",
			"get_by_id" 		=> "SELECT * FROM cache_data WHERE id = '%1\$s'",
			"set" 				=> "IF EXISTS (SELECT id FROM cache_data WHERE id = '%1\$s')
									  BEGIN
									     UPDATE cache_data SET last_run = %2\$d, cache_content = '%3\$s', num_hits = num_hits + 1 WHERE id = '%1\$s'
									  END
								  	ELSE
									  BEGIN
									     INSERT INTO cache_data (id, last_run, cache_content, num_hits)
									     VALUES ('%1\$s', %2\$d, '%3\$s', 0)
									  END",
			"delete_by_id" 		=> "DELETE FROM cache_data WHERE id = '%1\$s'",
			"delete" 			=> "DELETE FROM cache_data",
			"update_num_hits" 	=> "UPDATE cache_data SET num_hits = num_hits + 1 WHERE id = '%1\$s'"
		),
		"mysqli" => array(
			"get" 				=> "SELECT * FROM cache_data",
			"get_by_id" 		=> "SELECT * FROM cache_data WHERE id = '%1\$s'",
			"set" 				=> "INSERT INTO cache_data (id, last_run, cache_content, num_hits)
					 				VALUES ('%1\$s', %2\$d, '%3\$s', 0)
					 				ON DUPLICATE KEY UPDATE last_run = VALUES(last_run), cache_content = VALUES(cache_content), num_hits = VALUES(num_hits);",
			"delete_by_id" 		=> "DELETE FROM cache_data WHERE id = '%1\$s'",
			"delete" 			=> "DELETE FROM cache_data",
			"update_num_hits" 	=> "UPDATE cache_data SET num_hits = num_hits + 1 WHERE id = '%1\$s'"
		)
	);

	public $errors = array();

	public function __construct( $config )
	{
		$this->time = time();

    	if( $config )
    	{
	    	foreach( $config as $key => $value )

				$this->$key = $value;
		}

		$hash = isset( $this->cache_key ) ? hash( 'md5', $this->cache_key ): "";

		$this->id = $hash;

		if( !$this->cache_type || $this->cache_type === "file" )
		{
			$this->cache_type = "file";

			$this->cache_path = "" !== $this->cache_path ? $this->cache_path : sys_get_temp_dir();

			$this->cache_file = $this->cache_path . "\\" . $this->cache_prefix . $this->id . ".json";
		}

		if( !$this->has_cache_type_support() )

			array_push( $this->errors, "The $this->cache_type extension is not loaded.");

	}

	public function has_cache_type_support( $extension = "" )
	{
		$ext = "" !== $extension ? $extension: $this->cache_type;
		
		if( "file" !== $ext )

			return extension_loaded( $ext );

		else

			return true;
	}

	public function get_file_cache_dir()
	{
		return $this->cache_path;
	}

	/**
 	* Retrieve content from file cache
 	* 
 	* @return bool true if cache file exists and properly json decoded, false otherwise
 	*/
	private function from_file_cache()
    {
    	return file_exists( $this->cache_file ) ? json_decode( file_get_contents( $this->cache_file ) ) : false;
    }

    /**
 	* Retrieve content from wincache
 	* 
 	* @return bool true if cache file exists and properly json decoded, false otherwise
 	*/
	private function from_wincache()
    {
    	return wincache_ucache_exists( $this->id ) ? json_decode( wincache_ucache_get( $this->id ) ) : false;
    }

    /**
 	* Retrieve content from apcu
 	* 
 	* @return bool true if cache file exists and properly json decoded, false otherwise
 	*/
	private function from_apcu()
    {
    	return apcu_exists( $this->id ) ? json_decode( apcu_fetch( $this->id ) ) : false;
    }

    public function delete( $id = "" )
    {
    	$return = false;

    	switch( $this->cache_type )
    	{
    		case "sqlsrv":
    		case "mysqli":
    			if( "" !== $id )

    				$return = $this->do_query( sprintf( $this->queries[ $this->cache_type ]["delete_by_id"], $id ) );

    			else

    				$return = $this->do_query( $this->queries[ $this->cache_type ]["delete"] );

    			break;

    		case "wincache":

    			if( "" !== $id && wincache_ucache_exists( $id ) )

	    			$return = wincache_ucache_delete( $id );

	    		elseif( function_exists("wincache_ucache_clear") )

					$return = wincache_ucache_clear();

    			break;

    		case "apcu":

				if( "" !== $id && apcu_exists( $id ) )

				    $return =  apcu_delete( $id );

				elseif( function_exists("apcu_clear_cache") )
					
					$return = apcu_clear_cache();

    			break;

    		case "file":

    			if( "" !== $id && file_exists( $id ) )
					
					$return = unlink( $id );

				else {
					
					$errors = 0;

					$pattern = $this->get_file_cache_dir() . "\\*" . $this->cache_prefix . "*";

					foreach ( glob( $pattern ) as $filename )
					{
						if( false === unlink( $filename ) )

							$errors += 1;
					}

					return $errors === 0;
				}

    			break;
    	}

    	return $return;
    }

	/**
 	* Query the database
 	* 
 	* @param str $query The SQL query
 	* @return obj Object representing the data, false if query fails
 	*/
    public function do_query( $query )
    {
    	if( !$this->db_connection )

    		return false;

    	if ( "sqlsrv" === $this->cache_type )
    	{
			if( $stmt = sqlsrv_prepare( $this->db_connection, $query ) )
			{
				if( false === $stmt )
				{
					array_push( $this->errors, sqlsrv_errors() );
					return;
				}

				$result = sqlsrv_execute( $stmt );

				if( $result === false )
				{
					array_push( $this->errors, sqlsrv_errors() );
					return;
				}

				$obj = new stdClass();

				while ($row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) )
		        {
		            $obj->items[] = (object)$row;
		        }
			}
    	}
    	elseif( "mysqli" === $this->cache_type )
    	{
			if ( $stmt = @$this->db_connection->prepare( $query ) )
			{	
				if ( false === $stmt )

					array_push( $this->errors, $this->db_connection->error );
				
				$stmt->execute();

				$result = $stmt->get_result();

				if( isset( $result->num_rows ) )
				{
					$obj = new stdClass();

					while ($row = $result->fetch_array(MYSQLI_ASSOC))
			        {
			            $obj->items[] = (object)$row;
			        }
				}
			}
    	}

    	return isset( $obj ) && is_object( $obj ) ? $obj : false;
    }

	/**
 	* Determine if it's time to run based on cache age
 	* 
 	* @return bool True if $this->last_run is false or if the current time is greater than the timestamp + the cache age, otherwise false
 	*/
    private function cache_expired()
	{
		if( 0 === $this->last_run || $this->no_cache || $this->force_update_cache || !$this->data )

			return true;

		else

			return $this->time >= strtotime( "+" . $this->cache_age, $this->last_run );
	}

	/**
 	* Sets the content 
 	* 
 	* @return obj $this
 	*/
	public function set_content( $content )
	{
		$this->content = $content;

		return $this;
	}

	/**
 	* Escape content for sql server insert
 	* 
 	* @param str $this->data The string to escape
 	* @return str|int Empty string, integer if $this->data is numeric, or escaped string data
 	*/
	private function mssql_escape_string( $data )
	{
		if ( !isset( $data ) || empty( $data ) )

			return '';

		if ( is_numeric( $data ) )

			return $data;

		$non_displayables = array(
			'/%0[0-8bcef]/', // url encoded 00-08, 11, 12, 14, 15
			'/%1[0-9a-f]/',  // url encoded 16-31
			'/[\x00-\x08]/', // 00-08
			'/\x0b/',        // 11
			'/\x0c/',        // 12
			'/[\x0e-\x1f]/'  // 14-31
		);

		foreach ( $non_displayables as $regex )

			$data = preg_replace( $regex, '', $data );
		
		$data = str_replace("'", "''", $data );
		
		return $data;
	}

	/**
 	* Cache the data. Use database or file system depending on options
 	* 
 	* @return bool true if database query succeeds or cache file created successfully, false otherwise
 	*/
	public function cache()
	{
		if ( $this->no_cache )
		{
			return false;
		}
		elseif( "" !== $this->id && ("sqlsrv" === $this->cache_type || "mysqli" === $this->cache_type ) )
		{
			return $this->do_query( sprintf(
				$this->queries[ $this->cache_type ]["set"],
				$this->id,
				$this->time,
				$this->mssql_escape_string( $this->content )
			) );
		}
		elseif( "" !== $this->id && "wincache" === $this->cache_type )
		{
			$time = date( "00:i:s", strtotime( "+" . $this->cache_age, 0 ) );
			
			$seconds = strtotime("1970-01-01 $time UTC");
			
			return false !== wincache_ucache_set(
				$this->id,
				json_encode( array(
					"last_run" 		=> $this->time,
					"cache_content" => $this->content
				) ),
				$seconds
			);
		}
		elseif( "" !== $this->id && "apcu" === $this->cache_type )
		{
			$time = date( "00:i:s", strtotime( "+" . $this->cache_age, 0 ) );
			
			$seconds = strtotime("1970-01-01 $time UTC");
			
			return false !== apcu_store(
				$this->id,
				json_encode( array(
					"last_run" 		=> $this->time,
					"cache_content" => $this->content
				) ),
				$seconds
			);
		}
		elseif( "" !== $this->cache_file && "file" === $this->cache_type )
		{
			return false !== file_put_contents(
				$this->cache_file,
				json_encode( array(
					"last_run" 		=> $this->time,
					"cache_content" => $this->content
				) )
			);
		}
	}

	/**
 	* Retrieve content data from database, file cache, or real time depending on configuration
 	* 
 	* @return null
 	*/
    public function get_cached_content( $get_all = false )
    {
    	switch( $this->cache_type )
    	{
    		case "sqlsrv":
    		case "mysqli":
    			if( $get_all ) {
					$this->data = $this->do_query( $this->queries[ $this->cache_type ]["get"] );
    			} else {
    				$this->data = $this->do_query( sprintf( $this->queries[ $this->cache_type ]["get_by_id"], $this->id ) );
    				$this->do_query( sprintf( $this->queries[ $this->cache_type ]["update_num_hits"], $this->id ) );
    			}

    			break;

    		case "wincache":

    			$this->data = $this->from_wincache();

    			break;

    		case "apcu":

    			$this->data = $this->from_apcu();

    			break;

    		case "file":

				$this->data = $this->from_file_cache();
    	}

    	if( isset( $this->data->items ) && sizeof( $this->data->items ) === 1 ) {
    		$this->last_run = $this->data->items[0]->last_run;
    	} elseif( isset( $this->data->last_run ) ) {
    		$this->last_run = $this->data->last_run;
    	} else {
    		$this->last_run = 0;
    	}

    	$this->info = isset( $this->last_run ) && !$this->no_cache ? date("Y-m-d h:i A", $this->last_run) . " using " . $this->cache_type . " storage" : "Content not retrieved from cache.";

    	if( isset( $this->data->items ) && true === $get_all )

    		return $this->data->items;

    	elseif( isset( $this->data->items[0]->cache_content ) && false === $this->cache_expired() )

    		return $this->data->items[0]->cache_content;

    	elseif( isset( $this->data->cache_content ) && false === $this->cache_expired() )

    		return $this->data->cache_content;

    	else 

    		return "";
    }
}
