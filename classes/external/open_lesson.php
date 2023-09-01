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

class open_lesson extends \external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'lessonid' => new external_value(PARAM_INT, 'Lesson ID'),
                'userid' => new external_value(PARAM_INT, 'User ID')
            ]
        );
    }

    /**
     * Open Lesson.
     *
     * @param int $lessonid
     * @param int $userid
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($lessonid, $userid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $data = array();

        $startpageid = $DB->get_field(
            'lesson_pages',
            'id',
            array(
                'lessonid' => $lessonid,
                'prevpageid' => 0
            ),
            IGNORE_MULTIPLE
        );

        $data['startpageid'] = $startpageid;

        $sqllastseen = "SELECT nextpageid FROM {lesson_branch} WHERE lessonid = :lessonid AND userid = :userid ORDER BY timeseen DESC";

        $lastseenpageid = $DB->get_record_sql($sqllastseen,
            array(
                'lessonid' => $lessonid,
                'userid' => $userid
            ),
            IGNORE_MULTIPLE
        );

        $data['lastseenpageid'] = 0;
        if ($lastseenpageid && $lastseenpageid->nextpageid > 0) {
            $data['lastseenpageid'] = $lastseenpageid->nextpageid;
        }

        $transaction->allow_commit();

        return $data;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            array(
                'startpageid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'lastseenpageid' => new external_value(PARAM_INT, 'Standard Moodle primary key.')
            )
        );
    }

}