<?php
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}

include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/core/controller/site_content.php');
class st_site_contentDocLister extends site_contentDocLister
{
    public function getDocs($tvlist = '')
    {
        $docs = parent::getDocs($tvlist);

        $table = $this->getTable('st_videos');
        $rid = $this->modx->db->escape(implode(',',array_keys($docs)));
        $stOrderBy = $this->modx->db->escape($this->getCFGDef('stOrderBy','st_index ASC'));

        $sgDisplay = $this->getCFGDef('stDisplay','all');
        $stAddWhereList = $this->modx->db->escape($this->getCFGDef('stAddWhereList',''));

        if (!empty($stAddWhereList)) $stAddWhereList = ' AND ('.$stAddWhereList.')';
        if (!empty($rid) && ($stDisplay == 'all' || is_numeric($stDisplay))) {
            switch ($stDisplay) {
                case 'all':
                    $sql = "SELECT * FROM {$table} WHERE `st_rid` IN ({$rid}) {$stAddWhereList} ORDER BY {$stOrderBy}";
                    break;
                case '1':
                    $sql = "SELECT * FROM (SELECT * FROM {$table} WHERE `st_rid` IN ({$rid}) {$stAddWhereList} ORDER BY {$stOrderBy}) st GROUP BY st_rid";
                    break;
                default:
                    $sql = "SELECT * FROM (SELECT *, @rn := IF(@prev = `st_rid`, @rn + 1, 1) AS rn, @prev := `st_rid` FROM {$table} JOIN (SELECT @prev := NULL, @rn := 0) AS vars WHERE `sg_rid` IN ({$rid}) ORDER BY sg_rid, {$stOrderBy}) AS sg WHERE rn <= {$stDisplay}";
                    break;
            }
            $videos = $this->dbQuery($sql);
            while ($video = $this->modx->db->getRow($videos)) {
                $_rid = $video['st_rid'];
                $docs[$_rid]['videos'][] = $image;
            }
        }
        $this->_docs = $docs;
        return $docs;
    }
}
