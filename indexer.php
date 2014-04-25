<?php

define("SITE_PATH", '/www/cmep/html');
define("DOC_ROOT",'/www/cmep/html/');
define("ROOT_URL", 'http://cmep.maths.org/');
define("DB_HOST",'localhost');
define("DB_USER",'cmep');
define("DB_PASS",'@PASSWORD@');
define("DB_NAME",'cmepsearch');

/**
* 
*/
class Indexer {
	public $verbose = true;
	public $ignore = array();

	function __construct() {
		$this->cdb();
	}

	public function index() {
		$this->deleteAll();
		$this->indexsite();
	}

	public function ignore($parts) {
		$parts = array_map('trim',$parts);
		$parts = array_filter($parts);
		$this->ignore = $parts;
	}

	private function cdb() {
		$this->link = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME) or die("Error " . mysqli_error($link));
	}

	private function cleanup($data='') {
		$config = array(
       // 'indent' => false,
			'output-xhtml' => true,
     //   'wrap' => 400,
			'numeric-entities' => true
			);
		$config['char-encoding'] = 'utf8';
		$config['input-encoding'] = 'utf8';
		$config['output-encoding'] = 'utf8';

		$data = trim($data);
		$data = trim($data,'"');
		$data = str_replace('\r\n',' ',$data);
		$slh = '\"';
		$data = str_replace($slh, '"', $data);
		$data = trim($data);
		$tidy = new tidy();
		$tidy->parseString($data, $config, "utf8");
		$tidy->repairString($data, $config, "utf8");
		$body = $tidy->Body();
		$string = $body->value;
		$string = strip_tags($string);
		$string = strtolower($string);
		return trim($string);
	}

	private function recurseDir($dir,&$files=array()) {
		foreach (glob($dir."/*") as $name) {
			if (is_dir($name)) {
				$this->recurseDir($name,$files);
			}
		}
		foreach (glob($dir."/*html") as $html) {
			$files[] = $html;
		}
	}

	private function amputateFoot($data) {
		$pat = '/<div id="footer.*/ms';
		$op =  preg_replace($pat, '', $data);
		return $op;
	}

	private function removeSmall($data) {
		$pat = '/\b[a-z]{1,3}\b/ims';
		$data = preg_replace($pat, '', $data);
		return $data;
	}

	private function removePunc($data) {
		$pat = '/\W/ims';
		$data = preg_replace($pat, ' ', $data);
		return $data;
	}

	private function scrape($data) {
		$content = $this->amputateFoot($data);
		$words = $this->cleanup($content);
		$words = $this->removePunc($words);
		return $words;
	}

	private function getTitle($data) {
		$pat = '!<title>([a-z].*)</title>!ims';
		preg_match($pat, $data,$matches);
		return $matches[1];
	}

	private function removeUnwanted($files) {
	//remove stuff like bower etc;
		foreach ($files as $key => $value) {
			foreach ($this->ignore as $part) {
				if (strpos($value, $part) ) {
					unset($files[$key]);
					if ($this->verbose) {
						echo "removing".$value;
					}
					break 1;
				}
			}
		}
		return $files;
	}

	private function getSite($page) {
		$page = str_replace(ROOT_URL,'',$page);
		$exp = explode('/', $page);
		$site = $exp[0];
		if ($site == '') {
			$site = 'fenman';
		}
		return $site;
	}

	private function getMeta($filename) {
		$jsonfile = str_replace(".html", ".json", $filename);
		
		if (file_exists($jsonfile)) {
			$content = file_get_contents($jsonfile);
			$test = json_decode($content);
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
				echo ' - No JSON errors';
				break;
				case JSON_ERROR_DEPTH:
				echo ' - Maximum stack depth exceeded';
				break;
				case JSON_ERROR_STATE_MISMATCH:
				echo ' - Underflow or the modes mismatch';
				break;
				case JSON_ERROR_CTRL_CHAR:
				echo ' - Unexpected control character found';
				break;
				case JSON_ERROR_SYNTAX:
				echo ' - Syntax error, malformed JSON';
				break;
				case JSON_ERROR_UTF8:
				echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
				default:
				echo ' - Unknown error';
				break;
			}
			echo "\n";
		} else {
			$content = '';
			if ($this->verbose) {
				echo $filename." has no json metadata file\n";
			}
		}
		return $content;
	}

	public function indexsite() {
		$files = array();
		$this->recurseDir(SITE_PATH,$files);
		$files = $this->removeUnwanted($files);
		foreach ($files as $filename) {
			$content = file_get_contents($filename);
			$title = mysqli_real_escape_string($this->link, $this->getTitle($content));
			$content = mysqli_real_escape_string($this->link, $this->scrape($content));
			$page = str_replace(DOC_ROOT, ROOT_URL, $filename);
			$site = $this->getSite($page);
			$meta = mysqli_real_escape_string($this->link,$this->getMeta($filename));
			if ($this->verbose) {
				echo $filename."::'".$title."' added to index\n";
			}
			$sql = 'INSERT INTO cmepsearch (page,title,content,site,meta) VALUES ("'.$page.'","'.$title.'","'.$content.'","'.$site.'","'.$meta.'")';
			$result = $this->link->query($sql) or die($sql);
		}
	}

	private function deleteAll() {
		$sql = 'delete from cmepsearch';
		$result = $this->link->query($sql) or die($sql);
	}
}

if (php_sapi_name() == 'cli') {
	$index = new Indexer();
	chdir(realpath(dirname(__FILE__)));
	$exceptions = file('./exceptions.txt');
	if ($exceptions != array()) {
		$index->ignore($exceptions);
	}
	$index->index();
} else {
	echo "Only use by the CMD line.";
	exit();
}











