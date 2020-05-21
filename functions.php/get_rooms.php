<?php


/**
 * Function to get information on the rooms in a building
 *
 * Example usage:
 * require_once '../get_rooms.php';
 *
 * $rooms = get_rooms($bldg);
 *
 *
 *
 * @author Ben Cerjan
 *
 * returns multidimensional array of the form: array(0=>[room_id,capacity,building], ...)
 *

**/

function get_rooms() {
	// Require table variables:
	require __DIR__ . '/table_variables.php';

	// Include database connection
	require_once __DIR__ . '/db_connect.php';

	// Connect to db
	$db = db_connect();


	try {
		$sql = "SELECT * from $rooms_table";
		$stmt = $db->prepare($sql);

		$success = $stmt->execute();
	} catch(PDOException $e) {
		die('ERROR: ' . $e->getMessage() . "\n");
	}

	$result = $stmt->fetchAll();
	return $result;
}
