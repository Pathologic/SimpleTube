<?php

function simpletube_autoload($path) {
    $path = explode("\\", $path);

    if (count($path) == 1)
        return;

    if ($path[0] == "Panorama") {
        require implode('/', $path).'.php';
    } else require 'SimpleTube/simpletube.class.php';


}
spl_autoload_register("simpletube_autoload");

?>