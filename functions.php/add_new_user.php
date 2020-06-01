<?php
/**
 *    Copyright (c) 2020 Ben Cerjan, Lief Esbenshade
 *
 *    This file is part of Scheduler.
 *
 *    Scheduler is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as published $
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Scheduler is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with Scheduler.  If not, see <https://www.gnu.org/licenses/>.
**/

/**
 * Function for creation of new users.
 *
 * Example usage:
 * require_once '../add_new_user.php';
 *
 * add_new_user($email,$passwd,$uname);
 *
 *
 *
 * @author Ben Cerjan
 * @param string $netid : user's netid
 * @param string $name : user's name
 * @param string $pref : one of AM/PM/None indicating when the user would
 *											 prefer to be scheduled
 *
 * returns TRUE if insert was successful

**/

function add_new_user($netid,$name,$pref) {
	// Require table variables:
	require __DIR__ . '/table_variables.php';

	// Include database connection
	require_once __DIR__ . '/db_connect.php';

	// Connect to db
	$db = db_connect();

	// make netid lower case
  $netid = strtolower($netid);

	try {
		// This command won't allow you to update public users
		$sql = "INSERT INTO $users_table (netid, name, time_pref, test_user)
						VALUES (:netid, :name, :pref, :test_user)
						ON CONFLICT (netid) DO UPDATE SET
						name=EXCLUDED.name, time_pref=EXCLUDED.time_pref
						WHERE NOT $users_table.test_user = 'public';";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':netid', $netid);
		$stmt->bindValue(':name', $name);
		$stmt->bindValue(':pref', $pref);
		$stmt->bindValue(':test_user', $test_user);

		$success = $stmt->execute();
	} catch(PDOException $e) {
		die('ERROR: ' . $e->getMessage() . "\n");
	}

	return $success;
}
