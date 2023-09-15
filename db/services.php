<?php

$functions = [
        'local_botmanager_get_lesson_attemps_summary' => [
                'classname' => 'local_botmanager\external\get_lesson_attemps_summary',
                'description' => 'Get Lesson Attemps Summary',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_get_courses' => [
                'classname' => 'local_botmanager\external\get_courses',
                'description' => 'Get WhatsApp chatbot courses',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_get_user' => [
                'classname' => 'local_botmanager\external\get_user',
                'description' => 'Get Moodle user',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_create_user' => [
                'classname' => 'local_botmanager\external\create_user',
                'description' => 'Create a chatbot User',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_update_user' => [
                'classname' => 'local_botmanager\external\update_user',
                'description' => 'Update a chatbot User',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_open_lesson' => [
                'classname' => 'local_botmanager\external\open_lesson',
                'description' => 'Open lesson',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_view_lesson_page' => [
                'classname' => 'local_botmanager\external\view_lesson_page',
                'description' => 'view_lesson_page',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_get_feedback' => [
                'classname' => 'local_botmanager\external\get_feedback',
                'description' => 'Get Feedback',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_feedback_answer_question' => [
                'classname' => 'local_botmanager\external\feedback_answer_question',
                'description' => 'Feedback Answer Question',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_get_certificates' => [
                'classname' => 'local_botmanager\external\get_certificates',
                'description' => 'get certificates',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_get_activities' => [
                'classname' => 'local_botmanager\external\get_activities',
                'description' => 'get activities',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_request_quiz_attempt' => [
                'classname' => 'local_botmanager\external\request_quiz_attempt',
                'description' => 'quiz attempt',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_quiz_answer_question' => [
                'classname' => 'local_botmanager\external\quiz_answer_question',
                'description' => 'answer quiz question',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_get_quiz_attempts_summary' => [
                'classname' => 'local_botmanager\external\get_quiz_attempts_summary',
                'description' => 'quiz summary',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_view_lesson_page_contents' => [
                'classname' => 'local_botmanager\external\view_lesson_page_contents',
                'description' => 'lesson page contents',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_get_botdataentity' => [
                'classname' => 'local_botmanager\external\get_botdataentity',
                'description' => 'get user status in conversation with chatbot',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_update_botdataentity' => [
                'classname' => 'local_botmanager\external\update_botdataentity',
                'description' => 'update user status in conversation with chatbot',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_botmanager_delete_botdataentity' => [
                'classname' => 'local_botmanager\external\delete_botdataentity',
                'description' => 'delete user status in conversation with chatbot',
                'type' => 'read',
                'ajax' => true,
        ],
];

$services = [
        'Moodle_Chatbot_Plugin' => [
                'functions' => ['local_botmanager_get_lesson_attemps_summary','local_botmanager_get_courses','local_botmanager_get_user', 'local_botmanager_create_user',
                        'local_botmanager_update_user','local_botmanager_open_lesson','local_botmanager_view_lesson_page','local_botmanager_get_feedback','local_botmanager_feedback_answer_question',
                        'local_botmanager_get_certificates','local_botmanager_get_activities','local_botmanager_request_quiz_attempt','local_botmanager_quiz_answer_question','local_botmanager_get_quiz_attempts_summary',
                        'local_botmanager_view_lesson_page_contents','local_botmanager_get_botdataentity','local_botmanager_update_botdataentity','local_botmanager_delete_botdataentity'],
                'restrictedusers' => 0,
                'enabled' => 1,
        ],
];