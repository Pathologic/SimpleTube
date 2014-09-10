<?php
if (!defined('MODX_BASE_PATH')) die();
require_once __DIR__.'/lib/autoload.php';
$tube = new \SimpleTube\SimpleTube ($modx, $modx->event->params);
return $tube->getResult();
