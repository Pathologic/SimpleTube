<?php
if (IN_MANAGER_MODE != 'true') die();
$e = &$modx->Event;
if ($e->name == 'OnDocFormRender' && !!$id) {
include_once (MODX_BASE_PATH.'assets/plugins/simpletube/lib/plugin.class.php');
$simpleTube = new \SimpleTube\stPlugin($modx);
$output = $simpleTube->render();
if ($output) $e->output($output);
}
