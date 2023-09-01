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

class update_user extends \external_api {

        /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'firstname' => new external_value(PARAM_TEXT, 'First Name'),
                'lastname' => new external_value(PARAM_TEXT, 'Last Name'),
                'email' => new external_value(PARAM_TEXT, 'Email', VALUE_DEFAULT, ''),
                'password' => new external_value(PARAM_RAW, 'Password', VALUE_DEFAULT, '0000'),
                'gender' => new external_value(PARAM_INT, '1 => Male, 2 => Female, 3 => Other', VALUE_DEFAULT, 3),
                'dateofbirth' => new external_value(PARAM_TEXT, 'Date of Birth i.e. 1986-02-25', VALUE_DEFAULT, null),
                'lang' => new external_value(PARAM_TEXT, 'Lang', VALUE_DEFAULT, 'en'),
            )
        );
    }

    /**
     * Update User.
     *
     * @param int $userid
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($userid, $firstname, $lastname, $email = '', $password, $gender, $dateofbirth, $lang) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/user/lib.php');

        $availablelangs  = get_string_manager()->get_list_of_translations();
        $transaction = $DB->start_delegated_transaction();

        $user = array();

        // Make sure userid exists
        if($userid){
            if($DB->record_exists('user', array('id' => $userid))){
                $user['id'] = $userid;
            }else{
                throw new invalid_parameter_exception('The user id '.$userid.' does not exist in the current context');
            }
        }else{
            throw new invalid_parameter_exception('The field UserID cannot be blank');
        }

        // Make sure that the username, firstname and lastname are not blank.
        foreach (array($firstname,$lastname) as $fieldname) {
            if (trim($fieldname) === '') {
                throw new invalid_parameter_exception('The field '.$fieldname.' cannot be blank');
            }
        }

        $user['firstname'] = $firstname;
        $user['lastname'] = $lastname;

        // Make sure lang is valid.
        if (empty($availablelangs[$lang])) {
            throw new invalid_parameter_exception('Invalid language code: '.$lang);
        }

        $user['lang'] = $lang;

        $user['confirmed'] = true;
        $user['policyagreed'] = 1;

        // Email.
        if ($email) {
            $user['email'] = $email;
        } else {
            $user['email'] = $mobilephone.'@kiira.com';
        }

        $user['password'] = hash_internal_user_password($password);

        // Update the user data now!
        user_update_user($user, false, false);

        $transaction->allow_commit();

        $userupdated_ojt = new \lang_string('userupdatted', 'local_botmanager', null, $lang);
        $userupdated = $userupdated_ojt->out();

        return array('status' => $userupdated, 'userid' => $userid);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.')
            )
        );
    }

}
