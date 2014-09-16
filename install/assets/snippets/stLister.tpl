//<?php
/**
 * stLister
 * 
 * DocLister wrapper for SimpleTube table
 *
 * @category 	snippet
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@properties 
 * @internal	@modx_category Content
 * @author      Pathologic (m@xim.name)
 */

$params = array_merge(array(
	"controller" 	=> 	"onetable",
    "table" 		=> 	"st_videos",
    "idField" 		=> 	"st_id",
	"idType"		=>	"documents",
	"ignoreEmpty" 	=> 	"1"
	), $modx->event->params);
if (!isset($documents)) {
	$parents = isset($parents) ? $modx->db->escape($parents) : $modx->documentIdentifier; 
	if (isset($params['addWhereList'])) $params['addWhereList'] .= " AND ";
	$params['addWhereList'] .= "`st_rid` in ($parents)";
}
return $modx->runSnippet('DocLister', $params);