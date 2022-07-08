<?php
// This file is part of the Sloodle project (www.sloodle.org)

/**
* This file defines a Sloodle Object assign module for Sloodle.
*
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @copyright Copyright (c) 2016 Fumi.Hax {@link http://www.nsl.tuis.ac.jp}
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributor Peter R. Bloomfield
*/

/** The Sloodle module base. */
require_once(SLOODLE_LIBROOT.'/modules/module_base.php');
/** General Sloodle functions. */
require_once(SLOODLE_LIBROOT.'/general.php');

// Make sure the Sloodle Object assign type is installed
$incfile = $CFG->dirroot.'/mod/assign/locallib.php';
if (!file_exists($incfile)) return; // Leaves this script, but does not terminate execution

/** The Sloodle Object assign type. */
require_once($incfile);


/**
* The Sloodle Object assign module class.
* @package sloodle
*/
class SloodleModuleSloodleObj extends SloodleModule
{
    // DATA //

    /**
    * Internal for Moodle only - course module instance.
    * Corresponds to one record from the Moodle 'course_modules' table.
    * @var object
    * @access private
    */
    var $cm = null;

    /**
    * Internal only - Moodle assign module instance database object.
    * Corresponds to one record from the Moodle 'assign' table.
    * @var object
    * @access private
    */
    var $moodle_assign = null;

    /**
    * Internal only - the Moodle assign_submission structure.
    * Corresponds to one record from the Moodle 'assign_submission' table.
    * @var object
    * @access private
    */
    var $assign_submission = null;

    /**
    * Internal only - the Moodle sloodleobj structure.
    * Corresponds to one record from the Moodle 'assignsubmission_sloodleobj' table.
    * @var object
    * @access private
    */
    var $assign_sloodleobj = null;
  
    //
    var $max_objects = 1;

    var $user_flags = null;


    // FUNCTIONS //

    /**
    * Constructor
    */
    //function SloodleModuleSloodleObj(&$_session)
    function __construct(&$_session)
    {
        parent::__construct($_session);
    }
    

    /**
    * Loads data from the database.
    * Note: even if the function fails, it may still have overwritten some or all existing data in the object.
    * @param mixed $id The site-wide unique identifier for all modules. Type depends on VLE. On Moodle, it is an integer course module identifier ('id' field of 'course_modules' table)
    * @return bool True if successful, or false otherwise
    */
    function load($id)
    {
        global $DB;

        // Make sure the ID is valid
        $id = (int)$id;
        if ($id <= 0) return false;
        
        // Fetch the course module data
        if (!($this->cm = get_coursemodule_from_id('assign', $id))) {
            sloodle_debug("Failed to load course module instance #$id.<br />");
            return false;
        }

        // Make sure the module is visible
        if ($this->cm->visible==0) {
            sloodle_debug("Error: course module instance #$id not visible.<br />");
            return false;
        }
        
        // Load from the primary table: assign instance
        if (!($this->moodle_assign = sloodle_get_record('assign', 'id', $this->cm->instance))) {
            sloodle_debug("Failed to load assign with instance ID #{$cm->instance}.<br />");
            return false;
        }
        
        // Make sure this assign is of the correct type
        $cond = array('plugin'=>'sloodleobj', 'subtype'=>'assignsubmission', 'name'=>'enabled', 'assignment'=>$this->cm->instance);
        $config = $DB->get_record('assign_plugin_config', $cond);
        if (!$config || $config->value==0) {
            sloodle_debug("ERROR assign \"{$this->moodle_assign->name}\" is not of type 'sloodleobj'.");
            return false;
        }
        
        $cond['name'] = 'maxobjectsubmissions';
        $config = $DB->get_record('assign_plugin_config', $cond);
        if ($config) $this->max_objects = $config->value;

        return true;
    }
    

    /*
    check order:
        user_can_submit()       set $this->user_flags
        user_has_submitted()    set $this->assign_submission, $this->assign_sloodleobj
        resubmit_allowed()
        is_maximum_objects()
        is_too_early()
        is_too_late()
    */

    /**
    * Checks if the specified user is permitted to submit to this assign.
    * (Note: this only checks permissions, and no other settings, such as submission times).
    * @param SloodleUser $user The user to be checked
    * @return bool True if the user has permission to submit to this assign, or false otherwise.
    */
    function user_can_submit($user)
    {
        // Make sure a user is loaded
        if (!$user->is_user_loaded()) return false;
        // Login the current user, and check capabilities
        if (!$user->login()) return false;

        //return has_capability('mod/assign:submit', get_context_instance(CONTEXT_MODULE, $this->cm->id));
        if (!has_capability('mod/assign:submit', context_module::instance($this->cm->id))) return false;

        $this->user_flags = sloodle_get_record('assign_user_flags', 'assignment', $this->moodle_assign->id, 'userid', $user->get_user_id());
                 if ($this->user_flags && $this->user_flags->locked) return false;

        return true;
    }
    

    /**
    * Checks if the specified user is permitted to view submissions to this assign.
    * @param SloodleUser $user The user to be checked
    * @return bool True if the user has permission to view the submissions, or false otherwise.
    */
    function user_can_view($user)
    {
        // Make sure a user is loaded
        if (!$user->is_user_loaded()) return false;
        // Login the current user, and check capabilities
        if (!$user->login()) return false;

        return has_capability('mod/assign:view', context_module::instance($this->cm->id));
    }
    

    /**
    * Checks if the specified user has submitted to this assign already.
    * @param SloodleUser $user The user to be checked
    * @return bool True if the user has previously attempted this assign, or false otherwise
    */
    function user_has_submitted($user)
    {
        if (!$user->is_user_loaded()) return false;

        $assignid = $this->moodle_assign->id;
        $this->assign_submission = sloodle_get_record('assign_submission', 'assignment', $assignid, 'userid', $user->get_user_id());

        if ($this->assign_submission) {
            $submissionid = $this->assign_submission->id;
            $this->assign_sloodleobj = sloodle_get_record('assignsubmission_sloodleobj', 'assignment', $assignid, 'submission', $submissionid);
            $numobjects = $this->assign_sloodleobj->numobjects;
            //
            if ($this->assign_submission->status=='submitted' && $numobjects>0) return true;
        }

        return false;
    }
    

    /**
    * Checks if re-submissions are permitted.
    * @return bool True if resubmissions are permitted, or false otherwise
    */
    function resubmit_allowed()
    {
        if ($this->max_objects==1) return false;
        return true;
    }
    

    /**
    * Checks number of submitted objects 
    * @return bool True if submitted objects are greater than or equal to the maximum value
    */
    function is_maximum_objects()
    {
        if ($this->assign_sloodleobj) {
            if ($this->max_objects<=$this->assign_sloodleobj->numobjects && $this->max_objects!=0) return true;
        }

        return false;
    }


    /**
    * Checks if an assign submitted at the specified time would be too early for submission.
    * @param int $timestamp A timestamp giving the time to check (if omitted, it defaults to the current timestamp)
    * @return bool True if assign would be too early, or false if it's OK.
    */
    function is_too_early($timestamp = null)
    {
        // If no 'available' time is set, then nothing is too early
        if (empty($this->moodle_assign->timeavailable) || $this->moodle_assign->timeavailable <= 0) return false;
        // Use the current timestamp if need be
        if ($timestamp == null) $timestamp = time();

        // Check the time
        return ($timestamp < $this->moodle_assign->timeavailable);
    }
    

    /**
    * Checks if an assign submitted at the specified time would be too late for submission.
    * @param int $timestamp A timestamp giving the time to check (if omitted, it defaults to the current timestamp)
    * @return int 1 if assign would be too late and cannot be accepted, 0 if it is OK, or -1 if it would be late but still accepted
    */
    function is_too_late($timestamp = null)
    {
        // If no 'due' time is set, then nothing is too early
        if (empty($this->moodle_assign->timedue) || $this->moodle_assign->timedue <= 0) return false;
        // Use the current timestamp if need be
        if ($timestamp == null) $timestamp = time();
        
        // Check the time
        if ($timestamp > $this->moodle_assign->timedue) {
            // It's late... check if late submissions are prevented
            if (empty($this->moodle_assign->preventlate)) {
                return -1;
            }
            else {
                return 1;
            }
        }
        // It's OK
        return 0;
    }
    

    /**
    * Add a new submission (or replace an existing one).
    * Ignores all submission checks, such as permissions and time.
    * @param SloodleUser $user The user making the submission
    * @param string $obj_name Name of the object being submitted
    * @param int $num_prims Number of prims in the object being submitted
    * @param string $primdrop_name Name of the PrimDrop being submitted to
    * @param string $primdrop_uuid UUID of the PrimDrop being submitted to
    * @param string $primdrop_region Region of the PrimDrop being submitted to
    * @param string $primdrop_pos Position vector (<x,y,z>) of the PrimDrop being submitted to
    * @return bool True if successful, or false otherwise
    */
    function submit($user, $obj_name, $num_prims, $primdrop_name, $primdrop_uuid, $primdrop_region, $primdrop_pos)
    {
        // Make sure the user is loaded
        if (!$user->is_user_loaded()) return false;
 
        $ret = false;

        if ($this->assign_submission==null) {
            $this->assign_submission = new stdClass();
            $this->assign_submission->assignment    = $this->moodle_assign->id;
            $this->assign_submission->userid        = $user->get_user_id();
            $this->assign_submission->timecreated   = time();
            $this->assign_submission->timemodified  = time();
            $this->assign_submission->status        = 'submitted';
            $this->assign_submission->groupid       = 0;
            $this->assign_submission->attemptnumber = 0;
            $this->assign_submission->latest        = 1;
            $ret = sloodle_insert_record('assign_submission', $this->assign_submission);
            $this->assign_submission->id = $ret;
        }
        else {
            $this->assign_submission->timemodified = time();
            $this->assign_submission->status       = 'submitted';
            $ret = sloodle_update_record('assign_submission', $this->assign_submission);
        }
        if (!$ret) return false;

        if ($this->assign_sloodleobj==null) {
            $this->assign_sloodleobj = new stdClass();
            $this->assign_sloodleobj->assignment   = $this->assign_submission->assignment;
            $this->assign_sloodleobj->submission   = $this->assign_submission->id;
            $this->assign_sloodleobj->numobjects   = 1;
            $this->assign_sloodleobj->data1        = "$obj_name|$num_prims";
            $this->assign_sloodleobj->data2        = "$primdrop_name|$primdrop_uuid|$primdrop_region|$primdrop_pos";
            $this->assign_sloodleobj->objectname   = $obj_name;
            $ret = sloodle_insert_record('assignsubmission_sloodleobj', $this->assign_sloodleobj);
            $this->assign_sloodleobj->id = $ret;
        }
        else {
            $this->assign_sloodleobj->numobjects   = $this->assign_sloodleobj->numobjects + 1;
            $this->assign_sloodleobj->data1        = "$obj_name|$num_prims";
            $this->assign_sloodleobj->data2        = "$primdrop_name|$primdrop_uuid|$primdrop_region|$primdrop_pos";
            $this->assign_sloodleobj->objectname   = $obj_name;
            $ret = sloodle_update_record('assignsubmission_sloodleobj', $this->assign_sloodleobj);
        }
        if (!$ret) return false;

        return true;
    }
    
    
    // ACCESSORS //

    /**
    * Gets the name of this module instance.
    * @return string The name of this controller
    */
    function get_name()
    {
        return $this->moodle_assign->name;
    }
    

    /**
    * Gets the intro description of this module instance, if available.
    * @return string The intro description of this controller
    */
    function get_intro()
    {
        return $this->moodle_assign->intro;
    }
    

    /**
    * Gets the identifier of the course this controller belongs to.
    * @return mixed Course identifier. Type depends on VLE. (In Moodle, it will be an integer).
    */
    function get_course_id()
    {
        return (int)$this->moodle_assign->course;
    }
    

    /**
    * Gets the time at which this instance was created, or 0 if unknown.
    * @return int Timestamp
    */
    function get_creation_time()
    {
        return 0;
    }
    

    /**
    * Gets the time at which this instance was last modified, or 0 if unknown.
    * @return int Timestamp
    */
    function get_modification_time()
    {
        return (int)$this->moodle_assign->timemodified;
    }
    
    
    /**
    * Gets the short type name of this instance.
    * @return string
    */
    static function get_type()
    {
        return 'sloodleobj';
    }


    /**
    * Gets the full type name of this instance, according to the current language pack, if available.
    * Note: should be overridden by sub-classes.
    * @return string Full type name if possible, or the short name otherwise.
    */
    static function get_type_full()
    {
        return get_string('typesloodleobj', 'assign');
    }

}

