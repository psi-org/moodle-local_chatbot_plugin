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

require_once("{$CFG->libdir}/externallib.php");

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
                'whatsappid' => new external_value(PARAM_ALPHANUM, 'Whats App ID', VALUE_DEFAULT, ''),
                'password' => new external_value(PARAM_RAW, 'Password', VALUE_DEFAULT, '000000'),
                'country' => new external_value(PARAM_TEXT, 'Country', VALUE_DEFAULT, ''),
                'gender' => new external_value(PARAM_INT, '1 => Male, 2 => Female, 3 => Other', VALUE_DEFAULT, 3),
                'dateofbirth' => new external_value(PARAM_TEXT, 'Date of Birth i.e. 1986-02-25', VALUE_DEFAULT, null),
                'lang' => new external_value(PARAM_TEXT, 'Lang', VALUE_DEFAULT, 'en'),
                'profession_type' => new external_value(PARAM_INT, '1 => Doctor, 2 => Nurse, 3 => Other', VALUE_DEFAULT, 3),
                'professional_number' => new external_value(PARAM_TEXT, 'Professional ID', VALUE_DEFAULT, ''),
                'timezone' => new external_value(PARAM_TEXT, 'User Timezone', VALUE_DEFAULT, null)
            ]
        );
    }

    /**
     * Create User.
     *
     * @param int $userid
     * @param int $courseid
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($firstname, $lastname, $prefix, $mobilephone, $email = '',
                                       $whatsappid, $password, $country, $gender, $dateofbirth, $lang,
                                       $profession_type, $professional_number, $timezone) {
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

        // Make sure auth is valid.
        /*if (empty($availableauths[$user['auth']])) {
            throw new invalid_parameter_exception('Invalid authentication type: '.$user['auth']);
        }*/

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
            $user['email'] = $mobilephone.'@kiira.com';
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

        // // Insert in Kassai User Table.
        // $local_kassai_user_record = new stdClass();
        // $local_kassai_user_record->user_id = $user['id'];
        // $local_kassai_user_record->gender_id = $gender;

        // if ($dateofbirth) {
        //     if ($dtime = DateTime::createFromFormat("Y-m-d", $dateofbirth)) {
        //         if ($timestamp = $dtime->getTimestamp()) {
        //             $local_kassai_user_record->dateofbirth = $timestamp;
        //         }
        //     } else {
        //         throw new invalid_parameter_exception('Invalid value for Date of Birth. The format is yyyy-mm-dd i.e. 1986-02-25');
        //     }
        // }

        // $local_kassai_user_record->prefix = $prefix;
        // $local_kassai_user_record->mobile_phone = $mobilephone;
        // $local_kassai_user_record->whatsappid = $whatsappid;
        // $local_kassai_user_record->profession_type = $profession_type;
        // $local_kassai_user_record->professional_number = $professional_number;
        // $local_kassai_user_record->verified = 1;
        // $local_kassai_user_record->timecreated = time();
        // $local_kassai_user_record->timemodified = time();
        // $DB->insert_record('local_kassai_user', $local_kassai_user_record);

        $transaction->allow_commit();

        $usercreated_ojt = new \lang_string('usercreated', 'local_botmanager', null, $lang);
        $usercreated = $usercreated_ojt->out();
        //$usercreated = 'User Created';

        return array('status' => $usercreated, 'userid' => $user['id']);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
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