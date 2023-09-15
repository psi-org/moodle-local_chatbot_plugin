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
require_once($CFG->dirroot . "/course/externallib.php");
require_once($CFG->dirroot . "/grade/report/user/classes/external/user.php");

class request_quiz_attempt extends \external_api
{

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'quiz' => new external_value(PARAM_TEXT, 'Quiz ID'),
                'userid' => new external_value(PARAM_INT, 'User ID')
            ]
        );
    }


    public static function execute($quiz, $userid) {

        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $return = array();

        // Check if there is an open attempt.
        $quiz_attempt_state = $DB->get_record('quiz_attempts',
            array(
                'quiz' => $quiz,
                'userid' => $userid,
                'state' => 'inprogress'
            ),
            'id, attempt, uniqueid, state',
            IGNORE_MULTIPLE
        );

        // Get Course Module ID and Context.
        $quiz_module_id = $DB->get_field('modules', 'id', array('name' => 'quiz'), IGNORE_MULTIPLE);
        $cmid = $DB->get_field('course_modules', 'id', array('module' => $quiz_module_id, 'instance' => $quiz), IGNORE_MULTIPLE);
        $context = context_module::instance($cmid);
        $quiz_record = $DB->get_record('quiz', array('id' => $quiz), '*', MUST_EXIST);
        /*
        $activityimg = $DB->get_field(
            'local_kassai_image_url',
            'url',
            array('item_id' => $cmid, 'type' => 'mod'),
            IGNORE_MULTIPLE
        );
        */
        if ($quiz_attempt_state) {

            // Insert quiz_attempt timemodified

            // Get non answered questions and last answered.
            // Get Next question.
            $return = local_botmanager_get_next_question($quiz, $quiz_attempt_state->uniqueid, $userid);
            $return['activityattempt'] = $quiz_attempt_state->attempt;
            //$return['activityimg'] = $activityimg;
            $return['quizattemptsid'] = $quiz_attempt_state->id;
            $return['showanswerstatus'] = ($quiz_record->reviewcorrectness == 0 ? 0 : 1);
            $return['showanswerfeedback'] = ($quiz_record->reviewspecificfeedback == 0 ? 0 : 1);


        } else {

            $sql_max_attempt = "SELECT * FROM {quiz_attempts}
                WHERE quiz = :quiz AND userid = :userid AND state = :state
                    ORDER BY attempt DESC";

            $max_attempt = $DB->get_records_sql($sql_max_attempt,
                array(
                    'quiz' => $quiz,
                    'userid' => $userid,
                    'state' => 'finished'
                ), 0, 1);

            if ($max_attempt) {
                $max_attempt_first = reset($max_attempt);
                $attempt = $max_attempt_first->attempt + 1;
            } else {
                $attempt = 1;
            }

            // Start New Attempt.

            // Insert question_usages
            $question_usages_record = new stdClass();
            $question_usages_record->contextid = $context->id;
            $question_usages_record->component = 'mod_quiz';
            $question_usages_record->preferredbehaviour = 'deferredfeedback';
            $question_usages_record->id = $DB->insert_record('question_usages', $question_usages_record); //esto queda fijo, eso no es dinamico = 9

            $return['activityattempt'] = $attempt;

            // Calculate Layout.
            /*$sql_quiz_questions = "SELECT que.id
            FROM {quiz_slots} qs
            right join {question} que 
                ON qs.questionid = que.id
                WHERE qs.quizid = :quizid";*/

            $sql_quiz_questions = "SELECT q.id
                FROM mdl_quiz_slots slot
                LEFT JOIN mdl_question_references qr ON qr.component = 'mod_quiz'
                            AND qr.questionarea = 'slot' AND qr.itemid = slot.id
                LEFT JOIN mdl_question_bank_entries qbe ON qbe.id = qr.questionbankentryid
                LEFT JOIN mdl_question_versions qv ON qv.questionbankentryid = qbe.id
                LEFT JOIN mdl_question q ON q.id = qv.questionid
                WHERE slot.quizid = :quizid";



            $quiz_questions = $DB->get_records_sql($sql_quiz_questions, array('quizid' => $quiz));

            $quiz_questions_number = count($quiz_questions);
            $quiz_questions_range = range(1, $quiz_questions_number);
            $layout = implode(',0,', $quiz_questions_range);
            $layout .= ',0';

            // Insert quiz_attempts
            $quiz_attempts_record = new stdClass();
            $quiz_attempts_record->quiz = $quiz;
            $quiz_attempts_record->userid = $userid;
            $quiz_attempts_record->attempt = $attempt; // TODO num of attemp for this user - We can calculate this.
            $quiz_attempts_record->uniqueid = $question_usages_record->id;
            $quiz_attempts_record->layout = $layout; // TODO Get layout, we can calculate this with the number of questions ie 1,0,2,0,3,0
            $quiz_attempts_record->currentpage = 0; // TODO We don't use this. Check with Derson
            $quiz_attempts_record->preview = ''; // TODO We don't use this. Check with Derson
            $quiz_attempts_record->state = 'inprogress';
            $quiz_attempts_record->timestart = time();
            $quiz_attempts_record->timefinish = 0;
            $quiz_attempts_record->timemodified = time();
            $quiz_attempts_record->timemodifiedoffline = 0;
            $quiz_attempts_record->timecheckstate = null;
            $quiz_attempts_record->sumgrades = 0.0000000;
            $quiz_attempts_record->id = $DB->insert_record('quiz_attempts', $quiz_attempts_record);

            $return['quizattemptsid'] = $quiz_attempts_record->id;

            // Get questions and answers.
            /*
            $sql_quiz_questions = "SELECT que.id, que.qtype, que.name, que.questiontext, q.name AS activitytitle, qs.slot  
            FROM {quiz} q
            JOIN {quiz_slots} qs ON qs.quizid = q.id
            JOIN {question} que ON qs.questionid = que.id
                WHERE q.id = :quizid 
                ORDER BY qs.slot";*/

            $sql_quiz_questions = "SELECT q.id, q.qtype, q.name, q.questiontext, quiz.name AS activitytitle, slot.slot, queat.variant, slot.maxmark, queat.minfraction, queat.maxfraction, queat.flagged
                FROM mdl_quiz_slots slot
                LEFT JOIN mdl_quiz quiz ON quiz.id = slot.quizid
                LEFT JOIN mdl_question_references qr ON qr.component = 'mod_quiz' AND qr.questionarea = 'slot' AND qr.itemid = slot.id
                LEFT JOIN mdl_question_bank_entries qbe ON qbe.id = qr.questionbankentryid
                LEFT JOIN mdl_question_versions qv ON qv.questionbankentryid = qbe.id
                LEFT JOIN mdl_question q ON q.id = qv.questionid
                LEFT JOIN mdl_question_attempts queat ON queat.questionid = q.id
                WHERE slot.quizid = :quizid ORDER BY slot.slot";

            $initial_questions = $DB->get_records_sql($sql_quiz_questions, array('quizid' => $quiz));

            $quiz_questions = filter_questions_with_latest_versions($initial_questions);

            $slot = 0;
            $new_question_attempt_id = 0;

            $question_count = 1;
            foreach ($quiz_questions as $question) {

                if ($question_count == 1) {

                    $return['activitytitle'] = $question->activitytitle;
                    //$return['activityimg'] = $activityimg;
                    $return['currentpage'] = 0;
                    $return['questionname'] = $question->name;
                    $return['questiontext'] = strip_tags($question->questiontext);
                    $return['questiontype'] = $question->qtype;
                    $return['showanswerstatus'] = ($quiz_record->reviewcorrectness == 0 ? 0 : 1);
                    $return['showanswerfeedback'] = ($quiz_record->reviewspecificfeedback == 0 ? 0 : 1);

                    $question_answers = $DB->get_records('question_answers', array('question' => $question->id));

                    foreach($question_answers as $question_answer) {
                        $return['answers'][$question_answer->id]['answerid'] = $question_answer->id;
                        $return['answers'][$question_answer->id]['answer'] = strip_tags($question_answer->answer);
                        $return['answers'][$question_answer->id]['iscorrectanswer'] = intval($question_answer->fraction);
                        $return['answers'][$question_answer->id]['feedback'] = strip_tags($question_answer->feedback);
                    }
                }

                // Insert on question_attempts.
                $question_attempts_record = new stdClass();
                $question_attempts_record->questionusageid = $question_usages_record->id; // primera iteracion 9 ... segunda iteracion 9
                $question_attempts_record->slot = $question->slot; //primera iteracion 1 ... segunda iteracion 1
                $question_attempts_record->behaviour = "deferredfeedback";
                $question_attempts_record->questionid = $question->id;
                // $question_attempts_record->questionid = $question->questionid;
                $question_attempts_record->variant = 1;
                $question_attempts_record->maxmark = $question->maxmark;
                $question_attempts_record->minfraction = 0;
                $question_attempts_record->maxfraction = 1.0000000;
                $question_attempts_record->flagged = 0;

                $local_kassai_question_utils = new local_botmanager_question_utils();
                $question_attempts_record->questionsummary = $local_kassai_question_utils->to_plain_text($question->questiontext, $question->questiontextformat);
                $question_attempts_record->rightanswer = null;
                $question_attempts_record->responsesummary = null;
                $question_attempts_record->timemodified = time();

                $question_attempts_record->id = $DB->insert_record('question_attempts', $question_attempts_record);

                if ($question_count == 1) {
                    $return['questionattemptid'] = $question_attempts_record->id;
                }

                // Insert on question_attempt_steps.
                $question_attempt_steps_record = new stdClass();
                $question_attempt_steps_record->questionattemptid = $question_attempts_record->id;
                $question_attempt_steps_record->sequencenumber = 0;
                $question_attempt_steps_record->state = 'todo';
                $question_attempt_steps_record->fraction = null;
                $question_attempt_steps_record->timecreated = time();
                $question_attempt_steps_record->userid = $userid;
                $question_attempt_steps_record->id = $DB->insert_record('question_attempt_steps', $question_attempt_steps_record);

                $question_answers = $DB->get_records('question_answers', array('question' => $question->id), '', 'id');
                $question_answers_string = implode(",", array_keys($question_answers));

                if ($question->qtype == 'multichoice') {
                    // Insert on question_attempt_steps.
                    $question_attempt_step_data_record = new stdClass();
                    $question_attempt_step_data_record->attemptstepid = $question_attempt_steps_record->id;
                    $question_attempt_step_data_record->name = '_order';
                    $question_attempt_step_data_record->value = strip_tags($question_answers_string); // TODO Get order of the question displayed.
                    $question_attempt_step_data_record->id = $DB->insert_record('question_attempt_step_data', $question_attempt_step_data_record);
                }

                $question_count++;
            }

            // View Quiz.
            local_botmanager_view_module('quiz', $quiz, $userid);
        }

        $transaction->allow_commit();
        return $return;
    }


    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            array(
                'activitytitle' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                //'activityimg' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'activityattempt' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'quizattemptsid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'currentpage' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'questionname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'questiontext' => new external_value(PARAM_RAW, 'Standard Moodle primary key.'),
                'questiontype' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'showanswerstatus' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'showanswerfeedback' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'questionattemptid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'answer' => new external_value(PARAM_RAW, 'Standard Moodle primary key.'),
                            'answerid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                            'iscorrectanswer' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                            'feedback' => new external_value(PARAM_RAW, 'Standard Moodle primary key.')
                        )
                    )
                )
            )
        );
    }




}