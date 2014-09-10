<?php
namespace SimpleTube;
class SimpleTube extends \Panorama\Video {
	public $modx = null;
	public $_cfg = array();
	public $videoDetails = array();
	public $DLTemplate = null;
	
	public static function getInstance(\DocumentParser $modx) {
        if (null === self::$instance) {
            self::$instance = new self($modx);
        }
        return self::$instance;
    }

    private function __clone()
    {

    }

	public function __construct ($modx, $cfg = array()) {
		try {
			if ($modx instanceof \DocumentParser) {
                $this->modx = $modx;
                if (!is_array($cfg) || empty($cfg)) $cfg = $this->modx->Event->params;
            } else {
                throw new Exception('MODX var is not instaceof DocumentParser');
            }
        } catch (Exception $e) {
            die($e->getMessage());
        }
        
        //Defaults
        $cfg = array_merge(array(
			'folder' => 'assets/images/video/',
			'noImage' => 'assets/snippets/simpletube/noimage.png',
			'forceDownload' => false
		),$cfg);
        $this->_cfg = $cfg;
        
        try {
        	if (!isset ($cfg['input']) || empty($cfg['input'])) 
        		throw new Exception('Input parameter is empty');
        	parent::__construct($cfg['input']);	
        } catch (Exception $e) {
        	die($e->getMessage());
        }
        require_once ('assets/snippets/DocLister/lib/DLTemplate.class.php');
        require_once ('assets/lib/APIHelpers.class.php');
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
	}

	/*Get rid of redundant data*/
	public function getVideoDetails() {
		$this->videoDetails = array(
            "title"       => (string) $this->getTitle(),
            "thumbUrl"   => (string) $this->getThumbnail(),
            "embedUrl"    => (string) $this->getEmbedUrl(),
            "service"     => (string) $this->getService(),
            "duration"    => (string) $this->getDuration(),
        );
		$this->saveThumbnail($this->getCFGDef('folder'));
		return $this->videoDetails;
	}
    
	public function getResult() {
		$result = $this->getVideoDetails();
		switch ($this->getCFGDef('api')) {
			case '1':
				$result = json_encode($result);
				break;
			case '2':
				break;
			default:
				$result = $this->render();
				break;
		}
		return $result;
	}

	public function saveThumbnail ($folder) {
		$folder .= empty($this->getCFGDef('docId')) ? '' : $this->getCFGDef('docId') . '/';
		$url = &$this->videoDetails['thumbUrl'];
		if (empty($url)) $url = $this->getCFGDef('noImage');
		$filepath = $this->modx->config['base_path'] . $folder;
		if (!is_dir($filepath)) mkdir($filepath);
		$tmp = explode('.', $url);
		$ext = '.' . end($tmp);
		$filename = md5($url) . $ext;
		$image = $folder . $filename;
		if (!$this->getCFGDef('forceDownload') && file_exists($folder . $filename)) {
			$result = true;
		} else {
			$result = false;
			$response = $this->Curl($url);
			if (!empty($response)) $result = file_put_contents($image, $response);
		}	
		$url = ($result) ? $image : $this->getCFGDef('noImage');
	}

	public function Curl($url = '') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);

		$data = curl_exec($ch);
		return $data;
	}

	public function getCFGDef($name, $def = null)
    {
        return isset($this->_cfg[$name]) ? $this->_cfg[$name] : $def;
    }

    public function render() {
   		$out = $this->DLTemplate->parseChunk($this->getCFGDef('tpl'),$this->videoDetails);
   		return $out;
    }
}