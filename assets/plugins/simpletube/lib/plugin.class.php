<?php
namespace SimpleTube;
include_once (MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
include_once (MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

class stPlugin {
	public $modx = null;
	public $params = array();
	public $DLTemplate = null;
	
	public function __construct($modx, $debug = false) {
        $this->modx = $modx;
        $this->params = $modx->event->params;
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        
    }
    public function render() {
    	$templates = isset($this->params['templates']) ? explode(',',$this->params['templates']) : false;
		$roles = isset($this->params['roles']) ? explode(',',$this->params['roles']) : false;
		if (($templates && !in_array($this->params['template'],$templates)) || ($roles && !in_array($_SESSION['mgrRole'],$roles))) return false;
		
		$createTable = isset($this->params['createTable']) ? $this->params['createTable'] : 'No';
		$w = isset($this->params['w']) ? $this->params['w'] : '107';
		$h = isset($this->params['h']) ? $this->params['h'] : '80';
		if ($createTable == 'Yes') {
			$output = '<script type="text/javascript">alert("';
			if ($this->createTable()) {
				$output .= 'Таблица создана. Измените настройки плагина SimpleTube';
			} else {
				$output .= 'Не удалось создать таблицу.';
			}
			$output .= '");</script>';
			return $output;
		}
		
		$plugins = $this->modx->pluginEvent;
		if(array_search('ManagerManager',$plugins['OnDocFormRender']) === false) {
			$jquery = '<script type="text/javascript" src="'.$this->modx->config['site_url'].'assets/js/jquery.min.js"></script>';
		}
		
		$tpl = MODX_BASE_PATH.'assets/plugins/simpletube/tpl/simpletube.tpl';
		if(file_exists($tpl)) {
			$tpl = file_get_contents($tpl);
		} else {
			return false;
		}
		$ph = array(
			'jquery'	=>	$jquery,
			'id'		=>	$this->params['id'],
			'url'		=> 	$this->modx->config['site_url'].'assets/plugins/simpletube/ajax.php',
			'theme'		=>  $this->modx->config['manager_theme'],
			'tabName'	=>	$this->params['tabName'],
			'site_url'	=>	$this->modx->config['site_url'],
			'thumb_prefix' => $this->modx->config['site_url'].'assets/plugins/simpletube/ajax.php?mode=thumb&url=',
			'kcfinder_url'	=> MODX_MANAGER_URL."media/browser/mcpuk/browse.php?type=images",
			'w' => $w,
			'h' => $h
			);
		$output = $this->DLTemplate->parseChunk('@CODE:'.$tpl,$ph);
		return $output; 
    }
    public function createTable() {
    	$table = $this->modx->db->config['table_prefix'];
    	$sql = <<< OUT
CREATE TABLE IF NOT EXISTS `{$table}st_videos` (
`st_id` int(10) NOT NULL auto_increment,
`st_title` varchar(255) NOT NULL default '',
`st_videoUrl` varchar(255) NOT NULL default '',
`st_thumbUrl` varchar(255) NOT NULL default '',
`st_embedUrl` varchar(255) NOT NULL default '',
`st_service` varchar(30) NOT NULL default '',
`st_isactive` int(1) NOT NULL default '1',
`st_rid` int(10) default NULL,
`st_index` int(10) NOT NULL default '0',
`st_createdon` datetime NOT NULL, 
PRIMARY KEY  (`st_id`)
) ENGINE=MyISAM COMMENT='Datatable for SimpleTube plugin.';
OUT;
    	if ($this->modx->db->query($sql)) {
    		return true;
    	} else {
    		return false;
    	}
    }
}