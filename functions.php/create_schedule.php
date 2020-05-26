<?php

/**
 * Function to create a schedule for a given week and store it in the database.
 * Will not create a new schedule if a given schedule already exists for that week.
 * Also empties the requests table (as they are now in use)
 *
 * Example usage:
 * require_once '../create_schedule.php';
 *
 * $schedule = create_schedule();
 *
 *
 *
 * @author Ben Cerjan
 *
 * returns multidimensional array of the form: array(0=>[XXXXXX], ...)
 *

**/

function create_schedule() {
	// Require table variables:
	require __DIR__ . '/table_variables.php';

	// Include database connection
	require_once __DIR__ . '/db_connect.php';

	// First, we need to pull the requests along with each user's preferences:

	// Connect to db
	$db = db_connect();


	try {
		$sql = "SELECT r.netid, r.room_id, r.duration, r.hazardous, u.time_pref
		 FROM $requests_table AS r
		 LEFT JOIN $users_table AS u ON r.netid = u.netid;";
		$stmt = $db->prepare($sql);

		$success = $stmt->execute();
	} catch(PDOException $e) {
		die('ERROR: ' . $e->getMessage() . "\n");
	}

	$requests = $stmt->fetchAll();


	// Place some limits on our shifts:
	$max_length = 100000000; // No max length yet
	$gap = 0.5; // Hours, mandatory gap between shifts in the same room
							// Also the amount of time you can be working with hazards without a buddy
	$gap = $gap *60*60; // Convert to sec from hours
	$start_time = 7*60*60; // 7 AM
	$end_time = 23*60*60; // 11 PM


	// Preference valuations:
	$pref_value = 1.1; // value associated with matching a person to their preferred AM/PM
	$base_value = 1.0; // for when they have no preference
	$anti_value = 0.9; // if we're opposite their preferences

	$pref_values = array (
		'pref' => $pref_value,
		'none' => $base_value,
		'anti' => $anti_value
	);

	// Preference definitions: AM -> before 3 PM, PM -> after 3 PM
	$am_cutoff = 15*60*60; // 15:00 in sec

	// Store all this in an object so we can easily pass it around:
	$params = new Schedule_Parameters($start_time, $end_time, $max_length, $gap, $gap,
																		$pref_values, $am_cutoff);

	// "Value" for the schedule as described so far:
	$value = 0;

	// Start with empty schedule
	$schedule = new Schedule;


	// Now, we can recurse through all the requests and try to find the best combination:
	$best_sched = recursive_search_2($requests, $schedule, $params);

	//var_dump($best_sched);
	// Finally, store the schedule in the database:
	$week = date('Y-m-d', strtotime('Sunday this week'));
	$week = date('Y-m-d', strtotime('Sunday last week'));
	foreach ($best_sched->day_shifts as $day_shifts) {
		foreach ($day_shifts as $shift) {
			add_shift_to_db($db, $shift, $week);
		}
	}

/*	echo "Number of requests: " . count($requests) . "\n";
	echo "Number of Shifts Scheduled: " . $best_sched->count_shifts() . "\n";
	echo "Number of Shifts Out of Pref.: " . $best_sched->get_out_num() . "\n";*/

	// Return true if we created a new schedule
	return true;

}

/* Class for a shift */
class Shift {
	public $netid;
	public $day;
	public $start;
	public $end;
	public $room;
	public $hazardous;
	public $out_of_pref = 0;
	public $out_of_norm_hours = 0;
	public $hazard_num = 0;
	public $padded_end;
	public $value = 1;

	function __construct($netid, $day, $start, $end, $room, $hazardous) {
		$this->netid = $netid;
		$this->day = $day;
	 	$this->start = $start;
		$this->end = $end;
		$this->room = $room;
		$this->hazardous = $hazardous;
		if ($this->hazardous) {
			$this->hazard_num = 1;
		}
	}

	function get_out_num() {
		return $this->out_of_pref + $this->out_of_norm_hours;
	}

	function set_padded_end($gap) {
		$this->padded_end = $this->end + $gap;
	}
}

/* Class for the schedule */
class Schedule {
	public $day_shifts = array(
		0 => array(), // Sunday
		1 => array(), // Monday
		2 => array(), // ...
		3 => array(),
		4 => array(),
		5 => array(),
		6 => array()
	);
	public $out_of_pref = 0;
	public $out_of_norm_hours = 0;
	public $hazard_gaps = 0;
	public $value = 0;

	/* Takes a Shift object and adds it to the schedule, also increments total
	   schedule value to include new shift */
	function add_shift_to_schedule($shift) {
		$this->day_shifts[$shift->day][] = $shift;
		$this->out_of_pref += $shift->out_of_pref;
		$this->out_of_norm_hours += $shift->out_of_norm_hours;
		$this->hazard_gaps += $shift->hazard_num;
		$this->value += $shift->value;
	}

	function get_out_num() {
		return $this->out_of_pref + $this->out_of_norm_hours;
	}

	function get_value() {
		return $this->value;
	}
	function count_shifts() {
		$i = 0;
		foreach ($this->day_shifts as $day) {
			foreach ($day as $shift) {
				$i++;
			}
		}
		return $i;
	}

}

/* Class for static parameters */
class Schedule_Parameters {
	public $start_time; // Earliest time a shift can start
	public $end_time; // latest a shift can end
	public $max_length; // Longest allowed duration of a shift
	public $minimum_gap; // Minimum spacing between shifts in same room
	public $hazardous_gap; // Maximum time alone for a person with hazards
	public $pref_values; // Values for respecting personal preferences
	public $am_cutoff;
	public $normal_start = 9*60*60;
	public $normal_end = 18*60*60;

	function __construct($start, $end, $max_length, $minimum_gap, $hazardous_gap,
											 $pref_values, $am_cutoff) {
		$this->start_time = $start;
		$this->end_time = $end;
	 	$this->max_length = $max_length;
		$this->minimum_gap = $minimum_gap;
		$this->hazardous_gap = $hazardous_gap;
		$this->pref_values = $pref_values;
		$this->am_cutoff = $am_cutoff;
	}
}

/* Function to convert a request into a shift starting at a given time on a
 * given day. Verifies that this is an allowed shift (respects rules)
 *
 * returns a Shift object if allowed or null otherwise
 */
function request_to_shift($current_schedule, $request, $start_time, $day, $params) {

	$shift_allowed = false;

	$netid = $request['netid'];
	$room = $request['room_id'];
	$haz = $request['hazardous'];
	$pref = $request['time_pref'];

 	$duration = parse_duration($request['duration']);
	if ($duration > $params->max_length) {
		return null; // This shift is too long
	}

	$end_time = $start_time + $duration;
	$shift = new Shift($netid, $day, $start_time, $end_time, $room, $haz);
	$shift->set_padded_end($params->minimum_gap);
	$allowed = shift_allowed($current_schedule, $shift, $params);
	if ($allowed) {
		determine_value($shift, $request['time_pref'], $params);
		determine_hazard_status($current_schedule, $shift, $params);
		return $shift;
	} else {
		return null;
	}
	return null; // Should never get here, but just in case...
}


/* Function to convert a request into a shift and returns the resulting "value"
 * associated with the new schedule.
 *
 * returns schedule with shift added (or not if it can't be)
 */
function add_shift($current_schedule, $shift, $params) {
	/* Pseudocode:
	1. Turn request into a shift by assigning it a start and end time that
		 correspond to the duration (and a day of the week -- start with Monday)
	2. Check if this is a valid shift given the schedule that exists
		a. If this is not a valid shift, change time / day and try again
		b. Loop through each day before going to the next day (this will tend to
		pack shifts at the start of the week, which will allow for adjustments later on)
	3. If valid, return the schedule with the shift added (returns original
	   array if it cannot be added successfully)
	*/

	$shift_allowed = false;
	$t = 0; // Start of day in seconds
	$midnight = 24*60*60; // midnight in sec

	$increment = $params->minimum_gap; // Hours, how much do we slide a shift by before retrying?

	$day = date('w',strtotime('Monday')); // Integer representation of day of week
																				// Start with Monday then loop back around to Sunday
	$start_time = $params->start_time;
	$end_time = $params->end_time;

	$duration = parse_duration($request['duration']);
	// Make sure shift is within total time limit:
	if ($duration > $params->max_length) {
		return; // This shift is too long
	}

	// Declare here for scoping:
	$shift = new Shift(null,null,null,null,null,null,null);

	while (!$shift_allowed) {
		$shift_start = $t + $start_time;
		$shift_end = $shift_start + $duration;



		$shift = new Shift($request['netid'], $day, $shift_start,
																	 $shift_end,$request['room_id'],
																	 $request['hazardous'], 0);

		$shift_allowed = shift_allowed($current_schedule, $shift, $params);

		if ($shift_end + $increment <= $end_time) {
			$t += $increment;
		} elseif ($day == 0) {
			return; // This shift is not possible to fit in this week
		} else {
			$t = 0;
			$day++;
			$day = $day % 7; // So we loop back to Sunday
		}
	}

	// Check user preferences and add value:
	$shift->value = determine_value($shift, $request['time_pref'], $params);
	$shift->set_padded_end($params->minimum_gap);

	$current_schedule->add_shift_to_schedule($shift);

	return $current_schedule;

}


/* Function to determine the value of a given shift depending on the
 * preferences of the requester. Also increases value for "normal" working hours
 */
function determine_value(&$shift, $pref, $params) {
	$shift_start = $shift->start;
	$shift_end = $shift->end;
	$day = $shift->day;
	$temp_val = 0;
	if ($shift_end <= $params->am_cutoff) { // this is an AM shift
		switch ($pref) {
			case 'AM':
				$temp_val = 0;
				break;
			case 'PM':
				$temp_val = 2;
				break;
			}
		} elseif ($shift_start >= $params->am_cutoff) { // this is a PM shift
			switch ($pref) {
				case 'AM':
					$temp_val = 2;
					break;
				case 'PM':
					$temp_val = 0;
					break;
			}
		} else {
			$temp_val = 1; // If an in-between shift, it's neither best (0) nor worst (2)
		}


	// Now prefer ordinary working hours (09:00 - 18:00):
	if ( $shift_start <= $params->normal_start || $shift_end >= $params->normal_end ) {
		$temp_val += 1;
	}

	// And prefer during the work week (Mon-Fri):
	if (0 == $day || $day == 6) {
		$temp_val += 1; // This is more valuable than being during normal hours.
	}

	$shift->out_of_norm_hours = $temp_val;
	//echo "temp_val = " . $temp_val . "\n";
	return;
}

/* Function to check if new shift is allowed to be added.
 * checks for: overlaps in rooms, no back-to-back shifts,
 * and that if you are doing hazardous things someone else is around most of
 * the time.
 *
 * returns true if the shift is allowed (false otherwise)
 */
function shift_allowed($current_schedule, $new_shift, $params) {
	$day = $new_shift->day;
	$start = $new_shift->start;
	$end = $new_shift->end;
	$room = $new_shift->room;
	$netid = $new_shift->netid;

	// Make sure the room isn't booked at this time
	$day_schedule = $current_schedule->day_shifts[$day];
	$booked_times = find_room_shifts($room, $day_schedule);
	//var_dump($booked_times);

	if (!empty($booked_times)) {
		foreach ($booked_times as $booked_time) {
			if (shifts_overlap_padded($new_shift, $booked_time)) {
				return false;
			}
		}
	}

	// Make sure this user isn't booked at this time:
	$netid_times = find_netid_shifts($netid, $day_schedule);

	if (!empty($netid_times)) {
		foreach ($netid_times as $netid_time) {
			if (shifts_overlap($new_shift, $netid_time)) {
				return false;
			}
		}
	}

	return true;
}

/* Function to return all shifts that are in the input room_id. Takes an array
 * of shifts as input (e.g. from $schedule[0]).
 *
 * Returns an array of shifts in that room
 */
function find_room_shifts($room_id, $shift_array) {
	$out = array();
	foreach ($shift_array as $shift) {
		if ($shift->room == $room_id) {
			$out[] = $shift;
		}
	}
	return $out;
}

/* Function to return all shifts that a given netid has. Takes an array
 * of shifts as input (e.g. from $schedule[0]).
 *
 * Returns an array of shifts for that netid
 */
function find_netid_shifts($netid, $shift_array) {
	$out = array();
	foreach ($shift_array as $shift) {
		if ($shift->netid == $netid) {
			$out[] = $shift;
		}
	}
	return $out;
}

/* Function to find shifts from a schedule that at least partially overlap with
 * a given input shift for use as a hazard buddy
 *
 * returns an array of shifts (or empty if none) sorted by start time
 */
function find_buddy_shifts($schedule, $shift) {

	$day = $shift->day;
	$day_schedule = $schedule->day_shifts[$day];
	if (empty($schedule->day_shifts[$day])) {
		return [];
	}
	$overlapping_shifts = [];
	foreach ($day_schedule as $day_shift) {
		if (shifts_overlap($shift, $day_shift)) {
			// Note that this already exlcudes shifts in the same room as $shift as they can't overlap anyway
			$overlapping_shifts[] = $day_shift;
		}
	}
	usort($overlapping_shifts, "buddy_cmp");
	return $overlapping_shifts;
}

/* Function to sort buddy shifts by start time */
function buddy_cmp($a, $b) {
	if ($a->start == $b->start) {
		return 0;
	}
	return ($a->start < $b->start) ? -1 : 1;
}
/* Function to determine if two shifts overlap in time (not counting padded
 * endtime after a user leaves)
 *
 * Returns true if the shifts overlap
 */
function shifts_overlap($shift1,$shift2) {
	if ( ($shift1->start <= $shift2->start && $shift1->end > $shift2->start) ||
			 ($shift2->start <= $shift1->start && $shift2->end > $shift1->start) ) {
		return true;
	}
	return false;
}


/* Function to determine if two shifts overlap in time (including padded
 * endtime after a user leaves)
 *
 * Returns true if the shifts overlap
 */
function shifts_overlap_padded($shift1,$shift2) {
	if ( ($shift1->start <= $shift2->start && $shift1->padded_end > $shift2->start) ||
			 ($shift2->start <= $shift1->start && $shift2->padded_end > $shift1->start) ) {
		return true;
	}
	return false;
}


/* Function to parse a request's duration parameter into a number of seconds:
*
* Returns duration in seconds
*/
function parse_duration($time_str) {
 $parsed = date_parse($time_str);
 return $parsed['hour']*60*60 + $parsed['minute']*60 + $parsed['second'];

}


/* Function to track if a shift a) needs a buddy for safety and b) if it has
 * a buddy(ies) (or only smaller gaps)
 */

function determine_hazard_status($current_schedule, &$shift, $params) {
	if (!$shift->hazardous) {
		return; // This shift doesn't need a buddy
	} else {
		$buddy_shifts = find_buddy_shifts($current_schedule, $shift);
		determine_overlap_interval($buddy_shifts, $shift, $params);
	}
}

/* Recursive function to determine if a shift is totally covered (or not) by
 * the shifts it overlaps with for safety
 *
 * Assumes input $buddy_shifts are sorted with the earliest ones first
 *
 * Updates the shift in place if it is covered and safe
 */
function determine_overlap_interval($buddy_shifts, &$shift, $params) {

	if (empty($buddy_shifts) || $shift->hazard_num == 0) {
		return;
	} else {
		$gap = $params->hazardous_gap;
		$b_start = $buddy_shifts[0]->start;
		$b_end = $buddy_shifts[0]->end;
		$uncovered_shift = clone $shift;
		if ($b_start - $gap <= $shift->start) {
			$uncovered_shift->start = $b_end;
			unset($buddy_shifts[0]); // Remove this shift from our buddy array
			$buddy_shifts = array_values($buddy_shifts); // Reindex so we can reference from 0 again
			//exit();
			determine_overlap_interval($buddy_shifts, $uncovered_shift, $params);
		} elseif ($b_end + $gap >= $shift->end) {
			$uncovered_shift->end = $b_start;
			unset($buddy_shifts[0]); // Remove this shift from our buddy array
			determine_overlap_interval($buddy_shifts, $uncovered_shift, $params);
		}
	}

	$duration = $uncovered_shift->end - $uncovered_shift->start;

	/*if (!empty($buddy_shifts)) {
		var_dump($buddy_shifts);
		//exit();
	}*/
	if ($duration <= $gap) {
		$shift->hazard_num = 0;
	}
	return;
}


// Global to store best out-of-preference number:
$best_out = 99999;

// Value of shifts (really just number of shifts scheduled):
$best_val = 0;

// And number of hazard shifts:
$best_hazard = 99999;

/* Function to run our recursion to search for best solution:
 * This is the "outer loop" of the recursion, it runs only when there is a
 * complete schedule from the sub-recursion function.
 */
function recursive_search_main($requests, $schedule, $params) {
	if (empty($requests)) {
		return $schedule;
	} else {
		global $best_out, $best_hazard;

		$best_schedule = $schedule;

		// Constant time variables for when shifts can be
		$start_time = $params->start_time;
		$end_time = $params->end_time;
		$increment = $params->minimum_gap; // Hours, how much do we slide a shift by before retrying?

		$requests_remaining = $requests;

		foreach ($requests as $key => $request) {
		  unset($requests_remaining[$key]); // Removes the element we're working on

			$duration = parse_duration($request['duration']);

  		for ($day = 0; $day < 7; $day++) {
				for ($t = $start_time; $t <= $end_time - $duration; $t += $increment) {
					$this_schedule = clone $schedule;
					$shift = request_to_shift($schedule, $request, $t, $day, $params);
					if (!is_null($shift)) {
						if (empty($requests_remaining && $day == 6)) {
							echo "Shift netid: ".$shift->netid. "\n";
						}
						$total = $shift->get_out_num() + $this_schedule->get_out_num();
						if ($total >= $best_out) {
							/*echo "Score too high!!!\n";
							var_dump($shift);
							var_dump($this_schedule);
							return $schedule;*/
						} else {
							$this_schedule->add_shift_to_schedule($shift);
							$new_schedule = recursive_search_sub($requests_remaining, $this_schedule, $params);
							$best_out = $new_schedule->get_out_num();
							$best_schedule = $new_schedule;
							$best_hazard = $new_schedule->hazard_gaps;
							//if (empty($requests_remaining)) {
								echo "Inside new_schedule block...\n";
								//var_dump($best_schedule);
							//}
							//if (empty($requests_remaining)) {
								echo "Number of Shifts Scheduled: " . $new_schedule->count_shifts() . "\n";
								echo "Number of Shifts Out of Pref.: " . $new_schedule->get_out_num() . "\n";
								//var_dump($new_schedule);
								echo "------------------\n";
							//}
						}
					}
				}
			}
		}
		echo "Number of best_Shifts Scheduled: " . $best_schedule->count_shifts() . "\n";
		echo "Number of best_Shifts Out of Pref.: " . $best_schedule->get_out_num() . "\n";
		return $best_schedule;
	}
}

/* Recursive sub-function. Is the same as the _main version, but it doesn't
 * update the hazard number or best out of pref schedule. It does check against
 * them however.
 */
function recursive_search_sub($requests, $schedule, $params) {
	if (empty($requests)) {
		return $schedule;
	} else {
		global $best_out, $best_hazard;

		$best_schedule = $schedule;

		// Constant time variables for when shifts can be
		$start_time = $params->start_time;
		$end_time = $params->end_time;
		$increment = $params->minimum_gap; // Hours, how much do we slide a shift by before retrying?

		foreach ($requests as $key => $request) {
			$requests_remaining = $requests;
		  unset($requests_remaining[$key]); // Removes the element we're working on
			echo "Working on request: ".$request['netid']."\n";
			$duration = parse_duration($request['duration']);

  		//for ($day = 0; $day < 7; $day++) {
			$t = 0;
			$day = 0;
				for ($t = $start_time; $t <= $end_time - $duration; $t += $increment) {
					$this_schedule = clone $schedule;
					$shift = request_to_shift($this_schedule, $request, $t, $day, $params);
					if (!is_null($shift)) { // This is a valid shift
						$total = $shift->get_out_num() + $this_schedule->get_out_num();
						if ($total >= $best_out) {
							/*echo "Score too high!!!\n";
							var_dump($shift);
							var_dump($this_schedule);
							return $schedule;*/
						} else {
							echo "shift_out_num: ".$shift->get_out_num()."\n";
							echo "this_schedule_out_num: ".$this_schedule->get_out_num()."\n";
							$this_schedule->add_shift_to_schedule($shift);
							$new_schedule = recursive_search_sub($requests_remaining, $this_schedule, $params);
							$best_schedule = $new_schedule;
							$best_out = $best_schedule->get_out_num();
							echo "Number of best_Shifts Scheduled: " . $best_schedule->count_shifts() . "\n";
							echo "Number of best_Shifts Out of Pref.: " . $best_schedule->get_out_num() . "\n";
							echo "best_out: $best_out\n";
							echo "total: $total\n";

							//if (empty($requests_remaining)) {
								echo "Last scheduled netid: ".$shift->netid."\n";
								//var_dump($best_schedule);
							//}
							echo "________________________\n";
						}
					}
				}
			//}
		}
		return $best_schedule;
	}
}

function recursive_search_2($requests, $schedule, $params) {
	if (!empty($requests)) {
		global $best_out, $best_hazard, $best_val;

		$best_schedule = $schedule;
		// Constant time variables for when shifts can be
		$start_time = $params->start_time;
		$end_time = $params->end_time;
		$increment = $params->minimum_gap;

		foreach($requests as $key => $request) {
			$requests_remaining = $requests;
			unset($requests_remaining[$key]); // Remove this request from the list
			$duration = parse_duration($request['duration']);

			for ($day = 0; $day < 7; ++$day) {
			//$day = 0;
				//echo "day is: $day\n";
				for ($time = $start_time; $time + $duration <= $end_time; $time += $increment) {
					$shift = request_to_shift($schedule, $request, $time, $day, $params);
					if (!is_null($shift)) { // Check if a valid shift
						$temp_total = $shift->get_out_num() + $schedule->get_out_num();
						//echo "temp_total = $temp_total\n";
						if ($temp_total < $best_out) { // This shift combo might improve the schedule

							$this_schedule = clone $schedule;
							$this_schedule->add_shift_to_schedule($shift);
							$this_schedule = recursive_search_2($requests_remaining, $this_schedule, $params);
							$best_out = $this_schedule->get_out_num();
							if ($this_schedule->get_value() >= $best_val) {
								//echo "New best schedule found!\n";
								$best_schedule = $this_schedule;

								$best_val = $best_schedule->get_value();
								//echo "Updating best schedule values. best_out = $best_out!\n";
							}
						}
					}
				}
			}
			//echo "returning schedule\n";
			//var_dump($best_schedule);
			return $best_schedule;
		}
	} else {
		//echo "Updating best schedule values. best_out = ".$schedule->get_out_num()."!\n";
		//$best_out = $schedule->get_out_num();
		return $schedule;
	}
}


function recursive_search_tr($requests, $schedule, $params) {
	if (!empty($requests)) {
		$best_schedule = $schedule;
		global $best_out, $best_hazard;

		// Constant time variables for when shifts can be
		$start_time = $params->start_time;
		$end_time = $params->end_time;
		$increment = $params->minimum_gap;
		$this_schedule = $schedule;

		foreach($requests as $key => $request) {
			$running_total = 9999; // For tracking best placement of the request we're working on
			$requests_remaining = $requests;
			unset($requests_remaining[$key]); // Remove this request from the list
			$duration = parse_duration($request['duration']);

			for ($day = 0; $day < 7; $day++) {
			//$day = 0;
				//echo "day is: $day\n";
				for ($time = $start_time; $time + $duration <= $end_time; $time += $increment) {
					$shift = request_to_shift($schedule, $request, $time, $day, $params);
					if (!is_null($shift)) {
						$temp_total = $shift->get_out_num();
						//echo "temp_total = $temp_total\n";
						if ($temp_total < $running_total) { // This shift is better
							$running_total = $temp_total;
							if ($temp_total + $schedule->get_out_num() < $best_out) { // And now the whole schedule is better
								//echo "New best schedule found!\n";
								$this_schedule = clone $schedule;
								$this_schedule->add_shift_to_schedule($shift);
							}
						}
					}
				}
			}
			return recursive_search_tr($requests_remaining, $this_schedule, $params);
		}
	} else {
		$best_out = $schedule->get_out_num();
		return $schedule;
	}
}

/* Function to add a shift to the schedule database:
 *
 * $week is the date of the Sunday for the week we are scheduling for.
 */
function add_shift_to_db($db, $shift, $week) {
	require __DIR__ . "/table_variables.php";
	$start_time = gmdate('H:i:s', $shift->start);
	$end_time = gmdate('H:i:s', $shift->end);
	$day = $week. ' + ' . $shift->day . ' days';
	$date = date('Y-m-d', strtotime($day));
	$start_ts = $date . " " . $start_time; // Starting timestamp
	$end_ts = $date . " " . $end_time; // Ending timestamp

	$sql = "INSERT INTO $schedule_table (netid, room_id, start_time, end_time,
					week_start) VALUES (:netid, :room, :start_time, :end_time, :week)";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':netid', $shift->netid);
	$stmt->bindValue(':room', $shift->room);
	$stmt->bindValue(':start_time', $start_ts);
	$stmt->bindValue(':end_time', $end_ts);
	$stmt->bindValue(':week', $week);
	$success = $stmt->execute();
	return $success;
}
