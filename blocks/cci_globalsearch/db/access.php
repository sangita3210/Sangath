<?php

/**
 * *************************************************************************
 * *                       CCI - Course Search                          **
 * *************************************************************************
 * @package     block                                                   **
 * @subpackage  cci globalsearch                                        **
 * @name        CCI Global Search                                       **
 * @copyright   CCI                                                     **
 * @author      CCI                                                     **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later  **
 * *************************************************************************
 * ************************************************************************ */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'block/cci_globalsearch:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
        'block/cci_globalsearch:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
);
