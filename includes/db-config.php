<?php 

global $db_user, $db_pass, $db,$db_server, $db_connection;
$db_user = "communication";
$db_pass = "mNnvnA4ufGldQLlL";
$db = "cah";
$db_server = "NET1251.net.ucf.edu";
$db_connection = false;

function get_dbconnection() {
	global $db_user, $db_pass, $db, $db_server, $db_connection;
	if( $db_connection ) return $db_connection;
    $db_connection = mysqli_connect( $db_server, $db_user, $db_pass) or exit('Could not connect to server.' );
	mysqli_set_charset($db_connection,'utf8');
    mysqli_select_db($db_connection,$db) or exit('Could not select database.');
    return $db_connection;
}

function do_dbcleanup() {
	global $db_connection;
    if( $db_connection != false ) mysqli_close($db_connection);
    $db_connection = false;
}

function check_result($result, $sql, $debug=false) {
		$message= "";
		if (!$result) {
			if($debug)
			{
			$message  .= '<p>Invalid query: ' . mysqli_error(get_dbconnection()) . "</p>"; 
			$message .= '<p>Whole query: ' . $sql . "</p>";
			}	
			$message .= "There was a database problem.  Please report this to <a href='mailto:cahweb@ucf.edu'>cahweb@ucf.edu</a>.";	
		    die($message);
		}
		
	    else return true;
}

?>