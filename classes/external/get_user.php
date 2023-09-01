<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die;

//require_once("{$CFG->libdir}/externallib.php");

class get_user extends \external_api {
        /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'Category id to find user',VALUE_DEFAULT, 0),
                'mobile_phone' => new external_value(PARAM_INT, 'Category id to find user',VALUE_DEFAULT, 0),
                'whatsapp_id' => new external_value(PARAM_INT, 'Category id to find user',VALUE_DEFAULT, 0),
                'email' => new external_value(PARAM_TEXT, 'Category id to find user',VALUE_DEFAULT, '')
            )
        );
    }

    /**
     * Create groups
     * @param array $groups array of group description arrays (with keys groupname and courseid)
     * @return array of newly created groups
     */
    public static function execute($userid = 0, $mobile_phone = 0, $whatsapp_id = 0, $email = '') {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'userid' => $userid,
                'mobile_phone' => $mobile_phone,
                'whatsapp_id' => $whatsapp_id,
                'email' => $email
            ]
        );
        
        $userid = $params['userid'];

        $query_params = array();

        $transaction = $DB->start_delegated_transaction(); //If an exception is thrown in the below code, all DB queries in this code will be rollback.

        $sql_query = "SELECT * FROM mdl_user WHERE";

        if ($userid || $mobile_phone || $whatsapp_id || $email) {

            $and = false;

            if ($userid) {
                $query_params['userid'] = $params['userid'];
                $sql_query .= " id = :userid";
                $and = true;
            }

            if ($mobile_phone) {
                $query_params['mobile_phone'] = $params['mobile_phone'];
                if ($and) {
                    $sql_query .= " AND";
                }
                $sql_query .= " phone1 = :mobile_phone";
                $and=true;
            }

            if ($email) {
                $query_params['email'] = $params['email'];
                if ($and) {
                    $sql_query .= " AND";
                }
                $sql_query .= " email = :email";
                // $and = true;
            }

            $sql_query .= " AND deleted <> 1 AND suspended <> 1";
        } else {
            throw new invalid_parameter_exception('You need to pass at least one of these parameters: userid, mobile_phone, whatsapp_id, or email.');
        }

        $data = $DB->get_record_sql($sql_query, $query_params,IGNORE_MULTIPLE);

        if($data){
            profile_load_data($data);

            $data->userid = $data->id;
            $data->prefix = $data->profile_field_prefix;
            $data->mobile_phone = $data->phone1;
            $data->verified = 1;
            if (empty($data->firstname) || empty($data->lastname) || empty($data->email)){
                $data->verified = 0;
            }

        }else{
            $data = new \stdClass();
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
                'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'auth' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'confirmed' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'policyagreed' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'deleted' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'suspended' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'username' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'password' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'idnumber' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'firstname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'lastname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'email' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'emailstop' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'prefix' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'mobile_phone' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'address' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'city' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'country' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'lang' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'timezone' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'firstaccess' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'lastaccess' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'lastlogin' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'currentlogin' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'lastip' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'secret' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'picture' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'description' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'descriptionformat' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'mailformat' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'maildigest' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'maildisplay' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'autosubscribe' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'trackforums' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'timecreated' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'timemodified' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'imagealt' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'middlename' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'alternatename' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'verified' => new external_value(PARAM_INT, 'Standard Moodle primary key.')
            )
            
            // array(
            //     'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'auth' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'confirmed' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'policyagreed' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'deleted' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'suspended' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'username' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'password' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'idnumber' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'firstname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'lastname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'email' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'emailstop' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'address' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'city' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'country' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'lang' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'timezone' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'firstaccess' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'lastaccess' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'lastlogin' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'currentlogin' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'lastip' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'secret' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'picture' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'url' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'description' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'descriptionformat' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'mailformat' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'maildigest' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'maildisplay' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'autosubscribe' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'trackforums' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'timecreated' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'timemodified' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'imagealt' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'middlename' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'alternatename' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'health_unit_id' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     // 'health_unit' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'municipality' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     // 'province_id' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'province' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'profession_type' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'profession_type_name' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'professional_number' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'prefix' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'mobile_phone' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'whatsappid' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'verified' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'isdeleted' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'gender_id' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
            //     'gender' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
            //     'dateofbirth' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.')
            //     // 'projects' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.')
            // )
        );
    }

}