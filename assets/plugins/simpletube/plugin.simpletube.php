<?php
if (IN_MANAGER_MODE != 'true') die();
$e = &$modx->event;
if ($e->name == 'OnDocFormRender' && !!$id) {
include_once (MODX_BASE_PATH.'assets/plugins/simpletube/lib/plugin.class.php');
$simpleTube = new \SimpleTube\stPlugin($modx);
$output = $simpleTube->render();
if ($output) $e->output($output);
}
if ($e->name == 'OnEmptyTrash') {
$where = implode(',',$ids);
$modx->db->delete($modx->getFullTableName("st_videos"), "`st_rid` IN ($where)");
foreach ($ids as $id) {
	$dirPath = MODX_BASE_PATH.$e->params['thumbsCache'].$e->params['folder'].$id.'/';
	foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
    	$path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
	}
	rmdir($dirPath);
	$dirPath = MODX_BASE_PATH.$e->params['folder'].$id.'/';
	foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
    	$path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
	}
	rmdir($dirPath);
}
}
