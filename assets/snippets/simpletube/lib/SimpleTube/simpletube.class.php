<?php
namespace SimpleTube;
class SimpleTube extends \Panorama\Video {
	public $modx = null;
	public $_cfg = array();
	public $videoDetails = array();
	public $DLTemplate = null;
	public $errorMessage = array();
	
	public function __construct ($modx, $cfg = array()) {
		try {
			if ($modx instanceof \DocumentParser) {
                $this->modx = $modx;
                if (!is_array($cfg) || empty($cfg)) $cfg = $this->modx->event->params;
            } else {
                throw new \Exception('MODX var is not instaceof DocumentParser');
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        
        //Defaults

        $cfg = array_merge(array(
			'folder' => 'assets/images/video/',
			'forceDownload' => false
		),$cfg);
        $this->_cfg = $cfg;
        
        try {
        	if (!isset ($cfg['input']) || empty($cfg['input'])) 
        		throw new \Exception('Нет ссылки для обработки.');

        	/* https://gist.github.com/dperini/729294 */
        	$pattern = "@^(https?|ftp)://[^\s/$.?#].[^\s]*$@iS";
        	if (!preg_match($pattern, $cfg['input'],$match)) {
           		throw new \Exception('Такие ссылки не поддерживаются.');
        	}

        	parent::__construct($cfg['input']);	
        } catch (\Exception $e) {
        	$this->errorMessage[] = $e->getMessage();
        }
        require_once ($this->modx->config['base_path'].'assets/snippets/DocLister/lib/DLTemplate.class.php');
        require_once ($this->modx->config['base_path'].'assets/lib/APIHelpers.class.php');
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
	}

	/*Get rid of redundant data*/
	public function getVideoDetails() {
		if (!empty($this->errorMessage)) {
			$this->videoDetails = array (
				"st_error" => implode(', ',$this->errorMessage)
			);
		} else {
		$this->videoDetails = array(
            "st_title"       => (string) $this->getTitle(),
            "st_thumbUrl"   => (string) $this->getThumbnail(),
            "st_embedUrl"    => (string) $this->getEmbedUrl(),
            "st_service"     => (string) $this->getService(),
            "st_duration"    => (string) $this->getDuration(),
        );
	}
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
		$folder .= empty($this->getCFGDef('docId')) ? '' : $this->getCFGDef('rid') . '/';
		$url = &$this->videoDetails['st_thumbUrl'];
		if (empty($url)) return;
		$filepath = $this->modx->config['base_path'] . $folder;
		if (!is_dir($filepath)) mkdir($filepath,intval($this->modx->config['new_folder_permissions'],8),true);
		$tmp = explode('.', $url);
		$ext = '.' . end($tmp);
		$filename = md5($url) . $ext;
		$image = $filepath . $filename;
		if (!$this->getCFGDef('forceDownload') && file_exists($image)) {
			$result = true;
		} else {
			$result = false;
			$response = $this->Curl($url);
			if (!empty($response)) $result = file_put_contents($image, $response);
		}	
		
		if ($result) $url = $folder.$filename;
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