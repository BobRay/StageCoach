<?php
/**
 * Resolver for StageCoach extra
 *
 * Copyright 2012-2024 Bob Ray <https://bobsguides.com>
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
 * @package stagecoach
 * @subpackage build
 */

/* @var $object xPDOObject */
/* @var $modx modX */

/* @var array $options */

/** @var modTransportPackage $transport */
if ($transport) {
    $modx =& $transport->xpdo;
} else {
    $modx =& $object->xpdo;
}

$isMODX3Plus = $modx->getVersionData()['version'] >= 3;
if ($isMODX3Plus) {
    $classPrefix = 'MODX\Revolution\\';
} else {
    $classPrefix = '';
}

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_UPGRADE:
        /* remove connection to OnWebPageInit */
        $plugin = $modx->getObject($classPrefix . 'modPlugin', array('name' => 'StageCoach'));
        if ($plugin) {
            $pluginId = $plugin->get('id');
            $pluginEvent = $modx->getObject($classPrefix . 'modPluginEvent', array(
                   'pluginid' => $pluginId,
                   'event' => 'OnLoadWebDocument',
              ));
            if ($pluginEvent) {
                $pluginEvent->remove();
            }
        }
        break;

    case xPDOTransport::ACTION_INSTALL:
        $setting = $modx->getObject($classPrefix . 'modSystemSetting', array('key' => 'stagecoach_resource_id'));
        $res = $modx->getObject($classPrefix . 'modResource', array('alias' => 'staged-resources'));
        if ($res && $setting) {
            $setting->set('value', $res->get('id'));
            $setting->save();
        } else {
            $modx->log(modX::LOG_LEVEL_ERROR, 'Failed to set stagecoach_resource_id System Setting');
        }

        $setting = $modx->getObject($classPrefix . 'modSystemSetting', array('key' => 'stagecoach_archive_id'));
        $res = $modx->getObject($classPrefix . 'modResource', array('alias' => 'stagecoach-archive'));
        if ($res && $setting) {
            $setting->set('value', $res->get('id'));
            $setting->save();
        } else {
            $modx->log(modX::LOG_LEVEL_ERROR, 'Failed to set stagecoach_archive_id System Setting');
        }

    $setting = $modx->getObject($classPrefix . 'modSystemSetting', array('key' => 'stagecoach_stage_date_tv_id'));
    $tv = $modx->getObject($classPrefix . 'modTemplateVar', array('name' => 'StageDate'));
    if ($tv && $setting) {
        $setting->set('value', $tv->get('id'));
        $setting->save();
     } else {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Failed to set stagecoach_stage_date_tv_id System Setting');
    }

    $setting = $modx->getObject($classPrefix . 'modSystemSetting', array('key' => 'stagecoach_staged_resource_tv_id'));
    $tv = $modx->getObject($classPrefix . 'modTemplateVar', array('name' => 'StageID'));
    if ($tv && $setting) {
        $setting->set('value', $tv->get('id'));
        $setting->save();
    } else {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Failed to set stagecoach_staged_resource_tv_id System Setting');
    }
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        break;
}

return true;
