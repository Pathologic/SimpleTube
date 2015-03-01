<?php
namespace SimpleTube;
require_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php');
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/PHPThumb.php');

class stData extends \autoTable {
	/* @var autoTable $table */
	protected $table = 'st_videos';
	protected $pkName = 'st_id';
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
	public $thumbsCache = 'assets/.stThumbs/';
	protected $params = array();
	protected $fs = null;

	public function __construct($modx, $debug = false) {
		parent::__construct($modx, $debug);
		$this->modx = $modx;
		$this->params = (isset($modx->event->params) && is_array($modx->event->params)) ? $modx->event->params : array();
		$this->fs = \Helpers\FS::getInstance();
		$this->_table['st_videos'] = $this->makeTable($this->table);
	}

	/**
     * @param $ids
     * @param null $fire_events
     * @return mixed
     */
    public function deleteAll($ids, $rid, $fire_events = NULL) {
		$ids = $this->cleanIDs($ids, ',', array(0));
		if(empty($ids) || is_scalar($ids)) return false;
		$ids = implode(',',$ids);
		$videos = $this->query('SELECT `st_id`,`st_thumbUrl` FROM '.$this->_table['st_videos'].' WHERE `st_id` IN ('.$this->sanitarIn($ids).')');
		$this->clearIndexes($ids,$rid);
		$out = $this->delete($ids, $fire_events);
		while ($row = $this->modx->db->getRow($videos)) {
			$this->deleteThumb($row['st_thumbUrl']);
		}
		return $out;
	}

    private function clearIndexes($ids, $rid) {
        $rows = $this->query("SELECT MIN(`st_index`) FROM {$this->_table['st_videos']} WHERE `st_id` IN ({$ids})");
        $index = $this->modx->db->getValue($rows);
        $index = $index - 1;
        $this->query("ALTER TABLE {$this->_table['st_videos']} AUTO_INCREMENT = 1");
        $this->query("SET @index := ".$index);
        $this->query("UPDATE {$this->_table['st_videos']} SET `st_index` = (@index := @index + 1) WHERE (`st_index`>{$index} AND `st_rid`={$rid} AND `st_id` NOT IN ({$ids})) ORDER BY `st_index` ASC");
        $out = $this->modx->db->getAffectedRows();
        return $out;
    }

	public function deleteThumb($url, $cache = false) {
		$url = $this->fs->relativePath($url);
		if (empty($url)) return;
		if ($this->fs->checkFile($url)) {
			unlink(MODX_BASE_PATH . $url);
			$dir = $this->fs->takeFileDir($url);
			$iterator = new \FilesystemIterator($dir);
			if (!$iterator->valid()) $this->fs->rmDir($dir);
		}
		if ($cache) return;
		$thumbsCache = isset($this->params['thumbsCache']) ? $this->params['thumbsCache'] : $this->thumbsCache;
		$thumb = $thumbsCache.$url;
		if ($this->fs->checkFile($thumb)) $this->deleteThumb($thumb, true);
	}

	public function reorder($source, $target, $point, $rid, $orderDir) {
		$rid = (int)$rid;
		$point = strtolower($point);
		$orderDir = strtolower($orderDir);
		$sourceIndex = (int)$source['st_index'];
		$targetIndex = (int)$target['st_index'];
		$sourceId = (int)$source['st_id'];
		$rows = 0;
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

	/**
	 * @param $folder
	 * @param $url
	 * @param $options
	 * @return bool
	 */
	public function makeThumb($folder,$url,$options) {
		if (empty($url)) return false;
		$thumb = new \Helpers\PHPThumb();
		$inputFile = MODX_BASE_PATH . $this->fs->relativePath($url);
		$outputFile = MODX_BASE_PATH. $this->fs->relativePath($folder). '/' . $this->fs->relativePath($url);
		$dir = $this->fs->takeFileDir($outputFile);
		$this->fs->makeDir($dir, $this->modx->config['new_folder_permissions']);
		if ($thumb->create($inputFile,$outputFile,$options)) {
			return true;
		} else {
			$this->modx->logEvent(0, 3, $thumb->debugMessages, 'SimpleTube');
			return false;
		}
	}
}