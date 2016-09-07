//<?php
/**
 * SimpleTube
 * 
 * Plugin to create video galleries
 *
 * @category 	plugin
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic (m@xim.name)
 * @internal	@properties &tabName=Tab name;text;SimpleTube &templates=Templates;text; &role=Roles;text; &folder=Thumbs folder;text;assets/images/video/ &thumbsCache=Thumbs cache folder;text;assets/.stThumbs/ &noImage=No image picture;text;assets/snippets/simpletube/noimage.png &w=Thumbs width;text;107 &h=Thumbs height;text;80 &forceDownload=Force download;list;Yes,No;Yes &ytApiKey=Youtube API Key;text; 
 * @internal	@events OnDocFormRender,OnEmptyTrash
 * @internal    @installset base
 * @internal    @legacy_names MultiVideos
 */

require MODX_BASE_PATH.'assets/plugins/simpletube/plugin.simpletube.php';