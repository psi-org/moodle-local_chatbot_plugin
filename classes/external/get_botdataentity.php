<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;
use external_multiple_structure;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/botmanager/lib.php');

class get_botdataentity extends \external_api {

    //Defining parameters
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'realid' => new external_value(PARAM_TEXT, 'RealId'),
        ));
    }



//Implement the external function
    /**
     * Create groups
     * @param array $groups array of group description arrays (with keys groupname and courseid)
     * @return array of newly created groups
     */
    public static function execute($realid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['realid' => $realid]);

        //$transaction = $DB->start_delegated_transaction(); //If an exception is thrown in the below code, all DB queries in this code will be rollback.

        $result = array();
        $query = "SELECT * FROM {local_botmanager_dataentity} WHERE realid = :realid";
        $tableFetch = $DB->get_record_sql($query, ['realid' => $params['realid']]);

        $result['id'] = -1;
        $result['realid'] = "";
        $result['document'] = "";
        $result['createdtime'] = "";
        $result['timestamp'] = "";

        if ($tableFetch){
            $result['id'] = $tableFetch->id;
            $result['realid'] = $tableFetch->realid;
            $result['document'] = $tableFetch->document;
            $result['createdtime'] = $tableFetch->createdtime;
            $result['timestamp'] = $tableFetch->timestamp;
        }

        return $result;
    }

    //Execute_returns()
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'id'),
            'realid' => new external_value(PARAM_TEXT, 'internal id used by the chatbot'),
            'document' => new external_value(PARAM_TEXT, 'internal id used by the chatbot'),
            'createdtime' => new external_value(PARAM_TEXT, 'created time'),
            'timestamp' => new external_value(PARAM_TEXT, 'timestamp'),
        ]);
    }

}