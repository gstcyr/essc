<?
require_once('template.php');

if(@$_POST['cmd']){
	switch($_POST['cmd']){
		case 'getTeams':
			$season = @$_POST['season']?$_POST['season']:$currSeason;
			$sy = explode(' ',$currSeason);
			$season = $sy[0];
			$year = $sy[1];
			//$sql = "SELECT DISTINCT team1 t, div_id FROM schedules WHERE div_id IN (SELECT div_id FROM divisions WHERE season = '$season' AND year = '$year') UNION SELECT DISTINCT team2 t, div_id FROM schedules WHERE div_id IN (SELECT div_id FROM divisions WHERE season = '$season' AND year = '$year') ORDER BY t";		
			$sql = "SELECT t.*, d.div_name, d.night, s.s_name FROM teams t
					LEFT JOIN divisions d ON t.div_id = d.div_id
					LEFT JOIN sports s ON d.s_id = s.s_id 
					WHERE t.div_id IN (SELECT div_id FROM divisions WHERE season = '$season' AND year = '$year') ORDER BY team_name";
			$teams = $db->fetch_all($sql);
			echo $db->error();
			//$divisions = array();
			$html = "";
			$count = 0;
			foreach($teams as $t){
			//	if(!@$divisions[$t['div_id']]){
			//		$division = $db->get("SELECT div_name, night, s_name FROM divisions d LEFT JOIN sports s ON s.s_id = d.s_id WHERE div_id = '$t[div_id]'");	
			//		$divisions[$t['div_id']] = $division;
			//	}
			//	$d = $divisions[$t['div_id']];
				$onTeam = $db->getv("SELECT 1 FROM users_teams WHERE usr_id = '$userID' AND team_id = '$t[team_id]'");
				if($onTeam) $count++;
				$html .= "
					<li team_id='$t[team_id]' data-theme='".($onTeam?'b':'a')."' data-icon='".($onTeam?'minus':'plus')."' >
					<a href='#'>
						<h4>$t[team_name]</h4>
						<p>".@$nights[$t['night']]." $t[div_name] $t[s_name]</p>
					</a>					
				</li>";				
			}
			echo json_encode(array('html'=>$html, 'count'=>$count));
			
		break;
		case 'toggleTeam':
			$teamID = $_POST['team_id'];			
			
			$exists = $db->getv("SELECT 1 FROM users_teams WHERE usr_id = '$userID' AND team_id = '$teamID'");
			if($exists)
				$db->query("DELETE FROM users_teams WHERE usr_id = '$userID' AND team_id = '$teamID'");
			else 
				$db->query("INSERT INTO users_teams (usr_id, team_id) VALUES ('$userID','$teamID')");
			$err = $db->error();
			//$count = $db->getv("SELECT COUNT(*) FROM users_teams WHERE usr_id = '$userID' AND 
			
			echo json_encode(array('added'=>!$exists, 'err'=>$err));			
		break;
	}
	exit;
}

$seasons = $db->fetch_all("SELECT DISTINCT season, year FROM divisions ORDER BY year DESC, FIELD(season,'FALL','SUMMER','SPRING','WINTER')");

htmlHead('Search Teams');
?>	<div id="teamsPage" data-role="page" data-theme="a">
		<div data-role="header" data-position="fixed">
			<a href="index.php" data-role="button" data-icon="home"><span id="numSelected">0</span> Selected</a>
			<h1>Find teams</h1>				
		</div>
		<div data-role="content">
<!--			<div style="margin-bottom:15px;">
			<select id="season" data-native-menu="false" data-theme="a">
<?			foreach($seasons as $s){ ?>
				<option value="<?=$s['season'].' '.$s['year']?>"><?=$s['season'].' '.$s['year']?></option>
<?			}
?>			</select></div> -->
			<ul id="teamsList" data-role="listview" data-filter-placeholder="Search Team..." data-filter-theme="b" data-filter="true" data-theme="a" data-icon="plus" >
			</ul>
		</div>
		<script type="application/javascript">
			$('#teamsPage').on('pageinit',function(){
				$('#teamsPage').on('pagebeforeshow',function(){
					$('#season').on('change',getTeams);				
					function getTeams(){
						setTimeout(function(){$.mobile.loading('show');},0);
						$.ajax({
							url : 'teams.php',
							type : 'POST',
							data : {
								cmd : 'getTeams',
								season : $('#season').val()	
							},
							dataType : 'json'						
						}).done(function(json){
							var html = json.html;
							$('#numSelected').html(json.count);
							$('#teamsList').html(html);
							$('#teamsList').listview('refresh');
							
							$('#teamsList li').on('click',function(){
								var el = $(this);
								var team = el.attr('team_id');							
								setTimeout(function(){$.mobile.loading('show')},0);
								$.ajax({
									url : 'teams.php',
									method : 'POST',
									data : {
										cmd : 'toggleTeam',										
										'team_id'	: team
									},
									dataType : 'json'								
								}).done(function(json){
									if(json.added){
										el.removeClass('ui-btn-up-a ui-btn-hover-a');
										el.addClass('ui-btn-up-b');
										el.attr('data-theme','b');
										el.attr('data-icon','minus');
										el.find('span.ui-icon').removeClass('ui-icon-plus').addClass('ui-icon-minus');
										//$('#teamList').listview('refresh');			
										var count = parseInt($('#numSelected').html());
										$('#numSelected').html(count+1);
									} else {
										el.removeClass('ui-btn-up-b ui-btn-hover-b');
										el.addClass('ui-btn-up-a');
										el.attr('data-theme','a');
										el.attr('data-icon','plus');
										el.find('span.ui-icon').removeClass('ui-icon-minus').addClass('ui-icon-plus');
										var count = parseInt($('#numSelected').html());
										$('#numSelected').html(count-1);
										//$('#teamList').listview('refresh');
									}
								}).always(function(){
									$.mobile.loading('hide');
								});
							});
						}).always(function(){
							$.mobile.loading('hide');
						});					
					}
					
					getTeams();
				});
			});
		</script>
	</div>
<? htmlFoot(); 