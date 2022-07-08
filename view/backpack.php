<?php
// This file is part of the Sloodle project (www.sloodle.org)
/**
* Defines a class to render a view of SLOODLE course information.
* Class is inherited from the base view class.
*
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributor Edmund Edgar
* @contributor Paul Preibisch
*
*/ 

/** The base view class */
require_once(SLOODLE_DIRROOT.'/view/base/base_view.php');
/** SLOODLE logs data structure */
require_once(SLOODLE_LIBROOT.'/course.php');
require_once(SLOODLE_LIBROOT.'/currency.php');    

// Javascript for checking and unchecking checkboxes
sloodle_require_js($CFG->wwwroot . '/mod/sloodle/lib/jquery/jquery-1.3.2.min.js');
sloodle_require_js($CFG->wwwroot . '/mod/sloodle/lib/js/backpack.js');

/**
* Class for rendering a view of SLOODLE course information.
* @package sloodle
*/
class sloodle_view_backpack extends sloodle_base_view
{
    /**
    * The Moodle course object, retrieved directly from database.
    * @var object
    * @access private
    */
    var $course = 0;

    var $can_edit = false;

    /**
    * SLOODLE course object, retrieved directly from database.
    * @var object
    * @access private
    */
    var $sloodle_course = null;

    //
    var $opensim_helper_file = '';


    /**
    * Constructor.
    */
    //function sloodle_view_backpack()
    function __construct()
    {
    }


    /**
    * Check the request parameters to see which course was specified.
    */
    function process_request()
    {
        global $USER, $CFG;

        $id = required_param('id', PARAM_INT);
        //check if valid course
        if (!$this->course = sloodle_get_record('course', 'id', $id)) print_error('Could not find course.');
        $this->sloodle_course = new SloodleCourse();
        if (!$this->sloodle_course->load($this->course)) print_error(get_string('failedcourseload', 'sloodle'));

        // for OpenSim DTL/NSL Money Server
        if ($CFG->sloodle_opensim_money=='helper') {
            if (!empty($CFG->sloodle_helper_dir)) {
                if (file_exists($CFG->sloodle_helper_dir.'/helper/helpers.php')) {
                    $this->opensim_helper_file = $CFG->sloodle_helper_dir.'/helper/helpers.php';
                }
            }
        }
        else if ($CFG->sloodle_opensim_money=='modlos') {
            if (!empty($CFG->modlos_use_currency_server)) {
                if (file_exists(SLOODLE_DIRROOT.'/../../blocks/modlos/helper/helpers.php')) {
                    $this->opensim_helper_file = SLOODLE_DIRROOT.'/../../blocks/modlos/helper/helpers.php';
                }
            }
        }
    }


    function process_form()
    {
        global $CFG, $COURSE;

        $prefix = $CFG->prefix;
        if ($this->course) $courseid = $this->course->id;
        else               $courseid = $COURSE->id;

        $id = required_param('id', PARAM_INT);
        $isItemAdd = optional_param('isItemAdd', 0, PARAM_INT);
        $userIds   = optional_param_array('userIds', array(), PARAM_INT);

        //itemAdd form has been submitted
        if ($isItemAdd) {
            if (!$this->can_edit) {
                print_error("Permission denied");
            }
            $controllerid = required_param('controllerid', PARAM_INT);

            //fetch all currencies
            $all_currencies = SloodleCurrency::FetchIDNameHash();

            //create controller so we can fetch active round
            $controller = new SloodleController();
            if(!$controller->load_by_course_module_id($controllerid)) {
                print_error('Could not load controller for '.$controllerid);
            }
            $roundid = $controller->get_active_roundid(true);

            // go through each currency and see if it has been set, if it has, we have to update each user who
            // has been checked
            foreach($all_currencies as $currencyid => $currencyname) {
                // check if a currency update is necessary for this currency
                // build the currencyname field  for this currency
                $fieldname  = 'currency_'.$currencyid;
                $fieldvalue = optional_param($fieldname, 0, PARAM_INT);
                if ($fieldvalue==0) continue;

                foreach ($userIds as $u) {
                    // go through each user which was checked and give them the selected currency and amount
                    // create backpack item
                    $backpack_item = new stdClass();
                    $backpack_item->currencyid = intval($currencyid);
                    $backpack_item->userid = intval($u);
                    $backpack_item->amount = intval($fieldvalue);
                    $backpack_item->timeawarded = time();
                    $backpack_item->roundid = $roundid;
                    $backpack_item->description = "moodle add by ". $USER->username;
                    //add it to the users backpack                    
                    sloodle_insert_record('sloodle_award_points', $backpack_item);
                }         
            } 

            // to Grade
            foreach($all_currencies as $currencyid => $currencyname) {
                //
                $fieldname  = 'toGrade_'.$currencyid;
                $fieldvalue = optional_param($fieldname, '', PARAM_TEXT);
                if ($fieldvalue=='') continue;
                //
                $award = new stdClass();
                $award->id = $id;				// same $couseid
                $award->course = $courseid;
                $award->itemname   = $currencyname;
                $award->itemnumber = intval($currencyid);
                $award->grademax = 0;
                $award->grademin = 0;

                $grades = sloodle_get_user_grades($currencyid, $courseid);
                foreach($grades as $grade) {
                    $award->grademax = max($award->grademax, $grade->rawgrade);
                    $award->grademin = min($award->grademin, $grade->rawgrade);
                }

                $sqlstr = "SELECT * FROM {$prefix}grade_items WHERE courseid=? AND itemtype=? AND itemmodule=? AND iteminstance=? AND itemnumber=?";
                $params = array($courseid, 'mod', 'sloodle', $courseid, $currencyid);
                $grade_items = sloodle_get_records_sql_params($sqlstr, $params);
                if (!$grade_items or count($grade_items)==0) {
                    sloodle_grade_item_update($award);         // create
                }
                sloodle_grade_item_update($award, $grades);    // update
            }

            // to Money of OpenSim
            if ($this->opensim_helper_file!='') {
                require_once($this->opensim_helper_file); 
                //
                foreach($all_currencies as $currencyid => $currencyname) {
                    //
                    $fieldname  = 'toMoney_'.$currencyid;
                    $fieldvalue = optional_param($fieldname, '', PARAM_TEXT);
                    if ($fieldvalue=='') continue;
                    //
                    $fieldname  = 'rateMoney_'.$currencyid;
                    $rate = floatval(optional_param($fieldname, '1.0', PARAM_FLOAT));
                    $tomoney_grades = sloodle_get_user_grades($currencyid, $courseid, 0, 0);	// not transfered money
                    //
                    foreach($tomoney_grades as $grade) {
                        $amount = intval(floatval($grade->rawgrade)*$rate);
                        if ($amount>0) {
                            $avatar = sloodle_get_record('sloodle_users', 'userid', $grade->userid);
                            if ($avatar) {
                                $ret = send_money($avatar->uuid, $amount, 901);     // 901: AwardPoints
                                if ($ret) {
                                    $sqlstr = "SELECT p.* FROM {$prefix}sloodle_award_points AS p INNER JOIN {$prefix}sloodle_award_rounds AS r ON p.roundid=r.id ".
                                              "WHERE p.userid=? AND p.currencyid=? AND r.courseid=? AND p.tomoney='0'";
                                    $params = array($grade->userid, $currencyid, $courseid);
                                    $points = sloodle_get_records_sql_params($sqlstr, $params);
                                    foreach($points as $point) {
                                        $point->tomoney = 1;
                                        sloodle_update_record('sloodle_award_points', $point);
                                    }
                                }
                            }
                        }
                    }
                }
            }

        }
    }


    /**
    * Check that the user is logged-in and has permission to alter course settings.
    */
    function check_permission()
    {
        // Ensure the user logs in
        require_login($this->course->id);
        if (isguestuser()) print_error(get_string('noguestaccess', 'sloodle'));
        //add_to_log($this->course->id, 'course', 'view sloodle data', '', "{$this->course->id}");
        sloodle_add_to_log($this->course->id, 'module_viewed', 'view.php', array('_type'=>'backpack', 'id'=>$this->course->id), 'backpack: view sloodle data');
        // Ensure the user is allowed to update information on this course
        //$this->course_context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        $this->course_context = context_course::instance($this->course->id, IGNORE_MISSING);
        if (has_capability('moodle/course:update', $this->course_context)) $this->can_edit = true;
    }


    /**
    * Print the course settings page header.
    */
    function sloodle_print_header()
    {
        global $CFG;

        //print breadcrumbs
        $navigation = "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?_type=backpack&id={$this->course->id}\">";
        $navigation .= get_string('backpack:view', 'sloodle');
        $navigation .= "</a>";
        //print the header
        sloodle_print_header_simple(get_string('backpack','sloodle'), '&nbsp;', $navigation, "", "", true, '', false);
    }


    /**
    * Render the view of the module or feature.
    * This MUST be overridden to provide functionality.
    */
    function render()
    {                                      
        global $CFG, $COURSE, $USER;

        $view = optional_param('view', '', PARAM_TEXT);
        $controllerid = optional_param('controllerid', 0, PARAM_INT);

        $courseid = $this->course->id;
        $controllers = array();
        $prefix = $CFG->prefix;

        if ($controllerid==0) {
            $controllers = sloodle_get_records_sql_params("select * from {$prefix}sloodle where type=? AND course=?", array(SLOODLE_TYPE_CTRL, $courseid));
            if (!$controllers || count($controllers)==0) {
                print_error(get_string('objectauthnocontrollers', 'sloodle'));
                exit();
            }
            $cm = get_coursemodule_from_instance('sloodle', current($controllers)->id);
            $controllerid = $cm->id;
        }

        // Setup our list of tabs
        // We will always have a view option
        $action = optional_param('action', "", PARAM_TEXT);                 
        $context = context_course::instance($this->course->id, IGNORE_MISSING);
        $contextid = $context->id;

        echo '<br />';

        //print titles
        sloodle_print_box_start('generalbox boxaligncenter center  boxheightnarrow leftpara');                  

        echo '<div style="position:relative ">';
        echo '<span style="position:relative;font-size:36px;font-weight:bold;">';

        //print return to backpack icon
        echo '<img align="center" src="'.SLOODLE_WWWROOT.'/lib/media/backpack64.png" width="48"/>';

        //print return to backpack title text
        echo s(get_string('backpacks:backpacks', 'sloodle'));
        echo '</span>';
        echo '<span style="float:right;">';
        echo '<a  style="text-decoration:none" href="'.$CFG->wwwroot.'/mod/sloodle/view.php?_type=currency&id='.$COURSE->id.'">';
        echo s(get_string('currency:viewcurrencies', 'sloodle')).'<br />';

        //print return to currencies icon
        echo '<img src="'.SLOODLE_WWWROOT.'/lib/media/returntocurrencies.png"/></a>';
        echo '</span>';
        echo '</div>';
        echo '<br />';
        echo '<span style="position:relative;float:right;">';
        echo '</span>';

        //get all currency names
        $all_currencies = SloodleCurrency::FetchIDNameHash();
        $active_currency_ids = array();

        //build scoresql 
        $scoresql = "select max(p.id) as id, p.userid as userid, p.currencyid as currencyid, sum(amount) as balance
                     from {$prefix}sloodle_award_points p inner join {$prefix}sloodle_award_rounds ro on ro.id=p.roundid 
                     where ro.courseid=? group by p.userid, p.currencyid order by balance desc;";
                     //where ro.courseid=? and ro.controllerid=? group by p.userid, p.currencyid order by balance desc;";
        $scores = sloodle_get_records_sql_params($scoresql, array($courseid));

        //build usersql
        $usersql  = "select max(u.id) as userid, u.firstname as firstname, u.lastname as lastname, su.avname as avname 
                     from {$prefix}user u inner join {$prefix}role_assignments ra on u.id=ra.userid left outer join 
                     {$prefix}sloodle_users su on u.id=su.userid where ra.contextid=? group by u.id order by avname asc;";
        $students = sloodle_get_records_sql_params($usersql, array($contextid));

        //create an array by userid
        $students_by_userid = array();
        foreach($students as $student) {
            $students_by_userid[ $student->userid ] = $student;
        }

        // students with scores, in score order
        $student_scores_by_currency_id = array();

        //creating a two dimensional array keyed by user id, then by currency for our display table
        foreach($scores as $score) {
            $userid = $score->userid;
            $currencyid = $score->currencyid;
            $active_currency_ids[ $currencyid ] = true;

            // if student is deleted from course but their score is still there, dont display their score
            if (!isset($students_by_userid[ $userid ])) { 
                continue;
            }

            //makes sure every student has an array entry 
            if (!isset($student_scores_by_currency_id[$userid])) {
                $student_scores_by_currency_id[$userid] = array();
            }

            //put the students balance in the currency into the array
            $student_scores_by_currency_id[$userid][$currencyid] = $score->balance;
        }

        // students without scores to the end of the array, in scored order
        foreach($students_by_userid as $userid => $student) {
            if (!isset($student_scores_by_currency_id[$userid] )) {
                $student_scores_by_currency_id[$userid] = array();
            }
        }

        //
        $tomoney_grades = array();
        if ($this->opensim_helper_file!='') {
            foreach($all_currencies as $currencyid => $currencyname) {
                $tomoney_grades[$currencyid] = sloodle_get_user_grades($currencyid, $this->course->id, 0, 1);	// transfered money
            }
        }

        //now build display table
        $sloodletable = new stdClass(); 

        //create header
        $headerrow = array();
        $headerrow[] = s(get_string('awards:avname',   'sloodle'));
        $headerrow[] = s(get_string('awards:username', 'sloodle'));
        foreach($all_currencies as $currencyid => $currencyname) {
            $headerrow[] = s($currencyname);
        }
        $headerrow[] = $this->can_edit ? '<input type="checkbox" id="checkall" checked>' : '&nbsp;';

        //now add the header we just built
        $sloodletable->head = $headerrow;

        //set alignment of table cells 
        $aligns = array('center','center'); // name columns
        foreach($all_currencies as $curr) {
            $aligns[] = 'right'; // each currency
        }
        $aligns[] = 'center'; // checkboxes
        $sloodletable->align = $aligns;
        $sloodletable->width="95%";

        $system_context = context_system::instance();
        $has_permit = has_capability('moodle/site:viewparticipants', $system_context);

        //now display scores
        foreach($student_scores_by_currency_id as $userid => $currencybalancearray) {
            $student = $students_by_userid[ $userid ];
            $row = array();
            $url_moodleprofile = $CFG->wwwroot."/user/view.php?id={$userid}&amp;course={$COURSE->id}";
            $url_sloodleprofile = SLOODLE_WWWROOT."/view.php?_type=user&amp;id={$userid}&amp;course={$COURSE->id}";
            if ($userid==$USER->id or $has_permit) {
                $row[] = "<a href=\"{$url_sloodleprofile}\">".s($student->avname).'</a>';
            }
            else {
                $row[] = s($student->avname);
            }
            $row[] = "<a href=\"{$url_moodleprofile}\">".s($student->firstname).' '.s($student->lastname).'</a>';

            if ($this->opensim_helper_file=='') {
                foreach(array_keys($all_currencies) as $currencyid) {
                    if (isset($currencybalancearray[$currencyid])) {
                        $row[] = s($currencybalancearray[$currencyid]);
                    }
                    else {
                        $row[] = ' 0 ';
                    }
                }
            }
            // to money of OpenSim
            else {
                foreach(array_keys($all_currencies) as $currencyid) {
                    if (isset($currencybalancearray[$currencyid])) {
                        $tomoney = $tomoney_grades[$currencyid][$userid]->rawgrade;
                        $row[] = s($currencybalancearray[$currencyid].' ('.$tomoney.')');
                    }
                    else {
                        $row[] = ' 0 (0)';
                    }
                }
            }

            $row[] = $this->can_edit ? '<input type="checkbox" checked name="userIds[]" value="'.intval($userid).'">' : '&nbsp;';
            $sloodletable->data[] = $row;
        }

        if ($this->can_edit) {
            //create an extra row for the modify currency fields
            $row = array();
            $row[] = ' &nbsp; ';
            $row[] = '';
            $row[]='<span class="bpheader">'.s(get_string('backpacks:selectcontroller', 'sloodle'))."</span>";

            //build select drop down for the controllers in the course that any point updates will be linked too
            $rowText='<select style="left:20px;text-align:left;" name="controllerid">';

            //get all controllers
            if (count($controllers)==0) {
                $params = array(SLOODLE_TYPE_CTRL, $courseid);
                $controllers = sloodle_get_records_sql_params("select * from {$CFG->prefix}sloodle where type=? AND course=?", $params);
            }

            // Make sure we have at least one controller
            if (!$controllers || count($controllers)==0) {
                print_error(get_string('objectauthnocontrollers','sloodle'));
                exit();
            }

            foreach ($controllers as $controller){
                $option = '';
                $cm = get_coursemodule_from_instance('sloodle', $controller->id);
                if ($controllerid==$cm->id) $option = ' selected';
                $rowText.='<option name="controllerid" value="'.intval($cm->id).'"'.$option.'>'.s($controller->name).'</option>';
            }
            $rowText.='</select>';

            //add controller select cell to row       
            $row[] =$rowText; 
            $sloodletable->data[] = $row; 

            //create another row for the submit button 
            $row = array();
            $row[] = '&nbsp;';
            $row[] = '&nbsp;';
            foreach($all_currencies as $currencyid => $currencynames) {
                $row[] = '<input type="text" name="currency_'.$currencyid.'">';
            } 
            $row[] ='<input type="submit" value="'.get_string('update_backpack','sloodle').'" name="updateBackpacks">';
            $sloodletable->data[] = $row; 

            //
            $row = array();
            $row[] = '&nbsp;';
            $row[] = '&nbsp;';
            foreach($all_currencies as $currencyid => $currencynames) {
                $row[] = '<input type="submit" value="'.get_string('to_grade','sloodle').'" name="toGrade_'.$currencyid.'">';
            } 
            $row[] = '&nbsp;';
            $sloodletable->data[] = $row; 

            //
            if ($this->opensim_helper_file!='') {
                $row = array();
                $row[] = '&nbsp;';
                $row[] = '&nbsp;';
                foreach($all_currencies as $currencyid => $currencynames) {
                    $rowText = get_string('backpack:magnification','sloodle').' '.'<input type="text" value="1" size="5" style="text-align:right;" name= "rateMoney_'.$currencyid.'">';
                    $rowText.= '&nbsp;&nbsp;';
                    $rowText.= '<input type="submit" value="'.get_string('to_money','sloodle').'" name= "toMoney_'.$currencyid.'">';
                    $row[] = $rowText;
                } 
                $row[] = get_string('backpack:explaine_money_display','sloodle');
                $sloodletable->data[] = $row;  
            }
        }

        print('<form action="" method="POST">');
        echo '<input type="hidden" name="isItemAdd" value="1">';
        sloodle_print_table($sloodletable); 
        print '</form>';

        sloodle_print_box_end(); 
    }
}
