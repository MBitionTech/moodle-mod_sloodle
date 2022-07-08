<?php                          

defined('MOODLE_INTERNAL') || die;

/*
 function sloodle_get_user_grades($itemnumber, $courseid=0, $userid=0, $tomoney=-1)
 function sloodle_award_points_update($userid, $roundid, $currencyid, $amount, $desc)
*/


function sloodle_get_user_grades($itemnumber, $courseid=0, $userid=0, $tomoney=-1)
{
    global $CFG, $USER, $COURSE;
 
    $prefix = $CFG->prefix;
    if ($courseid<=0) $courseid = $COURSE->id;

    // Get Student(s) Data
    $users = array(); 
    if ($userid<=0) {
        $context = context_course::instance($courseid, IGNORE_MISSING);
        $users = get_users_by_capability($context, 'mod/sloodle:courseparticipate', 'u.id');
    }
    else {
        $users[$userid] = new stdClass();
        $users[$userid]->id = $userid;
    }

    $money_cond = '';
    if      ($tomoney==0) $money_cond = " AND p.tomoney='0'";
    else if ($tomoney==1) $money_cond = " AND p.tomoney='1'";

    //
    $grades = array();
    $sqlstr = "SELECT sum(p.amount) as point FROM {$prefix}sloodle_award_points AS p INNER JOIN {$prefix}sloodle_award_rounds AS r ON p.roundid=r.id ".
              "WHERE p.userid=? AND p.currencyid=? AND r.courseid=? ";
    $sqlstr.= $money_cond;
    //
    foreach($users as $user) {
        $grades[$user->id] = new stdClass();
        $grades[$user->id]->userid = $user->id;
        $grades[$user->id]->dategraded = time();
        $grades[$user->id]->usermodified = $USER->id;
        //
        $scores = sloodle_get_records_sql_params($sqlstr, array($user->id, $itemnumber, $courseid));
        $crnt = current($scores);
        if ($crnt->point) {
            $grades[$user->id]->rawgrade = $crnt->point;
        }
        else {
            $grades[$user->id]->rawgrade = 0;
        }
    }

    return $grades;
}


function sloodle_award_points_update($userid, $roundid, $currencyid, $amount, $desc)
{
    $point = sloodle_get_record('sloodle_award_points', 'roundid', $roundid, 'userid', $userid);

    if (!$point) {      // insert
        $point = new stdClass();
        $point->userid      = $userid;
        $point->currencyid  = $currencyid;
        $point->amount      = $amount;
        $point->timeawarded = time();
        $point->roundid     = $roundid;
        $point->tomoney     = 0;
        $point->description = $desc;
        sloodle_insert_record('sloodle_award_points', $point);
    }
    else {              // update
        if ($point->tomoney==0 and ($point->currencyid!=$currencyid or $point->amount!=$amount)) {
            $point->currencyid  = $currencyid;
            $point->amount      = $amount;
            $point->timeawarded = time();
            sloodle_update_record('sloodle_award_points', $point);
        }
    }

    return;
}


