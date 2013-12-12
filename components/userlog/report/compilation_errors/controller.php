<?php

/*
 *  Copyright (c) sbateman@upei.ca, lrosa@upei.ca
 */

require_once ('../class.userlogreport.php');
//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

checkSession();

$Userlogreport = new Userlogreport();

//////////////////////////////////////////////////////////////////
// Get Data for Chart
//////////////////////////////////////////////////////////////////

if ($_GET['action'] == 'get_data_for_chart') {
	// [0] => students
	// [1] => assignments
	// [2] => group_by
	$data_array = array();
	$data_array = $_POST['data_array'];
	
	$students = array();
	$assignments= array();
	$group_by = NULL;
	
	//error_log(print_r($data_array, true));
	
	if (isset($data_array[0])) {
		$students = $data_array[0];	
	}
	if (isset($data_array[1])) {
		$assignments = $data_array[1];
	}
	
	if (isset($data_array[2])) {
		$group_by = $data_array[2];
	}
	
	$course_id = $data_array[3];
	
	// Get data <--
	$compilation_errors = array();

	$error_to_log = "";
	/*
	 * $outputted_errors groups the errors and count them, each position is another array $error
	 */
	$outputted_errors = array();
	$single_error = array();
	
	// Check if there are no students, then load all of them
	if (!isset($students[0]) || count($students[0]) == 0) {
		$User = new User();
		//$User->users = getJSON('users.php');
		// Connect
		$mongo_client = new MongoClient();
		// select the database
		$database = $mongo_client -> selectDB(DATABASE_NAME);
		// Select the collection
		$collection = $database -> users;
		
		$users = $User -> GetUsersInCourse($course_id);
		
		$user_types = $User -> GetUsersTypes();
		$student_user_type = $user_types[0];
		
		$students = array();
		
		foreach ($users as $user) {
			if ($user['type'] == $student_user_type) {
				$students[] = $user['username'];
			}
		}
	}
	
	// Check if there are no assignments, then load all of them
	if (!isset($assignments[0]) || count($assignments[0]) == 0) {
		$Project = new Project();
		$current_user = $_SESSION['user'];
		$raw_assignments = $Project->GetAssignmentsInTheSameCoursesOfUser($current_user, $course_id);
		
		foreach($raw_assignments as $raw_assignment) {
			$assignments[] =$raw_assignment['id'];
		}
	}
	
	$assignments_with_counters = array();
	for ($k = 0; $k < count($assignments); $k++) {
		if ($group_by == 2) {
			$this_assignment_counters = array();
			$this_assignment_counters['assignment'] = $assignments[$k];
			$this_assignment_counters['counters'] = array();
			$assignments_with_counters[] = $this_assignment_counters;		
		}	
	}
	
	for ($idx = 0; $idx < count($students); $idx++) {
		
		$user_assignments = $assignments;

		for ($k = 0; $k < count($user_assignments); $k++) {
			$user_assignments[$k] = "AS_" . $students[$idx] . "_" . $user_assignments[$k];
		}
		
		
		$Userlogreport = new Userlogreport();
		$Userlogreport -> username =  $students[$idx];
		// Compilation attempt test
		$Compilation_userlog = new Userlog();
		$Compilation_userlog -> username = $students[$idx];
		$compilation_attempts = $Compilation_userlog -> GetAllLogsForCompilationAttempt();
		
		$current_error = "";

		/*
		 * $array_of_errors keeps the common errors to be compared with the javac output
		 */
		$array_of_errors = array();
		$array_of_errors[] = "javac: invalid flag:";
		$array_of_errors[] = "javac: file not found:";

		foreach ($compilation_attempts as $compilation_attempt) {
			//error_log("compilation_attempt output : " . $compilation_attempt['output']);
			$current_error = "";
			$display = array();
			$error = $compilation_attempt['output'];
			//error_log("::");error_log("::");error_log("::");
			//error_log("compilation_attempt['path'] = " . $compilation_attempt['path']);
			//error_log("array = " . print_r($user_assignments, true));
			//error_log("::");error_log("::");error_log("::");
			// sanitize array
			//for ($o = 0; $o < count($user_assignments); $o++) {
			//	$user_assignments[$o] = str_replace("@","", $user_assignments[$o]);
			//	$user_assignments[$o] = str_replace(".","", $user_assignments[$o]);
			//}
			
			for ($o = 0; $o < count($user_assignments); $o++) {
				$user_assignments[$o] = Userlogreport::SanitizePath($user_assignments[$o]);
			}
			
			if ($compilation_attempt['succeeded'] == 'FALSE' && in_array($compilation_attempt['path'], $user_assignments)) {
				preg_match('/error:(.*?)\n/', $error, $display);

				$array_of_errors_iterator = 0;
				// Iterate through the errors to find which one it corresponds to
				while ((!isset($display[1])) || @$display[1] == "") {
					$this_error = $array_of_errors[$array_of_errors_iterator];
					if (substr($error, 0, (strlen($this_error))) == $this_error) {
						$display[1] = $this_error . " 'FILENAME'";
					}
					$array_of_errors_iterator++;
				}
				/*
				 * Treat errors like: 'Class names, 'testhallo.txt', are only accepted if annotation processing is explicitly requested'
				 */
				// Remove class/file name
				$current_error = $display[1];
				//error_log("");
                //error_log("current error: ".$current_error);
				//error_log("");
				if (preg_match('/\'(.*?)\'/', $error, $display)) {
					$array_of_symbols = array(";", "\"", "(", ")", "{", "}", "[", "]", ":", ".", "!", "=");
					if (!in_array($display[1], $array_of_symbols)) {
						$current_error = str_replace($display[1], 'FILENAME', $current_error);
					}
				}

				/*
				 * Verify if the error is already inserted in the array
				 */
				$error_already_inserted = FALSE;
				if ($group_by == 0) {
					for ($k = 0; $k < count($outputted_errors); $k++) {
						if ($outputted_errors[$k]['error'] == $current_error) {
							$error_already_inserted = TRUE;
							$outputted_errors[$k]['count']++;
						}
					}	
				} else if ($group_by == 1) {
					for ($k = 0; $k < count($outputted_errors); $k++) {
						if ($outputted_errors[$k]['error'] == $current_error) {
							$error_already_inserted = TRUE;
							for ($l = 0; $l < count($outputted_errors[$k]['users']); $l++) {
								if ($outputted_errors[$k]['users'][$l]['username'] == $students[$idx]) {
									$outputted_errors[$k]['users'][$l]['count']++;
								}
							}
						}
					}					
				} else if ($group_by == 2) {
					for ($k = 0; $k < count($outputted_errors); $k++) {
						if ($outputted_errors[$k]['error'] == $current_error) {
							$error_already_inserted = TRUE;
							//$outputted_errors[$k]['count']++;
							// Add 1 more to the counter in the assignment
							for ($p = 0; $p < count($assignments_with_counters); $p++) {
								if (("AS_" . $students[$idx] . "_" . $assignments_with_counters[$p]['assignment']) == $compilation_attempt['path']) {
									//error_log(("AS_" . $students[$idx] . "_" . $assignments_with_counters[$p]['assignment']) . " == " . $compilation_attempt['path']);
									//error_log("p = $p and k = $k");
									//error_log("Assignment '" .$assignments_with_counters[$p]['assignment'] . " have " . count($assignments_with_counters[$p]['counters']) . " counters." );
									$assignments_with_counters[$p]['counters'][$k]++;
								}
							}
						}
					}
				}
				

				/*
				 * If it's not inserted yet, insert it
				 */
				 if ($group_by == 0) {
				 	if (!$error_already_inserted) {
						$single_error['error'] = $current_error;
						$single_error['count'] = 1;
						$outputted_errors[] = $single_error;
						$error_to_log = $error;
					}
				 } else if ($group_by == 1) {
				 	if (!$error_already_inserted) {
						$single_error['error'] = $current_error;
						$single_error['users'] = array();
						//$single_error['count'] = 1;
						for ($idx2 = 0; $idx2 < count($students); $idx2++) {
							$single_error['users'][$idx2]['username'] = $students[$idx2];
							$single_error['users'][$idx2]['count'] = 0;
							
							if ($single_error['users'][$idx2]['username'] == $students[$idx]) {
								$single_error['users'][$idx2]['count'] = 1;
							}	
						}
						
						$outputted_errors[] = $single_error;
						$error_to_log = $error;
					}
						
				 } else if ($group_by == 2) {
				 	if (!$error_already_inserted) {
						$single_error['error'] = $current_error;
						//$single_error['count'] = 1;
						$outputted_errors[] = $single_error;
						$error_to_log = $error;
						
						// Insert the counter in the assignment
						for ($p = 0; $p < count($assignments_with_counters); $p++) {
							if (("AS_" . $students[$idx] . "_" . $assignments_with_counters[$p]['assignment']) == $compilation_attempt['path']) {
								$assignments_with_counters[$p]['counters'][count($assignments_with_counters[$p]['counters'])] = 1;
							} else {
								$assignments_with_counters[$p]['counters'][count($assignments_with_counters[$p]['counters'])] = 0;
							}
							//error_log("it crossed here");
							//error_log("Assignment '" .$assignments_with_counters[$p]['assignment'] . " have " . count($assignments_with_counters[$p]['counters']) . " counters." );
							//error_log("Count = " . count($assignments_with_counters[$p]['counters']));
						}
					}
				 }

				//echo "<br> $current_error";
				//echo "<br> {" . $compilation_attempt['output'] . "}";
				//echo "<br>" . $display[1];
				//preg_match('/\'(.*?)\'/', $error, $display);
				//echo "<br> this: ";
				//echo "<br> 0 = " . $display[0];
				//echo "<br> 1 = " . $display[1];
				//echo "<br> 2 = " . $display[2];
				//echo "<hr>";
			}
		}
		error_log("Assignments with counters: " . var_export($assignments_with_counters, true). count($assignments_with_counters));
	}

	/*
	 * Show the errors and their counts
	 */
	//for ($k = 0; $k < count($outputted_errors); $k++) {
		//echo "<br>Error: " . $outputted_errors[$k]['error'];
		//echo "<br>Count: " . $outputted_errors[$k]['count'];
		//echo "<hr>";
	//}
	// Get data -->
	
	
	header('Content-type: application/json');
	$response_array['status'] = 'success';
	$response_array['outputted_errors'] = $outputted_errors;
	$response_array['assignments_with_counters'] = $assignments_with_counters;
	//error_log(print_r($response_array['outputted_errors'], true));
	//$response_array['group_by'] = $group_by;
	
	echo json_encode($response_array);
}
?>
