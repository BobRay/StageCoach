<?php
/**
 * StageCoach plugin for StageCoach extra
 *
 * Copyright 2012-2013 by Bob Ray <http://bobsguides.com>
 * Created on 12-22-2012
 *
 * StageCoach is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * StageCoach is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * StageCoach; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package stagecoach
 */

/**
 * Description
 * -----------
 * Stages Resources for future update
 *
 * Variables
 * ---------
 * @var $modx modX
 * @var $scriptProperties array
 * @var $tv modTemplateVar
 *
 * @package stagecoach
 **/

$doDebug = false;
if ((!isset($modx)) || (!$modx instanceof modX)) {
    return '';
}

$modx->getService('lexicon', 'modLexicon');
$modx->lexicon->load('stagecoach:default');

if (!function_exists("my_debug")) {
    function my_debug($message, $clear = false) {
        global $modx;
        $content = '';
        $chunk = $modx->getObject('modChunk', array('name' => 'Debug'));
        if (!$chunk) {
            $chunk = $modx->newObject('modChunk', array('name' => 'Debug'));
            $chunk->setContent('');
            $chunk->save();
            $chunk = $modx->getObject('modChunk', array('name' => 'Debug'));
        } else {
            if ($clear) {
                $content = '';
            } else {
                $content = $chunk->getContent();
            }
        }
        $content .= $message . "\n";
        $chunk->setContent($content);
        $chunk->save();
    }
}


switch ($modx->event->name) {
    case 'OnWebPageInit':
        $stageDateTvId = $modx->getOption('stagecoach_stage_date_tv_id');
        if (empty($stageDateTvId)) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] StageDate  System Setting is empty');
            return '';
        }
        $stagedResourceTvId = $modx->getOption('stagecoach_staged_resource_tv_id');
        if (empty($stagedResourceTvId)) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] StageID System Setting is empty');
            return '';
        }

        $resourceId = $modx->resourceIdentifier;
        if (empty($resourceId)) {
            $modx->log(modX::LOG_LEVEL_ERROR,
                '[StageCoach] Resource ID is empty');
            return '';
        }

        $tvr = $modx->getObject('modTemplateVarResource', array(
           'contentid' => $resourceId,
           'tmplvarid' => $stageDateTvId,
          ));
        if (!$tvr) {
            return '';
        }
        $date = $tvr->get('value');
        if (empty($date)) {
            return;
        } else {
            if ($doDebug) {
                my_debug('Date TV content: ' . $date, true);
            }
        }

        $timeStamp = strtotime($date);
        if (time() < $timeStamp) {
            return '';
        } else { /* It's time to update the Resource */
            $tvr = $modx->getObject('modTemplateVarResource', array(
                   'contentid' => $resourceId,
                   'tmplvarid' => $stagedResourceTvId,
              ));

            if (!$tvr) {
                $modx->log(modX::LOG_LEVEL_ERROR,
                    '[StageCoach] . No StageID templateVarTemplate');
                return;
            }
            $stageId = $tvr->get('value');
            if (empty($stageId)) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] StageID TV is empty');
            } else {
                if ($doDebug) {
                    my_debug('StageID TV value: ' . $stageId);
                }
            }

            $stagedResource = $modx->getObject('modResource', $stageId);
            if (!$stagedResource) {
                $modx->log(modX::LOG_LEVEL_ERROR,
                    '[StageCoach] Could not find Staged Resource');
            }

            $originalResource = $modx->getObject('modResource', $resourceId);
            if (!$originalResource) {
                $modx->log(modX::LOG_LEVEL_ERROR,
                    '[StageCoach] Could not find Original Resource');
            }
            if ($doDebug) {
                my_debug('Got both resources');
            }
            /* Archive original if option is set */
            $archive = $modx->getOption('stagecoach_archive_original', null, false);
            if ($archive) {
                $archiveFolder = $modx->getOption('stagecoach_archive_id', null, 0);
                if ($archiveFolder) {
                    /* set params for duplicate - pagetitle will contain date/time */
                    $params = array(
                        'publishMode' => 'unpublish',
                        'parent' => $archiveFolder,
                        'newName' => $stagedResource->get('pagetitle') . '-' . 'Archived',
                        'duplicateChildren' => false,
                    );

                    /* @var $archivedResource modResource */
                    $archivedResource = $originalResource->duplicate($params);

                    $archivedResource->save(0);

                    $archivedResource->setTVValue('stageID', '');
                    $archivedResource->setTVValue('stageDate', '');

                }
            }
            /* update original resource */
            $fields = $stagedResource->toArray();
            $originalFields = $originalResource->toArray();
            if ($doDebug) {
                my_debug('toArrays done');
            }
            /* Don't set these fields */
            unset($fields['id'], $fields['menuindex'], $fields['pagetitle'], $fields['publishedon'], $fields['alias'], $fields['published'], $fields['createdon'], $fields['hidemenu'], $fields['parent'], $fields['uri']);

            if ($doDebug) {
                my_debug('past publishedon update');
            }
            $totalFields = array_merge($originalFields, $fields);
            if ($doDebug) {
                my_debug(print_r($totalFields, true));
            }
            $originalResource->fromArray($totalFields);
            $originalResource->setTVValue('StageID', '');
            $originalResource->setTVValue('StageDate', '');

            if ($modx->getOption('stagecoach_update_publishedon_date', null, false)) {
                $originalResource->set('publishedon', $date);
            }

            $success = $originalResource->save(0);
            // $modx->reloadContext('web');

            $cKey = $originalResource->get('context_key');
            $modx->cacheManager->refresh(
                array(
                    'db' => array(),
                    'auto_publish' => array('contexts' => array($cKey)),
                    'context_settings' => array('contexts' => array($cKey)),
                    'resource' => array('contexts' => array($cKey)),
                )
            );

            $includeTvs = $modx->getOption('stagecoach_include_tvs', null, false);
            /* Transfer TV values if option is set */
            if ($includeTvs) {
                $tvrs = $stagedResource->getMany('TemplateVarResources');

                foreach ($tvrs as $oldTemplateVarResource) {
                    /** @var $tvr modTemplateVarResource */
                    /** @var $oldTemplateVarResource modTemplateVarResource */
                    /** @var $newTemplateVarResource modTemplateVarResource */
                    $value = $oldTemplateVarResource->get('value');

                    $c = array(
                        'contentid' => $resourceId,
                        'tmplvarid' => $oldTemplateVarResource->get('tmplvarid'),
                    );
                    /* get Tvr for current resource and set it from staged resource */
                    $tvr = $modx->getObject('modTemplateVarResource', $c);
                    if ($tvr) {
                        $tvr->set('value', $value);
                        $tvr->save();
                    } else { /* tvr does not exist -- create it */
                        $tvr = $modx->newObject('modTemplateVarResource');
                        $tvr->set('contentid', $resourceId);
                        $tvr->set('tmplvarid',
                            $oldTemplateVarResource->get('tmplvarid'));
                        $tvr->set('value', $value);
                        $tvr->save();
                    }
                }
            }
            /* remove staged Resource if original was saved */
            if ($success) {
                if (! $stagedResource->remove()) {
                    $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] Failed to remove staged resource');
                }

            } else {
                $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] Could not save original resource');
            }
        }
        return '';
        break;

    case 'OnDocFormSave':
        /* Create staged Resource if Stage Date TV is set */
        /* @var $oldTv modTemplateVar */

        /* Don't execute for new resources */
        if ($mode != modSystemEvent::MODE_UPD) {
            return;
        }
        $stageId = $resource->getTVValue('StageID');
        $key = $resource->get('context_key');
        //check if Context Setting exists
        $stageFolder = $modx->getObject('modContextSetting',array('context_key'=>$key,'key'=>'stagecoach_resource_id')); 
        //if so use that otherwise use system setting
        $stageFolder = (empty($stageFolder))?$modx->getOption('stagecoach_resource_id', null, 0):$stageFolder->get('value');
        
        //check if Context Setting exists
        $archiveFolder = $modx->getObject('modContextSetting',array('context_key'=>$key,'key'=>'stagecoach_archive_id')); 
        //if so use that otherwise use system setting
        $archiveFolder = (empty($archiveFolder))?$modx->getOption('stagecoach_archive_id', null, 0):$archiveFolder->get('value');

        /* Don't execute on staged or archived Resources */
        $thisParent = $modx->resource->get('parent');
        if ($thisParent && (($thisParent == $stageFolder) || ($thisParent == $archiveFolder))) {
            return '';
        }

        /* don't execute on the folders themselves */
        $thisId = $modx->resource->get('id');
        if ($thisId && (($thisId == $stageFolder) || ($thisId == $archiveFolder))) {
            return '';
        }

        /* get the Stage Date */

        $date = $resource->getTVValue('StageDate');
        if (empty($date)) {
            return '';
        }

        /* Append stage date to staged Resource pagetitle */
        $pt = $resource->get('pagetitle') . '-' . $date;

        if (!empty($stageId)) { /* If set, user is just updating the date */
            $res = $modx->getObject('modResource', $stageId);
            if ($res) { /* update pagetitle to new date */
                $res->set('pagetitle', $pt);
                $res->save();
            }
            return '';
        }

        /* make sure staged Resource doesn't already exist */
        $res = $modx->getObject('modResource', array('pagetitle' => $pt));
        if ($res) {
            return '';
        }

        /* set params for duplicate() */
        $params = array(
            'newName' => $pt,
            'publishMode' => 'unpublish',
            'parent' => $stageFolder,
            'duplicateChildren' => false,
        );

        /* duplicate and save the staged Resource */
        $stagedResource = $resource->duplicate($params);
        $stagedResource->save();

        /* unset the TVs in the staged Resource */
        $stagedResource->setTVValue('stageID', '');
        $stagedResource->setTVValue('stageDate', '');

        /* set the stageID TV in the original Resource */
        $newId = $stagedResource->get('id');
        $resource->setTVValue('stageID', $newId);
        $resource->save(0);
        break;
}
