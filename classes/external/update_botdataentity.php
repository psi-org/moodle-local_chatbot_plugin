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

class update_botdataentity extends \external_api {

    //Defining parameters
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'realid' => new external_value(PARAM_TEXT, 'RealId'),
            'document' => new external_value(PARAM_TEXT, 'Document'),
            'createdtime' => new external_value(PARAM_TEXT, 'Created Time'),
            'timestamp' => new external_value(PARAM_TEXT, 'Time Stamp'),
        ));
    }



//Implement the external function
    /**
     * Create groups
     * @param array $groups array of group description arrays (with keys groupname and courseid)
     * @return array of newly created groups
     */
    public static function execute($realid,$document,$createdtime,$timestamp) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['realid' => $realid,'document' => $document,'createdtime' => $createdtime, 'timestamp' => $timestamp]);

        $transaction = $DB->start_delegated_transaction(); //If an exception is thrown in the below code, all DB queries in this code will be rollback.

        $botdataentity_object = new \stdClass();
        $botdataentity_object->realid = $params['realid'];
        $botdataentity_object->document = $params['document'];
        $botdataentity_object->createdtime = $params['createdtime'];
        $botdataentity_object->timestamp = $params['timestamp'];


        //TODO PONERLE EL ID AL UPDATE_RECORD
        if ($DB->record_exists_sql('SELECT id from {local_botmanager_dataentity} WHERE realid = :realid', ['realid' => $params['realid']])){
            $record = $DB->get_record_sql('SELECT * FROM {local_botmanager_dataentity} WHERE realid = :realid', ['realid' => $params['realid']]);
            $botdataentity_object->id = $record->id;
            $botdataentity_object->createdtime = $record->createdtime;
            $DB->update_record('local_botmanager_dataentity', $botdataentity_object, ['id' => $record->id]);
        }else{

            $DB->insert_record('local_botmanager_dataentity', $botdataentity_object);
        }

        $transaction->allow_commit();

        return $botdataentity_object;
    }

    //Execute_returns()
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            //'id' => new external_value(PARAM_INT, 'id'),
            'realid' => new external_value(PARAM_TEXT, 'internal id used by the chatbot'),
            'document' => new external_value(PARAM_TEXT, 'internal id used by the chatbot'),
            'createdtime' => new external_value(PARAM_TEXT, 'created time'),
            'timestamp' => new external_value(PARAM_TEXT, 'timestamp'),
        ]);
    }

}