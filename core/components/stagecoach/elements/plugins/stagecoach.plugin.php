<?php
/**
 * StageCoach plugin for StageCoach extra
 *
 * Copyright 2012 by Bob Ray <http://bobsguides.com>
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


switch($modx->event->name) {

    case 'OnLoadWebDocument':

        $date = $modx->resource->getTVValue('StageDate');
        if ( empty($date)) {
            return '';
        }
        $timeStamp = strtotime($date);

        // my_debug('NOW: ' . time(), true);
        // my_debug('STAGE_DATE: ' . $timeStamp);
        if (time() >= $timeStamp) { /* Time to update Resource */
            $stageID = $modx->resource->getTVValue('StageID');
            $archive = $modx->getOption('stagecoach_archive_original', null, false);
            $includeTvs = $modx->getOption('stagecoach_include_tvs', null, false);
            if (!empty($stageID)) {
                $stagedResource = $modx->getObject('modResource', $stageID);
            } else { /* try with pagetitle */
                $pt = $modx->resource->get('pagetitle') . '-' . $date;
                $stagedResource = $modx->getObject('modResource', array('pagetitle' => $pt));
            }
            if (empty($stagedResource)) {
                // my_debug('No Resource');
                return;
            }
            if ($archive) {
                $archiveFolder = $modx->getOption('stagecoach_archive_id', null, 0);
                    if ($archiveFolder) {
                    $params = array(
                        'publishMode' => 'unpublish',
                        'parent' => $archiveFolder,
                        'newName' => $stagedResource->get('pagetitle') . '-' . 'Archived',
                    );
                    $archivedResource = $modx->resource->duplicate($params);
                    $archivedResource->setTVValue('stageID', '');
                    $archivedResource->setTVValue('stageDate', '');
                }
            }
            $fields = $stagedResource->toArray();
            unset($fields['id'], $fields['pagetitle'], $fields['alias'], $fields['published'], $fields['hidemenu'], $fields['parent'], $fields['uri']);
            $modx->resource->set('publishedon', time());
            $modx->resource->fromArray($fields);
            $modx->resource->setTVValue('StageID', '');
            $modx->resource->setTVValue('StageDate', '');
            $stagedResource->setTVValue('StageID', '');
            $stagedResource->setTVValue('StageDate','');
            // my_debug('PageTitle: ' . $modx->resource->get('pagetitle'));
            $modx->resource->save();
            if ($includeTvs) {
                $tvds = $stagedResource->getMany('TemplateVarResources');
                // my_debug('TVDS: ' . count($tvds) );
                $resourceId = $modx->resource->get('id');

                foreach ($tvds as $oldTemplateVarResource) {
                    /** @var $tvr modTemplateVarResource */
                    /** @var $oldTemplateVarResource modTemplateVarResource */
                    /** @var $newTemplateVarResource modTemplateVarResource */
                    $value = $oldTemplateVarResource->get('value');
                    // my_debug('ID: ' . $oldTemplateVarResource->get('tmplvarid') . ' -- Value: ' . $value);

                    $c = array(
                        'contentid' => $resourceId,
                        'tmplvarid' => $oldTemplateVarResource->get('tmplvarid'),
                    );
                    /* get Tvr for current resource and set it from staged resource */
                    $tvr = $modx->getObject('modTemplateVarResource', $c);
                    if ($tvr) {
                        $tvr->set('value', $value);
                        $tvr->save();
                    } else {
                        // my_debug('Failed to get TVR');
                    }
                }
            }
            $stagedResource->remove();

        }
        break;

    case 'OnDocFormSave':
        /* @var $oldTv modTemplateVar */
        $stageId = $resource->getTVValue('StageID');
        $stageFolder = $modx->getOption('stagecoach_resource_id', null, 0);
        $archiveFolder = $modx->getOption('stagecoach_archive_id', null, 0);

        /* Don't execute on staged or archived Resources */
        $thisParent = $modx->resource->get('parent');
        if ($thisParent && ( ($thisParent == $stageFolder) || ($thisParent == $archiveFolder))) {
            return '';
        }

        $date = $resource->getTVValue('StageDate');
        if (empty($date)) {
            return '';
        }
        $pt = $resource->get('pagetitle') . '-' . $date;

        if (!empty($stageId)) { /* user is just updating the date */
            $res = $modx->getObject('modResource', $stageId);
            if ($res) { /* update pagetitle to new date */
                $res->set('pagetitle',$pt);
                $res->save();
            }
            return '';
        }


        $res = $modx->getObject('modResource', array('pagetitle' => $pt));
        if ($res) {
            return '';
        }
        $params = array(
            'newName' => $pt,
            'publishMode' => 'unpublish',
            'parent' => $stageFolder,
        );

        $stagedResource = $resource->duplicate($params);
        $stagedResource->save();
        $stagedResource->setTVValue('stageID', '');
        $stagedResource->setTVValue('stageDate', '');
        $newId = $stagedResource->get('id');
        $resource->setTVValue('stageID', $newId);
        break;
}
