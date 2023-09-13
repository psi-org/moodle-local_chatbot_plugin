<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;
use external_multiple_structure;
use core_date;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir. "/externallib.php");

class create_user extends \external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters  {
        return new external_function_parameters(
            [
                'firstname' => new external_value(PARAM_TEXT, 'First Name'),
                'lastname' => new external_value(PARAM_TEXT, 'Last Name'),
                'prefix' => new external_value(PARAM_INT, 'Prefix'),
                'mobilephone' => new external_value(PARAM_INT, 'Mobile Phone'),
                'email' => new external_value(PARAM_TEXT, 'Email', VALUE_DEFAULT, ''),
                'password' => new external_value(PARAM_RAW, 'Password', VALUE_DEFAULT, '000000'),
                'country' => new external_value(PARAM_TEXT, 'Country', VALUE_DEFAULT, ''),
                'lang' => new external_value(PARAM_TEXT, 'Lang', VALUE_DEFAULT, 'en'),
                'timezone' => new external_value(PARAM_TEXT, 'User Timezone', VALUE_DEFAULT, null)
            ]
        );
    }

    /**
     * Create User.
     *
     * @param $firstname
     * @param $lastname
     * @param $prefix
     * @param $mobilephone
     * @param string $email
     * @param $password
     * @param $country
     * @param $lang
     * @param $timezone
     * @return array object
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function execute($firstname, $lastname, $prefix, $mobilephone, $email = '',
                                       $password, $country, $lang, $timezone): array {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');

        $availablelangs  = get_string_manager()->get_list_of_translations();

        $transaction = $DB->start_delegated_transaction();

        $user = array();

        // Make sure that the username, firstname and lastname are not blank.
        foreach (array($firstname,$lastname) as $fieldname) {
            if (trim($fieldname) === '') {
                throw new invalid_parameter_exception('The field '.$fieldname.' cannot be blank');
            }
        }

        $user['firstname'] = $firstname;
        $user['lastname'] = $lastname;

        // Make sure that the username doesn't already exist.
        if ($DB->record_exists('user', array('username' => $mobilephone, 'mnethostid' => $CFG->mnet_localhost_id))) {
            throw new invalid_parameter_exception('Mobile Phone already exists: '.$mobilephone);
        }

        $user['username'] = (string)$mobilephone;

        $user['phone1'] = $prefix.$mobilephone;

        $user['auth'] = 'manual';

        // Make sure lang is valid.
        if (empty($availablelangs[$lang])) {
            throw new invalid_parameter_exception('Invalid language code: '.$lang);
        }

        $user['lang'] = $lang;

        $user['confirmed'] = true;
        $user['mnethostid'] = $CFG->mnet_localhost_id;
        $user['policyagreed'] = 1;

        // Email.
        if ($email) {
            // Make sure that the email doesn't already exist.
            if ($DB->record_exists('user', array('email' => $email, 'mnethostid' => $CFG->mnet_localhost_id))) {
                throw new invalid_parameter_exception('Email already exists: '.$email);
            }
            $user['email'] = $email;
        } else {
            $user['email'] = $mobilephone.'@moodlechatbotapp.com';
        }

        $user['country'] = $country;

        $user['password'] = hash_internal_user_password($password);


        $timezones = core_date::get_list_of_timezones();

        if ($timezone) {
            if (!in_array($timezone, $timezones)) {
                throw new invalid_parameter_exception('Invalid Timezone value');
            }
        } else {
            $timezone = '99';
        }

        $user['timezone'] = $timezone;

        // Create the user data now!
        $user['id'] = user_create_user($user, false, false);

        // Save prefix in Custom field    

        $userObj = $DB->get_record("user", array('id'=>$user['id']));
        
        profile_load_data($userObj);
        
        $userObj->profile_field_prefix = $prefix;

        profile_save_data($userObj);

        $transaction->allow_commit();

        $usercreated_ojt = new \lang_string('usercreated', 'local_botmanager', null, $lang);
        $usercreated = $usercreated_ojt->out();

        return array('status' => $usercreated, 'userid' => $user['id']);
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure  {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.')
            )
        );
    }
}