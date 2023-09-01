<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");

class get_userid extends \external_api {

//Defining parameters
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'userid' => new external_value(PARAM_INT, 'User id'),
        ));
    }



//Implement the external function
     /**
     * Create groups
     * @param array $groups array of group description arrays (with keys groupname and courseid)
     * @return array of newly created groups
     */
    public static function execute($userid) {
        global $CFG, $DB;
        //require_once("$CFG->dirroot/user/lib.php");

        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);

        //$transaction = $DB->start_delegated_transaction(); //If an exception is thrown in the below code, all DB queries in this code will be rollback.

        $user = array();

        $userFetch = $DB->get_record('user', ['id' => $params['userid']]);

        if ($userFetch){
            $user['firstname'] = $userFetch->firstname;
            $user['lastname'] = $userFetch->lastname;
        }else{
            throw new invalid_parameter_exception('User does not exist');
        }

        return $user;
    }

    //Execute_returns()
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
                'firstname' => new external_value(PARAM_TEXT, 'user firstname'),
                'lastname' => new external_value(PARAM_TEXT, 'user lastname'),
        ]);
    }

}