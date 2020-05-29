<?php

/* Function to clear temporary tables from the demo
 *
 * Deletes any sets of tables older than 30 minutes
 *
 * no return
 */

function clear_temp_tables() {
  // Require table variables:
  require __DIR__ . '/table_variables.php';

	// Include database connection
  require_once __DIR__ . '/db_connect.php';
  $db = db_connect();

  // Find all old uuid's
  $sql = "SELECT uuid FROM $temp_table WHERE last_refresh + interval '1 day' < NOW();";
  $stmt = $db->prepare($sql);

  $success = $stmt->execute();
  $result = $stmt->fetchAll();


  foreach ($result as $user) {
    $sql = "DELETE FROM $users_table WHERE test_user = :test_user";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':test_user', $user['uuid']);
    $success = $stmt->execute();

    $sql = "DELETE FROM $requests_table WHERE test_user = :test_user";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':test_user', $user['uuid']);
    $success = $stmt->execute();

    $sql = "DELETE FROM $schedule_table WHERE test_user = :test_user";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':test_user', $user['uuid']);
    $success = $stmt->execute();
  }

  return;
}
