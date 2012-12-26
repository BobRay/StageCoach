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

/*if (!function_exists("my_debug")) {
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
*/

switch($modx->event->name) {
    case 'OnLoadWebDocument':
        /* Update original Resource if it's time - delete staged Resource
         * and create archived resource if set */

        /* Get Stage Date TV value - return if empty */
        $date = $modx->resource->getTVValue('StageDate');
        if ( empty($date)) {
            return '';
        }

        /* convert date to a unix timestamp */
        $timeStamp = strtotime($date);

        if (time() >= $timeStamp) { /* Time to update Resource */
            $stageID = $modx->resource->getTVValue('StageID');
            $archive = $modx->getOption('stagecoach_archive_original', null, false);
            $includeTvs = $modx->getOption('stagecoach_include_tvs', null, false);
            if (!empty($stageID)) { /* get the staged Resource */
                $stagedResource = $modx->getObject('modResource', $stageID);
            } else { /* try with pagetitle */
                $pt = $modx->resource->get('pagetitle') . '-' . $date;
                $stagedResource = $modx->getObject('modResource', array('pagetitle' => $pt));
            }
            if (empty($stagedResource)) {
                $modx->log(MODX::LOG_LEVEL_ERROR, '[StageCoach] ' . $modx->lexicon('stagecoach_no_resource~~No staged Resource found with ID ' . $stageID) );

                return;
            }

            /* Archive original if option is set */
            if ($archive) {
                $archiveFolder = $modx->getOption('stagecoach_archive_id', null, 0);
                if ($archiveFolder) {
                    /* set params for duplicate - pagetitle will contain date/time */
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

            /* Don't set these fields */
            unset($fields['id'], $fields['pagetitle'], $fields['publishedon'], $fields['alias'], $fields['published'], $fields['hidemenu'], $fields['parent'], $fields['uri']);
            if ($modx->getOption('stagecoach_update_publish_date', null, false)) {
                $modx->resource->set('publishedon', time());
            }
            /* Update original resource */
            $modx->resource->fromArray($fields);

            /* erase TV values for both docs (if $includeTVs is set,
               the old ones will copy over) */
            $modx->resource->setTVValue('StageID', '');
            $modx->resource->setTVValue('StageDate', '');
            $stagedResource->setTVValue('StageID', '');
            $stagedResource->setTVValue('StageDate','');

            $modx->resource->save();

            /* Transfer TV values if option is set */
            if ($includeTvs) {
                $tvrs = $stagedResource->getMany('TemplateVarResources');
                $resourceId = $modx->resource->get('id');

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
                    }
                }
            }
            /* remove staged Resource */
            $stagedResource->remove();

        }
        break;

    case 'OnDocFormSave':
        /* Create staged Resource if Stage Date TV is set */
        /* @var $oldTv modTemplateVar */

        /* Don't execute for new resources */
        if ($mode != modSystemEvent::MODE_UPD) {
            return;
        }
        $stageId = $resource->getTVValue('StageID');
        $stageFolder = $modx->getOption('stagecoach_resource_id', null, 0);
        $archiveFolder = $modx->getOption('stagecoach_archive_id', null, 0);

        /* Don't execute on staged or archived Resources */
        $thisParent = $modx->resource->get('parent');
        if ($thisParent && ( ($thisParent == $stageFolder) || ($thisParent == $archiveFolder))) {
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
                $res->set('pagetitle',$pt);
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
        break;
}
