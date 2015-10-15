<?
require_once('template.php');

htmlHead('settings.php');
?>	<div id="settingsPage" data-role="page" data-theme="a">
		<div data-role="header">
			<a href="index.php" data-role="button" data-icon="back" data-iconpos="notext" data-rel="back">Back</a>
			<h4>Settings</h4>
		</div>
		<div data-role="content">
			<div data-role="collapsible-set" data-inset="false">
				<div data-role="collapsible">
					<h3>User Details</h3>
					<form id="editUserForm" data-ajax="false">
						<fieldset data-role="fieldcontain">
							<label for="edit_name">Name: </label>
							<input type="text" id="edit_name" name="edit_name"/>
						</fieldset>
						<fieldset data-role="fieldcontain">
							<label for="edit_email">E-mail:</label>
							<input type="text" id="edit_email" name="edit_email"/>
						</fieldset>
					</form>
				</div>
				<div data-role="collapsible">
					<h3>Notification Options</h3>
					<p>Coming soon...</p>
				</div>
				<div data-role="collapsible">
					<h3>Change Password</h3>
					<div data-role="fieldcontain" >
						<label for="edit_password1">Password:</label>
						<input type="password" name="edit_password1" id="edit_password1" placeholder="Required"/>
					</div>
					<div data-role="fieldcontain" >
						<label for="edit_password2">Repeat Password:</label>
						<input type="password" name="edit_password2" id="edit_password2" placeholder="Required"/>
					</div>
					<button data-role="button">Change Password</button>
				</div>				
				<div data-role="collapsible">
					<h3>Delete Account</h3>
					<p>Are you sure?</p>
				</div>
			</div>
		</div>
	</div>
<? 
htmlFoot();
