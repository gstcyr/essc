<?
require_once('template.php');
//if($userID == 1) $userID = 3;

if(@$_POST['cmd']=='getGames'){
	$season = @$_POST['season']?$_POST['season']:$currSeason;
	$sy = explode(' ',$season);
	$season = $sy[0];
	$year = $sy[1];
	$orderStr = "'";
	$today = date('N')-1;
	$arrNights = array('M','T','W','Th','F','S','Su');
	for($i=$today; $i<$today+7; $i++){
		$j = $i%7;
		$orderStr .= $arrNights[$j]."','";
	}
	$orderStr = trim($orderStr,",'");
	$orderStr = "'".$orderStr."'";
	$sql = "SELECT ut.usr_id, ut.team_id, t.team_name, d.*, s.s_name FROM users_teams ut
			LEFT JOIN teams t ON ut.team_id = t.team_id
			LEFT JOIN divisions d ON t.div_id = d.div_id 
			LEFT JOIN sports s ON d.s_id = s.s_id
			WHERE ut.usr_id = '$userID'
			AND d.season = '$season' AND d.year = '$year'
			ORDER BY FIELD(night,$orderStr)";
	$myDivisions = $db->fetch_all($sql);
	if(!count($myDivisions)){
		echo "<li><a href='teams.php'>Add Team</a></li>";
		exit;	
	}
	foreach($myDivisions as $div){
	//	$team = $div['team_id'];
		$sql = "SELECT * FROM schedules s 
				LEFT JOIN facilities f ON s.facility_id  = f.facility_id 
				WHERE div_id = '$div[div_id]' 
				AND ('$div[team_id]' IN (team1_id, team2_id) OR (team1_id = '' AND team2_id = '')) 
				AND datetime >= NOW()
				ORDER BY s.datetime, matchnum ASC LIMIT 3"; // 2013-09-01 = NOW()
		$games = $db->fetch_all($sql);	
		$game = @$games[0];		
		if(!$game) continue;	
		$date = date('F jS',strtotime($game['datetime']));
		$time = date('g:iA',strtotime($game['datetime']));
		$matchesHTML = '';
		$matches = 0;
		foreach($games as $k=>$game){
			if($game['matchnum']==$k+1) $matches++;	
		}
		for($i=0; $i<$matches; $i++){			
			$game = $games[$i];
			if($matches>1) $matchesHTML .= "Match ".($i+1).": ";
			if($game['team1_id']==$div['team_id']) $matchesHTML .= "<span class='team myteam'>".$game['team1']."</span> vs. <span class='team'>$game[team2]</span><br>";
			if($game['team2_id']==$div['team_id']) $matchesHTML .= "<span class='team'>$game[team1]</span> vs. <span class='myteam team'>".$game['team2']."</span><br>";
		}
?>				<li>
					<a href="team.php?team=<?=$div['team_id']?>">
						<h3><?=$nights[$div['night']].' '.$div['div_name'].' '.$div['s_name']?></h3>
						<p style="font-size:0.9em"><strong><?=$date.' - <span class="myteam">'.$time.'</span>'?> @ <?=$game['facility_name']?></strong></p>											
						<p style="font-weight:bold"><?=$matchesHTML?></p>
					</a>
					<a href="<?=$game['facility_link']?>" target="_blank">Map</a>
				</li>
<?								
	}	
	exit;	
}

htmlHead('Upcoming Games');
?>
	<div id="upcomingGamesPage" data-role="page" data-theme="a">	
		<div data-role="header" data-id="head" data-position="fixed">			
			<a href="#settingsPanel" data-role="button" data-icon="gear" data-iconpos="notext">Settings</a>
			<h1>Upcoming Games</h1>
			<a href="teams.php" data-role="button" data-icon="plus" data-iconpos="notext">Add Team</a>
			<div class="ui-bar ui-bar-c" style="font-size:0.7em"><i>Disclaimer:</i> During beta info may be incorrect.</div>
		</div>
		
		<div data-role="content">			
			<ul id="upcomingGamesList" data-role='listview' data-split-icon="marker" data-split-theme="a">			
				<li>Loading...</li>
			</ul>	
		</div>		
		<div data-role="footer" data-position="fixed" style="margin:auto; text-align:center">
			<!-- ESSC Schedule Ad -->
			<ins class="adsbygoogle"
				 style="display:inline-block;width:320px;height:50px"
				 data-ad-client="ca-pub-7159310240299617"
				 data-ad-slot="8222509668"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
		</div>
		<div id="settingsPanel" data-role="panel">
			<ul data-role="listview">
				<li>Quick Menu</li>
				<!--<li><a href="settings.php">Settings</a></li>-->
				<li><a href="sponsorcard.php">Sponsor Card</a></li>
				<li><a href="logout.php">Logout</a></li>				
			</ul>
		</div>
		<script>
			$('#upcomingGamesPage').on('pageinit',function(){
				//var c = $.cookie('essc',);
				$('#upcomingGamesPage').on('pagebeforeshow', function(){
					$.ajax({
						url : 'index.php',
						type : 'POST',
						data : {
							'cmd' : 'getGames',
							'season' : ''
						},
						dataType : 'html'
					}).done(function(html){
						$('#upcomingGamesList').html(html).listview('refresh');
						
					}).always(function(){
						
					});
				});
			});
		</script>		
	</div>	
<? htmlFoot();