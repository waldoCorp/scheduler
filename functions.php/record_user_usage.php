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
