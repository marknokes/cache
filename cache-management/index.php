<?php
include "../Cache.php";

include "functions.php";

include "config.php";

$cache_type = $_GET["cache_type"] ?? "";

$db = get_db_instance( $cache_type, $db_options );

$cache = new Cache( array(
	"cache_type" 	=> $cache_type,
	"db_connection" => isset( $db->connection ) ? $db->connection : false
) );

if( isset( $_GET["del_key"] ) )

    $cache->delete( $_GET["del_key"] );

elseif( isset( $_GET["clear_all"] ) )

	$cache->delete();

$table_data = get_table_data( $cache, $date_format );

if ( isset( $db->connection ) )

	$db->close_connection()
?>
<html>
<head>
	<title>User Cache Management</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
	<div class="container">
		<h1>User Cache Management</h1>
		<ul class="nav">
			<li class="nav-item"><a class="nav-link" href="?cache_type=wincache">WinCache</a></li>
			<li class="nav-item"><a class="nav-link" href="?cache_type=apcu">APCu</a></li>
			<li class="nav-item"><a class="nav-link" href="?cache_type=sqlsrv">Sqlsrv</a></li>
			<li class="nav-item"><a class="nav-link" href="?cache_type=mysqli">MySQLi</a></li>
			<li class="nav-item"><a class="nav-link" href="?cache_type=file">File</a></li>
		</ul>
		<?php echo $table_data ? build_table( $table_data ): '<div class="alert alert-primary" role="alert">No data found.</div>'; ?>
		<?php if( $table_data ) { ?>
		<a href="?cache_type=<?=$cache_type?>&clear_all=1">[Clear All <?=$cache_type?> Entries]</a>
		<?php } ?>
	</div>
</body>
</html>