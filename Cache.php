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
    * The content to deliver.
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
    * Options are db and file. If a sql server db_connection object is passed into config.php, the $cache_type will be set to "db",
    * otherwise "file" will be used
    *
    * @var str
    */
    private $cache_type = "";

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
		"get" => "SELECT * FROM cache_data WHERE id = '%1\$s'",
		"set" => "IF EXISTS (SELECT id FROM cache_data WHERE id = '%1\$s')
					  BEGIN
					     UPDATE cache_data SET last_run = %2\$d, cache_content = '%3\$s' WHERE id = '%1\$s'
					  END
				  ELSE
					  BEGIN
					     INSERT INTO cache_data (id, last_run, cache_content)
					     VALUES ('%1\$s', %2\$d, '%3\$s')
					  END"
	);

	public function __construct( $config )
	{
		$this->time = time();

    	if( $config )
    	{
	    	foreach( $config as $key => $value )

				$this->$key = $value;
		}

		$hash = hash( 'md5', $this->cache_key );

		$this->id = $hash;

		if( !$this->cache_type || $this->cache_type === "file" )
		{
			$this->cache_type = "file";

			$this->cache_path = "" !== $this->cache_path ? $this->cache_path : sys_get_temp_dir();

			$this->cache_file = $this->cache_path . "\\" . $this->cache_prefix . $hash;
		}
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

	/**
 	* Query the database
 	* 
 	* @param str $query The SQL query
 	* @return obj Object representing the data, false if query fails
 	*/
    private function do_query( $query )
    {
		$stmt = sqlsrv_prepare( $this->db_connection, $query );

		if( !$stmt )

		    die( print_r( sqlsrv_errors(), true) );

		$result = sqlsrv_execute( $stmt );

		if( $result === false )

		  die( print_r( sqlsrv_errors(), true) );

		$obj = sqlsrv_fetch_object( $stmt );

		return is_object( $obj ) ? $obj : false;
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
		elseif( "" !== $this->id && "db" === $this->cache_type )
		{
			return $this->do_query( sprintf(
				$this->queries["set"],
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
    public function get_cached_content()
    {
    	switch( $this->cache_type )
    	{
    		case "db":

    			$this->data = $this->do_query( sprintf( $this->queries["get"], $this->id ) );

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

    	$this->last_run = isset( $this->data->last_run ) ? $this->data->last_run : 0;

    	$this->info =  isset( $this->data->last_run ) && !$this->no_cache ? date("Y-m-d h:i A", $this->data->last_run) . " using " . $this->cache_type . " storage" : "Content not retrieved from cache.";

    	return isset( $this->data->cache_content ) && false === $this->cache_expired() ? $this->data->cache_content : "";
    }
}
