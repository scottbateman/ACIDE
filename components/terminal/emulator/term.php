<?php
	//FIXME Support of windows, use command 'cd' instead of 'pwd'

    /*
    *  PHP+JQuery Terminal Emulator by Fluidbyte <http://www.fluidbyte.net>
    *
    *  This software is released as-is with no warranty and is complete free
    *  for use, modification and redistribution
    */
    
    //////////////////////////////////////////////////////////////////
    // Password
    //////////////////////////////////////////////////////////////////
    
    define('PASSWORD','terminal');
    
    //////////////////////////////////////////////////////////////////
    // Core Stuff
    //////////////////////////////////////////////////////////////////
    
    require_once('../../../common.php');
	require_once('../../../components/userlog/class.userlog.php');
    
    //////////////////////////////////////////////////////////////////
    // Verify Session or Key
    //////////////////////////////////////////////////////////////////
    
    checkSession();

    //////////////////////////////////////////////////////////////////
    // Globals
    //////////////////////////////////////////////////////////////////
    // if the user doesn't have any porject the ROOT will be the workspace
    $project_path = "";
    if (isset($_SESSION['project'])) {
    	$project_path = '/' . $_SESSION['project'];
    }
	
    define('ROOT',WORKSPACE . $project_path);
    
    define('BLOCKED','ssh,telnet');
    
    //////////////////////////////////////////////////////////////////
    // Terminal Class
    //////////////////////////////////////////////////////////////////
    
    class Terminal{
        
        ////////////////////////////////////////////////////
        // Properties
        ////////////////////////////////////////////////////
        
        public $command          = '';
        public $output           = '';
        public $directory        = '';
        
        // Holder for commands fired by system
        public $command_exec     = '';
        
        ////////////////////////////////////////////////////
        // Constructor
        ////////////////////////////////////////////////////
        
        public function __construct(){
            if(!isset($_SESSION['dir']) || empty($_SESSION['dir'])){
                if(ROOT==''){
                    $this->command_exec = 'pwd';
                    $this->Execute();
                    $_SESSION['dir'] = $this->output;
                }else{
                    $this->directory = ROOT;
                    $this->ChangeDirectory();
                }
            }else{
                $this->directory = $_SESSION['dir'];
                $this->ChangeDirectory();
            }
        }
        
        ////////////////////////////////////////////////////
        // Primary call
        ////////////////////////////////////////////////////
        
        public function Process(){
        	$current_directory = explode("/", $this->directory);
			$current_directory = $current_directory[count($current_directory)-1];
			
			if ($current_directory != "workspace") {
	            $this->ParseCommand();
	            $this->Execute();
			} else {
				return "Warning: No working directory set. Please choose one project in the project navigator first.";
			}
			
			if (substr($this->command,0,5) == "javac") {
				$Userlog = new Userlog();
				$Userlog->username = $_SESSION['user'];
				$Userlog->path = $this->directory;
				$Userlog->command = $this->command;
				$Userlog->language = "java";
				$Userlog->output = $this->output;
				
				if ($this->output != "") {
					$Userlog->succeeded = "FALSE";	
				} else {
					$Userlog->succeeded = "TRUE";
				}
				
				$Userlog->SaveAsCompilationAttempt();				
			}
            return $this->output;
        }
        
        ////////////////////////////////////////////////////
        // Parse command for special functions, blocks
        ////////////////////////////////////////////////////
        
        public function ParseCommand(){
			
			$current_directory = explode("/", $this->directory);
			$current_directory = $current_directory[count($current_directory)-1];
			
			if ($current_directory == "emulator") {
				$this->command = 'echo Warning: No working directory set. Please choose one project in the project navigator first.';
				$this->command_exec = $this->command . ' 2>&1';
			} else {			
	            // Explode command
	            $command_parts = explode(" ",$this->command);
				
				////////////////////////////////////////////////////
				// LF: Defining the allowed commands in the terminal
				////////////////////////////////////////////////////
				
				// LF: The blank command is necessary because apparently the terminal executes a blank command when it opens
				$allowed_commands[] = "";
				$allowed_commands[] = "ls";
				$allowed_commands[] = "javac";
				$allowed_commands[] = "java";
				$allowed_commands[] = "cd";
				$java_command_executed = FALSE;
				/* LF: 
				 * Compare the array of allowed commands with the commands received from the terminal,
				 * if there is at least one intersection, the rest of the code is executed, if not
				 * a message "echo ERROR: Command not allowed" is showed in the terminal.
				 */ 
				
				$first_command = $command_parts[0];
				$result = in_array($first_command, $allowed_commands);
				
				$unallowed_commands[] = ";";
				$unallowed_commands[] = "|";
				$unallowed_commands[] = ">";
				$unallowed_commands[] = "<";
				$unallowed_commands[] = "`";
				$unallowed_commands[] = "$";
				$unallowed_commands[] = "!";
				$unallowed_commands[] = "-";
				
				// TODO REMOVE LS PARAMETERS
				
				$unallowed_command_detected = FALSE;
				foreach($command_parts as $command_part) {
					for ($o = 0 ; $o < count($unallowed_commands); $o++){ 	
						if (strpos($command_part, $unallowed_commands[$o]) !== false) {
						    $unallowed_command_detected = TRUE;
						}
					}
				}
				
				if (!empty($result) && !$unallowed_command_detected) {
		            // Handle 'cd' command
		            if(in_array('cd',$command_parts) || in_array('ls',$command_parts)){
		            	if (in_array('cd',$command_parts)) {
		            		$cd_key = array_search('cd', $command_parts);	
		            	} else {
		            		$cd_key = array_search('ls', $command_parts);
		            	}
		                
		                $cd_key++;
						$unmodified_previous_directory = $this->directory;
		                @$this->directory = $command_parts[$cd_key];
						
						$cd_allowed = TRUE;
						
						// LF: Handle access
						if ($this->directory[0] == '/') {
							$cd_allowed = FALSE;
						} else if (substr($this->directory, 0, 2) == '..') {
							$previous_directory = explode('/', $unmodified_previous_directory);
							if ($previous_directory[count($previous_directory) -2] == "workspace") {
								$cd_allowed = FALSE;
							}
						} 
						
						// LF: Get how many '..' are in the user's command
						$two_dots_count = 0;
						$current_directory = explode('/', $this->directory);
						for ($in = 0; $in < count($current_directory); $in++) {
							if ($current_directory[$in] == '..') {
								$two_dots_count++;
							}
						}
						
						if ($two_dots_count > 1) {
							$cd_allowed = FALSE;
						}
						
						if ($cd_allowed) {
							if (in_array('cd',$command_parts)) {
								$this->ChangeDirectory();
			                	// Remove from command
			                	$this->command = str_replace('cd '.$this->directory,'',$this->command);
								//$this->output = "cd executed, eh?";	
								//$this->command .= " ; echo cd executed, eh?" . ' 2>&1';
							} else {
								$this->command_exec = $this->command . ' 2>&1';
							}
								
						} else {
							// Resetting the directory in case the last command modified it 
							$this->directory = $unmodified_previous_directory;
							$this->command = 'echo ERROR: Command not allowed';
							//$this->command_exec = $this->command . ' 2>&1';
						}
		            } else if(in_array('java',$command_parts)){            	
						// Handle java command
						
						$java_command_executed = TRUE;
						
						$filename = $command_parts[1] . ".java";
						
						/*
						 * Get the arguments
						 */
						$arguments = array();
						$ai = 2;
						while ($ai < count($command_parts) && $command_parts[$ai] != "&&") {
							$arguments[] = $command_parts[$ai];	
							$ai++;
						}
						
						$file = file($filename);
						$matchedLines = array();
						
						// $this->command = 'echo Executing file ' . $this->directory . "/" .$command_parts[1];
						// $this->command_exec = $this->command . ' 2>&1';
						
						/*
						 * Get class name
						 */
						$indx = 0;
						$class_name = "";
						
						foreach($file as $line) {
							$indx++;
							$class_string_position = strpos($line, "public class");
							if ($class_string_position !== FALSE) {
								preg_match('/public class (.*?){/', $line, $class_name_match);
								$class_name = trim($class_name_match[1]);
							} 
						}
						
					
						/*
						* Create jar File
						*/
						$exploded_directory = explode("/", $this->directory);
						$jar_name = $exploded_directory[count($exploded_directory)-1]. ".jar";
						
						define('__ROOT__', dirname(dirname(dirname(dirname(__FILE__))))); 
						$jar_path_and_name = __ROOT__ . "/javaws_workspace/" . $jar_name;
						
						system("jar cf ". $jar_path_and_name . " *.class");
						 
						// Sign the file
						system("jarsigner -keystore /var/acide_files/keystore_file.keys -storepass '123123' " . $jar_path_and_name . " http://hci.csit.upei.ca/");
						//system("jarsigner -keystore /var/codiad_files/jaxb.keys -storepass 'keystore password' " . $jar_path_and_name . " http://hci.csit.upei.ca/"); 
						/* 
						 * Download the file
						 */
						 
						// Organize the arguments
 						$url_arguments = "";
						for ($i = 0; $i < count($arguments); $i++) {
							$url_arguments .= "&arguments[]=" . $arguments[$i];
						}
						
						// Get page URL
						$pageURL = WEB_BASE_PATH;
						
						
						// Set the GET arguments
						$pageURL .= "/javaws_workspace/jnlp_xml/jnlp_xml_generator.php";
						$pageURL .= "?jar_name=" . $jar_name;
						$pageURL .= "\&class_name=" . $class_name;
						
						$pageURL .= $url_arguments;
						
						//header('Content-type: application/x-java-jnlp-file');
						//header('Location: ' . $pageURL);
						//exit;
						//$javascript ="	<script  type=\"text/javascript\">window.open('". $pageURL ."','_blank');</script>";
						urlencode($pageURL);
						$this->command = 'echo opnths::: ' . $pageURL;
						
						$this->command_exec = $this->command. ' 2>&1';
					}
		            
					if (!$java_command_executed) {
						// Replace text editors with cat
			            $editors = array('vim','vi','nano');
			            $this->command = str_replace($editors,'cat',$this->command);
		            
			            // Handle blocked commands
			            $blocked = explode(',',BLOCKED);
			            if(in_array($command_parts[0],$blocked)){
			                $this->command = 'echo ERROR: Command not allowed';
			            }
		            	
			            // Update exec command
			            $this->command_exec = $this->command . ' 2>&1';
					}
				} else {
					$this->command = 'echo ERROR: Command not allowed';
					$this->command_exec = $this->command . ' 2>&1';
				}
			}
		}
        
        ////////////////////////////////////////////////////
        // Chnage Directory
        ////////////////////////////////////////////////////
        
        public function ChangeDirectory(){
            chdir($this->directory);
            // Store new directory
            $_SESSION['dir'] = exec('pwd');
        }
        
        ////////////////////////////////////////////////////
        // Execute commands
        ////////////////////////////////////////////////////
        
        public function Execute(){
            //system
            if(function_exists('system')){
                ob_start();
                system($this->command_exec);
                $this->output = ob_get_contents();
                ob_end_clean();
            }
            //passthru
            else if(function_exists('passthru')){
                ob_start();
                passthru($this->command_exec);
                $this->output = ob_get_contents();
                ob_end_clean();
            }
            //exec
            else if(function_exists('exec')){
                exec($this->command_exec , $this->output);
                $this->output = implode("\n" , $output);
            }
            //shell_exec
            else if(function_exists('shell_exec')){
                $this->output = shell_exec($this->command_exec);
            }
            // no support
            else{
                $this->output = 'Command execution not possible on this system';
            }
        }        
        
    }
    
    //////////////////////////////////////////////////////////////////
    // Processing
    //////////////////////////////////////////////////////////////////
    
    $command = '';
    if(!empty($_POST['command'])){ $command = $_POST['command']; }
    
	if(! isset($_SESSION['term_auth']) || $_SESSION['term_auth']!='true'){
		// Removing the password when the terminal opens
		$_SESSION['term_auth'] = 'true';
	}
	
    if(strtolower($command=='exit')){
        
        //////////////////////////////////////////////////////////////
        // Exit
        //////////////////////////////////////////////////////////////
        
        $_SESSION['term_auth'] = 'false';
        $output = '[CLOSED]';
        
    }else if(! isset($_SESSION['term_auth']) || $_SESSION['term_auth']!='true'){
        
        //////////////////////////////////////////////////////////////
        // Authentication
        //////////////////////////////////////////////////////////////
        
        if($command==PASSWORD){
            $_SESSION['term_auth'] = 'true';
            $output = '[AUTHENTICATED]';
        }else{
            $output = 'Enter Password:';
        }
        
    }else{
    
        //////////////////////////////////////////////////////////////
        // Execution
        //////////////////////////////////////////////////////////////
        
        // Split &&
        $Terminal = new Terminal();
        $output = '';
		
		if ($command == "change_directory") {
			/*$Terminal->directory = ROOT . $_POST['target_path'];
			error_log("TARGET PATHY :"  . ROOT);
			error_log("TARGET PATHE = " . $_POST['target_path']);
			error_log("TARGET PATH = " . $Terminal->directory);
			 * 
			 */
			//$output .= "Directory changed to '" . $_POST['target_name'] . "' project root directory.";
			$Terminal->directory = ROOT;
			$Terminal->ChangeDirectory();
		}
		
		if ($command == "get_current_directory") {
			//error_log("get_current_directory called: current directory is :" . $_SESSION['dir']);
			$output = $_SESSION['dir'];
		} else if (substr($command, 0, 20) == "change_directory_to:") {
			$this_directory = substr($command, 20, (strlen($command)-20));
			$this_directory = explode("/", $this_directory);
			$new_directory = "";
			
			for($s = 1; $s < count($this_directory); $s++) {
				$new_directory .= "/" . $this_directory[$s];
			}
			$new_directory = ROOT . $new_directory;
			//error_log("changing directory to ->" . $new_directory);
			//error_log("root  = ". ROOT);
			
			$Terminal->directory = $new_directory;
			$Terminal->ChangeDirectory();
			
			$output = $_SESSION['dir'];
		} else {
			$command = explode("&&", $command);
			//$command = explode(";", $command);
			
	        foreach($command as $c){
	            $Terminal->command = $c;
	            $output .= $Terminal->Process();
	        }	
		}
    }
	
    echo(htmlentities($output));



?>
