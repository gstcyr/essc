<?
require_once('template.php');

if(@$_POST['cmd']){
	switch($_POST['cmd']){
		case 'register':
			// Process registration	
			$db->esc($_POST);
			extract($_POST);
			$reg_username = strtolower($reg_username);
			$reg_email = strtolower($reg_email);
			$exists = $db->getv("SELECT 1 FROM users WHERE usr_username = '$reg_username' OR usr_email = '$reg_email'");
			if($exists){
				$err = "Username or Email already exists";			
			} else {
				$password = md5($reg_password1);
				$sql = "INSERT INTO users (usr_username, usr_password, usr_name, usr_email, usr_logins, usr_lastLogin) ".
					   "VALUES ('$reg_username','$password','$reg_name','$reg_email','1',NOW())";
				$db->query($sql);
				$err = $db->error();
				if(!$err){
					$usr_id = $db->getv("SELECT LAST_INSERT_ID()");
				}
			}
			if(!$err){
				session_start();
				$_SESSION['usr_id'] = $usr_id;
				$_SESSION['usr_username'] = $reg_username;
				session_write_close();
				$msg = "Registration Successful. Please wait.";
			} else{
				$msg = $err;	
			}
			
			echo json_encode(array("success"=>$err?false:true, "message"=>$msg));
		break;
		case 'checkUsername':
			$db->esc($_POST['username']);
			$exists = $db->getv("SELECT 1 FROM users WHERE usr_username = '$_POST[username]'");
			if(!$exists) echo json_encode(true);
			else echo json_encode("Username taken");		
		break;
		case 'checkEmail':
			$db->esc($_POST['email']);
			$exists = $db->getv("SELECT 1 FROM users WHERE usr_email = '$_POST[email]'");
			if(!$exists) echo json_encode(true);
			else echo json_encode("Email already in-use");
		break;
	}
	exit;	
}

htmlHead('Register');
?>	<div id="registerPage" data-role="page" data-theme="a">
		<div data-role="header">
			<a href="#" data-role="button" data-icon="back" data-iconpos="notext" data-rel="back">Back</a>
			<h1>Register</h1>
			<div id="registerMB" class="ui-bar ui-bar-b" style="display:none"></div>
		</div>
		<div data-role="content">
			<form id="registerForm" data-ajax="false" onSubmit="return false" autocomplete="off" >
				<input type="hidden" name="cmd" value="register" />
				<div data-role="fieldcontain">
					<label for="reg_username">Username:</label>
					<input type="text" name="reg_username" id="reg_username" placeholder="Required"/>
				</div>
				<div data-role="fieldcontain" >
					<label for="reg_password1">Password:</label>
					<input type="password" name="reg_password1" id="reg_password1" placeholder="Required"/>
				</div>
				<div data-role="fieldcontain" >
					<label for="reg_password2">Repeat Password:</label>
					<input type="password" name="reg_password2" id="reg_password2" placeholder="Required"/>
				</div>
				<div data-role="fieldcontain" >
					<label for="reg_name">Full Name:</label>
					<input type="text" name="reg_name" id="reg_name" placeholder="(Optional)"/>
				</div>
				<div data-role="fieldcontain">
					<label for="reg_email">Email:</label>
					<input type="email" name="reg_email" id="reg_email" placeholder="Required"/>
				</div>
				
				<div class="ui-grid-a">
					<div class="ui-block-a">
						<button data-role="button" id="registerSubmit">Submit</button>
					</div>
					<div class="ui-block-b">
						<a href="#" data-role="button" data-theme="b" data-rel="back">Cancel</a>
					</div>
				</div>
			</form>
		</div>
		
		<script>
			$('#registerPage').on('pageinit', function(){	
				$('#registerPage').on('pagebeforeshow', function(){
					$('#registerForm').validate({
						rules : {
							reg_username : {
								required : true,
								minlength : 3,
								remote : {
									url : 'register.php',
									type : 'post',
									data : {
										cmd : 'checkUsername',
										username : function(){ return $('#reg_username').val() }	
									}
								}
							},
							reg_password1 : {
								required : true,
								minlength : 5								
							},
							reg_password2 : {
								required : true,
								minlength : 5,
								equalTo : "#reg_password1"	
							},
							reg_name : {
								
							},
							reg_email : {
								required : true,
								email : true,
								remote : {
									url : 'register.php',
									type : 'post',
									data : {
										cmd : 'checkEmail',
										email : function(){ return $('#reg_email').val() }	
									}
								}
							}							
						},
						messages : {
							reg_username : "Username Required",
							reg_password1 : {
								required : "Please provide a password",
								minlength : "Password must be a minimum of 5 characters"
							},
							reg_password2 : {
								required : "Please confirm your password",
								minlength : "Password must be a minimum of 5 characters",
								equalTo : "Please enter the same password as above"
							},
							reg_email : {
								required : "Please provide a valid e-mail address"	
							}
						},
						errorPlacement: function(error, element) {							
							error.insertAfter($(element).parent());								
						}
					});			
					$('#registerSubmit').on('click', function(){
						if(!$('#registerForm').valid()) return;
						
						$('#registerSubmit').button('disable');
						$.ajax({
							url : 'register.php',
							type : 'POST',
							data : $('#registerForm').serialize(),
							dataType : 'json'
						}).done(function(json){
							$('#registerMB').html(json.message).show();
							if(json.success){														
								location.href = 'teams.php';
							}
						}).always(function(){
							$('#registerSubmit').button('enable');
						});
						
					});
				});
				
			});
		</script>
	</div>
