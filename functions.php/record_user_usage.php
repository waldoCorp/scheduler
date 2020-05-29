<?php

/* Function to record when a user last used the site
 *
 * also triggers clearing of old tables
 *
 * no return
 */

function record_user_usage($uuid) {
  // Require table variables:
  require __DIR__ . '/table_variables.php';

	// Include database connection
  require_once __DIR__ . '/db_connect.php';
  $db = db_connect();

  // Find all old uuid's
  $sql = "INSERT INTO $temp_table (uuid) VALUES (:uuid)
  ON CONFLICT (uuid) DO UPDATE SET last_refresh = NOW()";
  $stmt = $db->prepare($sql);
  $stmt->bindValue(':uuid', $uuid);
  $success = $stmt->execute();

  require_once __DIR__ . '/clear_temp_tables.php';
  clear_temp_tables();

  return;
}
