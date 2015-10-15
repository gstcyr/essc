<?
require_once('template.php');
require_once('calcStandings.php');

if(@$_POST['cmd']=='removeTeam'){
	$teamID = $_POST['teamID'];
	if(!$teamID) exit;
	
	$sql = "DELETE FROM users_teams WHERE usr_id = '$userID' AND team_id = '$teamID'";
	$db->query($sql);
	echo json_encode(array('success'=>1));
	exit;	
} elseif(@$_POST['cmd']=='oldGames'){
	
}

$teamID = $_GET['team'];
$teamInfo = $db->get("SELECT div_id, team_name FROM teams WHERE team_id = '$teamID'");
$team = $teamInfo['team_name'];

$divInfo = $db->get("SELECT * FROM divisions d LEFT JOIN sports s ON d.s_id = s.s_id WHERE d.div_id = (SELECT div_id FROM teams WHERE team_id = '$teamID')");

$games = $db->fetch_all("SELECT s.datetime, s.details, s.matchnum, s.team1_id, s.team2_id, s.team1, s.team2, f.facility_name, f.facility_address, f.facility_link ".
				  	"FROM schedules s ".
					"LEFT JOIN facilities f ON s.facility_id = f.facility_id  ".
					"WHERE '$teamID' IN (team1_id, team2_id) AND datetime >= NOW() AND details = '' ORDER BY s.datetime, matchnum ASC");

$playoffs = $db->fetch_all("SELECT s.*, f.facility_name, f.facility_address, f.facility_link 
							FROM schedules s 
							LEFT JOIN facilities f ON f.facility_id = s.facility_id 
							WHERE div_id = '$teamInfo[div_id]'
							AND datetime >= NOW() 
							AND details != '' ORDER BY game_id");
$standings = new standings($divInfo['div_id']);
$ranks = $standings->getRanks();

$match = '';
foreach($playoffs as $p){		
	if($p['team1_id'] == $teamID || $p['team2_id'] == $teamID){
		$match = $p['details'];
		$p['playoff'] = true;
		array_push($games,$p);
	} 
}
foreach($playoffs as $p){
	$p['details'] = preg_replace('/\s\s+/',' ',$p['details']);
	@$debug .= "Finding $match in $p[details]".strpos($p['details'],$match)."<br>";
	if(@$match && $p['team1_id'] == '' && strpos($p['details'],$match)!==false){		
		$p['playoff'] = true;
		array_push($games, $p);
	}
}

htmlHead($team);
?>
	<div id="teamPage" data-role="page" data-theme="a">
		<div data-role="header">
			<a href="index.php" data-role="button" data-icon="back" data-iconpos="notext" data-rel="back">Back</a>
			<h4><?=$team?> Schedule</h4>
			<a href="#confirmRemove" data-rel="popup" data-position-to="window" data-role="button" data-icon="delete" data-iconpos="notext" title="Remove From My Teams"></a>
		</div>
		<div data-role="content">						
			<h3>
				<a href="standings.php?divID=<?=$divInfo['div_id']?>" data-role="button" data-icon="grid" data-iconpos="notext" data-inline="true" data-mini="true"></a> 
				<a href="standings.php?divID=<?=$divInfo['div_id']?>"><?=$nights[$divInfo['night']].' '.$divInfo['div_name'].' '.$divInfo['s_name']?></a>
			</h3>
			<h2 class='myteam'><?=$team?></h2>			
			<ul id="schedule" data-role="listview" data-theme="a" data-inset="true">				
				<li data-role="list-divider" data-theme="a">Schedule <span class="ui-li-aside" style="margin:0"><a href="<?=$divInfo['div_link']?>" target="_blank"><img src="favicon.png"/></a></span></li>
				<li data-icon="arrow-u"><a href='history.php?team=<?=$teamID?>'>Match History</a></li>
<?		$gameHTML = '';
		$showPlayoff = true;
		foreach($games as $i=>$game){
			if(@$game['playoff'] && $showPlayoff){
				$showPlayoff = false;
				echo "<li data-role='list-divider'>Playoffs</li>";					
			}
			if($game['datetime'] == '2038-01-01 00:00:00')
				$fdate = 'TBD';
			else 
				$fdate = date('l, F jS - <\s\p\a\n \c\l\a\s\s="\m\y\t\e\a\m">g:iA</\s\p\a\n>',strtotime($game['datetime']));
			if($game['matchnum']==1){
				if($gameHTML) echo $gameHTML."</div></li>";
				$gameHTML = "
				<li>
					<h3>$fdate</h3>
					<p><a href='$game[facility_link]' target='_blank'>$game[facility_name] ($game[facility_address])</a></p>
					<div class='matches'>"; 			
			}
			if($game['team1']==$team){
				$team2 = $game['team2_id'];
				$game['team1'] = "<span class='myteam'>".$game['team1']."</span>";
			} elseif($game['team2']==$team){
				$team2 = $game['team1_id'];
				$game['team2'] = "<span class='myteam'>".$game['team2']."</span>";
			}
			if(@$games[$i+1]['matchnum']>1 || $game['matchnum']>1) $gameHTML .= "<span>Match ".$game['matchnum'].": </span>";
			if($game['team1']){			
				$gameHTML .= "<span><a data-role='button' data-inline='true' data-mini='true' data-icon='vs' data-iconpos='notext' href='matchup.php?team1=$teamID&team2=$team2'>VS</a> ".$game['team1'].(@$ranks[$game['team1_id']]?" (".numord($ranks[$game['team1_id']]).")":"")." vs. ".$game['team2'].(@$ranks[$game['team2_id']]?" (".numord($ranks[$game['team2_id']]).")":"").($game['details']?" ($game[details])":"")."</span><br>";			
			} else {
				$gameHTML .= "<span>".$game['details']."</span><br>";	
			}
		}
		echo $gameHTML."</div></li>";
		if($showPlayoff == true){
			echo "<li data-role='list-divider'>Playoffs</li>";
			echo "<li>TBD</li>";	
		}
?>			</ul>			
		</div>	
		<div id="confirmRemove" data-role="popup" data-theme="b" style="min-width:300px;">
			<ul data-role="listview">
				<li data-role="list-divider" data-theme="b">Remove from your teams?</li>
				<li>
					<div class="ui-grid-a">
						<div class="ui-block-a">
							<button onClick="$('#confirmRemove').popup('close')">Cancel</button>
						</div>
						<div class="ui-block-b">
							<button id="confirmRemoveB" data-theme="b">OK</button>
						</div>
					</div>
				</li>
			</ul>
		</div>
		<script>
			$('#teamPage').on('pageinit',function(){
				$('#teamPage').on('pagebeforeshow',function(){
					$('#confirmRemoveB').on('click',function(){
						console.log('clicky');
						setTimeout(function(){$.mobile.loading('show')},0);
						$.ajax({
							url : 'team.php',
							type : 'POST',
							data : {
								'cmd' : 'removeTeam',
								'teamID' : '<?=$teamID?>'							
							},
							dataType : 'json'
						}).done(function(json){
							if(json.success) location.href = 'index.php';
						});
					});
				});
				//$('#teamPage').on('pagebeforeshow',
			});
		</script>
	</div>
<?
htmlFoot();