<?php
/**
 * StageCoach plugin for StageCoach extra
 *
 * Copyright 2012-2017 Bob Ray <https://bobsguides.com>
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
 * Plugin should be connected to OnWebPageInit, OnDocFormSave, and OnDocFormRender.
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

/* Don't execute outside of MODX */
if ((!isset($modx)) || (!$modx instanceof modX)) {
    return '';
}

$modx->getService('lexicon', 'modLexicon');
$modx->lexicon->load('stagecoach:default');

$stageCoachConfirmDelete = $modx->lexicon('stagecoach_delete_confirm');

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

if (!function_exists("checkTvr")) {
    function checkTvr($modx, $templateId, $tvId) {
        /** @var $modx modX */

      //  $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] Event: ' . $modx->event->name);
      //  $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] TvId: ' . $tvId);
      //  $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] templateId: ' . $templateId);

        $fields = array(
            'tmplvarid' => $tvId,
            'templateid' => $templateId,
        );
        $tvr = $modx->getObject('modTemplateVarTemplate', $fields);
        return $tvr? true : false;
    }
}


/** @var $resource modResource */

/* Bail if new resource is being created */
if (isset($mode) && ($mode === modSystemEvent::MODE_NEW)) {
    return '';
}

if (isset($resource) && $resource instanceof modResource && $resource->get('deleted')) {
    return '';
}

/* Make sure we have both TvIds */
$stageDateTvId = $modx->getOption('stagecoach_stage_date_tv_id');
if (empty($stageDateTvId)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] stagecoach_stage_date_tv_id  System Setting is empty');
    return '';
}
$stagedResourceTvId = $modx->getOption('stagecoach_staged_resource_tv_id');
if (empty($stagedResourceTvId)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] stagecoach_staged_resource_tv_id System Setting is empty');
    return '';
}

if ($modx->event->name === 'OnWebPageInit') {
    $doc = $modx->getObject('modResource', $modx->resourceIdentifier);
} else {
    $doc = $resource;
}

if ( (!$doc) || (! $doc instanceof modResource)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] Could not get Resource object');
    return '';
}
$templateId = $doc->get('template');
$resourceId = $doc->get('id');

if (empty ($resourceId)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] Could not get Resource ID in :' . $modx->event->name);
    return '';
}


/* Make sure TVs are connected to this template */

if ( (!checkTvr($modx, $templateId, $stagedResourceTvId)) || (!checkTvr($modx, $templateId, $stageDateTvId)) ) {
    // $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] TVs not Connected');
    return '';
}

switch ($modx->event->name) {

    case 'OnDocFormRender': {
        $managerUrl = $modx->getOption('manager_url');

        $button = '';
        /* Get TV ID  and Resource ID*/
        // $stagedResourceTvId = $modx->getOption('stagecoach_staged_resource_tv_id');
        // $stageDateTvId = $modx->getOption('stagecoach_stage_date_tv_id');
        $resourceId = $resource->get('id');



        $modx->regClientStartupHTMLBlock('
            <script type="text/javascript">
                    Ext.override(MODx.panel.Resource, {
                        originalSuccess: MODx.panel.Resource.prototype.success
                        ,success: function(o) {
                            this.originalSuccess(o);
        
                            var stageDateTv = document.getElementById("tv' . $stageDateTvId . '").value;
                            var stagedResourceTv = document.getElementById("tv' . $stagedResourceTvId . '").value;
                            
                            if (!!stageDateTv && (!stagedResourceTv || !stagedResourceTv.length) ) {
                                var url = location.href, i = url.indexOf("?") + 3;
                                MODx.loadPage(url.substr(i));
                                // console.log(" Original HREF: " + location.href);
                                // console.log("Converted HREF: " + url.substr(i));
                                // console.log("StageDateTV: " + stageDateTv);
                                // console.log("StagedResourceTV: " + stagedResourceTv);
                            }    
        
                        }
                    });
            </script>
    ');

        /* See if this resource has a staged resource */
        $c = array(
            'tmplvarid' => $stagedResourceTvId,  /* TV ID */
            'contentid' => $resourceId,  /* Resource ID */
        );

        $query = $modx->newQuery('modTemplateVarResource', $c);
        $query->select('value');
        $scId = $modx->getValue($query->prepare());

        if (!empty($scId)) { // this is an original with a staged resource with ID $scId
            /* Bail if staged resource is deleted or removed */
            $c = array('id' => $scId, 'deleted' => '0');

            /* If staged resource is gone, clear TVs, save resource, and bail out */
            if (! $modx->getCount('modResource', $c)) {
                $resource->setTVValue($stagedResourceTvId, '');
                $resource->setTVValue($stageDateTvId, '');
                $resource->save();
                return '';
            }

        } else {
            /* $scId is empty; it's not an original, see if this is a staged resource
               Look for an original that has this resource in its Staged Resource TV */

            $c = array(
                'tmplvarid' => $stagedResourceTvId,  /* TV ID */
                'value' => $resourceId,  /* Resource ID */
            );

            $query = $modx->newQuery('modTemplateVarResource', $c);
            $query->select('contentid');
            $liveId = $modx->getValue($query->prepare());

            if ($liveId) {
                // This is a staged resource - $liveId is the ID of the original
            }
        }

        if (empty($scId) && empty($liveId)) {
            // No connections - bail out
            return '';
        }
        $deleteDraftButton = <<<DELETDRAFTBUTTON
        
        
        var deleteDraftButton = buttonRow.insertCell(0);
        deleteDraftButton.innerHTML = '\
            <span id="stagecoach_delete_draft_button" class="x-btn x-btn-small stagecoach-link">\
                <button onclick="stagecoachDeleteDraft({$scId});">Delete Draft</button>\
            <span>';
DELETDRAFTBUTTON;


        $deleteDraftFunction = <<<FNDELETEDRAFT
        function stagecoachDeleteDraft(id) {
             //console.log("ID = " + id);
            MODx.msg.confirm({
        text: '$stageCoachConfirmDelete'
        , url: MODx.config.connector_url
        , params: {
            action: 'resource/delete'
            , id: id
        }
        , listeners: {
            success: {
                fn: function (r) {
                    if (r.object.deletedCount > 0) { // successfully deleted staged resource
                        /* Enable empty trash icon */
                        var trashcan = Ext.getCmp('emptifier');
                        if (trashcan !== 0[0]) {
                            trashcan.enable();
                        }

                        /* Add overstrike in tree (might need this in the future)
                             var rTree = Ext.getCmp('modx-resource-tree');
                             var nd = rTree.getNodeById('web_' + id);
                             if (nd) {
                             nd.getUI().addClass('deleted');
                             }
                         */


                        document.getElementById('tv{$stagedResourceTvId}').value = '';
                        MODx.fireResourceFormChange();
                        document.getElementById('tv{$stageDateTvId}').value = '';
                        MODx.fireResourceFormChange();

                        document.getElementById("tv{$stageDateTvId}-tr").getElementsByTagName("td")[0].getElementsByTagName("input")[0].value = "";
                        document.getElementById("tv{$stageDateTvId}-tr").getElementsByTagName("td")[1].getElementsByTagName("input")[0].value = "";

                        document.getElementById('modx-abtn-save').click();

                        document.getElementById('stagecoach_delete_draft_button').style.display = 'none';
                        document.getElementById('stagecoach_edit_draft_button').style.display = 'none';

                        /* Reload page (no longer used) */
                            // var url = location.href, i = url.indexOf("?") + 3;
                            // MODx.loadPage(url.substr(i));
                    }
                }, scope: this
            }
        }
    });
    

}

FNDELETEDRAFT;

$editDraftButton = <<<EDITDRAFTBUTTON

        var editDraftButton = buttonRow.insertCell(0);
        editDraftButton.innerHTML = '\
            <span id="stagecoach_edit_draft_button" class="x-btn x-btn-small stagecoach-link">\
                <button onclick="window.location.replace(\\'{$managerUrl}?a=resource/update&id={$scId}\\')">Edit Draft</button>\
            </span>';
EDITDRAFTBUTTON;

        $editOriginalButton = <<<EDITORIGINALBUTTON
        
        var editOriginalButton = buttonRow.insertCell(0);
        editOriginalButton.innerHTML = '\
            <span id="stagecoach_edit_original_button" class="x-btn x-btn-small stagecoach-link">\
                <button onclick="window.location.replace(\\'{$managerUrl}?a=resource/update&id={$liveId}\\')">Edit Original</button>\
            </span>';
EDITORIGINALBUTTON;

        $jScript = <<< STAGECOACHJS
<script type="text/javascript">
Ext.onReady(function () {
    // var siteUrl = "$siteUrl";
    // var scId = $scId;

    var buttonDiv = document.getElementById('modx-action-buttons');
    var buttonRows = buttonDiv.getElementsByClassName("x-toolbar-left-row");
    var buttonRow = buttonRows[0];

    if (buttonRow) {
        /* Buttons */
    }
    });

    /* DeleteDraftFunction */
</script>
STAGECOACHJS;

        if (!empty($scId)) {
            $jScript = str_replace('/* Buttons */', $editDraftButton . $deleteDraftButton, $jScript);
            $jScript = str_replace('/* DeleteDraftFunction */', $deleteDraftFunction, $jScript);
        } else {
            $jScript = str_replace('/* Buttons */', $editOriginalButton, $jScript);
        }

        $modx->regClientStartupScript($jScript);


        break;
    }

    case 'OnWebPageInit':


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
            return '';
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
                    '[StageCoach] No Staged Resource templateVarResource');
                return '';
            }
            $stageId = $tvr->get('value');
            if (empty($stageId)) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[StageCoach] Staged Resource ID TV is empty');
            } else {
                if ($doDebug) {
                    my_debug('StageID TV value: ' . $stageId);
                }
            }
            $stagedResource = $modx->getObject('modResource', $stageId);
            if (!$stagedResource) {
                $modx->log(modX::LOG_LEVEL_ERROR,
                    '[StageCoach] Could not find Staged Resource');
                return '';
            } elseif ($stagedResource->get('deleted')) {
                return '';
            }

            $originalResource = $modx->getObject('modResource', $resourceId);
            if (!$originalResource) {
                $modx->log(modX::LOG_LEVEL_ERROR,
                    '[StageCoach] Could not find Original Resource');
                    return '';
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
                my_debug('toArray done');
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

            $cKey = $originalResource->get('context_key');
            $modx->cacheManager->refresh(
                array(
                    'db' => array(),
                    'auto_publish' => array('contexts' => array($cKey)),
                    'context_settings' => array('contexts' => array($cKey)),
                    'resource' => array('contexts' => array($cKey)),
                )
            );

            /* See if we need to transfer TV values from the staged resource */
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
            /* Remove staged Resource if original was saved successfully */
            if ($success) {
                if (!$stagedResource->remove()) {
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
        /* @var $resource modResource */

        /* Don't execute for new resources */
        if ($mode != modSystemEvent::MODE_UPD) {
            return '';
        }
        $stageId = $resource->getTVValue('StageID');
        $key = $resource->get('context_key');
        /* Code from Mat Dave Jones to allow
           context-specific staging */

        /* Check if Context Setting exists */
        $stageFolder = $modx->getObject('modContextSetting', array('context_key' => $key, 'key' => 'stagecoach_resource_id'));
        /* If so use that otherwise use system setting */
        $stageFolder = (empty($stageFolder)) ? $modx->getOption('stagecoach_resource_id', null, 0) : $stageFolder->get('value');

        /* check if Context Setting exists */
        $archiveFolder = $modx->getObject('modContextSetting', array('context_key' => $key, 'key' => 'stagecoach_archive_id'));
        /* if so use that otherwise use system setting */
        $archiveFolder = (empty($archiveFolder)) ? $modx->getOption('stagecoach_archive_id', null, 0) : $archiveFolder->get('value');
        /* ************ */

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

return '';