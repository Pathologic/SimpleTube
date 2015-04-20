<?php
namespace SimpleTube;
include_once (MODX_BASE_PATH . 'assets/lib/SimpleTab/plugin.class.php');

class stPlugin extends  \SimpleTab\Plugin {
	public $pluginName = 'SimpleTube';
	public $table = 'st_videos';
	public $tpl = 'assets/plugins/simpletube/tpl/simpletube.tpl';
	public $jsListDefault = 'assets/plugins/simpletube/js/scripts.json';
	public $jsListCustom = 'assets/plugins/simpletube/js/custom.json';
	public $cssListDefault = 'assets/plugins/simpletube/css/styles.json';
	public $cssListCustom = 'assets/plugins/simpletube/css/custom.json';
	
	public  function getTplPlaceholders() {
		$ph = array(
			'id'			=>	$this->params['id'],
			'lang'			=>	$this->lang_attribute,
			'url'			=> 	$this->modx->config['site_url'].'assets/plugins/simpletube/ajax.php',
			'theme'			=>  MODX_MANAGER_URL.'media/style/'.$this->modx->config['manager_theme'],
			'tabName'		=>	$this->params['tabName'],
			'site_url'		=>	$this->modx->config['site_url'],
			'manager_url'	=>	MODX_MANAGER_URL,
			'thumb_prefix' 	=> 	$this->modx->config['site_url'].'assets/plugins/simpletube/ajax.php?mode=thumb&url=',
			'kcfinder_url'	=> 	MODX_MANAGER_URL."media/browser/mcpuk/browse.php?type=images",
			'w' 			=> 	isset($this->params['w']) ? $this->params['w'] : '107',
			'h' 			=> 	isset($this->params['h']) ? $this->params['h'] : '80',
			'noImage' 		=> 	isset($this->params['noImage']) ? $this->params['noImage'] : 'assets/snippets/simpletube/noimage.png'
			);
		return array_merge($this->params,$ph);
    }
    public function createTable() {
    	$sql = <<< OUT
CREATE TABLE IF NOT EXISTS {$this->_table} (
`st_id` int(10) NOT NULL auto_increment,
`st_title` varchar(255) NOT NULL default '',
`st_videoUrl` varchar(255) NOT NULL default '',
`st_thumbUrl` varchar(255) NOT NULL default '',
`st_embedUrl` varchar(255) NOT NULL default '',
`st_duration` int(10) default NULL,
`st_service` varchar(30) NOT NULL default '',
`st_isactive` int(1) NOT NULL default '1',
`st_rid` int(10) default NULL,
`st_index` int(10) NOT NULL default '0',
`st_createdon` datetime NOT NULL, 
PRIMARY KEY  (`st_id`)
) ENGINE=MyISAM COMMENT='Datatable for SimpleTube plugin.';
OUT;
		return $this->modx->db->query($sql);
    }
}