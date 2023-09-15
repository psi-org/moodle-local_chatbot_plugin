<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;
use external_multiple_structure;
use core_course_external;
use gradereport_user\external\user as user_external;
use stdClass;

defined('MOODLE_INTERNAL') || die;
//global $CFG;
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/botmanager/lib.php');
require_once($CFG->dirroot . "/course/externallib.php");
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . "/grade/report/user/classes/external/user.php");

class get_activities extends \external_api
{

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters()
    {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'courseactivityid' => new external_value(PARAM_INT, 'Course Activity ID', VALUE_DEFAULT, 0),
                'activityid' => new external_value(PARAM_INT, 'Activity ID', VALUE_DEFAULT, 0)
            ]
        );
    }

    /**
     * Get Activities.
     *
     * @param int $courseid
     * @param int $useridg
     * @param int $courseactivityid
     * @param int $activityid
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($courseid, $userid, $courseactivityid = 0, $activityid = 0)
    {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $params = array('courseid' => $courseid, 'userid' => $userid);

        // Check if user is enrolled.
        $enrolled_sql = "SELECT ue.id FROM {user_enrolments}  AS ue 
            JOIN {enrol} AS e ON ue.enrolid = e.id 
            WHERE e.courseid = :courseid AND ue.userid = :userid";

        if (!$DB->get_records_sql($enrolled_sql, $params)) {
            // Enrol user.
            local_botmanager_api_enrol_student($userid, $courseid);
        }

        $transaction->allow_commit(); //TEMPORAL


        $activitytypes = $DB->get_records('modules');
        $courseModulesCompletion = $DB->get_records('course_modules_completion');
        $userGradesDB = $DB->get_records('grade_items');

        // WS Functions
        $course = core_course_external::get_courses(array("ids" => array($courseid)));
        $userGrades = user_external::get_grade_items($courseid, $userid);
        $sections = core_course_external::get_course_contents($courseid);

        $activities = array();

        $permittedActivities = array('lesson', 'quiz', 'feedback');

        foreach ($sections as $index=>&$section){
            $sections[$index]['modules'] = array_filter($section['modules'], function($module) use (&$section, $index, $permittedActivities){
                if (in_array($module['modname'], $permittedActivities)){
                    $section['sequence'] = $section['sequence'] . ',' . $module['id'];
                    return true;
                }
            });
        }


        foreach ($sections as $sectionIndex => $CleanSection) {

            $counterActivities = 0;
            foreach ($CleanSection['modules'] as $activity) {

                $activity['activityIndexBasedOnSection'] = $counterActivities;
                $activity['numberActivitiesInSection'] = count($CleanSection['modules'])-1; // -1 subtracted because count function retrieves the actual number starting from 1

                $activity['sectionindex'] = $sectionIndex;
                $activity['sectionid'] = $CleanSection['id'];
                $activity['sectionname'] = $CleanSection['name'];
                $activity['sequence'] = $CleanSection['sequence'];

                $counterActivities++;

                if ($activityid === 0 || $activityid === null) {
                    $activities[] = $activity;

                    continue;
                }

                if ($activityid == $activity['id']) {
                    $activities[] = $activity;
                    break 2;
                }
            }
        }

        $activites_to_return = [];

        foreach($activities as $index => $activity) {

            $activityAttempts = array();
            //codigo aparte
            if ($activity['modname'] === 'quiz') {

                $user_attempts = quiz_get_user_attempts($activity['instance'], $userid);

                foreach ($user_attempts as $user_attempt) {
                    $activityAttempt = array(
                        'quizid' => $user_attempt->id,
                        'quizattemptid' => $user_attempt->quiz,
                        'userid' => $userid,
                        'attempt' => $user_attempt->attempt,
                        'uniqueid' => $user_attempt->uniqueid,
                        'currentpage' => $user_attempt->currentpage,
                        'state' => $user_attempt->state,
                        'sumgrades' => $user_attempt->sumgrades,
                        'timestarted' => setDateTime($user_attempt->timestart),
                        'timefinished' => setDateTime($user_attempt->timefinish)
                    );
                    $activityAttempts[] = $activityAttempt;
                }
            }
//termina codigo aparte

//codigo aparte

            $courseCompletion = null;

            foreach ($courseModulesCompletion as $courseModuleCompletion) {
                if ($courseModuleCompletion->userid == $userid && $courseModuleCompletion->coursemoduleid == $activity['id']) {
                    $courseCompletion = $courseModuleCompletion;
                }
            }


//termina codigo aparte


            $data = new stdClass();

            $gradeItem = getGradeItem($userGrades, $activity['id']);
            $gradeItem2 = getGradeItem2($userGradesDB, $activity['name']);


            $data->courseactivityid = $courseactivityid;
            $data->instanceid = $activity['instance']; //MALO
            $data->courseid = $courseid;
            $data->courseshortname = $course[0]['shortname'];
            $data->coursefullname = $course[0]['fullname'];
            $data->sectionid = $activity['sectionid'];
            $data->sectionname = $activity['sectionname'];
            $data->activityid = $activity['id'];
            $data->activitytypeid = setActivityTypeId($activitytypes, $activity['modname']);
            $data->activitytype = $activity['modname'];
            $data->activityvisible = $activity['visible'];
            $data->activityname = $activity['name'];
            //$data->activitydescription = strip_tags($activity['description']);
            $data->activitydescription = keepHtmlText($activity['description']);
            //$data->activityimg = $activity['activityimg'];
            $data->activitysequencelist = ltrim($activity['sequence'],","); //es indent o es el orden del activity como ya esta?
            $data->isfirstactivity = isFirst($index);
            $data->islastactivity = isLast($activities, $index);
            //$data->isfirstsectionactivity = isFirst($activity['sectionindex']);
            $data->isfirstsectionactivity = getSectionNumberActivty($activity['activityIndexBasedOnSection'], 0);
            $data->islastsectionactivity = getSectionNumberActivty($activity['activityIndexBasedOnSection'], $activity['numberActivitiesInSection']);
            //$data->activityviewed =
            $data->userid = $userid;
            $data->activitycompletionsstatusid = $courseCompletion->completionstate;

            $data->activitygrademax = $gradeItem['grademax'];
            $data->activitygradepass = $gradeItem2->gradepass;
            $data->activitygradetype = $gradeItem2->gradetype;
            $data->activityrawgrade = $gradeItem['graderaw'];

            $data->lastattemptid = 0;
            $data->lastattemptstatte = '';
            $data->numofattempts = count($activityAttempts);
            $data->activityattempts = $activityAttempts;

        //Codigo aparte

            if (!is_numeric(trim($gradeItem['percentageformatted'], '%'))) {
                $data->activitypercentagegrade = 0;
            } else {
                $data->activitypercentagegrade = floatval(trim($gradeItem['percentageformatted'], '%'));
            }

            /*
            if ($courseCompletion->completionstate === null) {
                $data->activitycompletionsstatusid = -1;
                $data->activitycompletionstatus = 'Not Started';
            }
            */

            if ($courseCompletion->completionstate == 0 || $courseCompletion->completionstate == null) {
                $data->activitycompletionsstatusid = -1;
                $data->activitycompletionstatus = 'Not Completed';
            }

            if ($courseCompletion->completionstate == 1) {

                $data->activitycompletionstatus = 'Completed';
            }

            if ($courseCompletion->completionstate == 2) {

                $data->activitycompletionstatus = 'Complete Pass';
            }

            if ($courseCompletion->completionstate == 3) {

                $data->activitycompletionstatus = 'Complete Fail';
            }

        //Termina Codigo aparte
            array_push($activites_to_return, $data);

        }



        return $activites_to_return;
    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     */
    public
    static function execute_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'courseactivityid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //este es un campo concatenado del courseID y el cmdID (o tambien llamado activity ID)
                    'instanceid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // instanceID lo podemos sacar de gradereport_user_get_grade_items, se llama iteminstance gradereport_user_get_grade_items
                    'courseid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //este valor ya nos viene como parametro
                    'courseshortname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), //este lo podemos sacar utilizando la funcion core_course_get_courses
                    'coursefullname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), //este lo podemos sacar utilizando la funcion core_course_get_courses
                    'sectionid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // este lo podemos sacar de core_course_get_contents, este valor es el id del objeto principal, que dentro del objeto contiene un arreglo de modulos que estos son los lessons, quiz...
                    'sectionname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), // este lo podemos sacar de core_course_get_contents, este valor es el id del objeto principal, que dentro del objeto contiene un arreglo de modulos que estos son los lessons, quiz...
                    'activityid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // activityid lo podemos sacar de gradereport_user_get_grade_items (y tambien de core_course_get_contents), este campo se llama cmid en gradereport_user_get_grade_items
                    'activitytypeid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //se puede ir a sacar de la base de datos, pero este valor se necesita el acitityType que se saca de core_course_get_contents
                    'activitytype' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),// este valor se saca de core_course_get_contents, dentro del objeto modules
                    'activityvisible' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // este valor se saca de core_course_get_contents, dentro del objeto modules
                    'activityname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), // este valor se saca de core_course_get_contents, dentro del objeto modules
                    'activitydescription' => new external_value(PARAM_RAW, 'Standard Moodle primary key.'), // este valor se saca de core_course_get_contents, dentro del objeto modules
                    //'activityimg' => new external_value(PARAM_RAW, 'Standard Moodle primary key.'),  // este la usaremos?
                    'activitysequencelist' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), //este sequencelist lo sacan ellos utilizando el id de las actividades que estan encapsuladas en las secciones, por ejemplo: core_course_get_contents trae secciones y cada sección trae los modulos, si la seccion 165 trae las actividades id 144 y 235, este sequence se forma con esos ID: 144,235 (se concatenan los id de las actividades separadas por coma)
                    'isfirstactivity' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //este valor es 0 o 1, si es la primera actividad de la sección, se le pone 1, si no, 0
                    'islastactivity' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //este valor es 0 o 1, si es la ultima actividad de la sección, se le pone 1, si no, 0
                    'isfirstsectionactivity' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //este valor es 0 o 1, si es la primera seccion, se le pone 1, si no, 0
                    'islastsectionactivity' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //este valor es 0 o 1, si es la ultima sección, se le pone 1, si no, 0
                    //'activityviewed' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // se puede conseguir de la tabla mdl_course_modules_completion, columna viewed
                    'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // ya viene como parametro
                    'activitycompletionsstatusid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // se puede conseguir de la tabla mdl_course_modules_completion, columna completion state
                    'activitycompletionstatus' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), // Los textos se crean a partir del ID ()
                    'activitygrademax' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'), // se puede obtener de la función gradereport_user_get_grade_items
                    'activitygradepass' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'), // se puede obtener de la tQabla mdl_grade_items, columna gradepass
                    'activitygradetype' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // se puede obtener de la tabla mdl_grade_items, columna gradetype
                    'activityrawgrade' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'), // se puede obtener de la función gradereport_user_get_grade_items
                    'activitypercentagegrade' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'), // se puede obtener de la función gradereport_user_get_grade_items
                    'lastattemptid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),// se puede obtener de mod_quiz_get_user_attempts, sacando el length del arreglo que nos viene de attempts, acceder al ultimo y obtener su id
                    'lastattemptstatte' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),// se puede obtener de mod_quiz_get_user_attempts, accediendo al ultimo attempt y sacando su state
                    'numofattempts' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // se puede obtener de mod_quiz_get_user_attempts, sacando el length del arreglo que nos viene de attempts
                    'activityattempts' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'quizid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),  // el quizid es el instance ID, instanceID lo podemos sacar de gradereport_user_get_grade_items, se llama iteminstance gradereport_user_get_grade_items
                                'quizattemptid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //quizattemptID lo obtenemos de mod_quiz_get_user_attempts, este campo es el ID del attempt
                                'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), //ya lo tenemos de los parametros
                                'attempt' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // se puede obtener de mod_quiz_get_user_attempts
                                'uniqueid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // se puede obtener de mod_quiz_get_user_attempts
                                'currentpage' => new external_value(PARAM_INT, 'Standard Moodle primary key.'), // se puede obtener de mod_quiz_get_user_attempts
                                'state' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), // se puede obtener de mod_quiz_get_user_attempts
                                'sumgrades' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'), // se puede obtener de mod_quiz_get_user_attempts
                                'timestarted' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), // se puede obtener de mod_quiz_get_user_attempts
                                'timefinished' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), // se puede obtener de mod_quiz_get_user_attempts
                            )
                        )
                    ),
                    //'activitycompletiontimemodified' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'), // se puede obtener mdl_course_modules_completion
                    //'activitygradetimecreated' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    //'activitygradetimemodified' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'showcertificate' => new external_value(PARAM_INT, 'Show Certificate after this activity', VALUE_DEFAULT, 0),
                    'accessrestriction' => new external_value(PARAM_INT, '0 => False, 1 => True', VALUE_DEFAULT, 0),
                    'accessrestrictionmessage' => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
                    'accessrestrictionid' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'cmid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                            )
                        ), '', VALUE_DEFAULT, array()
                    ),
                )
            )
        );
    }


}