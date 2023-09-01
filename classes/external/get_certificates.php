<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;
use external_multiple_structure;
use info_module;
use context_course;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");
require_once($CFG->libdir . "/moodlelib.php");
require_once($CFG->libdir . "/modinfolib.php");
require_once($CFG->dirroot . "/availability/classes/info_module.php");

class get_certificates extends \external_api
{
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */

    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            [
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'courseid' => new external_value(PARAM_INT, 'Course ID')
            ]
        );
    }


    /**
     * Get Certificates.
     *
     * @param int $userid
     * @param int $courseid
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($userid, $courseid)
    {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $data = array();

        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $fullname = fullname($user);

        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        $certificates = $DB->get_records('customcert', array('course' => $courseid), '', 'id, name, intro');

        // Get Course Module ID.
        $cert_module_id = $DB->get_field('modules', 'id', array('name' => 'customcert'), IGNORE_MULTIPLE);

        foreach ($certificates as $certificate) {

            // Restriction.
            $cmid = $DB->get_field('course_modules', 'id', array('module' => $cert_module_id, 'instance' => $certificate->id), IGNORE_MULTIPLE);
            $modinfo = get_fast_modinfo($course, $userid);
            $cminfo = $modinfo->get_cm($cmid);

            // Get availability information.
            $ci = new \core_availability\info_module($cminfo);

            if ($ci->is_available($cminfo->availableinfo, true,
                $userid, $cminfo->modinfo)) {

                $data[$certificate->id]['title'] = '';//$certificate->name;
                $data[$certificate->id]['description'] = '';//$certificate->intro;
                $data[$certificate->id]['fullname'] = $fullname;
                $data[$certificate->id]['course'] = '';//$course->shortname;
                $data[$certificate->id]['completiondate'] = '';

                $data[$certificate->id]['titleposx'] = 0;
                $data[$certificate->id]['titleposy'] = 0;
                $data[$certificate->id]['descriptionposx'] = 0;
                $data[$certificate->id]['descriptionposy'] = 0;
                $data[$certificate->id]['fullnameposx'] = 0;
                $data[$certificate->id]['fullnameposy'] = 0;
                $data[$certificate->id]['courseposx'] = 0;
                $data[$certificate->id]['courseposy'] = 0;
                $data[$certificate->id]['completiondateposx'] = 0;
                $data[$certificate->id]['completiondateposy'] = 0;

                /*
                $activityimg = $DB->get_field(
                    'local_kassai_image_url',
                    'url',
                    array('item_id' => $cmid, 'type' => 'mod'),
                    IGNORE_MULTIPLE
                );

                --$data[$certificate->id]['activityimg'] = $activityimg;
                */

                // Get first page.
                $sql_pageid = "SELECT cp.id FROM {customcert} AS c 
                    JOIN {customcert_pages} AS cp ON cp.templateid = c.templateid
                    WHERE c.id = :certificateid";

                if ($pageid = $DB->get_field_sql($sql_pageid, array('certificateid' => $certificate->id), IGNORE_MULTIPLE)) {
                    if ($elements = $DB->get_records('customcert_elements', array('pageid' => $pageid))) {
                        foreach ($elements as $element) {

                            if ($element->name == 'title' && $element->element == 'text') {
                                $data[$certificate->id]['title'] = $element->data;
                                $data[$certificate->id]['titleposx'] = $element->posx;
                                $data[$certificate->id]['titleposy'] = $element->posy;
                            }

                            if ($element->name == 'description' && $element->element == 'text') {
                                $data[$certificate->id]['description'] = $element->data;
                                $data[$certificate->id]['descriptionposx'] = $element->posx;
                                $data[$certificate->id]['descriptionposy'] = $element->posy;
                            }


                            if ($element->name == 'fullname' && $element->element == 'studentname') {
                                $data[$certificate->id]['fullnameposx'] = $element->posx;
                                $data[$certificate->id]['fullnameposy'] = $element->posy;
                            }

                            if ($element->name == 'course' && $element->element == 'text') {
                                $data[$certificate->id]['course'] = $element->data;
                                $data[$certificate->id]['courseposx'] = $element->posx;
                                $data[$certificate->id]['courseposy'] = $element->posy;
                            } else if ($element->name == 'course' && $element->element == 'coursename') {
                                $data[$certificate->id]['course'] = $course->shortname;;
                                $data[$certificate->id]['courseposx'] = $element->posx;
                                $data[$certificate->id]['courseposy'] = $element->posy;
                            }

                            if ($element->name == 'completiondate' && $element->element == 'date') {
                                //TODO esta query fue cambiada, pero, debería ser un select? o deberíamos select a timecompleted y luego modificar valores si es null? esto porque el query esta ligado con mariadb, en otra BD no funcionará
                                $sql_timecompleted = "SELECT IFNULL(DATE_FORMAT(DATE_ADD(FROM_UNIXTIME(timecompleted), INTERVAL 0 SECOND), '%d-%m-%Y'), '') FROM {course_completions} WHERE course = :courseid AND userid = :userid";
                                if ($timecompleted = $DB->get_field_sql($sql_timecompleted, array('courseid' => $courseid, 'userid' => $userid), IGNORE_MULTIPLE)) {
                                    $data[$certificate->id]['completiondate'] = $timecompleted;
                                }
                                $data[$certificate->id]['completiondateposx'] = $element->posx;
                                $data[$certificate->id]['completiondateposy'] = $element->posy;
                            }

                            if ($element->name == 'image' && $element->element == 'bgimage') {

                                $course_content = context_course::instance($courseid);

                                $fs = get_file_storage();
                                $files = $fs->get_area_files($course_content->id, 'mod_customcert', 'image', 0, '', false);
                                $file = reset($files);

                                if ($file) {
                                    $moodle_url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                                        $file->get_filearea(), 0, $file->get_filepath(), $file->get_filename());

                                    //$data[$certificate->id]['activityimg'] = $moodle_url->out();
                                }

                            }

                        }
                    }
                }

            }
        }

        $transaction->allow_commit();

        return $data;
    }


    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns():external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'title' => new external_value(PARAM_TEXT, 'Certificate title.'),
                    'description' => new external_value(PARAM_RAW, 'Certificate description.'),
                    'fullname' => new external_value(PARAM_TEXT, 'User Fullname.'),
                    'course' => new external_value(PARAM_TEXT, 'Course Shortname.'),
                    'completiondate' => new external_value(PARAM_TEXT, 'Course Completion date'),
                    'titleposx' => new external_value(PARAM_INT, 'Title Element position X'),
                    'titleposy' => new external_value(PARAM_INT, 'Title Element position X'),
                    'descriptionposx' => new external_value(PARAM_INT, 'Description Element position X'),
                    'descriptionposy' => new external_value(PARAM_INT, 'Description Element position X'),
                    'fullnameposx' => new external_value(PARAM_INT, 'Fullname Element position X'),
                    'fullnameposy' => new external_value(PARAM_INT, 'Fullname Element position X'),
                    'courseposx' => new external_value(PARAM_INT, 'Course Element position X'),
                    'courseposy' => new external_value(PARAM_INT, 'Course Element position X'),
                    'completiondateposx' => new external_value(PARAM_INT, 'Completion date Element position X'),
                    'completiondateposy' => new external_value(PARAM_INT, 'Completion date Element position X'),
                    //'activityimg' => new external_value(PARAM_RAW, 'URL of the activity image.')
                )
            )
        );
    }


}