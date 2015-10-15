<?	
require_once('phpQuery/phpQuery/phpQuery.php');
require_once('db.php');
$db = new db();

// Add Logic to only update recent games (Add LastStandingsParse to Divisions?)
$times = array();
$sports = $db->fetch_all("SELECT * FROM sports WHERE s_page = 'dodgeball' ORDER BY s_id ASC");

$ctx = stream_context_create(array(
	'http' => array(
		'timeout' => 8
	)
));

$timeStart = microtime(true);
?><PRE><?
foreach($sports as $sport){
	echo "<h2>".$sport['s_name']."</h2>";
	//array_push($times,array('loadPage',microtime(true)));
	$page = file_get_contents("http://www.edmontonsportsclub.com/".$sport['s_page']."/standings.shtml", false, $ctx);
	//array_push($times,array('loadPhpQuery',microtime(true)));
	$doc = phpQuery::newDocumentHTML($page);
	//array_push($times,array('findLinks',microtime(true)));
	$links = $doc['a[href*="Standings.aspx"]'];
	//array_push($times,array('foundLinks',microtime(true)));
	foreach($links as $link){
		$href = $link->attributes->getNamedItem('href')->textContent;			
		//array_push($times,array('getStandings',microtime(true)));
		$standingsPage = file_get_contents($href, false, $ctx);
		//array_push($times,array('loadStandings',microtime(true)));
		$sDoc = phpQuery::newDocumentHTML($standingsPage);
	//	array_push($times,array('foundStandings',microtime(true)));
		echo $href."<BR>";
		$teamLinks = $sDoc['a[href*="TeamResults.aspx"]'];
		foreach($teamLinks as $teamLink){
			$teamLink = pq($teamLink);			
			$href = "http://www.edmontonsportsclub.com/pdb/".$teamLink->attr('href');
			echo $href."<BR>";
			//array_push($times,array('getTeamPage',microtime(true)));
			$teamPage = file_get_contents($href, false, $ctx);
			//array_push($times,array('loadTeamPage',microtime(true)));
			$tDoc = phpQuery::newDocumentHTML($teamPage);
			//array_push($times,array('processTeam',microtime(true)));
			$teamName = $tDoc['#lblTeamName']->text();
			
			$tableRows = $tDoc['#gvResults tr'];
			if(!$tableRows->html()) continue;
			
			$headers = array();
			$results = array();
			foreach($tableRows as $rowNum => $row){
				$row = pq($row);	
				// Header Row:			
				if($rowNum==0){
					$cells = $row['th'];
					foreach($cells as $c){
						array_push($headers,$c->textContent);
					}				
				} else {
					// Details:
					$cells = $row['td'];
					$result = array();
					foreach($cells as $col=>$cell){
						$result[$headers[$col]] = trim(str_replace("\r\n","",$cell->textContent));
					}
					$t1 = $teamName;
					$db->esc($t1);
					$t2 = $result['Opponent'];
					$db->esc($t2);
					$ymd = date("Y-m-d",strtotime($result['Date']));
					$gameID = $db->getv("SELECT game_id FROM schedules s
							 LEFT JOIN teams t1 ON s.team1_id = t1.team_id
							 LEFT JOIN teams t2 ON s.team2_id = t2.team_id
							 WHERE t1.team_name = '$t1'
							 AND t2.team_name = '$t2'
							 AND LEFT(datetime,10) = '$ymd'");
					$found = false;
					if($gameID){ 
						$team1 = true;
						$found = true;
						$result['gameID'] = $gameID;
					} else {
						$gameID = $db->getv("SELECT game_id FROM schedules s
							 LEFT JOIN teams t1 ON s.team1_id = t1.team_id 
							 LEFT JOIN teams t2 ON s.team2_id = t2.team_id 
							 WHERE t1.team_name = '$t2'
							 AND t2.team_name = '$t1'
							 AND LEFT(datetime,10) = '$ymd'");	
						if($gameID){
							$team1 = false;	
							$found = true;
							$result['gameID'] = $gameID;
						} else {
							echo "$sport[s_name] - Game not found! $teamName $href<BR>";
							print_r($result);
							continue;
						}
					}					
					$matches = explode(',', $result['Score']);
					$score1 = array();
					$score2 = array();
					
					foreach($matches as $m){
						$m = trim($m);
						$scores = explode('-',$m);
						if($team1){
							array_push($score1,$scores[0]);
							array_push($score2,$scores[1]);
						} else {
							array_push($score2,$scores[0]);
							array_push($score1,$scores[1]);
						}
					}					
					$result['score1'] = implode(',',$score1);
					$result['score2'] = implode(',',$score2);
					if(isset($result['Spirit'])){
						$spiritCol = $team1?'spirit1':'spirit2';
						$spiritSQL = ", $spiritCol = '".$result['Spirit']."'";						
					} else $spiritSQL = "";
					$db->esc($result['Comments']);
					
					$sql = "UPDATE schedules SET score1 = '$result[score1]', score2 = '$result[score2]' $spiritSQL , comments = '$result[Comments]' WHERE game_id = '$gameID'";
					$db->query($sql);
					echo $db->error();
					
					array_push($results,$result);
				}
			}
			//array_push($times,array('teamsProcessed',microtime(true)));
			print_r($results);			
			// Only do first team for testing:
			//break;
		}
		// Only do first Link for testing
	//	break;
	}
}
echo microtime(true)-$timeStart;
/*
foreach($times as $i=>$t){
	if(	$i==0){  continue; }
	$diff = $t[1]-$times[$i-1][1];
	echo $times[$i-1][0].": $diff s<br>";
}*/

?></PRE>