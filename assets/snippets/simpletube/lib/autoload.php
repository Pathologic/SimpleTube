<?php

function simpletube_autoload($path) {
    $path = explode("\\", $path);

    if (count($path) == 1)
        return;

    if ($path[0] == "Panorama") {
        require_once implode('/', $path).'.php';
    } else require_once 'SimpleTube/simpletube.class.php';


}
spl_autoload_register("simpletube_autoload");

?>