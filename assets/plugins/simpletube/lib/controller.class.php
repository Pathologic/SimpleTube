<?php namespace SimpleTube;

require_once(MODX_BASE_PATH . 'assets/lib/SimpleTab/controller.abstract.php');
require_once(MODX_BASE_PATH . 'assets/plugins/simpletube/lib/table.class.php');

class stController extends \SimpleTab\AbstractController {
    public $rfName = 'st_rid';
    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $this->data = new \SimpleTube\stData($modx);
        $this->dlInit();
        $defaults = array(
            'thumbsCache' => $this->data->thumbsCache,
            'w' => 107,
            'h' => 80,
            'folder' => 'assets/images/video/',
            'forceDownload' => 'Yes'
        );
        foreach ($defaults as $key => $value) if (!isset($this->params[$key])) $this->params[$key] = $value;
        $this->params['thumbOptions'] = isset($this->params['customThumbOptions']) ? $this->params['customThumbOptions'] : 'w=[+w+]&h=[+h+]&far=C&f=jpg';
        $this->params['thumbOptions'] = urldecode(str_replace(array('[+w+]', '[+h+]'), array($this->params['w'], $this->params['h']), $this->params['thumbOptions']));
        $this->params['forceDownload'] = $this->params['forceDownload'] == 'Yes' ? 1 : 0;
        $this->params['lang'] = $this->getLanguageCode();
    }

    /**
     * @return array
     */
    public function addRow()
    {
        $out = array();
        $url = isset($_REQUEST['stUrl']) ? $_REQUEST['stUrl'] : '';
        $url = array_shift(explode('&', $url));
        if (empty($url)) {
            $out['success'] = false;
            $out['message'] = 'empty_url';
        } elseif ($this->data->isUnique($url, $this->rid)) {
            extract($this->params);
            $params = array('input' => $url, 'api' => '2', 'rid' => $this->rid, 'forceDownload' => $forceDownload, 'lang'=>$lang);
            $fields = $this->modx->runSnippet('SimpleTube', $params);
            if (is_array($fields) && !isset($fields['st_error'])) {
                $fields = array_merge(array(
                    'st_videoUrl' => $url,
                    'st_rid' => $this->rid
                ), $fields);
                if ($this->data->create($fields)->save()) {
                    $this->data->makeThumb($thumbsCache, $fields['st_thumbUrl'], $thumbOptions);
                    $out['success'] = true;
                }
            } else {
                $out['success'] = false;
                $out['message'] = $fields['st_error'];
            }
        } else {
            $out['success'] = false;
            $out['message'] = 'video_exists';
        }
        return $out;
    }
    
    public function edit() {
        $id = isset($_REQUEST['st_id']) ? (int)$_REQUEST['st_id'] : 0;
        if ($id) {
            $out = $origin = $this->data->edit($id)->toArray();
        } else {
            die();
        }
        extract($this->params);
        $url = array_shift(explode('&', $_REQUEST['st_videoUrl']));
        if ($url != $origin['st_videoUrl']) {
            $params = array('input' => $url, 'api' => '2', 'rid' => $origin['st_rid'], 'forceDownload' => $forceDownload, 'lang'=>$lang);
            $out = $this->modx->runSnippet('SimpleTube', $params);
            if (isset($out['st_error']) || !$this->data->isUnique($url,$this->rid)) $out = $origin;
        } else {
            $thumbUrl = $_REQUEST['st_thumbUrl'];
            if ($out['st_thumbUrl'] != $thumbUrl) {
                if (in_array(strtolower($this->FS->takeFileExt($thumbUrl)), array('gif', 'png', 'jpeg', 'jpg'))) {
                    $dest = str_replace ($this->FS->takeFileName($out['st_thumbUrl']),md5($out['st_thumbUrl'].time()),$out['st_thumbUrl']);
                    if ($this->FS->copyFile($thumbUrl, $dest)) {
                        $this->data->deleteThumb($out['st_thumbUrl']);
                        $out['st_thumbUrl'] = $dest;
                    }
                }
            }
            $out['st_title'] = $_REQUEST['st_title'];
            $out['st_isactive'] = (int)!!$_REQUEST['st_isactive'];
        }
        $this->data->fromArray($out)->save();
        return $out;
    }

    public function thumb()
    {
        $url = $_REQUEST['url'];
        extract($this->params);
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