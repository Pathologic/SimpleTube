<?php
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');   
    
$prepare = array();
$prepare[] = \APIhelpers::getkey($modx->event->params, 'BeforePrepare', '');
$prepare[] = 'DLstController::prepare';
$prepare[] = \APIhelpers::getkey($modx->event->params, 'AfterPrepare', '');
$modx->event->params['prepare'] = trim(implode(",", $prepare), ',');

$params = array_merge(array(
    "controller"    =>  "st_site_content",
    "dir"        =>  "assets/snippets/simpletube/controller/"
), $modx->event->params);
if(!class_exists("DLstController", false)){
    class DLstController{
        public static function prepare(array $data = array(), DocumentParser $modx, $_DocLister, prepare_DL_Extender $_extDocLister){
            if (isset($data['videos'])) {
                $wrapper='';
                $imageField = $_DocLister->getCfgDef('imageField','st_thumbUrl');
                $thumbOptions = $_DocLister->getCfgDef('thumbOptions');
                $thumbSnippet = $_DocLister->getCfgDef('thumbSnippet');
                foreach ($data['videos'] as $video) {
                    $ph = $video;
                    if(!empty($thumbOptions) && !empty($thumbSnippet)){
                        $_thumbOptions = json_decode($thumbOptions,true);
                        if (is_array($_thumbOptions)) {
                            foreach ($_thumbOptions as $key => $value) {
                                $postfix = $key == 'default' ? '.' : '_'.$key.'.';
                                $ph['thumb'.$postfix.$imageField] = $modx->runSnippet($thumbSnippet, array(
                                    'input' => $ph[$imageField],
                                    'options' => $value
                                )); 
                                $info = getimagesize(MODX_BASE_PATH.$ph['thumb'.$postfix.$imageField]);
                                $ph['thumb'.$postfix.'width.'.$imageField] = $info[0];
                                $ph['thumb'.$postfix.'height.'.$imageField] = $info[1];
                            }
                        } else {
                            $ph['thumb.'.$imageField] = $modx->runSnippet($thumbSnippet, array(
                                'input' => $ph[$imageField],
                                'options' => $thumbOptions
                            )); 
                            $info = getimagesize(MODX_BASE_PATH.$ph['thumb.'.$imageField]);
                            $ph['thumb.width.'.$imageField] = $info[0];
                            $ph['thumb.height.'.$imageField] = $info[1];
                        }
                    }
                    $ph['e.st_title'] = \APIHelpers::e($video['st_title']);
                    $wrapper .= $_DocLister->parseChunk($_DocLister->getCfgDef('stRowTpl'), $ph);
                }
                $data['videos'] = $_DocLister->parseChunk($_DocLister->getCfgDef('stOuterTpl'),array('wrapper'=>$wrapper));
            }
            return $data;
        }
    }
}
return $modx->runSnippet("DocLister", $params);
?>