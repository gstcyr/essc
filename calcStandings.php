<?
class standings{
	private $divID;
	public $ranks;
	public $results;
	public $multiMatch = false;
	
	public function __construct($divID,$teamID=''){
		$this->divID = $divID;
		if(!@$db) $db = new db();
		
		$divInfo = $db->get("SELECT * FROM divisions WHERE div_id = '$this->divID'");		
		$isRec = false;		
		if(strpos($divInfo['div_name'],'Recreational')!==false && strpos($divInfo['div_name'],'Plus')===false){
			$isRec = true;	
		}
		
		$games = $db->fetch_all("SELECT * FROM schedules WHERE div_id = '$this->divID' AND (score1 IS NOT NULL AND score2 IS NOT NULL)");
		$this->games = $games;
		$results = array();
		
		foreach($games as $g){
			if(!@$results[$g['team1_id']]) $results[$g['team1_id']] = array('TeamID'=>$g['team1_id'],'MatchesWon'=>0,'MatchesLost'=>0,'MatchesTied'=>0,'GamesWon'=>0,'GamesLost'=>0,'GamesTied'=>0,'PointsFor'=>0,'PointsAgainst'=>0,'Spirit'=>0, 'TotalPoints'=>0);
			if(!@$results[$g['team2_id']]) $results[$g['team2_id']] = array('TeamID'=>$g['team2_id'],'MatchesWon'=>0,'MatchesLost'=>0,'MatchesTied'=>0,'GamesWon'=>0,'GamesLost'=>0,'GamesTied'=>0,'PointsFor'=>0,'PointsAgainst'=>0,'Spirit'=>0, 'TotalPoints'=>0);
			$scores1 = explode(',',$g['score1']);
			$scores2 = explode(',',$g['score2']);
			$matches = count($scores1);		
			$won1 = 0;
			$won2 = 0;	
			for($i=0;$i<count($scores1);$i++){
				@$results[$g['team1_id']]['PointsFor'] += $scores1[$i];
				@$results[$g['team2_id']]['PointsFor'] += $scores2[$i];
				@$results[$g['team1_id']]['PointsAgainst'] += $scores2[$i];
				@$results[$g['team2_id']]['PointsAgainst'] += $scores1[$i];
				
				if($scores1[$i] > $scores2[$i]){
					$won1++;
					@$results[$g['team1_id']]['GamesWon']++;
					@$results[$g['team2_id']]['GamesLost']++;					
				} elseif($scores2[$i] > $scores1[$i]){
					$won2++;
					@$results[$g['team2_id']]['GamesWon']++;
					@$results[$g['team1_id']]['GamesLost']++;
				} else {
					@$results[$g['team1_id']]['GamesTied']++;
					@$results[$g['team2_id']]['GamesTied']++;
				}											
			}
			$team1win = false; $team2win = false; $tie = false;
			if($won1 > $won2){
				$team1win = true;
				@$results[$g['team1_id']]['MatchesWon']++;
				@$results[$g['team2_id']]['MatchesLost']++;					
			} elseif($won2 > $won1){
				$team2win = true;
				@$results[$g['team2_id']]['MatchesWon']++;
				@$results[$g['team1_id']]['MatchesLost']++;
			} else {
				$tie = true;
				@$results[$g['team1_id']]['MatchesTied']++;
				@$results[$g['team2_id']]['MatchesTied']++;
			}				
			@$results[$g['team1_id']]['Spirit'] += $g['spirit1'];
			@$results[$g['team2_id']]['Spirit'] += $g['spirit2'];
			
			@$results[$g['team1_id']]['TotalPoints'] += ($team1win?2:0) + ($tie?1:0) + ($isRec?$g['spirit1']:0);
			@$results[$g['team2_id']]['TotalPoints'] += ($team2win?2:0) + ($tie?1:0) + ($isRec?$g['spirit2']:0);		
		}
		
		// Calculate Differentials
		foreach($results as $i => $r){
			$results[$i]['PointsDiff'] = $r['PointsFor'] - $r['PointsAgainst'];
			$results[$i]['GamesDiff'] = $r['GamesWon'] - $r['GamesLost'];
			$results[$i]['WinPercentage'] = ($r['MatchesWon'] + 0.5*$r['MatchesTied'])/($r['MatchesWon']+$r['MatchesLost']);
			$results[$i]['MaxSpirit'] = 3 * ($r['MatchesWon']+$r['MatchesLost']+$r['MatchesTied']);
		}	

		// Sort Results
		if(@$matches==1)
			usort($results, array("standings","sortResultsSingleMatch"));
		else {
			usort($results, array("standings","sortResultsMultiMatch"));			
			$this->multiMatch= true;	
		}
		
		$this->results = $results;				
				
	//	print_r($this->results);
	}
	public function getRanks(){
		if(!count($this->ranks)){			
			foreach($this->results as $i=>$r){			
				$this->ranks[$r['TeamID']] = $i+1;
			}
		}
		return $this->ranks;
	}	
	public function getRank($teamID){
		if(!count($this->ranks)) $this->getRanks();
		return $this->ranks[$teamID];
	}
	private function sortResultsSingleMatch($a, $b){			
		// 1st Criteria: Compare Total Points
		if(@$a['TotalPoints']==@$b['TotalPoints']){
			// 2nd Criteria: Compare Spirit
			if($a['Spirit']==$b['Spirit']){										
				// 3rd Criteria: Compare points differential
				if($a['PointsDiff'] == $b['PointsDiff']){
					// 4th Criteria: Compare games between teams
					foreach($this->games as $g){
						if( $g['team1_id'] == $a['TeamID'] && $g['team2_id'] == $b['TeamID']){							 
							return $g['score1'] >= $g['score2'] ? -1 : 1;
						} elseif ($g['team1_id'] == $b['TeamID'] && $g['team2_id'] == $a['TeamID']){
							return $g['score2'] >= $g['score1'] ? -1 : 1;
						}
					}
					return 0;										
				} else {
					return $a['PointsDiff'] > $b['PointsDiff'] ? -1 : 1;	
				}
			} else return $a['Spirit'] > $b['Spirit'] ? -1 : 1;
		} else return @$a['TotalPoints'] > @$b['TotalPoints'] ? -1 : 1;
	}
	private function sortResultsMultiMatch($a, $b){
		// 1st Criteria : Win Percentage
		if($a['WinPercentage'] == $b['WinPercentage']){
			// 2nd Criteria : Game Differential
			if($a['GamesDiff'] == $b['GamesDiff']){
				// 3rd Criteria : Points Differential
				if($a['PointsDiff'] == $b['PointsDiff']){
					// should compare matches between tied teams here, too lazy
					 return 0;
				} else return $a['PointsDiff'] > $b['PointsDiff'] ? -1 : 1;
			} else return $a['GamesDiff'] > $b['GamesDiff'] ? -1 : 1;						
		} else return $a['WinPercentage'] > $b['WinPercentage'] ? -1 : 1;
	}	
}






?>