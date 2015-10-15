<?
require_once('template.php');
if(@$_POST['cmd']=='getHistory'){
	$html = '';
	$teamID = @$_POST['teamID'];
	$teamInfo = $db->get("SELECT * FROM teams WHERE team_id = '$teamID'");
	$divInfo = $db->get("SELECT d.*, s.s_name FROM divisions d LEFT JOIN sports s ON d.s_id = s.s_id WHERE div_id = '$teamInfo[div_id]'");
	$divInfo['night'] = $nights[$divInfo['night']];
	$sql = "SELECT datetime, matchnum, team1, team2, team1_id, team2_id, details, score1, score2, spirit1, spirit2 FROM schedules WHERE '$teamID' IN (team1_id, team2_id) AND datetime < NOW() ORDER BY datetime, matchnum ASC";
	$games = $db->fetch_all($sql);
	$showSpirit = false;
	foreach($games as $k=>$g){
		$team1 = $g['team1_id']==$teamID ? true: false;
		$games[$k]['datetime'] = date("l, M jS, Y ",strtotime($g['datetime']));
		$games[$k]['opponent'] = $team1?("<a data-ajax='false' href='history.php?team=$g[team2_id]'>$g[team2]</a>"):("<a data-ajax='false' href='history.php?team=$g[team1_id]'>$g[team1]</a>");	
		
		$scores1 = explode(',',$g['score1']);
		$scores2 = explode(',',$g['score2']);
		$scores = array();
		$win1 = $win2 = 0;
		foreach($scores1 as $i=>$s){
			if($s > $scores2[$i]) $win1++;
			elseif($s < $scores2[$i]) $win2++;
			if($team1) array_push($scores, $s.'-'.$scores2[$i]);
			else array_push($scores,$scores2[$i].'-'.$s);
		}
		if(count($scores)==0)
			$result = 'N/A';
		else if($team1){
			$result = $win1==$win2?'Tie':($win1>$win2?'Win':'Loss');	
		} else{
			$result = $win1==$win2?'Tie':($win1>$win2?'Loss':'Win');	
		}	
		$games[$k]['score'] = implode(', ',$scores);		
		$games[$k]['spirit'] = $team1?$g['spirit1']:$g['spirit2'];
		if($games[$k]['spirit']) $showSpirit = true;
		$games[$k]['result'] = $result;	
	}
	$divLink = "<a href='standings.php?divID=$divInfo[div_id]'><button href=# data-role='button' data-icon='grid' data-iconpos='notext' data-inline='true' data-mini='true'></button> ".$divInfo['night'].' '.$divInfo['div_name'].' '.$divInfo['s_name']."</a>";
	
	echo json_encode(array('team'=>$teamInfo['team_name'],'div'=>$divLink,'games'=>$games,'showSpirit'=>$showSpirit));
	
	exit;
}

htmlHead('Game History');
?> <div id="historyPage" data-role="page" data-theme="a">
		<div data-role="header">
			<a href="#" data-icon="back" data-iconpos="notext" data-rel="back"></a>
			<h3>Match History</h3>
			<a href="index.php" data-icon="home" data-iconpos="notext">Home</a>		
		</div>
		<div data-role="content">
			<h2 id="divName">Loading...</h2>
			<h3 id="teamName"></h3>
			<table data-role="table" class='table-stroke ui-table ui-responsive' style="display:none">
				<thead>
					<th>Date</th>
					<th>Opponent</th>
					<th>Score</th>
					<th>Result</th>
					<th id="spirit" style="display:none">Spirit</th>					
				</thead>
				<tbody id="gameHistory">									
				</tbody>
			</table>
		</div>
		<script>
			$('#historyPage').on('pageinit',function(){
				
				$('#historyPage').on('pagebeforeshow',function(o){
					console.log(o);
					setTimeout(function(){$.mobile.loading('show');},0);
					$.ajax({
						url : 'history.php',
						type : 'POST',
						dataType : 'json',
						data : {
							'cmd' : 'getHistory',
							'teamID' : '<?=$_GET['team']?>'	
						}
					}).done(function(json){
						var games = json.games;
						$('#teamName').html(json.team);
						$('#divName').html(json.div);
						if(json.showSpirit) $('#spirit').show();
						loadGames(games, json.showSpirit);	
						$('#historyPage').trigger('create');
						$.mobile.loading('hide');					
					});
						
				});
				function loadGames(games, spirit){
					var tbl = $('#gameHistory');
					tbl.empty();
					for(var i in games){
						var g = games[i];
						tbl.append("<tr><td>"+g['datetime']+"</td><td>"+g['opponent']+"</td><td>"+g['score']+"</td><td >"+g['result']+"</td>"+(spirit?("<td>"+g['spirit']+"</td>"):"")+"</tr>");
					}
					tbl.closest('table').table('refresh').show();					
				}
			});
		</script>
	</div>
<? 
htmlFoot();