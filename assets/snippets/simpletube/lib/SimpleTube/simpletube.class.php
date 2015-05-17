<?php
namespace SimpleTube;
class SimpleTube extends \Panorama\Video {
	public $modx = null;
	public $_cfg = array();
	public $videoDetails = array();
	public $DLTemplate = null;
	public $errorMessage = array();
	public $lang = array();
	
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

        //try to get Youtube API key from plugin settings if snippet parameter is empty
        if (isset($modx->pluginCache['SimpleTubeProps'])) {
            $pluginParams = $modx->parseProperties($modx->pluginCache['SimpleTubeProps'],'SimpleTube','plugin');
            if (empty($cfg['ytApiKey'])) $cfg['ytApiKey'] = $pluginParams['ytApiKey'];
        }

        //Defaults
        $this->_cfg = array_merge(array(
			'folder' => 'assets/images/video/',
			'noImage' => 'assets/snippets/simpletube/noimage.png',
			'forceDownload' => false
		),$cfg);
		$langfile = MODX_BASE_PATH."assets/snippets/simpletube/lib/SimpleTube/lang/{$this->getCFGDef('lang','en')}.php";
		$this->lang = array();
		if (file_exists($langfile) && is_readable($langfile)) {
			include_once $langfile;
			$this->lang = $lang;
		}
        try {
        	if (!isset ($cfg['input']) || empty($cfg['input'])) 
        		throw new \Exception('No URL to parse.');

        	$pattern = "@^(https?|ftp)://[^\s/$.?#].[^\s]*$@iS";
        	if (!preg_match($pattern, $cfg['input'],$match)) {
           		throw new \Exception('Unsupported URL.');
        	}
            $options = array();
            if (!empty($cfg['ytApiKey']))
                $options['youtube']['api_key'] = $cfg['ytApiKey'];
        	parent::__construct($cfg['input'], $options);
        } catch (\Exception $e) {
        	$this->errorMessage[] = $this->translate($e->getMessage());
        }
        require_once (MODX_BASE_PATH.'assets/snippets/DocLister/lib/DLTemplate.class.php');
        require_once (MODX_BASE_PATH.'assets/lib/APIHelpers.class.php');
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
	}

	/*Get rid of redundant data*/
	public function getVideoDetails() {
		if (!empty($this->errorMessage)) {
			$this->videoDetails = array (
				'st_error' => implode(' ',$this->errorMessage)
			);
		} else {
			$this->videoDetails = array(
				'st_title'		=> (string) $this->getTitle(),
				'st_thumbUrl'	=> (string) $this->getThumbnail(),
				'st_embedUrl'	=> array_shift(explode('?',(string) $this->getEmbedUrl())),
				'st_service'	=> (string) $this->getService(),
				'st_duration'	=> (string) $this->getDuration()
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
				if (empty($result['st_thumbUrl'])) $this->videoDetails['st_thumbUrl'] = $this->getCFGDef('noImage');
				$result = $this->render();
				break;
		}
		return $result;
	}

	public function saveThumbnail ($folder) {
		$rid = $this->getCFGDef('rid');
		$folder .= empty($rid) ? '' : $rid . '/';
		$url = &$this->videoDetails['st_thumbUrl'];
		if (empty($url)) return;
		$filepath = $this->modx->config['base_path'] . $folder;
		if (!is_dir($filepath)) mkdir($filepath,intval($this->modx->config['new_folder_permissions'],8),true);
		$tmp = explode('.', $url);
		$ext = '.' . end($tmp);
		$filename = md5($this->getCFGDef('input')) . $ext;
		$image = $filepath . $filename;
		if (!$this->getCFGDef('forceDownload') && file_exists($image)) {
			$result = true;
		} else {
			$result = false;
			$response = $this->Curl($url);
			if (!empty($response)) $result = file_put_contents($image, $response);
		}
		$url = '';
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
        return array_key_exists($name,$this->_cfg) ? $this->_cfg[$name] : $def;
    }

    public function render() {
   		$out = $this->DLTemplate->parseChunk($this->getCFGDef('tpl'),$this->videoDetails);
   		return $out;
    }

	public function translate($phrase) {
		return array_key_exists($phrase,$this->lang) ? $this->lang[$phrase] : $phrase;
	}
}