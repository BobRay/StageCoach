<?php
/**
 * templateVars transport file for StageCoach extra
 *
 * Copyright 2012-2017 by Bob Ray <https://bobsguides.com>
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
/* @var xPDOObject[] $templateVars */


$templateVars = array();

$templateVars[1] = $modx->newObject('modTemplateVar');
$templateVars[1]->fromArray(array (
  'id' => 1,
  'property_preprocess' => false,
  'type' => 'date',
  'name' => 'StageDate',
  'caption' => 'Stage Date',
  'description' => 'Date Resource will be updated',
  'elements' => '',
  'rank' => 0,
  'display' => '',
  'default_text' => '',
  'properties' => NULL,
  'input_properties' => 
  array (
  ),
  'output_properties' => 
  array (
  ),
), '', true, true);
$templateVars[2] = $modx->newObject('modTemplateVar');
$templateVars[2]->fromArray(array (
  'id' => 2,
  'property_preprocess' => false,
  'type' => 'textfield',
  'name' => 'StageID',
  'caption' => 'Stage ID',
  'description' => 'ID of staged Resource (set automatically)',
  'elements' => '',
  'rank' => 1,
  'display' => 'default',
  'default_text' => '',
  'properties' => 
  array (
  ),
  'input_properties' => 
  array (
  ),
  'output_properties' => 
  array (
  ),
), '', true, true);
return $templateVars;
