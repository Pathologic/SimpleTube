<?php
if (IN_MANAGER_MODE != 'true') die();
$e = &$modx->event;
if ($e->name == 'OnDocFormRender' && !!$id) {
	include_once (MODX_BASE_PATH.'assets/plugins/simpletube/lib/plugin.class.php');
	global $modx_lang_attribute;
	$simpleTube = new \SimpleTube\stPlugin($modx, $modx_lang_attribute);
	$output = $simpleTube->render();
	if ($output) $e->output($output);
}
if ($e->name == 'OnEmptyTrash') {
	$where = implode(',',$ids);
	$modx->db->delete($modx->getFullTableName("st_videos"), "`st_rid` IN ($where)");
	include_once (MODX_BASE_PATH.'assets/plugins/simpletube/lib/plugin.class.php');
	$simpleTube = new \SimpleTube\stPlugin($modx);
	$simpleTube->clearFolders($ids,MODX_BASE_PATH.$e->params['thumbsCache'].$e->params['folder']);
	$simpleTube->clearFolders($ids,MODX_BASE_PATH.$e->params['folder']);
}
