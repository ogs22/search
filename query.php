<?php

define("DB_HOST",'localhost');
define("DB_USER",'cmep');
define("DB_PASS",'@PASSWORD@');
define("DB_NAME",'cmepsearch');

/**
* 
*/
class CmepSearch {
	public $num_results = 0;
	public $limit = 25;
	public $type = "all";
	
	function __construct($term) {
		if ($term == ''){
			return false;
		}
		$this->cdb();
		$this->term = mysqli_real_escape_string($this->link,$term);
		$this->doSearch();
		$this->getResults();
	}

	public function outputJson() {
		return json_encode($this->raw);
	}

	private function getResults() {
		while($row = mysqli_fetch_assoc($this->result)) {
        	$this->raw[]=$row;
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
		$sql = "SELECT title,page, MATCH (title,content) AGAINST ('".$this->term."') AS score 
		FROM cmepsearch WHERE MATCH (title,content) AGAINST ('".$this->term."') limit ".$this->limit;
		return $sql;
	}

	private function expand() {
		$sql = "SELECT title,page, MATCH (title,content) AGAINST ('".$this->term."' WITH QUERY EXPANSION) AS score 
		FROM cmepsearch WHERE MATCH (title,content) AGAINST ('".$this->term."' WITH QUERY EXPANSION) limit ".$this->limit;
		return $sql;
	}

	private function like() {
		$sql = "SELECT title,page from cmepsearch where content like '%".$this->term."%' limit ".$this->limit;
		return $sql;
	}

}

if (php_sapi_name() == 'cli') {
    $_GET['search'] = true;
    $_GET['term'] = $argv[1];
    if ($_GET['term']=='') {
    	echo "\nNo search term entered.\n";
    	echo "CMD line usage: ".$argv[0]." search_term\n\n";
    	exit();
    }
}

if ($_GET['search']) {
	$search = new CmepSearch($_GET['term']);
	if ($search->num_results > 0) {
		header('content-type: application/json; charset=utf-8');
		print $search->outputJson();
	}	
}





