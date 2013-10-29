<?php

require_once("twitteroauth/twitteroauth.php");
require_once("config.php");
class twittersearch {
	
	private $consumerkey = CONSUMERKEY;
	private $consumersecret = CONSUMERSECRET;
	private $accesstoken = ACCESSTOKEN;
	private $accesstokensecret = ACCESSTOKENSECRET;
	
	private $dbhost = DBHOST;
	private $dbname = DBNAME;
	private $dbuser = DBUSER;
	private $dbpassword = DBPASSWORD;
	
	private $connection;
	private $querystring;
	
	private $dbconnection = null;
	
	private $q = 'Salt Lake City';
	private $count = 100;
	private $queryparams = array();
	private $results;
	private $searchid = 1;
	private $chartdata;
	
	public function createconnection() {
		$this->connection = new TwitterOAuth($this->consumerkey, $this->consumersecret, $this->accesstoken, $this->accesstokensecret);
	}
	
	public function createdbconnection() {
		$this->dbconnection = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword);
		mysql_select_db($this->dbname);
	}
	
	private function setupchartdata() {
		$yvals = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		$drilldowncats = array('0-9','10-19','20-29','30-39','40-49','50-59');
		$drilldowncounts = array(0,0,0,0,0,0);
		
		$chartdata = array();
		$colorcounter = 0;
		foreach ($yvals as $yval) {
			$colorcounter = $colorcounter < 10 ? $colorcounter : 0;
			$chartdata[$yval] = array(
									'y'=>0,
									'color'=>"colors[$colorcounter]",
									'drilldown'=> array(
										'name'=>$yval,
										'categories'=>$drilldowncats,
										'data'=>$drilldowncounts,
										'color'=>"colors[$colorcounter]"
									)
								);
			$colorcounter++;
		}
		$this->chartdata = $chartdata;
	}
	
	public function setq($q) {
		if (trim($q) != '') {
			$this->q = $q;
		}
	}
	
	public function setcount($count) {
		if (trim($count) != '') {
			$this->count = $count;
		}
	}
	
	public function setsearchid($searchid) {
		if (trim($searchid) != '') {
			$this->searchid = $searchid;
		}
	}
	
	public function clearqueryparams() {
		$this->queryparams = array();
	}
	
	public function addqueryparam($key, $value) {
		$this->queryparams[$key] = $value;
	}
	
	private function createquerystring() {
		if (!array_key_exists('q',$this->queryparams)) {
			$this->addqueryparam('q', $this->q);
		}
		
		if (!array_key_exists('count',$this->queryparams)) {
			$this->addqueryparam('count', $this->count);
		}
		
		$this->querystring = http_build_query($this->queryparams);
	}
	
	public function searchtwitter() {
		$this->createquerystring();
		$x = 'search/tweets.json?'.$this->querystring;
		
		$this->results = $this->connection->get('search/tweets.json?'.$this->querystring);
		
	}
	
	public function storetwitterresults() {
		if (!$this->dbconnection) {
			$this->createdbconnection();
		}
		
		mysql_query("INSERT INTO searches (
						`SearchTimestamp`, 
						`Query`
					) 
					VALUES (
						'".mysql_escape_string(date("Y-m-d H:i:s"))."',
						'".mysql_escape_string($this->q)."'
					);", $this->dbconnection);
		
		$SearchId = mysql_insert_id($this->dbconnection);
		
		foreach ($this->results->statuses as $Status) {
			$TwitterId = $Status->id_str;
			
			preg_match("/[a-zA-Z]/", $Status->text, $matches);
			$SortingLetter = $matches[0];
			
			$Second = (int)date("s",strtotime($Status->created_at));
			
			if ($Second <= 9) {
				$MinutePosition = 0;
			} elseif ($Second >= 10 && $Second <= 19) {
				$MinutePosition = 1;
			} elseif ($Second >= 20 && $Second <= 29) {
				$MinutePosition = 2;
			} elseif ($Second >= 30 && $Second <= 39) {
				$MinutePosition = 3;
			} elseif ($Second >= 40 && $Second <= 49) {
				$MinutePosition = 4;
			} elseif ($Second >= 50 && $Second <= 59) {
				$MinutePosition = 5;
			}
			
			$SQL = "INSERT IGNORE INTO searchresults (
							`TwitterId`, 
							`CreatedTimestamp`, 
							`UserId`, 
							`ScreenName`, 
							`Tweet`, 
							`SortingLetter`, 
							`MinutePosition`, 
							`AllData`
						)
						VALUES (
							'".mysql_escape_string($TwitterId)."',
							'".date("Y-m-d H:i:s",strtotime($Status->created_at))."',
							'".mysql_escape_string($Status->user->id_str)."',
							'".mysql_escape_string($Status->user->screen_name)."',
							'".mysql_escape_string($Status->text)."',
							'".mysql_escape_string(strtolower($SortingLetter))."',
							'".$MinutePosition."',
							'".mysql_escape_string(serialize($Status))."'
						);";
			
			mysql_query($SQL, $this->dbconnection);
			
			$XREF_SQL = "INSERT INTO searches_searchresults_xref (
								`SearchId`,
								`TwitterId`
							)
							VALUES (
								'$SearchId',
								'".mysql_escape_string($TwitterId)."'
							);";
			
			mysql_query($XREF_SQL, $this->dbconnection);
		}
		
		$return_array = array('SearchId'=>$SearchId, 'q'=>$this->q);
		return json_encode($return_array);
	}
	
	public function getsearches() {
		if (!$this->dbconnection) {
			$this->createdbconnection();
		}
		
		$SQL = "SELECT * FROM searches ORDER BY SearchId DESC";
		
		$Result = mysql_query($SQL);
		
		return $Result;
	}
	
	public function getsearchresults() {
		if (!$this->dbconnection) {
			$this->createdbconnection();
		}
		
		$this->setupchartdata();
		
		if ($this->searchid == null) {
			$this->searchid = 1;
		}
		
		$SQL = "SELECT * FROM searchresults 
				INNER JOIN searches_searchresults_xref as xref on xref.TwitterId = searchresults.TwitterId
				WHERE xref.SearchId = '".mysql_escape_string($this->searchid)."' 
				ORDER BY SearchId";
		
		$Result = mysql_query($SQL);
		
		$Data = array();
		
		while ($Row = mysql_fetch_assoc($Result)) {
			$Data[] = $Row;
			
			$this->chartdata[$Row['SortingLetter']]['y']++;
			$this->chartdata[$Row['SortingLetter']]['drilldown']['data'][$Row['MinutePosition']]++;
		}
		
		$return_array = array('Data'=>$Data, 'ChartData'=>$this->chartdata);
		return json_encode($return_array);
	}
	
}

?>
