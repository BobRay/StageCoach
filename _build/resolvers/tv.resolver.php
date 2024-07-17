<?php
/**
* Resolver to connect TVs to templates for StageCoach extra
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
            if (!isset($objectFields[$field])) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[TV Resolver] Missing field: ' . $field);
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

$classPrefix = $modx->getVersionData()['version'] >= 3
        ? 'MODX\Revolution\\'
        : '';

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:

        $intersects = array (
            0 =>  array (
              'templateid' => 'default',
              'tmplvarid' => 'StageDate',
              'rank' => 1,
            ),
            1 =>  array (
              'templateid' => 'default',
              'tmplvarid' => 'StageDate',
              'rank' => 1,
            ),
            2 =>  array (
              'templateid' => 'default',
              'tmplvarid' => 'StageID',
              'rank' => 1,
            ),
            3 =>  array (
              'templateid' => 'default',
              'tmplvarid' => 'StageID',
              'rank' => 1,
            ),
        );

        if (is_array($intersects)) {
            foreach ($intersects as $k => $fields) {
                /* make sure we have all fields */
                if (!checkFields($modx, 'tmplvarid,templateid', $fields)) {
                    continue;
                }
                $tv = $modx->getObject($classPrefix . 'modTemplateVar', array('name' => $fields['tmplvarid']));
                if ($fields['templateid'] == 'default') {
                    $template = $modx->getObject($classPrefix . 'modTemplate', $modx->getOption('default_template'));
                } else {
                    $template = $modx->getObject($classPrefix . 'modTemplate', array('templatename' => $fields['templateid']));
                }
                if (!$tv || !$template) {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find Template and/or TV ' .
                        $fields['templateid'] . ' - ' . $fields['tmplvarid']);
                    continue;
                }
                $tvt = $modx->getObject($classPrefix . 'modTemplateVarTemplate', array('templateid' => $template->get('id'), 'tmplvarid' => $tv->get('id')));
                if (! $tvt) {
                    $tvt = $modx->newObject($classPrefix . 'modTemplateVarTemplate');
                }
                if ($tvt) {
                    $tvt->set('tmplvarid', $tv->get('id'));
                    $tvt->set('templateid', $template->get('id'));
                    if (isset($fields['rank'])) {
                        $tvt->set('rank', $fields['rank']);
                    } else {
                        $tvt->set('rank', 0);
                    }
                    if (!$tvt->save()) {
                        $modx->log(xPDO::LOG_LEVEL_ERROR, 'Unknown error creating templateVarTemplate for ' .
                            $fields['templateid'] . ' - ' . $fields['tmplvarid']);
                    }
                } else {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Unknown error creating templateVarTemplate for ' .
                        $fields['templateid'] . ' - ' . $fields['tmplvarid']);
                }


            }

        }
        break;

    case xPDOTransport::ACTION_UPGRADE:
    case xPDOTransport::ACTION_UNINSTALL:
        break;
}

return true;
