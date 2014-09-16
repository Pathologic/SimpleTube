<?php
define('MODX_API_MODE', true);
include_once(dirname(__FILE__)."/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}

if(!isset($_SESSION['mgrValidated'])){
    die();
}
if (isset($modx->pluginCache['SimpleTubeProps'])) {
	$params = $modx->parseProperties($modx->pluginCache['SimpleTubeProps']);
} else {
	die();
}

$roles = isset($params['role']) ? explode(',',$params['role']) : false;
if ($roles && !in_array($_SESSION['mgrRole'], $roles)) die();
	
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : null;
$rid = isset($_REQUEST['st_rid']) ? (int)$_REQUEST['st_rid'] : 0;

include_once(MODX_BASE_PATH.'assets/plugins/simpletube/lib/table.class.php');
$data = new \SimpleTube\stData($modx);
switch ($mode) {
	case 'addRow' :
		if (!$rid) die();
		$url = isset($_REQUEST['stUrl']) ? $_REQUEST['stUrl'] : '';
		$url = explode('&',$url);
		$url = $url[0];
		if (empty($url)) {
			$out['success'] = false;
			$out['message'] = "Неверный URL";
		} elseif ($data->isUnique($url,$rid)) {
			$params = array('input' => $url, 'api'=> '2', 'docId' => $rid);
			$thumbsCache = 'assets/.stThumbs/';
			$w = 107;
			$h = 80;
			if (isset($modx->pluginCache['SimpleTubeProps'])) {
				$pluginParams = $modx->parseProperties($modx->pluginCache['SimpleTubeProps']);
				if (isset($pluginParams['thumbsCache'])) $thumbsCache = $pluginParams['thumbsCache'];
				if (isset($pluginParams['w'])) $w = $pluginParams['w'];
				if (isset($pluginParams['h'])) $h = $pluginParams['h'];
				if (isset($pluginParams['folder'])) $params['folder'] = $pluginParams['folder'];
				if (isset($pluginParams['forceDownload'])) $params['forceDownload'] = ($pluginParams['forceDownload'] == 'Yes') ? '1' : '0';
			} 
			$fields = $modx->runSnippet('SimpleTube',$params);
			if (is_array($fields) && !isset($fields['st_error'])) {
				
				$fields =array_merge(array(
					'st_videoUrl' => $url,
					'st_rid' => $rid,
					),$fields);
				if($data->create($fields)->save()) {
					$data->makeThumb($thumbsCache,$fields['st_thumbUrl'],$w,$h);
					$data->close();
					$out['success'] = true;}
			} else {
				$out['success'] = false;
				$out['message'] = $fields['st_error'];
			}
		} else {
			$out['success'] = false;
			$out['message'] = 'Это видео уже есть в галерее.';
		}
		break;
	case 'remove':
		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
		if ($id) {
			$data->delete($id);
			$out['success'] = true;
		} else {
			$out['success'] = false;
			$out['message'] = "Не удалось удалить.";
		}
		break;
	case 'edit':
		$id = isset($_REQUEST['st_id']) ? (int)$_REQUEST['st_id'] : 0;
		if ($id) {
			$origin = $data->edit($id)->toArray();
		} else {
			die();}
		$new = array();
		$url = explode('&',$_REQUEST['st_videoUrl']);
		$url = $url[0];
		$checkUrl = ($origin['st_videoUrl'] == $url);
		$checkThumb = ($origin['st_thumbUrl'] == $_REQUEST['st_thumbUrl']);
		if (!$checkUrl) {
			$fields = $modx->runSnippet('SimpleTube',array('input'=>$url,'api'=>'2','docId'=>$origin['st_rid']));
			$new['st_videoUrl'] = $url;
		}
		if (!$checkThumb) {
			$source = realpath(MODX_BASE_PATH.$_REQUEST['st_thumbUrl']);
			if ($source && file_exists($source)) {
				$fileinfo = pathinfo($source);
				if (in_array(strtolower($fileinfo['extension']), array('gif','png','jpeg','jpg'))) {
					if (isset($modx->pluginCache['SimpleTubeProps'])) 
						$pluginParams = $modx->parseProperties($modx->pluginCache['SimpleTubeProps']);
					$folder = isset($pluginParams['folder']) ? $pluginParams['folder'] : 'assets/video/';
					$folder .= $origin['st_rid'].'/';
					if (!is_dir(MODX_BASE_PATH.$folder)) mkdir(MODX_BASE_PATH.$folder,intval($modx->config['new_folder_permissions'],8),true);
					$dest = $folder.$fileinfo['filename'].time().'.'.$fileinfo['extension'];
					if (copy($source, MODX_BASE_PATH.$dest)) {
						$_REQUEST['st_thumbUrl'] = $dest;
					}else {
						$checkThumb = false;
					}
				} else {
					$checkThumb = false;
				}
			} else {
				$checkThumb = false;
			}
		}
		$new['st_title'] = $checkUrl ? $_REQUEST['st_title'] : $fields['st_title'];
		$new['st_isactive'] = ($fields['st_isactive'] != $_REQUEST['st_isactive']) ? 1 : 0;
		$new['st_thumbUrl'] = $checkUrl ? $_REQUEST['st_thumbUrl'] : $fields['st_thumbUrl'];
		$new['st_index'] = (int)$_REQUEST['st_index'];
		if($data->fromArray($new)->save()) {
			if (!$checkUrl || !$checkThumb) $data->deleteThumb($origin['st_thumbUrl']);
			$data->close();
			$out = json_encode($new);}
		break;
	case 'reorder' :
		if (!$rid) die();
		$source = $_REQUEST['source'];
		$target = $_REQUEST['target'];
		$point = $_REQUEST['point'];
		$orderDir = $_REQUEST['orderDir'];
		$rows = $data->reorder($source,$target,$point,$rid,$orderDir);
		
		if ($rows) {
			$out['success'] = true;
		} else {
			$out['success'] = false;
			$out['message'] = "Не удалось сохранить данные.";
		}

		break;
	case 'thumb':
		$w = 107;
		$h = 80;
		$url = $_REQUEST['url'];
		$thumbsCache = 'assets/.stThumbs/';
		if (isset($modx->pluginCache['SimpleTubeProps'])) {
			$pluginParams = $modx->parseProperties($modx->pluginCache['SimpleTubeProps']);
			if (isset($pluginParams['thumbsCache'])) $thumbsCache = $pluginParams['thumbsCache'];
			if (isset($pluginParams['w'])) $w = $pluginParams['w'];
			if (isset($pluginParams['h'])) $h = $pluginParams['h'];
		}
		$file = MODX_BASE_PATH.$thumbsCache.$url;
		if (file_exists($file)) {
			$info = getimagesize($file);
			if ($w != $info[0] || $h != $info[1]) {
				$data->makeThumb($thumbsCache,$url,$w,$h);
			}
		} else {
			$data->makeThumb($thumbsCache,$url,$w,$h);
		}
		header('Content-Type: image/jpeg');
		readfile($file);
		break;
	default:
		if (!$rid) die();
		$fields = "id,title,videoUrl,thumbUrl,service,isactive,duration,createdon,index";
		$param = array(
            "controller" 	=> 	"onetable",
            "table" 		=> 	"st_videos",
            'idField' 		=> 	"st_id",
            "api" 			=> 	"st_".str_replace(',',',st_',$fields),
            "idType"		=>	"documents",
            'ignoreEmpty' 	=> 	"1",
            'JSONformat' 	=> 	"new"
		);
		$display = 10;
		$display = isset($_REQUEST['rows']) ? (int)$_REQUEST['rows'] : $display;
		$offset = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
		$offset = $display*abs($offset-1);

		$param['display'] = $display;
		$param['offset'] = $offset;

		if(isset($_REQUEST['sort'])){
			$sort = $_REQUEST['sort'];
			$param['sortBy'] = preg_replace('/[^A-Za-z0-9_\-]/', '', $sort);
			if(''==$param['sortBy']){
				unset($param['sortBy']);
			}
		}
		if(isset($_REQUEST['order']) && in_array(strtoupper($_REQUEST['order']), array("ASC","DESC"))){
			$param['sortDir'] = $_REQUEST['order'];
		}
		$param['addWhereList'] = "`st_rid`=$rid";
		$out = $modx->runSnippet("DocLister", $param);
		break;
}
echo ($out = is_array($out) ? json_encode($out) : $out);