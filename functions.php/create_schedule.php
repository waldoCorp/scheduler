<?php
s
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
	$max_length = 10000000; // No max length yet
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
	$am_cutoff = 15*60*60; // 15:00 in ms

	// Store all this in an object so we can easily pass it around:
	$params = new Schedule_Parameters($start_time, $end_time, $max_length, $gap, $gap,
																		$pref_values, $am_cutoff);

	// "Value" for the schedule as described so far:
	$value = 0;

	// Start with empty schedule
	$schedule = new Schedule;

	// TESTING
	/*foreach ($requests as $request) {
		$schedule = add_shift($schedule, $request, $params);
	}*/
	// Now, we can recurse through all the requests and try to find the best combination:
	$best_sched = recursive_search($requests, $schedule, $params);
	//$best_sched = $schedule;
	return $best_sched;

}

/* Class for a shift */
class Shift {
	public $netid;
	public $day;
	public $start;
	public $end;
	public $room;
	public $hazardous;
	public $value;
	public $padded_end;

	function __construct($netid, $day, $start, $end, $room, $hazardous, $value) {
		$this->netid = $netid;
		$this->day = $day;
	 	$this->start = $start;
		$this->end = $end;
		$this->room = $room;
		$this->hazardous = $hazardous;
		$this->value = $value;
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
	public $value = 0;

	/* Takes a Shift object and adds it to the schedule, also increments total
	   schedule value to include new shift */
	function add_shift_to_schedule($shift) {
		$this->day_shifts[$shift->day][] = $shift;
		$this->value += $shift->value;
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

/* Function to convert a request into a shift and returns the resulting "value"
 * associated with the new schedule.
 *
 * returns schedule with shift added (or not if it can't be)
 */
function add_shift($current_schedule, $request, $params) {
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
function determine_value($shift, $pref, $params) {
	$value = $params->pref_values['none'];
	$shift_start = $shift->start;
	$shift_end = $shift->end;
	$day = $shift->day;
	if ($shift_end < $params->am_cutoff) { // this is an AM shift
		switch ($pref) {
			case 'AM':
				$value = $params->pref_values['pref'];
				break;
			case 'PM':
				$value = $params->pref_values['anti'];
				break;
			}
		} elseif ($shift_start > $params->am_cutoff) { // this is a PM shift
			switch ($pref) {
				case 'AM':
					$value = $params->pref_values['anti'];
					break;
				case 'PM':
					$value = $params->pref_values['pref'];
					break;
			}
		}

	// Now prefer ordinary working hours (09:00 - 18:00):
	if ($shift_start <= $params->normal_start && $shift_end <= $params->normal_end) {
		$value += 0.01;
	}

	// And prefer during the work week (Mon-Fri):
	if (0 < $day && $day < 6){
		$value += 0.02; // This is more valuable than being during normal hours.
	}

	return $value;
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

/* Function to run our recursion to search for best solution: */
function recursive_search($requests, $schedule, $params) {
	if (empty($requests)) {
		return $schedule;
	} else {
		$best_schedule = $schedule;
		$best_val = $schedule->value;
		$requests_remaining = $requests;
		var_dump($requests);

		foreach ($requests as $key => $request) {
		  unset($requests_remaining[$key]); // Removes the element we're working on

		  // try to add this request:
		  $new_schedule = add_shift($schedule, $request, $params);
		  if (!is_null($new_schedule)) {
			  $new_schedule = recursive_search($requests_remaining, $new_schedule, $params);
			  if ($new_schedule->value > $best_val) {
				 $best_schedule = $new_schedule;
				 $best_val = $schedule->value;
			  }
		  }
		}
		return $best_schedule;
	}
}
