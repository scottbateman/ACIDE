<?php

    /*
    *  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
    *  as-is and without warranty under the MIT License. See 
    *  [root]/license.txt for more. This information must remain intact.
    */

    require_once('../../common.php');
	require_once('../course/class.course.php');
	require_once('../permission/class.permission.php');
	require_once('class.user.php');    
    //////////////////////////////////////////////////////////////////
    // Verify Session or Key
    //////////////////////////////////////////////////////////////////
    
    checkSession();

    switch($_GET['action']){
    
        //////////////////////////////////////////////////////////////
        // List Projects
        //////////////////////////////////////////////////////////////
        
        case 'list':

			$import_user_url = WEB_BASE_PATH . "/components/user/importusers";
			
            $projects_assigned = false;
            if(!checkAccess()){ 
            ?>
            <label>Restricted</label>
            <pre>You can not edit the user list</pre>
            <button onclick="codiad.modal.unload();return false;">Close</button>
            <?php } else { ?>
            <label>User List</label>
            <div id="user-list" style="overflow-y: scroll; height: 400px;">
            <table width="100%">
                <tr>
                    <th>Login</th>
                    <th width="5">Password</th>
                    <th width="5">Projects</th>
                    <th width="5">Edit</th>
                    <th width="5">Delete</th>
                </tr>
            <?php
        
        	/*
            // Get projects JSON data
            //$users = getJSON('users.php');
            // Load the users from the database and verifies if one of them is this one
			// Connect
			$mongo_client = new MongoClient();
			// select the database
			$database = $mongo_client->selectDB(DATABASE_NAME);
			// Select the collection 
			$collection = $database->users;
			// Get all the users in the database
			$users = $collection->find();
			*/
			
			// Load users in the same class
			$User = new User();
			$current_user = $_SESSION['user'];
			$users = $User->GetUsersInTheSameCoursesOfUser($current_user);
						
            foreach($users as $user=>$data){        
            ?>
            <tr>
                <td><a onclick="codiad.user.edit('<?php echo($data['username']); ?>');"><?php echo($data['username']); ?></a></td>
                <td><a onclick="codiad.user.password('<?php echo($data['username']); ?>');" class="icon-flashlight bigger-icon"></a></td>
                <td><a onclick="codiad.user.projects('<?php echo($data['username']); ?>');" class="icon-archive bigger-icon"></a></td>
                <td><a onclick="codiad.user.edit('<?php echo($data['username']); ?>');" class="icon-pencil bigger-icon"></a></td>
                
                <?php
                    if($_SESSION['user'] == $data['username']){
                    ?>
                    <td><a onclick="codiad.message.error('You Cannot Delete Your Own Account');" class="icon-block bigger-icon"></a></td>
                    <?php
                    }else{
                    ?>
                    <td><a onclick="codiad.user.delete('<?php echo($data['username']); ?>');" class="icon-cancel-circled bigger-icon"></a></td>
                    <?php
                    }
                    ?>
            </tr>
            <?php
            }
            ?>
            </table>
            </div>
            <button class="btn-left" onclick="codiad.user.createNew();">New Account</button><button class="btn-mid" onclick="window.open('<?=$import_user_url?>'); codiad.modal.unload();return false;">Import from CSV File</button><button class="btn-right" onclick="codiad.modal.unload();return false;">Close</button>
            <?php
            }
            
            break;
            
        //////////////////////////////////////////////////////////////////////
        // Create New User
        //////////////////////////////////////////////////////////////////////
        
        case 'create':
        	
			$User = new User();
			$Permission = new Permission($_SESSION['user']);
			$returnAdminType = $Permission->GetAdminPermission();
			
			$types = $User->GetUsersTypes($returnAdminType);
			
			$Course = new Course();
			$courses = $Course->GetAllCourses();
			
            ?>
            <form>
            <label>Username</label>
            <input type="text" name="username" autofocus="autofocus" autocomplete="off">
            <label>E-mail</label>
            <input type="text" name="email" autocomplete="off">
            <label>Type</label>
            <select name="type">
            	<?
            	for ($i = 0; $i < count($types); $i++) {
            	?>
            		<option value="<?=$types[$i]?>"><?=ucfirst($types[$i])?></option>
            	<?
            	}
            	?>
            </select>
            <label>Course</label>
            <select name="course">
            	<?
            	foreach($courses as $course) {
            	?>
            		<option value="<?=$course['_id']?>"><?=$course['code'] ." - ". $course['name']?></option>
            	<?
            	}
            	?>
            </select>
            
            <label>Password</label>
            <input type="password" name="password1">
            <label>Confirm Password</label>
            <input type="password" name="password2">
            <button class="btn-left">Create Account</button><button class="btn-right" onclick="codiad.user.list();return false;">Cancel</button>
            <form>
            <?php
            break;
        
		//////////////////////////////////////////////////////////////////////
        // Edit User
        //////////////////////////////////////////////////////////////////////
        
        case 'edit':
        	
			$User = new User();
			$Permission = new Permission($_SESSION['user']);
			$returnAdminType = $Permission->GetAdminPermission();
			
			$types = $User->GetUsersTypes($returnAdminType);
			$User->username = $_GET['username'];
			$User->Load();
			$Course = new Course();
			$courses = $Course->GetAllCourses();
			
            ?>
            <form>
            <input type="hidden" name="username" value="<?=$User->username; ?>" />
            <label>Editing user "<?=$User->username; ?>"</label>
            <br />
            <label>E-mail</label>
            <input type="text" name="email" autofocus="autofocus" autocomplete="off" value="<?=$User->email;?>" />
            <label>Type</label>
            <select name="type">
            	<?
            	for ($i = 0; $i < count($types); $i++) {
            	?>
            		<option value="<?=$types[$i]?>"
            			<? if($types[$i] == $User->type) { echo "selected=\"selected\""; } ?> 
            			>
            			<?=ucfirst($types[$i])?>
            		</option>
            	<?
            	}
            	?>
            </select>
            <table>
		        <?
		        foreach($courses as $course) {
		        ?>
		        	<tr>
		        		<td><input type="checkbox" disabled="disabled" <? if (in_array($course['_id'], $User->courses)) { echo "checked=\"checked\""; } ?> /></td>
		        		<td><?=$course['code'] ." - ". $course['name'] ?></td>
		        	</tr>
		        <?
				}
		        ?>
            </table>
            <button class="btn-left">Save</button><button class="btn-right" onclick="codiad.user.list();return false;">Cancel</button>
            <form>
            <?php
            break;
		
        //////////////////////////////////////////////////////////////////////
        // Set Project Access
        //////////////////////////////////////////////////////////////////////
        
        case 'projects':
        
            // Get project list
            //$projects = getJSON('projects.php');
            $projects = getProjectsForUser($_SESSION['user']);
            // Get control list (if exists)
            $projects_assigned = false;
            if(file_exists(BASE_PATH . "/data/" . $_GET['username'] . '_acl.php')){
                $projects_assigned = getJSON($_GET['username'] . '_acl.php');
            }
        
        ?>
            <form>
            <input type="hidden" name="username" value="<?php echo($_GET['username']); ?>">
            <label>Project Access for <?php echo(ucfirst($_GET['username'])); ?></label>
            <select name="access_level" onchange="if($(this).val()=='0'){ $('#project-selector').slideUp(300); }else{ $('#project-selector').slideDown(300).css({'overflow-y':'scroll'}); }">
                <option value="0" <?php if(!$projects_assigned){ echo('selected="selected"'); } ?>>Access ALL Projects</option>
                <option value="1" <?php if($projects_assigned){ echo('selected="selected"'); } ?>>Only Selected Projects</option>
            </select>
            <div id="project-selector" <?php if(!$projects_assigned){ echo('style="display: none;"'); }  ?>>
                <table>
                <?php
                    // Build list
                    foreach($projects as $project=>$data){
                        $sel = '';
                        if($projects_assigned && in_array($data['path'],$projects_assigned)){ $sel = 'checked="checked"'; }
                        echo('<tr><td width="5"><input type="checkbox" name="project" '.$sel.' id="'.$data['path'].'" value="'.$data['path'].'"></td><td>'.$data['name'].'</td></tr>');
                    }
                ?>
                </table>
            </div>
            <button class="btn-left">Confirm</button><button class="btn-right" onclick="codiad.user.list();return false;">Close</button>
            <?php
            break;
        
        //////////////////////////////////////////////////////////////////////
        // Delete User
        //////////////////////////////////////////////////////////////////////
        
        case 'delete':
        
        ?>
            <form>
            <input type="hidden" name="username" value="<?php echo($_GET['username']); ?>">
            <label>Confirm User Deletion</label>
            <pre>Account: <?php echo($_GET['username']); ?></pre>
            <button class="btn-left">Confirm</button><button class="btn-right" onclick="codiad.user.list();return false;">Cancel</button>
            <?php
            break;
            
        //////////////////////////////////////////////////////////////////////
        // Change Password
        //////////////////////////////////////////////////////////////////////
        
        case 'password':
            
            if($_GET['username']=='undefined'){
                $username = $_SESSION['user'];
            }else{
                $username = $_GET['username'];
            }
        
        ?>
            <form>
            <input type="hidden" name="username" value="<?php echo($username); ?>">
            <label>New Password</label>
            <input type="password" name="password1" autofocus="autofocus">
            <label>Confirm Password</label>
            <input type="password" name="password2">
            <button class="btn-left">Change <?php echo(ucfirst($username)); ?>&apos;s Password</button><button class="btn-right" onclick="codiad.modal.unload();return false;">Cancel</button>
            <?php
            break;
        
    }
    
?>
