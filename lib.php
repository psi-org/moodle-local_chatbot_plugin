<?php

//These functions works to classes/external
global $CFG;
require_once($CFG->dirroot . "/question/engine/lib.php");

class local_botmanager_question_utils extends question_utils
{

}

function local_botmanager_api_enrol_student($userid, $courseid)
{

    global $DB;

    // Validate user.
    if (!$DB->record_exists('user', array('id' => $userid))) {
        throw new invalid_parameter_exception('Invalid User.');
    }

    // Validate course.
    if (!$DB->record_exists('course', array('id' => $courseid))) {
        throw new invalid_parameter_exception('Invalid Course.');
    }

    // Insert in Role Assignments.
    $role_assignments = new stdClass();

    $student = $DB->get_field('role', 'id', array('shortname' => 'student'), IGNORE_MULTIPLE);

    $role_assignments->roleid = $student;

    $context = context_course::instance($courseid);

    $role_assignments->contextid = $context->id;
    $role_assignments->userid = $userid;
    $role_assignments->timemodified = time();
    $role_assignments->modifierid = 2; // TODO check
    $role_assignments->itemid = 0;
    $role_assignments->sortorder = 0;
    $DB->insert_record('role_assignments', $role_assignments);

    // Insert in User Enrollments.
    $user_enrolments = new stdClass();
    $user_enrolments->status = 0;

    $enrolid = $DB->get_field('enrol', 'id', array('courseid' => $courseid, 'enrol' => 'manual'), IGNORE_MULTIPLE);

    $user_enrolments->enrolid = $enrolid;
    $user_enrolments->userid = $userid;
    $user_enrolments->timestart = time();
    $user_enrolments->timeend = 0;
    $user_enrolments->modifierid = 2; // TODO check
    $user_enrolments->timecreated = time();
    $user_enrolments->timemodified = time();
    $DB->insert_record('user_enrolments', $user_enrolments);

}

// view module
function local_botmanager_view_module($modulename, $instanceid, $userid)
{

    global $DB, $CFG;

    require_once($CFG->libdir . '/completionlib.php');

    $cm = get_coursemodule_from_instance($modulename, $instanceid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm, $userid);
}

function local_botmanager_get_next_question($quiz, $questionusageid, $userid)
{

    global $DB;

    $sql_next_question = "SELECT queans.id, q.name AS activitytitle, slot.id AS slot, que.id AS questionid, que.qtype, que.name , que.questiontext, queans.id AS answerid, queans.answer, queans.fraction, queans.feedback, quea.id AS attempt 
                FROM mdl_quiz_slots slot
                JOIN mdl_quiz q ON q.id = slot.quizid
                JOIN mdl_question_references qr ON qr.component = 'mod_quiz' AND qr.questionarea = 'slot' AND qr.itemid = slot.id
                JOIN mdl_question_bank_entries qbe ON qbe.id = qr.questionbankentryid
                JOIN mdl_question_versions qv ON qv.questionbankentryid = qbe.id
                JOIN mdl_question que ON que.id = qv.questionid
                JOIN mdl_question_answers queans ON queans.question = que.id
                JOIN mdl_question_attempts quea ON que.id = quea.questionid 
                JOIN mdl_question_attempt_steps queas ON quea.id = queas.questionattemptid
                WHERE slot.quizid = :quizid AND queas.userid = :userid AND quea.questionusageid = :questionusageid ORDER BY slot.slot";

    $questions = $DB->get_records_sql($sql_next_question,
        array('quizid' => $quiz, 'questionusageid' => $questionusageid, 'userid' => $userid));

    $next_question = array();
    $slot = 0;
    foreach ($questions as $question) {
        $next_question['activitytitle'] = $question->activitytitle;

        if (($slot == 0 || $slot == $question->slot) && !$DB->record_exists('question_attempt_steps', array('questionattemptid' => $question->attempt, 'state' => 'complete'))) {
            $next_question['questionname'] = $question->name;
            $next_question['questiontext'] = strip_tags($question->questiontext);
            $next_question['questiontype'] = $question->qtype;
            $next_question['questionattemptid'] = $question->attempt;

            $currentpage = $DB->get_field('quiz_attempts', 'currentpage', array('uniqueid' => $questionusageid), IGNORE_MULTIPLE);
            $next_question['currentpage'] = $currentpage;

            $next_question['answers'][$question->answerid]['answerid'] = $question->answerid;
            $next_question['answers'][$question->answerid]['answer'] = strip_tags($question->answer);
            $next_question['answers'][$question->answerid]['iscorrectanswer'] = intval($question->fraction);
            $next_question['answers'][$question->answerid]['feedback'] = strip_tags($question->feedback);

            $slot = $question->slot;
        }

    }
    /*
    $next_question = array();
    $next_question['activitytitle'] = "test activity title";
    $next_question['questionname'] = "test question name";
    $next_question['questiontext'] = "test question text name";
    $next_question['questiontype'] = "test question type";
    $next_question['questionattemptid'] = 1;
    $next_question['currentpage'] = 1;
    $next_question['answers'][0]['answerid'] = 1;
    $next_question['answers'][0]['answer'] = "a";
    $next_question['answers'][0]['iscorrectanswer'] = 100;
    $next_question['answers'][0]['feedback'] = "test feedback";
    */

    return $next_question;
}

function local_botmanager_islastquestion($quiz, $questionid)
{
    global $DB;

    // Get next question.
    $sql_next_question = "SELECT slot.id, slot.slot, q.id as questionid
                FROM mdl_quiz_slots slot
                LEFT JOIN mdl_question_references qr ON qr.component = 'mod_quiz'
                            AND qr.questionarea = 'slot' AND qr.itemid = slot.id
                LEFT JOIN mdl_question_bank_entries qbe ON qbe.id = qr.questionbankentryid
                LEFT JOIN mdl_question_versions qv ON qv.questionbankentryid = qbe.id
                LEFT JOIN mdl_question q ON q.id = qv.questionid
                WHERE slot.quizid = :quizid
                ORDER BY slot.slot";

    $questions = $DB->get_records_sql($sql_next_question,
        array('quizid' => $quiz));

    $last = end($questions);

    if (isset($last->questionid) && $last->questionid == $questionid) {
        return true;
    }
    return false;

}

function setIntegerValue($value)
{
    if ($value == null) {
        return 0;
    }
    return $value;
}

function setDateTime($unix_timestamp)
{
    if ($unix_timestamp == null) {
        return date('Y-m-d H:i:s.v', 0);
    }
    return date('Y-m-d H:i:s.v', $unix_timestamp);;
}

function setActivityTypeId($dbActivityTypes, $typeToSearch)
{
    foreach ($dbActivityTypes as $activityType) {
        if ($activityType->name === $typeToSearch) {
            return $activityType->id;
        }
    }
    return -1;
}

function isFirst($itemPosition): int
{
    if ($itemPosition == 0) {
        return 1;
    }
    return 0;
}

function isLast($arrayToCount, $itemPosition): int
{
    if (count($arrayToCount) == $itemPosition + 1) {
        return 1;
    }
    return 0;

}


function getGradeItem($userGrades, $activityIDToSearch)
{
    foreach ($userGrades['usergrades'] as $userGrade) {
        foreach ($userGrade['gradeitems'] as $gradeitem) {
            if ($gradeitem['cmid'] == $activityIDToSearch) {
                return $gradeitem;
            }
        }
    }
}

function getGradeItem2($userGradesDB, $activityNameToSearch)
{
    foreach ($userGradesDB as $userGrade) {
        if ($userGrade->itemname == $activityNameToSearch) {
            return $userGrade;
        }
    }
}

function keepHtmlText($string)
{
    if ($string === null || $string === '') {
        return '';
    }
    $dom = new DOMDocument();
    $dom->loadHTML($string);

// Get all the <p> elements in the document
    $paragraphs = $dom->getElementsByTagName('p');

// Variable to store the extracted text
    $extracted_text = '';

// Extract the text from each <p> element
    foreach ($paragraphs as $p) {
        $text = trim($p->textContent);
        if (!empty($text)) {
            if ($p->getElementsByTagName('br')->length > 0) {
                // Replace <br> tags with escaped line breaks (\n)
                $text = str_replace('<br>', '\n', $text);
            }
            $extracted_text .= $text . ' '; // Add a space after each paragraph
        }
    }

// Output the extracted text with line breaks and spaces between paragraphs
    return $extracted_text;
}

function getSectionNumberActivty($index_of_activity_based_on_section, $index_to_match)
{
    if ($index_of_activity_based_on_section == $index_to_match) {
        return 1;
    }
    return 0;
}

function get_lesson_pages_contents($string)
{
    $dom = new DOMDocument();
    $dom->loadHTML($string);

    $ptag = $dom->getElementsByTagName('p');

    $text_separated = array();

    foreach ($ptag as $pharagraph) {
        $imgtags = $pharagraph->getElementsByTagName('img');
        $videotags = $pharagraph->getElementsByTagName('video');
        $atags = $pharagraph->getElementsByTagName('a');

        if (count($atags) > 0) {
            $videolink = $atags[0]->getAttribute('href');
            $object = new stdClass();
            $object->type = 'video';
            $object->content = $videolink;
            $text_separated[] = $object;

        }

        if (count($videotags) > 0) {
            $sourcetags = $videotags[0]->getElementsByTagName('source');
            $videolink = $sourcetags[0]->getAttribute('src');
            $object = new stdClass();
            $object->type = 'video';
            $object->content = $videolink;
            $text_separated[] = $object;
        }

        if (count($imgtags) > 0) {
            $imglink = $imgtags[0]->getAttribute('src');
            $objecimg = new stdClass();
            $objecimg->type = 'image';
            $objecimg->content = $imglink;
            $text_separated[] = $objecimg;
        }

        if ($pharagraph->textContent != null || $pharagraph->textContent != '' && $videotags == 0 ) {
            $objecttext = new stdClass();
            $objecttext->type = 'text';
            $objecttext->content = $pharagraph->textContent;
            $text_separated[] = $objecttext;
        }

    }
    return $text_separated;
}

function separateStringWithPunctuation($inputString)
{
    $stringLength = 300;
    $resultArray = array();
    $currentChunk = '';
    $punctuationMarks = array('.', ',', ';', ':', '!', '?');

    // Split the input string into words using whitespace as delimiter
    $words = preg_split('/\s+/', $inputString, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($words as $word) {
        // Check if adding the current word exceeds the 300-character limit

        if (preg_match('/<>(.*?)<>/', $word)) {
            $resultArray[] = trim($currentChunk);
            $currentChunk = '';
            $word = str_replace('<>', '', $word);
            $resultArray[] = $word;
            continue;
        }

        if (strlen($currentChunk) + strlen($word) <= $stringLength) {
            $currentChunk .= $word . ' '; // Add the word to the current chunk
        } else {
            // Find the last punctuation mark in the chunk, if available
            $lastPunctuationIndex = -1;
            foreach ($punctuationMarks as $punctuation) {
                $lastPunctuationIndex = strrpos($currentChunk, $punctuation);
                if ($lastPunctuationIndex !== false) {
                    break;
                }
            }

            // Extract the part of the chunk that ends with punctuation (if found)
            if ($lastPunctuationIndex !== false) {
                $resultArray[] = trim(substr($currentChunk, 0, $lastPunctuationIndex + 1));
                $currentChunk = substr($currentChunk, $lastPunctuationIndex + 1);
            } else {
                // If no punctuation found, simply split the chunk at 300 characters
                $resultArray[] = trim(substr($currentChunk, 0, $stringLength));
                $currentChunk = '';
            }

            // Add the current word to the new chunk
            $currentChunk .= $word . ' ';
        }
    }

    // Add any remaining content to the result array
    if (!empty($currentChunk)) {
        $resultArray[] = trim($currentChunk);
    }

    return $resultArray;
}

function get_lesson_pages_whole_string($array): string
{
    $string = '';
    foreach ($array as $lesson_page) {
        if ($lesson_page->type != 'text') {
            $lesson_page->content = '<>' . $lesson_page->content . '<>';
        }
        $string = $string . ' ' . $lesson_page->content;
    }
    return $string;
}

;

function get_lesson_content_structures($lesson_texts)
{
    $objects = array();
    //$pattern = '/^(https?|ftp):\/\/[^\s\/$.?#].[^\s]*$/i';
    $pattern1 = '/@@PLUGINFILE@@(.*)/i';
    $pattern2 = '/@@PLUGINFILE@@([^?]+)/i';

    foreach ($lesson_texts as $lesson_text) {
        $content = new stdClass();
        $content->type = 'text';
        $content->content = $lesson_text;

        if (preg_match($pattern1, $lesson_text)) {
            preg_match_all($pattern2, $lesson_text, $matches);

            $fileInfo = pathinfo($matches[0][0]);

            $extension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';

            //TODO more image and video extensions should be managed, here and also in the chatbot
            if ($extension == 'jpg') {
                $content->type = 'image';
                $content->content = $matches[0][0];
            }
            //TODO more image and video extensions should be managed, here and also in the chatbot
            if ($extension == 'mp4') {
                $content->type = 'video';
                $content->content = $matches[0][0];
            }

        }


        $objects[] = $content;

    }

    return $objects;

}

function filter_questions_with_latest_versions($arrayOfObjects)
{
    // Initialize an associative array to store the objects with the greatest 'id' for each 'slot'
    $uniqueSlots = [];

// Loop through the array of objects
    foreach ($arrayOfObjects as $object) {
        $slot = $object->slot;
        $id = $object->id;

        // If the slot is not already in the $uniqueSlots array or the current 'id' is greater than the existing one
        if (!isset($uniqueSlots[$slot]) || $id > $uniqueSlots[$slot]->id) {
            $uniqueSlots[$slot] = $object;
        }
    }

// Convert the associative array back to a simple array containing only the unique objects
    $uniqueObjects = array_values($uniqueSlots);

    return $uniqueObjects;
}

