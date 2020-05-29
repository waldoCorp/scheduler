<?php

/**
 * Function to get information on all users
 *
 * Example usage:
 * require_once '../get_names.php';
 *
 * $names = get_names();
 *
 *
 *
 * @author Ben Cerjan
 *
 * returns multidimensional array of the form: array(0=>[netid, name, pref], ...)
 *

**/

function get_names() {
	// Require table variables:
	require __DIR__ . '/table_variables.php';

	// Include database connection
	require_once __DIR__ . '/db_connect.php';

	// Connect to db
	$db = db_connect();


	try {
		$sql = "SELECT * from $users_table WHERE test_user = 'public' OR
						test_user = :test_user";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':test_user', $test_user);

		$success = $stmt->execute();
	} catch(PDOException $e) {
		die('ERROR: ' . $e->getMessage() . "\n");
	}

	$result = $stmt->fetchAll();
	return $result;
}
