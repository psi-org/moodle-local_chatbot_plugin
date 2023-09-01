<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");

class get_lesson_attemps_summary extends \external_api {

    const LOCAL_BOTMANAGER_LESSON_ALL = 2;

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters  {
        return new external_function_parameters(
            [
                'lessonid' => new external_value(PARAM_INT, 'Lesson ID'),
                'userid' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, 0),
                'completed' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, 2)
            ]
        );
    }

    /**
     * Get Lesson Attemps Summary.
     *
     * @param int $lessonid
     * @param int $userid
     * @param int $completed
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($lessonid, $userid = 0, $completed = self::LOCAL_BOTMANAGER_LESSON_ALL) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $data = array();

        $where = array('lessonid' => $lessonid);

        if ($userid) {
            $where['userid'] = $userid;
        }

        if ($completed < self::LOCAL_BOTMANAGER_LESSON_ALL) {
            $where['completed'] = $completed;
        }

        $lesson_timer = $DB->get_records('lesson_timer', $where, 'starttime desc');

        foreach($lesson_timer as $timer) {

            if (!isset($data[$timer->userid]['userid'])) {
                $data[$timer->userid]['userid'] = $timer->userid;
                $data[$timer->userid]['name'] = fullname($DB->get_record('user', array('id' => $timer->userid)));
            }

            $data[$timer->userid]['attempts'][$timer->id]['starttime'] = userdate($timer->starttime);
            $data[$timer->userid]['attempts'][$timer->id]['endtime'] = userdate($timer->lessontime);

            $timetotake = $timer->lessontime - $timer->starttime;
            $data[$timer->userid]['attempts'][$timer->id]['duration'] = format_time($timetotake);
            $data[$timer->userid]['attempts'][$timer->id]['completed'] = $timer->completed;

        }

        $transaction->allow_commit();

        return $data;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                    'name' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                    'attempts' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'starttime' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                                'endtime' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                                'duration' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                                'completed' => new external_value(PARAM_INT, 'Standard Moodle primary key.')
                            )
                        )
                    )
                )
            )
        );
    }
}