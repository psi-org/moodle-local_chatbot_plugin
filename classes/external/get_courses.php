<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;
use external_multiple_structure;
use coursecat_helper;
use core_course_category;
use core_course_external;
use core_enrol_external;
use gradereport_user\external\user as user_external;
use stdClass;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/course/renderer.php");
require_once($CFG->dirroot . "/course/externallib.php");
require_once($CFG->dirroot . "/enrol/externallib.php");
require_once($CFG->dirroot . "/grade/report/user/classes/external/user.php");
require_once($CFG->dirroot . '/local/botmanager/lib.php');

class get_courses extends \external_api
{
    const COURSECAT_SHOW_COURSES_EXPANDED = 20;

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            [
                'userid' => new external_value(PARAM_INT, 'User ID')
            ]
        );
    }

    /**
     * Get courses by passing the user id
     *
     * @param int $userid user id
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($userid)
    {
        global $DB;

        $courses = core_course_external::get_courses();
        $enroled_courses = core_enrol_external::get_users_courses($userid);

        $botmode_courses = array_filter($courses, function ($course) {
            foreach ($course['customfields'] as $customfield) {
                if (strtolower($customfield['shortname']) === 'botmode' && strtolower($customfield['value']) === 'yes') {
                    return true;
                }
            }
        });
        /*
        foreach($enroled_courses as $index_enrolled_course => $enroled_course){
            foreach ($botmode_courses as $index_botmode_course =>$botmode_course){
                if ($botmode_course['id'] === $enroled_course['id']){
                    $completion = $DB->get_record('course_completions', array('userid' => $userid,'course' => $enroled_course['id']), '*');
                    $enroled_course['enrolledtime'] =  $completion->timeenrolled;
                    $enroled_course['timestarted'] =  $completion->timestarted;
                    $enroled_course['timecompleted'] =  $completion->timecompleted;
                    $enroled_course['categoryid'] = $botmode_courses['categoryid'];
                    $botmode_courses[$index_botmode_course] = $enroled_course;
                    break;
                }
            }
        }
        */

        $enroledCoursesById = [];

        foreach ($enroled_courses as $enroled_course) {
            $enroledCoursesById[$enroled_course['id']] = $enroled_course;
        }

        foreach ($botmode_courses as $index_botmode_course => $botmode_course) {
            $enroled_course_id = $botmode_course['id'];

            if (isset($enroledCoursesById[$enroled_course_id])) {
                $enroled_course = $enroledCoursesById[$enroled_course_id];
                $completion = $DB->get_record('course_completions', array('userid' => $userid, 'course' => $enroled_course_id), '*');

                $enroled_course['enrolledtime'] = $completion->timeenrolled;
                $enroled_course['timestarted'] = $completion->timestarted;
                $enroled_course['timecompleted'] = $completion->timecompleted;
                $enroled_course['categoryid'] = $botmode_course['categoryid'];

                $botmode_courses[$index_botmode_course] = $enroled_course;
            }
        }

        //print_r($botmode_courses);

        $courses_to_return = [];
        foreach ($botmode_courses as $key => $course) {

            $grade_items = user_external::get_grade_items($course['id'], $userid);

            $data = new stdClass();
            $data->courseid = $course['id'];
            $data->coursecategoryid = $course['categoryid']; //no viene en enrol
            $data->sortorder = $key; //no viene en enrol
            $data->shortname = $course['shortname'];
            $data->fullname = $course['fullname'];
            $data->summary = $course['summary'];
            $data->format = $course['format'];
            $data->userid = $userid;
            $data->enrol = 'self'; //que es esto?
            $data->courseprogress = setIntegerValue($course['progress']); // 0
            //$data->rawgrade = setValue($course['grade']); //0 ; //este grade no existe, hay que calcularlo o sacarlo de otro lado
            $data->gradepass = 0; //hay que sacarlo de algun lado
            $data->enrolledtime = setDateTime($course['enrolledtime']); //date('Y-m-d H:i:s.v', $completion->timeenrolled);
            $data->timestarted = setDateTime($course['timestarted']);
            $data->timecompleted = setDateTime($course['timecompleted']);


            foreach ($grade_items['usergrades'][0]['gradeitems'] as $key => $item) {
                if ($item['itemtype'] == "course") {
                    $data->rawgrade = $item['graderaw'];
                    //$data->gradepass = $item ->grademax;
                    //TODO Revisar aca si se debe hacer un trim o no?

                    $data->percentagegrade = 0;
                    $is_grademax_greater_than_graderaw = $item['grademax'] >= $item['graderaw'];

                    if ($is_grademax_greater_than_graderaw && $item['grademax'] != null && $item['graderaw'] != null) {
                        $data->percentagegrade = ($item['graderaw'] / $item['grademax']) * 100;
                    }
                }
            }

            if ($course['progress'] === null) {
                $data->coursestatusid = 1;
                $data->coursestatus = 'Not enrolled';
            }
            if ($course['progress'] === 0) {
                $data->coursestatusid = 2;
                $data->coursestatus = 'Enrolled';
            }
            if ($course['progress'] > 0 && $course['progress'] <= 100 && !$course['completed']) { //falta caso del 100?
                $data->coursestatusid = 3;
                $data->coursestatus = 'In progress';
            }

            if ($course['completed'] === true) {
                $data->coursestatusid = 4;
                $data->coursestatus = 'Completed';
            }

            /*
            6 completed fail
            5 completed pass
            4 completed
            3 in progress
            2 enrolled
            1 not enrolled

            */

            array_push($courses_to_return, $data);
        }

        return $courses_to_return;
    }


    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns(): external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'courseid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                    'coursecategoryid' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'sortorder' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                    'shortname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'fullname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'summary' => new external_value(PARAM_RAW, 'Standard Moodle primary key.'),
                    'format' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    //'courseimg' => new external_value(PARAM_RAW, 'Standard Moodle primary key.'),
                    'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                    'coursestatusid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                    'coursestatus' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'enrol' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'courseprogress' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'),
                    'rawgrade' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'),
                    'percentagegrade' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'),
                    'gradepass' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'),
                    'enrolledtime' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'timestarted' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'timecompleted' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.')

                )
            )
        );
    }
}