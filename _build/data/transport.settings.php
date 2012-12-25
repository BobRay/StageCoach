<?php
/**
 * systemSettings transport file for StageCoach extra
 *
 * Copyright 2012 by Bob Ray <http://bobsguides.com>
 * Created on 12-24-2012
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
/* @var xPDOObject[] $systemSettings */


$systemSettings = array();

$systemSettings[1] = $modx->newObject('modSystemSetting');
$systemSettings[1]->fromArray(array(
    'key' => 'stagecoach_resource_id',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
    'description' => 'ID of StageCoach Staged Resource folder',
), '', true, true);
$systemSettings[2] = $modx->newObject('modSystemSetting');
$systemSettings[2]->fromArray(array(
    'key' => 'stagecoach_archive_id',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
    'description' => 'ID of StageCoach Archived Resource folder',
), '', true, true);
$systemSettings[3] = $modx->newObject('modSystemSetting');
$systemSettings[3]->fromArray(array(
    'key' => 'stagecoach_archive_original',
    'value' => false,
    'xtype' => 'combo-boolean',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
    'description' => 'If set, original versions or Resources will be archived when updated; default: false',
), '', true, true);
$systemSettings[4] = $modx->newObject('modSystemSetting');
$systemSettings[4]->fromArray(array(
    'key' => 'stagecoach_include_tvs',
    'value' => false,
    'xtype' => 'combo-boolean',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
    'description' => 'If set, updated Template Variables in staged resources will be copied; default: false',
), '', true, true);
return $systemSettings;
