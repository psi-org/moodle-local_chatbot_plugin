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

class quiz_answer_question extends \external_api
{
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'quizattemptsid' => new external_value(PARAM_INT, 'Quiz Attempts ID'),
                'answerid' => new external_value(PARAM_INT, 'User ID'),
                'userid' => new external_value(PARAM_INT, 'Number of Attempt for this user'),
                'questionattemptid' => new external_value(PARAM_INT, 'Number of Attempt for this user'),
                'currentpage' => new external_value(PARAM_INT, 'Number of Attempt for this user')
            ]
        );
    }

    /**
     * Quiz Answer Question.
     *
     * @param int $quizattemptsid
     * @param int $answerid
     * @param int $userid
     * @param int $questionattemptid
     * @param int $currentpage
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($quizattemptsid, $answerid, $userid, $questionattemptid, $currentpage) {

        global $DB;

        $return = array();

        $answer_record = $DB->get_record('question_answers', array('id' => $answerid), 'id, answer, fraction', IGNORE_MULTIPLE);
        $fraction = $answer_record->fraction;
        $state = 'wrong';
        if (intval($fraction)) {
            $state = 'correct';
        }

        // Update quiz_attempt.
        $quiz_attempts_record = $DB->get_record('quiz_attempts', array('id' => $quizattemptsid),'*', IGNORE_MULTIPLE);

        $currentpage = $currentpage + 1;
        $quiz_attempts_record->currentpage = $currentpage;

        $questionid = $DB->get_field('question_attempts', 'questionid', array('id' => $questionattemptid), IGNORE_MULTIPLE);
        $lastquestion = local_botmanager_islastquestion($quiz_attempts_record->quiz, $questionid);
        $return['islastquestion'] = 0;
        if ($lastquestion) {
            // Update question_attempts. time.
            $return['islastquestion'] = 1;
        }

        $quiz_attempts_record->state = 'inprogress';
        if ($lastquestion) {
            $quiz_attempts_record->state = 'finished';
            $quiz_attempts_record->timefinish = time();
        }
        $quiz_attempts_record->timemodified = time();

        if (intval($fraction)) {

            $sum = 0.0000000;
            $query = "SELECT q.id,slot.slot, slot.maxmark 
                FROM mdl_quiz_slots slot
                LEFT JOIN mdl_quiz quiz ON quiz.id = slot.quizid
                LEFT JOIN mdl_question_references qr ON qr.component = 'mod_quiz'
                            AND qr.questionarea = 'slot' AND qr.itemid = slot.id
                LEFT JOIN mdl_question_bank_entries qbe ON qbe.id = qr.questionbankentryid
                LEFT JOIN mdl_question_versions qv ON qv.questionbankentryid = qbe.id
                LEFT JOIN mdl_question q ON q.id = qv.questionid
                LEFT JOIN mdl_question_attempts queat ON queat.questionid = q.id
                WHERE slot.quizid = :quizid AND q.id = :questionid ORDER BY slot.slot";


            if ($record = $DB->get_record_sql($query, array('quizid' => $quiz_attempts_record->quiz, 'questionid' =>$questionid))) {
                $sum = $record->maxmark;
            }

            $quiz_attempts_record->sumgrades = $quiz_attempts_record->sumgrades + $sum;
        }

        $DB->update_record('quiz_attempts', $quiz_attempts_record);

        // Insert question_attempt_steps.
        $question_attempt_steps_record = new stdClass();
        $question_attempt_steps_record->questionattemptid = $questionattemptid;
        $question_attempt_steps_record->sequencenumber = 1;
        $question_attempt_steps_record->state = 'complete';
        $question_attempt_steps_record->fraction = null;
        $question_attempt_steps_record->timecreated = time();
        $question_attempt_steps_record->userid = $userid;

        $question_attempt_steps_record->id = $DB->insert_record('question_attempt_steps', $question_attempt_steps_record);

        // Get Answer Value.
        $questiontype = $DB->get_field('question', 'qtype', array('id' => $questionid), IGNORE_MULTIPLE);
        if ($questiontype == 'truefalse') {
            if ($answer_record->answer == 'True') {
                $answervalue = 1;
            } else {
                $answervalue = 0;
            }
        } else {
            $get_order_sql = "SELECT qasd.value FROM {question_attempt_steps} qas 
            JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id
            WHERE qas.questionattemptid = :questionattemptid 
                AND qas.state = 'todo'
                AND qasd.name = '_order'";

            $order = $DB->get_field_sql($get_order_sql, array('questionattemptid' => $questionattemptid), IGNORE_MULTIPLE);
            $orderedanswersid = explode(',', $order);
            $answervalue = array_search($answerid, $orderedanswersid);
        }

        $question_attempt_step_data_record = new stdClass();
        $question_attempt_step_data_record->attemptstepid = $question_attempt_steps_record->id;
        $question_attempt_step_data_record->name = 'answer';
        $question_attempt_step_data_record->value = $answervalue;

        $DB->insert_record('question_attempt_step_data', $question_attempt_step_data_record);

        // Insert question_attempt_steps.
        $finish_question_attempt_steps_record = new stdClass();
        $finish_question_attempt_steps_record->questionattemptid = $questionattemptid;
        $finish_question_attempt_steps_record->sequencenumber = 2;
        $finish_question_attempt_steps_record->state = 'gradedwrong';
        $finish_question_attempt_steps_record->fraction = 0.0000000;
        if (intval($fraction)) {
            $finish_question_attempt_steps_record->state = 'gradedright';
            $finish_question_attempt_steps_record->fraction = 1.0000000;
        }
        $finish_question_attempt_steps_record->timecreated = time();
        $finish_question_attempt_steps_record->userid = $userid;

        $finish_question_attempt_steps_record->id = $DB->insert_record('question_attempt_steps', $finish_question_attempt_steps_record);

        $finish_question_attempt_step_data_record = new stdClass();
        $finish_question_attempt_step_data_record->attemptstepid = $finish_question_attempt_steps_record->id;
        $finish_question_attempt_step_data_record->name = '-finish';
        $finish_question_attempt_step_data_record->value = 1;

        $DB->insert_record('question_attempt_step_data', $finish_question_attempt_step_data_record);
        $quiz = $DB->get_record('quiz', array('id' => $quiz_attempts_record->quiz), '*', MUST_EXIST);

        $return['answerstatus'] = $state;
        $return['answerfeed'] = strip_tags($DB->get_field('question_answers', 'feedback', array('id' => $answerid), IGNORE_MULTIPLE));
        $return['showanswerstatus'] = ($quiz->reviewcorrectness == 0 ? 0 : 1);
        $return['showanswerfeedback'] = ($quiz->reviewspecificfeedback == 0 ? 0 : 1);

        if ($lastquestion) {
            quiz_save_best_grade($quiz, $userid);
        }

        return $return;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns() {
        return new external_single_structure(
            array(
                'answerstatus' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'answerfeed' => new external_value(PARAM_RAW, 'Standard Moodle primary key.'),
                'showanswerstatus' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'showanswerfeedback' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'islastquestion' => new external_value(PARAM_INT, 'Standard Moodle primary key.')
            )
        );
    }

}