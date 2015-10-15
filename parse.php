<?
require_once('phpQuery/phpQuery/phpQuery.php');
require_once('db.php');

$force = @$_GET['force'];


function debug($msg){
	$bt = debug_backtrace();
	$caller = array_shift($bt);
	echo $caller['line'].": ".$msg."<BR>";	
}
function parseTeams($teamStr){
	global $teams;
	$debug = '';
	$pieces = explode(' vs.',$teamStr);
	$maxTeams = count($teams);;
	$teamStr = str_replace('vs.',' vs. ',$teamStr); // Rare situations when there is no space between vs. and Team Name
	// If more the 2 pieces, it's a League Champ game on a 2 match night (ie (Winner(1st vs 4th) vs. Winner(2nd vs. 3rd)
	if(count($pieces)>2){
		$team1 = '';
		$team2 = '';
		$details = $teamStr;	
	} elseif(count($pieces)==2) {
		// Otherwise it's a normal game or league champ game on a 1 match night
		$places = array('1st','2nd','3rd','4th','5th','6th','7th','8th','9th','10th','11th','12th','13th','14th','15th','16th','17th','18th','19th','20th','21st','22nd','23rd','24th','25th','26th');
		$det = array();
		$teamStr = ' '.$teamStr;
		for($i=0; $i<$maxTeams; $i++){
			$p = $places[$i];
			if(strpos($teamStr,' '.$p." Place - ")!==false){
				array_push($det,$p);
				$teamStr = str_replace(' '.$p.' Place - ',' ',$teamStr);
			} else if(strpos($teamStr,' '.$p." Place")!==false){								
				array_push($det,$p);	
				//$debug .= "Before: ".$teamStr." | ";			
				$teamStr = str_replace(' '.$p.' Place',' ',$teamStr);
				//$debug .= "After: ".$teamStr.' | ';	
			}
		}
		if(count($det)){
			$details = implode(' vs. ',$det);
			if(preg_match('/\s*-*\s*League Champ Game!/',$teamStr)){	
				$details .= " - League Champ Game!";			
				$teamStr = preg_replace('/\s*-*\s*League Champ Game!/','',$teamStr);		
			}
		}
		$debug .= $teamStr;		
		$pieces = explode(' vs.',$teamStr);
		$team1 = trim($pieces[0]);
		$team2 = trim($pieces[1]);		
		
	} else {
		$team1 = '';
		$team2 = '';
		$details = '';	
		$debug = $teamStr;
	}
	
	$team1 = str_replace('"','',$team1);	
	$team2 = str_replace('"','',$team2);
	
	if($team1) $teams[$team1] = true;
	if($team2) $teams[$team2] = true;
	/* Sort team1/team2 to be alphabetical // would mess up details? hm
	if(strcasecmp($team1, $team2)>0){
		$tmp = $team2;
		$team2 = $team1;
		$team1 = $tmp;
	}*/
	return array('Team1'=>$team1, 'Team2'=>$team2, 'Details'=>@$details, 'Debug'=>@$debug);	
}

function findFacility($text){
	global $facilities;
	
	if(strpos($text,'Rescheduled')!==false){
		return 0;
	}
	$longest = 0;
	$bg = 0;
	foreach($facilities as $f){
		$tmp = similar_text($f['Name'], $text);
		if(	$tmp > $longest ){
			$longest = $tmp;
			$bg = $f['facility_id'];										
		}
	}
	if(!$bg) echo "Facility not found: ".$text."<BR>";
	return $bg;	
}

function addGame($text){
	global $schedules, $date, $time, $facility, $currMatch;	
	$game = array();	
	$game['Date'] = $date;
	$game['Time'] = $time;
	$game['Facility'] = $facility;
	$game['Match'] = $currMatch;
	$teams = parseTeams($text);
	$game = array_merge($game,$teams);
	
	array_push($schedules,$game);
	$currMatch++;	
}

$db = new db();

$s_id = $_GET['s_id'];
if(!$s_id){
	exit('<a href="parseAll.php">Click Here</a>');

}

$sports = $db->fetch_all("SELECT * FROM sports WHERE s_id = '$s_id' ORDER BY s_id ASC");
$nights = array('mon'=>'M','tue'=>'T','wed'=>'W','thu'=>'Th','thr'=>'Th','fri'=>'F','sat'=>'S','sun'=>'Su');

echo "<PRE>";
foreach($sports as $sport){
	echo "<h2>".$sport['s_name']."</h2>";
	$page = file_get_contents("http://www.edmontonsportsclub.com/".$sport['s_page']."/schedules.shtml");
	$doc = phpQuery::newDocumentHTML($page);
	
	$season = $doc['font[face=Verdana][size=2]'];
	
	//$links = $doc['table[width=100%][border=1] tr:contains(a)']->not(':has(img)');
	$links = $doc['table[border=1] a']->parents('tr')->not(':has(table)')->not(':has(img)');
	
	$seasonText = preg_replace('/\s+/',' ',trim(str_replace("\r\n","",$season->text())));
	$seasonInfo = explode(' ',$seasonText);
	
	$season = $seasonInfo[0];
	$year = $seasonInfo[1];

	// Cycle through Division Links for each sport:
	$weekday = '';

	foreach($links as $r=>$row){		

		$pq = pq($row);

		$row = $pq['td'];
		foreach($row as $i=>$td){					
			if($i==0){ 
				$lpq = pq($td);

				// Get child 'a' element of TD cell:								
				$l = $lpq->find('a');
			} elseif($i==1){				
				$updated = $td->textContent;
			}
		}
		if(!$l->text())	continue;		
		
		
					
		$updatedStr = str_ireplace(array('posted','schedule','updated','playoffs','season','complete','st','nd','rd','th','.',"\r\n"),'',$updated);	
		if(!$updatedStr) continue;
		$lastUpdate = date("Y-m-d", strtotime($updatedStr.date(' Y')));			

		$divName = $l->text();
		
		$href = $l->attr('href');		
		$p = strrpos($href,"/");
		if($p===false) $href = "http://www.edmontonsportsclub.com/".$sport['s_page'].'/'.$href;
		$night = $nights[strtolower(substr($href,strrpos($href,"/")+1,3))];		
		$page = substr($href,strrpos($href,"/")+1);
		
		$divCode = substr($page,0,strpos($page,'.'));
		//if($divCode != 'sunrecplus') continue;
		echo $divCode." : ".$divName." - $updated<BR>";
		$div = $db->get("SELECT div_id, lastUpdate FROM divisions WHERE s_id = '$sport[s_id]' AND season = '$season' AND year = '$year' AND div_code = '$divCode'");
		$firstRun = false;
		if(count($div)){
			$div_id = $div['div_id'];
			// No need to update
			if($div['lastUpdate'] == $lastUpdate){
				if(!$force) continue;
				else $firstRun = true;
			} 
			
			// Get All teams if division already exists:
			// $teamList = array(); 
			
			// Schedule Updated - Wipe out entries for games not yet played:
			$db->query("DELETE FROM schedules WHERE	div_id = '$div_id' AND datetime > NOW()");
			$db->query("UPDATE divisions SET lastUpdate = '$lastUpdate' WHERE div_id = '$div_id'");
			
		} else {
			$firstRun = true;
			$dEsc = $divName;
			$db->esc($dEsc);
			$db->query("INSERT INTO divisions (s_id, div_code, season, year, night, div_name, div_link, lastUpdate) VALUES ('$sport[s_id]','$divCode','$season','$year','$night','$dEsc','$href','$lastUpdate')");
			echo $db->error();
			$div_id = $db->getv("SELECT LAST_INSERT_ID()");
		}
		// Fetch Schedule for Current Division:
		$schedule = file_get_contents($href);
		$sdoc = phpQuery::newDocumentHTML($schedule);
		
		$tfacilities = $sdoc['td:contains("Facility")']->not(':has(table)')->parent('tr');
		$facilities = array();
		$fields = array('Name','Address','Link','Sponsor');
		foreach($tfacilities as $i=>$f){
			if($i==0) continue;			
			$row = pq($f);
			$tds = $row->children('td');
			
			foreach($tds as $k=>$td){
				$val = trim(str_replace("\r\n"," ",$td->textContent));
				$val = preg_replace('/\s\s+/', ' ', $val);
				if($fields[$k]=='Link'){				
					$pq = pq($td);	
					$facilities[$i-1]['Link'] = $pq['a']->attr('href');
				} else $facilities[$i-1][$fields[$k]] = $val;
			}									
		}
		
		
		foreach($facilities as $i=>$f){
			$db->esc($f['Name']);
			$fID = $db->getv("SELECT facility_id FROM facilities WHERE facility_name = '$f[Name]'");
			if(!$fID){				 
				$db->esc($f['Address']);
				$db->esc($f['Sponsor']);
				$db->query("INSERT INTO facilities (facility_name, facility_address, facility_link, facility_closestsponsor) ". 
				"VALUES ('$f[Name]','$f[Address]','$f[Link]','$f[Sponsor]')");				
				$fID = $db->getv("SELECT LAST_INSERT_ID()");
			}
			$facilities[$i]['facility_id'] = $fID;	
		}
		//echo "FACILITIES\r\n";
		print_r($facilities);
		
		$tscheds = $sdoc['table[cellpadding=0][cellspacing=0][border=0]']->not(':has(table)');
		$schedules = array();
		$count = -1;
		$teams = array();
		
		foreach($tscheds as $i=>$s){
			// $s is a table for a specific date		
			$facility = '';		
			$matches = 1;		
			foreach($s->childNodes as $rowNum=>$row){
				if($rowNum==0){
					$dateStr = trim(str_replace("\r\n","",$row->textContent));
					if(strpos($dateStr, 'TBD')!==false){
						$date = '2038-01-01';
					} else {
						$handleRescheduled = explode('-',$dateStr); // Rescheduled games have - Rescheduled at the end
						$date = date('Y-m-d',strtotime($handleRescheduled[0]));
					}				
				} else if($rowNum == 1){
					/*if(strpos($row->textContent,'Match 3')!==false)
						$matches=3;
					else if(strpos($row->textContent,'Match 2')!==false){
						$matches=2;	
					} else {
						$matches=1;	
					}		*/	
					continue;							
				} else {												
					$tds = pq($row)->children('td');
					if($tds->length < 3) continue;
					
					$currMatch = 1;
					$facilityRow = false;
					
					foreach($tds as $cellNum => $td){
						// Remove New Lines, extra spaces, $nbsp;, and trim result
						$text = str_replace("\xc2\xa0",' ',$td->textContent);
						$text = trim(str_replace("\r\n"," ",preg_replace('/\s\s+/',' ',$text)));
												
						if(!$text) continue;
						
						if($cellNum == 0){
							$facilityRow = true;
							if($text=='TBD')
							 	$facility = '1'; // Facility ID 1 is 'TBD'
							else {
								$facility = findFacility($text);
							}							 
						} else if($cellNum == 1){
							if($facilityRow){
								if($facility==1){
									if($text=='12:00AM'){
										$time = '00:00';
									} else {
										$time = '00:00';
										addGame($text);									
									}
								} else {
									$time = $text;									
								}
							} else {
								addGame($text);	
							}
						} else {							
							addGame($text);
						}
					}
				}								
			}
		}
		// Get Team ID's:		
		foreach($teams as $team=>$exists){
			$escTeam = $team;
			$db->esc($escTeam);
			$teamID = $db->getv("SELECT team_id FROM teams WHERE team_name = '$escTeam' AND s_id = '$sport[s_id]' AND div_id = '$div_id'");
			if(!$teamID){				
				$db->query("INSERT INTO teams (team_name, s_id, div_code, div_id) VALUES ('$escTeam','$sport[s_id]','$divCode','$div_id')");
				if($db->error()) echo "Error Inserting Team: ".$db->error()."<BR>";
				$teamID = $db->getv("SELECT LAST_INSERT_ID()");
			}
			$teams[$team] = $teamID;
		} 
			
		echo "TEAMS:<BR>";
		print_r($teams);
		
		// Update Schedules:
		echo "SCHEDULES<BR>";	
		foreach($schedules as $i=>$s){
			if($s['Facility']=='1'){
				echo "Skipped TBD game<BR>";
				continue;
			}
			
			if(!@$s['Date']||!@$s['Facility']){
				echo "error: <br>";
				print_r($s);
 		 		continue;
			}
			$team1id = @$teams[$s['Team1']];
			$team2id = @$teams[$s['Team2']];						
			
			if((!$team1id || !$team2id) && !$s['Details'] ){
				echo "Teams and Details Missing: ";
				print_r($s);
				continue;	
			}		
			$datetime = date("Y-m-d H:i", strtotime($s['Date'].' '.$s['Time']));
			$s['datetime'] = $datetime;
			if($firstRun || $datetime > date('Y-m-d H:i')){ // Only insert games greater then today unless it's the first time parsing this division
				$db->esc($s);
				$db->query("INSERT INTO schedules (div_id, datetime, facility_id, team1, team2, team1_id, team2_id, matchnum, details) ".
						   "VALUES ('$div_id','$datetime','$s[Facility]','$s[Team1]','$s[Team2]','$team1id','$team2id','$s[Match]','$s[Details]')");
				if($db->error()){
					echo "Database Error: ".$db->error();			
					print_r($s);
				}			
			}
		}			
	}	
}