<?php
/**
 * resources transport file for StageCoach extra
 *
 * Copyright 2012-2024 Bob Ray <https://bobsguides.com>
 * Created on 03-05-2013
 *
 * @package stagecoach
 * @subpackage build
 */

if (! function_exists('stripPhpTags')) {
    function stripPhpTags($filename) {
        $o = file_get_contents($filename);
        $o = str_replace('<' . '?' . 'php', '', $o);
        $o = str_replace('?>', '', $o);
        $o = trim($o);
        return $o;
    }
}
/* @var $modx modX */
/* @var $sources array */
/* @var xPDOObject[] $resources */


$resources = array();

$resources[1] = $modx->newObject('modResource');
$resources[1]->fromArray(array (
  'id' => 1,
  'type' => 'document',
  'contentType' => 'text/html',
  'pagetitle' => 'Staged Resources',
  'longtitle' => 'StageCoach Archive',
  'description' => 'Folder for StageCoach archived Resources',
  'alias' => 'staged-resources',
  'alias_visible' => true,
  'link_attributes' => '',
  'published' => false,
  'isfolder' => true,
  'introtext' => '',
  'richtext' => false,
  'template' => 'default',
  'menuindex' => 57,
  'searchable' => true,
  'cacheable' => true,
  'createdby' => 1,
  'editedby' => 1,
  'deleted' => false,
  'deletedon' => 0,
  'deletedby' => 0,
  'menutitle' => '',
  'donthit' => false,
  'privateweb' => false,
  'privatemgr' => false,
  'content_dispo' => 0,
  'hidemenu' => true,
  'context_key' => 'web',
  'content_type' => 1,
  'hide_children_in_tree' => 0,
  'show_in_tree' => 1,
  'properties' => '',
), '', true, true);
$resources[1]->setContent(file_get_contents($sources['data'].'resources/staged resources.content.html'));

$resources[2] = $modx->newObject('modResource');
$resources[2]->fromArray(array (
  'id' => 2,
  'type' => 'document',
  'contentType' => 'text/html',
  'pagetitle' => 'StageCoach Archive',
  'longtitle' => 'StageCoach Archive',
  'description' => 'Folder for StageCoach archived Resources',
  'alias' => 'stagecoach-archive',
  'alias_visible' => true,
  'link_attributes' => '',
  'published' => false,
  'isfolder' => true,
  'introtext' => '',
  'richtext' => false,
  'template' => 'default',
  'menuindex' => 58,
  'searchable' => true,
  'cacheable' => true,
  'createdby' => 1,
  'editedby' => 1,
  'deleted' => false,
  'deletedon' => 0,
  'deletedby' => 0,
  'menutitle' => '',
  'donthit' => false,
  'privateweb' => false,
  'privatemgr' => false,
  'content_dispo' => 0,
  'hidemenu' => true,
  'context_key' => 'web',
  'content_type' => 1,
  'hide_children_in_tree' => 0,
  'show_in_tree' => 1,
  'properties' => '',
), '', true, true);
$resources[2]->setContent(file_get_contents($sources['data'].'resources/stagecoach archive.content.html'));

return $resources;
