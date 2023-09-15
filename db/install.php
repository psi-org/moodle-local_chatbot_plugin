<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');

function xmldb_local_botmanager_install() {
    global $DB;

    $transaction = $DB->start_delegated_transaction();

    try {

        $customfieldcategoryid = 1;

        if (!$DB->record_exists('user_info_field', ['shortname' => 'prefix'])) {
            $prefixfield = (object) [
                    'shortname' => 'prefix',
                    'name' => 'Prefix',
                    'datatype' => 'text',
                    'description' => 'User prefix field',
                    'categoryid' => 1,
                    'sortorder' => 0,
                    'locked' => 0,
                    'defaultdata' => '',
                    'param1' => 30, // Display size.
                    'param2' => 255 // Max length.
            ];

            $DB->insert_record('user_info_field', $prefixfield);
        }

        if (!$DB->record_exists('customfield_category', ['name' => 'Moodle Chatbot Plugin'])) {
            $botmodefield_category = (object) [
                    'name' => 'Moodle Chatbot Plugin',
                    'descriptionformat' => 0,
                    'sortorder' => 0,
                    'timecreated' => time(),
                    'timemodified' => time(),
                    'component' => 'core_course',
                    'area' => 'course',
                    'itemid' => 0,
                    'contextid' => 1
            ];

            $customfieldcategoryid = $DB->insert_record('customfield_category', $botmodefield_category);
        }

        if (!$DB->record_exists('customfield_field', ['shortname' => 'botmode'])) {
            $botmodefield = (object) [
                    'shortname' => 'botmode',
                    'name' => 'BotMode',
                    'type' => 'select',
                    'description' => 'Course Bot Mode field',
                    'categoryid' => $customfieldcategoryid,
                    'configdata' => '{"required":"0","uniquevalues":"0","options":"No\r\nYes","defaultvalue":"No","locked":"0","visibility":"2"}',
                    'timecreated' => time(),
                    'timemodified' => time()
            ];

            $DB->insert_record('customfield_field', $botmodefield);
        }

        $transaction->allow_commit();
    }catch (Exception $e){
        $DB->rollback_delegated_transaction($transaction, $e);
    }



    return true;
}
