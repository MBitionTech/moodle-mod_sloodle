<?php

require_once($CFG->dirroot.'/mod/sloodle/init.php');

// // VERSION INFO // //

// Construct the version info
// Sloodle version
$str = sloodle_print_heading(get_string('sloodleversion','sloodle').': '.(string)SLOODLE_VERSION, 'center', 4, 'main', true);

// Release number
require($CFG->dirroot.'/mod/sloodle/version.php');
$releasenum = $plugin->version;

// Construct a help button to
$hlp = $OUTPUT->help_icon('help:versionnumbers', 'sloodle');

// Add the version info section
$settings->add(new admin_setting_heading('sloodle_version_header', "Version Info ".$hlp, $str));


// // GENERAL SETTINGS // //

// General settings section
$settings->add(new admin_setting_heading('sloodle_settings_header', "Sloodle Settings", ''));

// Get some localization strings
$stryes = get_string('yes');
$strno  = get_string('no');
    
// This selection box determines whether or not auto-registration is allowed on the site
$settings->add( new admin_setting_configselect(
                'sloodle_allow_autoreg',
                '',
                get_string('autoreg:allowforsite','sloodle').$OUTPUT->help_icon('help:autoreg','sloodle'), 0, array(0 => $strno, 1 => $stryes)));
    
// This selection box determines whether or not auto-enrolment is allowed on the site
$settings->add( new admin_setting_configselect(
                'sloodle_allow_autoenrol',
                '',
                get_string('autoenrol:allowforsite','sloodle').$OUTPUT->help_icon('help:autoenrol','sloodle'), 0, array(0 => $strno, 1 => $stryes)));

// This text box will let the user set a number of days after which active objects should expire
$settings->add( new admin_setting_configtext(
                'sloodle_active_object_lifetime',
                get_string('activeobjectlifetime', 'sloodle'),
                get_string('activeobjectlifetime:info', 'sloodle').$OUTPUT->help_icon('activeobjects','sloodle'), 7));
                
// This text box will let the user set a number of days after which user objects should expire
$settings->add( new admin_setting_configtext(
                'sloodle_user_object_lifetime',
                get_string('userobjectlifetime', 'sloodle'),
                get_string('userobjectlifetime:info', 'sloodle').$OUTPUT->help_icon('userobjects','sloodle'), 21));

//
$settings->add( new admin_setting_configcheckbox('sloodle_use_new_filemanager',
                get_string('use_new_filemanager', 'sloodle'),
                get_string('use_new_filemanager_desc', 'sloodle'), 1));

$options = array('none'=>'not use', 'helper'=>'use with Helper Scripts', 'modlos'=>'use with Modlos');
$settings->add(new admin_setting_configselect('sloodle_opensim_money',
                get_string('opensim_money_func', 'sloodle'),
                get_string('opensim_money_func_desc', 'sloodle'), 'none', $options));

$settings->add( new admin_setting_configtext('sloodle_helper_dir',
                get_string('helper_script_dir', 'sloodle'),
                get_string('helper_script_dir_desc', 'sloodle'), '/var/www/html/htdocs/helper_scripts'));


// Pull in the freemail config, if we're using it.
if ( (defined('SLOODLE_FREEMAIL_ACTIVATE') && SLOODLE_FREEMAIL_ACTIVATE) && ( (!defined('SLOODLE_FREEMAIL_HIDE_CONFIG') || !SLOODLE_FREEMAIL_HIDE_CONFIG)) ) {
    include($CFG->dirroot.'/mod/sloodle/freemail/settings.php');
}
