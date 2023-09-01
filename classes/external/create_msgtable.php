<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");

class create_msgtable extends \external_api {

//Defining parameters
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'message' => new external_value(PARAM_RAW, 'Text to save into the new table'),
        ));
    }



//Implement the external function
     /**
     * Create groups
     * @param array $groups array of group description arrays (with keys groupname and courseid)
     * @return array of newly created groups
     */
    public static function execute($text) {
        global $CFG, $DB;
        //require_once("$CFG->dirroot/user/lib.php");

        $params = self::validate_parameters(self::execute_parameters(), ['message' => $text]);

        $transaction = $DB->start_delegated_transaction(); //If an exception is thrown in the below code, all DB queries in this code will be rollback.

        $message = array();


        return $message;
    }

    //Execute_returns()
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
                'firstname' => new external_value(PARAM_TEXT, 'user firstname'),
                'lastname' => new external_value(PARAM_TEXT, 'user lastname'),
        ]);
    }

}