<?php

////////////////////////////////////////////////////////////////////////////////
//  Code fragment to define the module version etc.
//  This fragment is called by /admin/index.php
////////////////////////////////////////////////////////////////////////////////

defined('MOODLE_INTERNAL') || die();
if (!isset($plugin)) $plugin = new stdClass();

$plugin->requires  = 2013111800;        // Moodle 2.6
$plugin->component = 'mod_sloodle';     // Full name of the module (used for diagnostics)
$plugin->cron      = 60;
$plugin->maturity  = MATURITY_STABLE;

$plugin->release   = '2.2.0';           // JOG Branch

$plugin->version   = 2016100200;        // The current module version (Date: YYYYMMDDXX)

