<?php
/**
* Resource resolver  for StageCoach extra.
* Sets template, parent, and (optionally) TV values
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
/* @var $modx modX */
/* @var $parentObj modResource */
/* @var $templateObj modTemplate */

/* @var array $options */

if (!function_exists('checkFields')) {
    function checkFields($modx, $required, $objectFields) {

        $fields = explode(',', $required);
        foreach ($fields as $field) {
            if (! isset($objectFields[$field])) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[Resource Resolver] Missing field: ' . $field);
                return false;
            }
        }
        return true;
    }
}

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
    case xPDOTransport::ACTION_INSTALL:

        $intersects = array (
                0 =>  array (
                  'pagetitle' => 'Staged Resources',
                  'parent' => 0,
                  'template' => 'default',
                ),
                1 =>  array (
                  'pagetitle' => 'StageCoach Archive',
                  'parent' => 0,
                  'template' => 'default',
                ),
            );

        if (is_array($intersects)) {
            foreach ($intersects as $k => $fields) {
                /* make sure we have all fields */
                if (! checkFields($modx, 'pagetitle,parent,template', $fields)) {
                    continue;
                }
                $resource = $modx->getObject($classPrefix . 'modResource',
                    array('pagetitle' => $fields['pagetitle']));
                if (! $resource) {
                    continue;
                }

                if ($fields['template'] == 'default') {
                    $resource->set('template', $modx->getOption('default_template'));
                } elseif (empty($fields['template'])) {
                    $resource->set('template', 0);
                } else {
                    $templateObj = $modx->getObject($classPrefix . 'modTemplate',
                        array('templatename' => $fields['template']));
                    if ($templateObj) {
                        $resource->set('template', $templateObj->get('id'));
                    } else {
                        $modx->log(modX::LOG_LEVEL_ERROR, '[Resource Resolver] Could not find template: ' . $fields['template']);
                    }
                }
                if (!empty($fields['parent'])) {
                    if ($fields['parent'] != 'default') {
                        $parentObj = $modx->getObject($classPrefix . 'modResource', array('pagetitle' => $fields['parent']));
                        if ($parentObj) {
                            $resource->set('parent', $parentObj->get('id'));
                        } else {
                            $modx->log(modX::LOG_LEVEL_ERROR, '[Resource Resolver] Could not find parent: ' . $fields['parent']);
                        }
                    }
                }

                if (isset($fields['tvValues'])) {
                    foreach($fields['tvValues'] as $tvName => $value) {
                        $resource->setTVValue($tvName, $value);
                    }

                }
                $resource->save();
            }

        }
        break;

    case xPDOTransport::ACTION_UPGRADE:
    case xPDOTransport::ACTION_UNINSTALL:
        break;
}


return true;
