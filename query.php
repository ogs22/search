<?php

define("DB_HOST",'localhost');
define("DB_USER",'cmep');
define("DB_PASS",'@PASSWORD@');
define("DB_NAME",'cmepsearch');
header('Access-Control-Allow-Origin: *');

/**
* 
*/
class CmepSearch {
	public $num_results = 0;
	public $limit = 25;
	public $type = "all";
	public $site = "all";
	
	function __construct($term,$site='fenman') {
		if ($term == ''){
			return false;
		}
		if ($site == '') {
			$site = 'fenman';
		}
		$this->cdb();
		$this->site = mysqli_real_escape_string($this->link,$site);
		$this->term = mysqli_real_escape_string($this->link,$term);
		$this->doSearch();
		$this->getResults();
	}

	public function outputJson() {
		return json_encode($this->raw);
	}


	private function getResults() {
		while($row = mysqli_fetch_assoc($this->result)) {
			$x = array();
			$meta = array();
			$x['title'] = $row['title'];
			$x['page'] = $row['page'];
			if (isset($row['score'])) {
			   $x['score'] = $row['score'];
			} else {
			   $x['score'] = 0;
			}
			if ($row['meta']!='') {
				$meta = json_decode($row['meta'],true);
			}	
			$raw = array_merge($x,$meta);
        	$this->raw[]=$raw;
        }
    }

    private function doSearch() {
    	if ($this->type == "all") {
    		$this->result = $this->link->query($this->normal()) or die($this->normal());
    		if (mysqli_num_rows($this->result) == 0) {
    			$this->result = $this->link->query($this->expand()) or die($this->expand());
    		}
    		if (mysqli_num_rows($this->result) == 0) {
    			$this->result = $this->link->query($this->like()) or die($this->like());
    		}
    	} else {
    		if (method_exists($this, $this->type)) {
    			$this->result = $this->link->query($this->{$this->type}()) or die($this->{$this->type}());
    		}
    	}
    	$this->num_results = mysqli_num_rows($this->result);
    }

	private function cdb() {
		$this->link = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME) or die("Error " . mysqli_error($this->link));
	}

	private function normal() {
		$sql = "SELECT meta,title,page, MATCH (title,content) AGAINST ('".$this->term."') AS score 
		FROM cmepsearch WHERE MATCH (title,content) AGAINST ('".$this->term."') and site = '".$this->site."' limit ".$this->limit;
		return $sql;
	}

	private function expand() {
		$sql = "SELECT meta,title,page, MATCH (title,content) AGAINST ('".$this->term."' WITH QUERY EXPANSION) AS score 
		FROM cmepsearch WHERE MATCH (title,content) AGAINST ('".$this->term."' WITH QUERY EXPANSION) and site = '".$this->site."' limit ".$this->limit;
		return $sql;
	}

	private function like() {
		$sql = "SELECT meta,title,page from cmepsearch where content like '%".$this->term."%' and site = '".$this->site."' limit ".$this->limit;
		return $sql;
	}

}

if (php_sapi_name() == 'cli') {
    $_GET['search'] = true;
    $_GET['term'] = $argv[1];
    $_GET['site'] = $argv[2];
    if ($_GET['term']=='') {
    	echo "\nNo search term entered.\n";
    	echo "CMD line usage: php ".$argv[0]." search_term site_name\n\n";
    	echo "php ".$argv[0]." triangle fenman\n\n";
    	exit();
    }
}

if ($_GET['search'] and $_GET['term']) {
   if (!isset($_GET['site'])) {
      $_GET['site'] ='';
   }
	$search = new CmepSearch($_GET['term'],$_GET['site']);
	if ($search->num_results > 0) {
		header('content-type: application/json; charset=utf-8');
		print $search->outputJson();
	}	
}



