<?php
namespace SimpleTube;
require_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php');

class stData extends \autoTable {
	/* @var autoTable $table */
	protected $table = 'st_videos';
	protected $pkName = 'st_id';
	/* @var autoTable $_table */
	public $_table = '';

	public $default_field = array(
		'st_title' => '',
		'st_videoUrl' => '',
		'st_thumbUrl' => '',
		'st_embedUrl' => '',
		'st_duration' => 0,
		'st_service' => '',
		'st_createdon' => '',
		'st_index' => 0,
		'st_isactive' => '1',
		'st_rid'=>0
		);

	public function __construct($modx, $debug = false) {
		parent::__construct($modx, $debug);
        $this->_table['st_videos'] = $this->makeTable($this->table);
        $this->modx = $modx;
        $this->params = $modx->event->params;
	}

	public function delete($ids, $fire_events = NULL) {
		if (!is_int($ids)) return; //yet only single id to delete;
		$fields = $this->edit($ids)->toArray();
		$out = parent::delete($ids);
		$rows = $this->modx->db->update( '`st_index`=`st_index`-1', $this->_table['st_videos'], '`st_rid`='.($fields['st_rid'] ? $fields['st_rid'] : 0).' AND `st_id` > ' . $ids);
		$this->deleteThumb($fields['st_thumbUrl']);
		return $out;
	}

	public function deleteThumb($url, $cache = false) {
		if (empty($url)) return;
		if (!$cache) {
			$rows = $this->modx->db->select("`st_thumbUrl`",$this->_table['st_videos'],"`st_thumbUrl`='$url'");
			if ($this->modx->db->getRecordCount($rows)) return;
		}
		$thumb = $this->modx->config['base_path'].$url;
		if (file_exists($thumb)) {
			$dir = pathinfo($thumb);
			$dir = $dir['dirname'];
			unlink($thumb);
			$iterator = new \FilesystemIterator($dir);
			if (!$iterator->valid()) rmdir ($dir);
		}
		if ($cache) return;
		$thumbsCache = 'assets/.stThumbs/';
		if (isset($this->modx->pluginCache['SimpleTubeProps'])) {
			$pluginParams = $this->modx->parseProperties($this->modx->pluginCache['SimpleTubeProps']);
			if (isset($pluginParams['thumbsCache'])) $thumbsCache = $pluginParams['thumbsCache'];
		}
		$thumb = $thumbsCache.$url;
		if (file_exists($this->modx->config['base_path'].$thumb)) $this->deleteThumb($thumb, true);
	}

	public function reorder($source, $target, $point, $rid, $orderDir) {
		$rid = (int)$rid;
		$point = strtolower($point);
		$orderDir = strtolower($orderDir);
		$sourceIndex = (int)$source['st_index'];
		$targetIndex = (int)$target['st_index'];
		$sourceId = (int)$source['st_id'];
		/* more refactoring  needed */
		if ($target['st_index'] < $source['st_index']) {
			if (($point == 'top' && $orderDir == 'asc') || ($point == 'bottom' && $orderDir == 'desc')) {
				$rows = $this->modx->db->update('`st_index`=`st_index`+1',$this->_table['st_videos'],'`st_index`>='.$targetIndex.' AND `st_index`<'.$sourceIndex.' AND `st_rid`='.$rid);
				$rows = $this->modx->db->update('`st_index`='.$targetIndex,$this->_table['st_videos'],'`st_id`='.$sourceId);				
			} elseif (($point == 'bottom' && $orderDir == 'asc') || ($point == 'top' && $orderDir == 'desc')) {
				$rows = $this->modx->db->update('`st_index`=`st_index`+1',$this->_table['st_videos'],'`st_index`>'.$targetIndex.' AND `st_index`<'.$sourceIndex.' AND `st_rid`='.$rid);
				$rows = $this->modx->db->update('`st_index`='.(1+$targetIndex),$this->_table['st_videos'],'`st_id`='.$sourceId);				
			}
		} else {
			if (($point == 'bottom' && $orderDir == 'asc') || ($point == 'top' && $orderDir == 'desc')) {
				$rows = $this->modx->db->update('`st_index`=`st_index`-1',$this->_table['st_videos'],'`st_index`<='.$targetIndex.' AND `st_index`>'.$sourceIndex.' AND `st_rid`='.$rid);
				$rows = $this->modx->db->update('`st_index`='.$targetIndex,$this->_table['st_videos'],'`st_id`='.(int)$source['st_id']);				
			} elseif (($point == 'top' && $orderDir == 'asc') || ($point == 'bottom' && $orderDir == 'desc')) {
				$rows = $this->modx->db->update('`st_index`=`st_index`-1',$this->_table['st_videos'],'`st_index`<'.$targetIndex.' AND `st_index`>'.$sourceIndex.' AND `st_rid`='.$rid);
				$rows = $this->modx->db->update('`st_index`='.(-1+$targetIndex),$this->_table['st_videos'],'`st_id`='.$sourceId);				
			}
		}
		
		return $rows;
	}

	public function isUnique($url,$rid) {
        $url = $this->modx->db->escape($url);
        $rid = (int)$rid;
        $rows = $this->modx->db->select("`st_id`",$this->_table['st_videos'],"`st_videoUrl`='$url' AND `st_rid`=$rid");
        return !$this->modx->db->getRecordCount($rows);
    }

	public function save($fire_events = null, $clearCache = false) {
		if ($this->newDoc) {
			$rows = $this->modx->db->select('`st_id`', $this->_table['st_videos'], '`st_rid`='.$this->field['st_rid']);
			$this->field['st_index'] = $this->modx->db->getRecordCount($rows);
			$this->field['st_createdon'] = date('Y-m-d H:i:s');
		}
		return parent::save();
	}

	public function makeThumb($thumbsCache,$url,$w,$h) {
		if (empty($thumbsCache) || empty($url)) return;
		include_once($this->modx->config['base_path'].'assets/snippets/phpthumb/phpthumb.class.php');
		$thumb = new \phpthumb();
		$thumb->sourceFilename = $this->modx->config['base_path'].$url;
	  	$thumb->setParameter('w', $w);
  		$thumb->setParameter('h', $h);
  		$thumb->setParameter('f', 'jpg');
  		$thumb->setParameter('far', 'C');
  		$outputFilename = $this->modx->config['base_path'].$thumbsCache.$url;
  		$info = pathinfo($outputFilename);
  		$dir = $info['dirname'];
  		if (!is_dir($dir)) mkdir($dir,intval($this->modx->config['new_folder_permissions'],8),true);
		if ($thumb->GenerateThumbnail()) {
        	$thumb->RenderToFile($outputFilename);
		}  		
	}
}