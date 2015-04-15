<?php
namespace SimpleTube;
require_once (MODX_BASE_PATH . 'assets/lib/SimpleTab/table.abstract.php');
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/PHPThumb.php');

class stData extends \SimpleTab\dataTable {
	/* @var autoTable $table */
	protected $table = 'st_videos';
	protected $pkName = 'st_id';
	protected $indexName = 'st_index';
	protected $rfName = 'st_rid';

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

	/**
     * @param $ids
     * @param null $fire_events
     * @return mixed
     */
    public function deleteAll($ids, $rid, $fire_events = NULL) {
		$ids = $this->cleanIDs($ids, ',', array(0));
		if(empty($ids) || is_scalar($ids)) return false;
		$videos = $this->query("SELECT `st_id`,`st_thumbUrl` FROM {$this->makeTable($this->table)} WHERE `st_id` IN ({$this->sanitarIn($ids)})");
		$out = $this->delete($ids, $fire_events);
		while ($row = $this->modx->db->getRow($videos)) {
			$this->deleteThumb($row['st_thumbUrl']);
		}
		return $out;
	}

	public function isUnique($url,$rid) {
        $url = $this->modx->db->escape($url);
        $rid = (int)$rid;
        $rows = $this->modx->db->select("`st_id`",$this->makeTable($this->table),"`st_videoUrl`={$url} AND `st_rid`={$rid}");
        return !$this->modx->db->getRecordCount($rows);
    }

	public function save($fire_events = null, $clearCache = false) {
		if ($this->newDoc) {
			$rows = $this->modx->db->select("`st_id`", $this->makeTable($this->table), "`st_rid`={$this->field['st_rid']}");
			$this->field['st_index'] = $this->modx->db->getRecordCount($rows);
			$this->touch('st_createdon');
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