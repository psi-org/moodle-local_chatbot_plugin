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
require_once($CFG->dirroot . '/local/botmanager/lib.php');

class enrol_user extends \external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters  {
        return new external_function_parameters(
            [
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'courseid' => new external_value(PARAM_INT, 'Course ID')
            ]
        );
    }

    /**
     * Enrol User.
     *
     * @param int $userid
     * @param int $courseid
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($userid, $courseid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $status = 'Not Enrolled';

        $params = array('courseid' => $courseid, 'userid' => $userid);

        // Check if user is enrolled.
        $enrolled_sql = "SELECT ue.id FROM {user_enrolments}  AS ue
            JOIN {enrol} AS e ON ue.enrolid = e.id
            WHERE e.courseid = :courseid AND ue.userid = :userid";

        if (!$DB->get_records_sql($enrolled_sql, $params)) {
            local_botmanager_api_enrol_student($userid, $courseid);
            $status = 'Enrolled';
        } else {
            $status = 'The user was already registered.';
        }

        $transaction->allow_commit();

        return array('status' => $status);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns(): external_single_structure{
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.')
            )
        );
    }
}