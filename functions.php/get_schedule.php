<?php


/**
 * Function to get the schedule for a given week
 *
 * Example usage:
 * require_once '../get_schedule.php';
 *
 * $week = '2020-05-16' (Sunday of the week we are working on)
 * $rooms = get_rooms($week);
 *
 *
 *
 * @author Ben Cerjan
 *
 * returns multidimensional array of the form: array(0=>[netid, room_id, start_time...], ...)
 *

**/

function get_schedule($week) {
	// Require table variables:
	require __DIR__ . '/table_variables.php';

	// Include database connection
	require_once __DIR__ . '/db_connect.php';

	// Connect to db
	$db = db_connect();

	try {
		$sql = "SELECT s.netid, s.room_id, s.start_time, s.end_time, s.week_start,
	  u.name FROM $schedule_table AS s
		LEFT JOIN $users_table AS u ON s.netid = u.netid
		WHERE week_start = :week";
		
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':week',$week);

		$success = $stmt->execute();
	} catch(PDOException $e) {
		die('ERROR: ' . $e->getMessage() . "\n");
	}

	$result = $stmt->fetchAll();
	return $result;
}
