<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;
use external_multiple_structure;
use core_course_external;
use local_botmanager_question_utils;
use stdClass;
use context_module;
use question_utils;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir. "/externallib.php");
require_once($CFG->dirroot. "/question/engine/lib.php");
require_once($CFG->dirroot . '/local/botmanager/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

class get_quiz_attempts_summary extends \external_api
{

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'quizattemptid' => new external_value(PARAM_INT, 'Quiz Attempt ID')
            ]
        );
    }

    /**
     * Get Quiz Attempts Summary.
     *
     * @param int $quizattemptsid
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($quizattemptid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $params = array('quizattemptid' => $quizattemptid);

        $sql_view = "SELECT cm.course, cm.id, qza.quiz as quizid, qza.id as quizattemptid, qza.userid, qza.attempt, qza.uniqueid, qza.currentpage, qza.state, qz.sumgrades as maxgrade, qz.grade, qza.sumgrades as attemptsumgrade, qza.timestart, qza.timefinish
                        FROM mdl_quiz qz INNER JOIN
                        mdl_quiz_attempts qza ON qz.id = qza.quiz INNER JOIN						 
                        mdl_course_modules cm ON cm.instance = qz.id
						INNER JOIN mdl_modules mo ON cm.module = mo.id AND mo.name = 'quiz' 
                        WHERE qza.id=:quizattemptid ";

        $data = $DB->get_record_sql($sql_view, $params, IGNORE_MULTIPLE);
        $quizAttemptSummary = new stdClass();
        $quizAttemptSummary->courseid = $data->course;
        $quizAttemptSummary->courseactivityid = $data->course . $data->id;
        $quizAttemptSummary->quizid = $data->quizid;
        $quizAttemptSummary->quizattemptid = $data->quizattemptid;
        $quizAttemptSummary->userid = $data->userid;
        $quizAttemptSummary->attempt = $data->attempt;
        $quizAttemptSummary->uniqueid = $data->uniqueid;
        $quizAttemptSummary->currentpage = $data->currentpage;
        $quizAttemptSummary->attemptstate = $data->state;
        $quizAttemptSummary->activitygrademax = $data->maxgrade;
        $quizAttemptSummary->attemptfinalgrade = $data->attemptsumgrade;
        $quizAttemptSummary->attemptpercentagegrade = round(($data->attemptsumgrade/$data->maxgrade)*$data->grade,2);
        $quizAttemptSummary->timestarted =setDateTime($data->timestart);
        $quizAttemptSummary->timefinished = setDateTime($data->timefinish);
        $quizAttemptSummary->timetakeninminutes = floor(abs($data->timefinish - $data->timestart) / 60);

        $transaction->allow_commit();

        return $quizAttemptSummary;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns() {
        return new external_single_structure(
            array(
                'courseid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'courseactivityid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'quizid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'quizattemptid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'attempt' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'uniqueid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'currentpage' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'attemptstate' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'activitygrademax' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'),
                'attemptfinalgrade' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'),
                'attemptpercentagegrade' => new external_value(PARAM_FLOAT, 'Standard Moodle primary key.'),
                'timestarted' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'timefinished' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'timetakeninminutes' => new external_value(PARAM_INT, 'Standard Moodle primary key.')
            )
        );
    }
}