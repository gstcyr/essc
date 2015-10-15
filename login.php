<?
require_once('template.php');

if(@$_POST['cmd'] == 'login'){
	$db->esc($_POST);
	extract($_POST);
	$md5pass = md5($usr_password);
	$usr_username = strtolower($usr_username);
	$user = $db->get("SELECT usr_id, usr_username, usr_name FROM users WHERE (LOWER(usr_username) = '$usr_username' OR LOWER(usr_email) = '$usr_username') AND usr_password = '$md5pass'");
	if(!$user){
		$msg = "Invalid username or password.";	
	} else {
		$db->query("UPDATE users SET usr_logins = usr_logins+1, usr_lastLogin = NOW() WHERE usr_id = '$user[usr_id]'");
		session_start();
			$_SESSION['usr_id'] = $user['usr_id'];
			$_SESSION['usr_username'] = $user['usr_username'];
		session_write_close();
		$msg = "Login Successful. Please wait.";			
	}
	echo json_encode(array('success'=>$user?'1':'','message'=>$msg));
	exit;
}

htmlHead('Welcome');
?>	<div id="loginPage" data-role="page" data-theme="a">
		<div data-role="header" data-position="fixed">
			<h1 style="margin:.6em 10% .8em">ESSC Scheduler Login</h1>			
		</div>			
		<div data-role="content">
			<div class="ui-body" style="max-width:400px; margin:auto">
				<div id="loginMB" class="ui-bar ui-bar-a" style="display:none"></div>
				<form id="loginForm" data-ajax="false" onSubmit="return false">
				<ul data-role="listview" data-inset="true">
					<li data-role="list-divider">Login</li>
					<li data-role="fieldcontain" class="ui-hide-label">
						<label for="usr_username">Username:</label>
						<input type="text" name="usr_username" id="usr_username" placeholder="Username"/>
					</li>
					<li data-role="fieldcontain" class="ui-hide-label">
						<label for="usr_password">Password:</label>
						<input type="password" name="usr_password" id="usr_password" placeholder="Password"/>
					</li>					
					<li>						
						<div class="ui-grid-a">
							<div class="ui-block-a">
								<a data-role="button" data-theme="a" href="register.php">Register</a>
							</div>
							<div class="ui-block-b">
								<button data-role="button" id="loginB" data-theme="b">Submit</button>
							</div>
						</div>
					</li>
				</ul>
				</form>
			</div>
		</div>
		<script>
			$('#loginPage').on('pageinit',function(){
				$('#usr_password').on('keypress',function(ev){
					if(ev.keyCode==13) processLogin();
				});
				$('#loginB').on('click',processLogin);	
				function processLogin(){
					setTimeout(function(){$.mobile.loading('show');},0);
					$('#loginMB').hide();
					$.ajax({
						url : 'login.php',
						type : 'POST',
						data : {
							cmd : 'login',
							usr_username : $('#usr_username').val(),
							usr_password : $('#usr_password').val()
						},
						dataType : 'json'
					}).done(function(json){
						if(json.success){
							location.href = 'index.php';	
						} else {
							$('#loginMB').html(json.message).show();
							$.mobile.loading('hide');
						}
					});
				}
				$('#loginPage').on('pageshow',function(){
					setTimeout(function(){
					//cosnsole.log($('#usr_username').val());
					if($('#usr_username').val() && $('#usr_password').val()){
						processLogin();	
					}
					},500);
				});
			});
		</script>
	</div>
<? htmlFoot(); 