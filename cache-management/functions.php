<?php

function build_table( $array, $class = false )
{
    $class = $class ? $class : "";

    $table = '<table class="table table-striped ' . $class . '">';

    foreach( $array as $key => $value )
    {
        $value = is_array( $value ) ? build_table( $value ) : $value;
        $table .= '<tr>';
        $table .= '<td>' . $key . '</td>';
        $table .= '<td>' . $value . '</td>';
        $table .= '</tr>';
    }
    
    $table .= '</table>';
    
    return $table;
}

function build_link( $key )
{
	return sprintf( "<a href='?%1\$s&del_key=%2\$s'>[Delete Now]</a>", $_SERVER["QUERY_STRING"], $key );
}

function get_db_instance( $which_one, $db_options )
{
	$errors = array();

	if(
		( $which_one !== "mysqli" && $which_one !== "sqlsrv" ) ||
		!isset( $db_options["db_config_ini"],
				$db_options[ $which_one ]["db_class_path"],
				$db_options[ $which_one ]["db_config_ini_section"] ) )
		
		return false;

	$to_include = $db_options[ $which_one ]["db_class_path"];

	if( !file_exists( $to_include ) )

		array_push( $errors, "$to_include does not exist." );

	else

		include $to_include;

	if( !class_exists("db") )

		array_push( $errors, "db class not loaded." );

	$db_config = file_exists( $db_options["db_config_ini"] ) ? parse_ini_file( $db_options["db_config_ini"], true ) : false;

	$ini_section = $db_options[ $which_one ]["db_config_ini_section"];

	if( $db_config && isset( $db_config[ $ini_section ] ) )
	{
		$db = new db( $db_config[ $ini_section ] );

		if( $db->errors )

			array_push( $errors, $db->errors );

		else

			return $db;
	}
	else

		array_push( $errors, "Error in db configuration." );

	if( $errors )
	{
		$return = new StdClass();

		$return->errors = $errors;

		return $return;
	}
}

function get_table_data( $cache, $date_format )
{
	if( $cache->errors )

		return $cache;

	$table_data = array();

	switch ( $cache->cache_type )
	{
		case 'wincache':

			$ucache_info = wincache_ucache_info();
			
			foreach ($ucache_info["ucache_entries"] as $entry)
			{
				$date = new DateTime("now");
				$age  = $entry["age_seconds"];
				$date->modify("- $age seconds");
				$table_value = array(
					"created" 	  => $date->format( $date_format ),
					"delete item" => build_link( $entry['key_name'] )
				);

				$table_data[ $entry["key_name"] ] = $table_value;
			}

			break;

		case 'apcu':

			$apcu_cache_info = apcu_cache_info();

			foreach ( $apcu_cache_info["cache_list"] as $entry )
			{
				$table_value = array(
					"created" 	  => date( $date_format, $entry["creation_time"] ),
					"delete item" => build_link( $entry['info'] )
				);

				$table_data[ $entry["info"] ] = $table_value;
			}


			break;

		case 'sqlsrv':
		case 'mysqli':

			if ( $items = $cache->get_cached_content( $get_all = true ) )

				foreach ( $items as $entry )
				{
					$table_value = array(
						"created"  	  => date( $date_format, $entry->last_run ),
						"delete item" => build_link( $entry->id )
					);

					$table_data[ $entry->id ] = $table_value;
				}

			break;

		case 'file':

			foreach ( glob( $cache->get_file_cache_dir() . "\\*cache*" ) as $filename )
			{
				$table_value = array(
					"created" 	  => date( $date_format, json_decode( file_get_contents( $filename ) )->last_run ),
					"delete item" => build_link( $filename )
				);

			    $table_data[ $filename ] = $table_value;
			}

			break;
	}

	return $table_data;
}