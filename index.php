<?php
// This file is part of the Sloodle project (www.sloodle.org)

/**
* Index page for listing all instances of the Sloodle module.
* Used as an interface script by the Moodle framework.
*
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributor Peter R. Bloomfield
*
*/

/** Sloodle/Moodle configuration script. */
require_once('init.php');
/** Sloodle core library functionality */
require_once(SLOODLE_DIRROOT.'/lib.php');
require_once(SLOODLE_DIRROOT.'/lib/io.php');

// Fetch the course ID from request parameters
$id = optional_param('id', 0, PARAM_INT);

// Fetch the course data
$course = null;
if ($id) {
    $course = sloodle_get_record("course", "id", $id);
    if (! $course = sloodle_get_record("course", "id", $id)) {
        print_error("Course ID is incorrect");
    }
}
else {
    if (! $course = get_site()) {
        print_error("Could not find a top-level course!");
    }
}

//
$urlparams = array();
if ($id!=0) $urlparams['id'] = $id;
$PAGE->set_url('/mod/sloodle/index.php', $urlparams);

// Require that the user logs in
require_login($course, false);
//add_to_log($course->id, "sloodle", "view sloodle modules", "index.php?id=$course->id");
sloodle_add_to_log($course->id, 'module_viewed', 'index.php', array('id'=>$course->id), 'index: view sloodle modules');

// Fetch our string table data
$strsloodle = get_string('modulename', 'sloodle');
$strsloodles = get_string('modulenameplural', 'sloodle');
$strid = get_string('ID', 'sloodle');
$strname = get_string('name', 'sloodle');
$strdescription = get_string('description');
$strmoduletype = get_string('moduletype', 'sloodle');

// Fetch the full names of each module type
$sloodle_type_names = array();
foreach ($SLOODLE_TYPES as $ST) {        
    // Get the module type name
    $sloodle_type_names[$ST] = get_string("moduletype:$ST", 'sloodle');
}

// We're going to make one table for each module type
$sloodle_tables = array();

// Get all Sloodle modules for the current course
$sloodles = sloodle_get_records('sloodle', 'course', $course->id, 'name');
if (!$sloodles) $sloodles = array();
// Go through each module    
foreach ($sloodles as $s) {
    // Find out which course module instance this SLOODLE module belongs to
    $cm = get_coursemodule_from_instance('sloodle', $s->id);
    if ($cm === false) continue;
        
    // Prepare this line of data
    $line = array();
    $line[] = "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?id={$cm->id}\">$s->name</a>";
    $line[] = $s->intro;
    // Insert it into the appropriate table
    $sloodle_tables[$s->type]->data[] = $line;
}

// Add header information to each table
// (cannot use "foreach" on the $sloodle_tables array as PHP4 doesn't support alteration of the original array that way)
$table_types = array_keys($sloodle_tables);
foreach ($table_types as $k) {
    $sloodle_tables[$k]->head = array($strname, $strdescription);
    $sloodle_tables[$k]->align = array('left', 'left');
    $sloodle_tables[$k]->size = array('50%', '50%');
}

// Page header
if ($course->id != SITEID) {
    sloodle_print_header("{$course->shortname}: $strsloodles", $course->fullname,
                "<a href=\"../../course/view.php?_type=course&amp;id=$course->id\">$course->shortname</a> -> $strsloodles", "", "", true, "", false);
}
else {
    sloodle_print_header("$course->shortname: $strsloodles", $course->fullname, "$strsloodles", "", "", true, "", false);
}

//-----------------------------------------------------
// Quick links (top right of page)   

// Open the section
/*echo "<div style=\"text-align:right; font-size:80%;\">\n";

// Link to own avatar profile
echo "<a href=\"\" title=\"\">View my avatar details</a><br />\n";

// Course information
if (empty($sloodlecourse->autoreg)) {
    echo "This course does not allow auto-registration<br />";
}
else {
    echo "This course allows auto-registration<br />";
}

// Display the link for editing course settings
//$course_context = get_context_instance(CONTEXT_COURSE, $course->id);
$course_context = context_course::instance($course->id, IGNORE_MISSING);
if (has_capability('moodle/course:update', $course_context)) {
    echo "<a href=\"\" title=\"\">Edit Sloodle course settings</a><br />\n";
}

echo "</div>\n";*/

//-----------------------------------------------------

// Make sure we got some results
if (is_array($sloodle_tables) && count($sloodle_tables) > 0) {
    // Go through each Sloodle table
    foreach ($sloodle_tables as $type => $table) {
        // Output a heading for this type
        if (!array_key_exists($type, $sloodle_type_names)) $sloodle_type_names[$type] = get_string("moduletype:{$type}", 'sloodle');
        sloodle_print_heading($sloodle_type_names[$type],  'sloodle');
        // Display the table
        sloodle_print_table($table);
    }
}
else {
    sloodle_print_heading(get_string('noentries', 'sloodle'));
}

// Page footer
sloodle_print_footer($course);
