<?php
$params = is_array($params) ? $params : array();

$params['dir'] = 'assets/snippets/simpletube/controller/';
$params['controller'] = 'st_site_content';

return $modx->runSnippet("DocLister", $params);