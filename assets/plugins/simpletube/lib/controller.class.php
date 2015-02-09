<?php namespace SimpleTube;

require_once(MODX_BASE_PATH . 'assets/lib/SimpleTab/controller.abstract.php');
require_once(MODX_BASE_PATH . 'assets/plugins/simpletube/lib/table.class.php');

class stController extends \SimpleTab\AbstractController {
    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $this->data = new \SimpleTube\stData($modx);
        $this->ridField = 'st_rid';
        $this->rid = isset($_REQUEST[$this->ridField]) ? (int)$_REQUEST[$this->ridField] : 0;
    }

    public function addRow()
    {
        $out = array();
        $url = isset($_REQUEST['stUrl']) ? $_REQUEST['stUrl'] : '';
        $url = explode('&', $url);
        $url = $url[0];
        if (empty($url)) {
            $out['success'] = false;
            $out['message'] = "Неверный URL";
        } elseif ($this->data->isUnique($url, $this->rid)) {
            $params = array('input' => $url, 'api' => '2', 'rid' => $this->rid);
            $w = 107;
            $h = 80;
            $thumbsCache = $this->data->thumbsCache;
            if (isset($this->params)) {
                if (isset($this->params['thumbsCache'])) $thumbsCache = $this->params['thumbsCache'];
                if (isset($this->params['w'])) $w = $this->params['w'];
                if (isset($this->params['h'])) $h = $this->params['h'];
                if (isset($this->params['folder'])) $params['folder'] = $this->params['folder'];
                if (isset($this->params['forceDownload'])) $params['forceDownload'] = ($this->params['forceDownload'] == 'Yes') ? '1' : '0';
            }
            $fields = $this->modx->runSnippet('SimpleTube', $params);
            if (is_array($fields) && !isset($fields['st_error'])) {
                $fields = array_merge(array(
                    'st_videoUrl' => $url,
                    'st_rid' => $this->rid
                ), $fields);
                if ($this->data->create($fields)->save()) {
                    $this->data->makeThumb($thumbsCache, $fields['st_thumbUrl'], "w=$w&h=$h&q=96&f=jpg");
                    $out['success'] = true;
                }
            } else {
                $out['success'] = false;
                $out['message'] = $fields['st_error'];
            }
        } else {
            $out['success'] = false;
            $out['message'] = 'Это видео уже есть в галерее.';
        }
        return $out;
    }
    public function remove()
    {
        $out = array();
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        if ($id) {
            $this->data->delete($id);
            $out['success'] = true;
        } else {
            $out['success'] = false;
            $out['message'] = "Не удалось удалить.";
        }
        return $out;
    }
    public function edit() {
        $id = isset($_REQUEST['st_id']) ? (int)$_REQUEST['st_id'] : 0;
        if ($id) {
            $origin = $this->data->edit($id)->toArray();
        } else {
            die();
        }
        $new = array();
        $url = explode('&', $_REQUEST['st_videoUrl']);
        $url = $url[0];
        $checkUrl = ($origin['st_videoUrl'] == $url);
        $checkThumb = ($origin['st_thumbUrl'] == $_REQUEST['st_thumbUrl']);
        if (!$checkUrl) {
            $fields = $this->modx->runSnippet('SimpleTube', array('input' => $url, 'api' => '2', 'docId' => $origin['st_rid']));
            $new['st_videoUrl'] = $url;
        }
        if (!$checkThumb) {
            $source = realpath(MODX_BASE_PATH . $_REQUEST['st_thumbUrl']);
            if ($source && file_exists($source)) {
                $fileinfo = pathinfo($source);
                if (in_array(strtolower($fileinfo['extension']), array('gif', 'png', 'jpeg', 'jpg'))) {
                    $folder = isset($this->params['folder']) ? $this->params['folder'] : 'assets/video/';
                    $folder .= $origin['st_rid'] . '/';
                    if (!is_dir(MODX_BASE_PATH . $folder)) mkdir(MODX_BASE_PATH . $folder, intval($this->modx->config['new_folder_permissions'], 8), true);
                    $dest = $folder . $fileinfo['filename'] . time() . '.' . $fileinfo['extension'];
                    if (copy($source, MODX_BASE_PATH . $dest)) {
                        $_REQUEST['st_thumbUrl'] = $dest;
                    } else {
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
        $new['st_thumbUrl'] = $checkUrl ? $_REQUEST['st_thumbUrl'] : $fields['st_thumbUrl'];
        $new['st_isactive'] = (int)!!$_REQUEST['st_isactive'];
        $new['st_index'] = (int)$_REQUEST['st_index'];
        if ($this->data->fromArray($new)->save()) {
            if (!$checkUrl || !$checkThumb) $this->data->deleteThumb($origin['st_thumbUrl']);
            $this->data->close();
        }
        return $new;
    }
    public function reorder()
    {
        $out = array();
        $source = $_REQUEST['source'];
        $target = $_REQUEST['target'];
        $point = $_REQUEST['point'];
        $orderDir = $_REQUEST['orderDir'];
        $rows = $this->data->reorder($source, $target, $point, $this->rid, $orderDir);

        if ($rows) {
            $out['success'] = true;
        } else {
            $out['success'] = false;
            $out['message'] = "Не удалось сохранить данные.";
        }
        return $out;
    }
    public function thumb()
    {
        $w = 107;
        $h = 80;
        $url = $_REQUEST['url'];
        $thumbsCache = $this->data->thumbsCache;
        if (isset($this->params)) {
            if (isset($this->params['thumbsCache'])) $thumbsCache = $this->params['thumbsCache'];
            if (isset($this->params['w'])) $w = $this->params['w'];
            if (isset($this->params['h'])) $h = $this->params['h'];
        }
        $thumbOptions = isset($this->params['customThumbOptions']) ? $this->params['customThumbOptions'] : 'w=[+w+]&h=[+h+]&far=C&f=jpg';
        $thumbOptions = urldecode(str_replace(array('[+w+]', '[+h+]'), array($w, $h), $thumbOptions));
        $file = MODX_BASE_PATH . $thumbsCache . $url;
        if ($this->FS->checkFile($file)) {
            $info = getimagesize($file);
            if ($w != $info[0] || $h != $info[1]) {
                @$this->data->makeThumb($thumbsCache, $url, $thumbOptions);
            }
        } else {
            @$this->data->makeThumb($thumbsCache, $url, $thumbOptions);
        }
        session_start();
        header("Cache-Control: private, max-age=10800, pre-check=10800");
        header("Pragma: private");
        header("Expires: " . date(DATE_RFC822, strtotime(" 360 day")));
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($file))) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT', true, 304);
            $this->isExit = true;
            return;
        }
        header("Content-type: image/jpeg");
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
        readfile($file);
    }
}