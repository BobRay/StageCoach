<?php
/**
* Resolver to connect plugins to system events for StageCoach extra
*
* Copyright 2012-2024 Bob Ray <https://bobsguides.com>
* Created on 03-05-2013
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
/* @var $pluginObj modPlugin */
/* @var $mpe modPluginEvent */
/* @var xPDOObject $object */
/* @var array $options */
/* @var $modx modX */
/* @var $pluginObj modPlugin */
/* @var $pluginEvent modPluginEvent */
/* @var $newEvents array */

if (!function_exists('checkFields')) {
    function checkFields($modx, $required, $objectFields) {
        $fields = explode(',', $required);
        foreach ($fields as $field) {
            if (!isset($objectFields[$field])) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[Plugin Resolver] Missing field: ' . $field);
                return false;
            }
        }
        return true;
    }
}


$newEvents = array (
            );

/** @var modTransportPackage $transport */
if ($transport) {
    $modx =& $transport->xpdo;
} else {
    $modx =& $object->xpdo;
}

$classPrefix = $modx->getVersionData()['version'] >= 3
        ? 'MODX\Revolution\\'
        : '';

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:

        foreach($newEvents as $k => $fields) {

            $event = $modx->getObject($classPrefix . 'modEvent', array('name' => $fields['name']));
            if (!$event) {
                $event = $modx->newObject($classPrefix . 'modEvent');
                if ($event) {
                    $event->fromArray($fields, "", true, true);
                    $event->save();
                }
            }
        }

        $intersects = array (
            0 =>  array (
              'pluginid' => 'StageCoach',
              'event' => 'OnDocFormRender',
              'priority' => '0',
              'propertyset' => '0',
            ),
            1 =>  array (
              'pluginid' => 'StageCoach',
              'event' => 'OnDocFormSave',
              'priority' => '0',
              'propertyset' => '0',
            ),
            2 =>  array (
              'pluginid' => 'StageCoach',
              'event' => 'OnWebPageInit',
              'priority' => '0',
              'propertyset' => '0',
            ),
        );

        if (is_array($intersects)) {
            foreach ($intersects as $k => $fields) {
                /* make sure we have all fields */
                if (!checkFields($modx, 'pluginid,event,priority,propertyset', $fields)) {
                    continue;
                }
                $event = $modx->getObject($classPrefix . 'modEvent', array('name' => $fields['event']));

                $plugin = $modx->getObject($classPrefix . 'modPlugin', array('name' => $fields['pluginid']));
                $propertySetObj = null;
                if (!empty($fields['propertyset'])) {
                    $propertySetObj = $modx->getObject($classPrefix . 'modPropertySet',
                        array('name' => $fields['propertyset']));
                }
                if (!$plugin || !$event) {
                    if (!$plugin) {
                        $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find Plugin  ' .
                            $fields['pluginid']);
                    }
                    if (!$event) {
                        $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find Event ' .
                            $fields['event']);
                    }
                    continue;
                }
                $pluginEvent = $modx->getObject($classPrefix . 'modPluginEvent', array('pluginid'=>$plugin->get('id'),'event' => $fields['event']) );

                if (!$pluginEvent) {
                    $pluginEvent = $modx->newObject($classPrefix . 'modPluginEvent');
                }
                if ($pluginEvent) {
                    $pluginEvent->set('event', $fields['event']);
                    $pluginEvent->set('pluginid', (integer) $plugin->get('id'));
                    $pluginEvent->set('priority', (integer) $fields['priority']);
                    if ($propertySetObj) {
                        $pluginEvent->set('propertyset', (integer) $propertySetObj->get('id'));
                    } else {
                        $pluginEvent->set('propertyset', 0);
                    }

                }
                if (! $pluginEvent->save()) {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Unknown error saving pluginEvent for ' .
                        $fields['plugin'] . ' - ' . $fields['event']);
                }
            }
        }
        break;

    case xPDOTransport::ACTION_UPGRADE:
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        foreach($newEvents as $k => $fields) {
            $event = $modx->getObject($classPrefix . 'modEvent', array('name' => $fields['name']));
            if ($event) {
                $event->remove();
            }
        }
        break;
}

return true;
