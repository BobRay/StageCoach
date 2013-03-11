<?php
/**
 * systemSettings transport file for StageCoach extra
 *
 * Copyright 2012-2013 by Bob Ray <http://bobsguides.com>
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
/* @var xPDOObject[] $systemSettings */


$systemSettings = array();

$systemSettings[1] = $modx->newObject('modSystemSetting');
$systemSettings[1]->fromArray(array(
    'key' => 'stagecoach_resource_id',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
), '', true, true);
$systemSettings[2] = $modx->newObject('modSystemSetting');
$systemSettings[2]->fromArray(array(
    'key' => 'stagecoach_archive_id',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
), '', true, true);
$systemSettings[3] = $modx->newObject('modSystemSetting');
$systemSettings[3]->fromArray(array(
    'key' => 'stagecoach_archive_original',
    'value' => false,
    'xtype' => 'combo-boolean',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
), '', true, true);
$systemSettings[4] = $modx->newObject('modSystemSetting');
$systemSettings[4]->fromArray(array(
    'key' => 'stagecoach_include_tvs',
    'value' => false,
    'xtype' => 'combo-boolean',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
), '', true, true);
$systemSettings[5] = $modx->newObject('modSystemSetting');
$systemSettings[5]->fromArray(array(
    'key' => 'stagecoach_update_publishedon_date',
    'value' => false,
    'xtype' => 'combo-boolean',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
), '', true, true);
$systemSettings[6] = $modx->newObject('modSystemSetting');
$systemSettings[6]->fromArray(array(
    'key' => 'stagecoach_stage_date_tv_id',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
), '', true, true);
$systemSettings[7] = $modx->newObject('modSystemSetting');
$systemSettings[7]->fromArray(array(
    'key' => 'stagecoach_staged_resource_tv_id',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'stagecoach',
    'area' => 'StageCoach',
), '', true, true);
return $systemSettings;
