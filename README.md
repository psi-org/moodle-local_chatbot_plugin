# Moodle Chatbot Plugin (local_botmanager)

+ Version: 1.0.0 (Sep 1/ 2023)
+ Copyright: Population Services International
+ License: [GNU General Public License, Version 3](http://www.gnu.org/licenses/gpl-3.0.html)
+ Maintained by: [PSI’s Digital Health Management](https://www.psi.org/practice-area/digital-health/) 

## Supported Moodle versions
Supports: Moodle 4.1 (LTS)
Planned: 4.2

## Summary
The Moodle Chatbot Plugin adds custom functions which allow the [Moodle Chatbot app](https://github.com/psi-org/moodle-chatbot_app) to interact with users and course data. Additionally, two custom fields are added: **_Dialing prefix_** in the user’s profile to store the country dialing code, and **_Bot Mode_** in the course settings to identify which courses should be shown to the chatbot users. Finally, It also creates the table **_mdl_local_botmanager_data_entity_**, which allows the persistence of the user’s session.

## What does it do?
The Moodle Chatbot Plugin contains fifteen functions that create the endpoints required by the companion **_Moodle Chatbot app_** to provide a learning experience in a conversation style chatbot. Users can create their accounts and enroll on chatbot compatible courses, on which they can use a limited number of Moodle course activities. Currently, Lesson, Quiz, and Feedback are supported. If installed, a course certificate can be issued using the custom certificate plugin.

Further information about the companion **_Moodle Chatbot app_** can be found here:
https://psi.atlassian.net/wiki/spaces/MoodleChatbot/overview 

## Features 

### Adds the following custom properties:
+ **Dialing Prefix field:** stores the user's country dialing code.
+ **Bot Mode field:** identifies if a course has been configured as a chatbot course.

### Adds a custom table:
+ **Mdl_local_botmanager_data_entity:** table is used to store the user’s chatbot session.

### Adds the following functions:
+ **local_botmanager_get_courses:** search and filter courses with **_Bot Mode_** custom field set to “Yes” value. It also provides course completion percentage and the progress status name.
+ **local_botmanager_get_activities:** provides information related to the activities supported by the chatbot (quiz, lesson, and feedback). It also checks if the user is enrolled in the queried course; if not, it enrolls the user in the course.
+ **local_botmanager_get_quiz_attempts_summary:** gets a summary overview of **_a specific quiz attempt_**, including grades and time spent, formatted for display.
+ **local_botmanager_open_lesson:** initiates a user's interaction with the content of a lesson through the **_Moodle Chatbot app_**.
+ **local_botmanager_view_lesson_page:** obtains lesson contents by breaking down each paragraph into small texts to help with legibility on chatbot based renderings.
+ **local_botmanager_get_lesson_attemps_summary:** retrieves the summary of a user attempt on a lesson. It includes information about participation, including start and end times, duration, and completion status.
+ **local_botmanager_get_feedback:** provides feedback activity's title, item ID, name, type, and completion status fields. Additionally, it performs transformations to better represent questions on the **_Moodle Chatbot app_**.
+ **local_botmanager_feedback_answer_question:** allows users to submit answers to questions in a feedback activity. It handles the processing of user responses, verifies correctness, and manages completion status.
+ **local_botmanager_get_certificates:** retrieves information about certificates earned by a user in a specific course. It gathers data such as certificate title, description, user's full name, course short name, completion date, and element positions for display.
+ **local_botmanager_create_user:** additionally to Moodle’s core fields, it also populates custom user profile field **_Dialing prefix:_**.
+ **local_botmanager_get_user:** allows user search based on “userid”, “mobile_phone” or “email” fields. In contrast to Moodle's core function, it supports search by **_mobile_phone_**.
+ **Local_botmanager_update_user:** allows updating specific **_user profile fields_** intended for the **_Moodle Chatbot app_**.
+ **local_botmanager_get_botdataentity:** fetch a single record from the table mdl_local_botmanager_dataentity. This helps the **_Moodle Chatbot app_** to track the user's state in the conversation flow.
+ **local_botmanager_update_botdataentity:** performs an update on an existing record in the table mdl_local_botmanager_dataentity.
+ **local_botmanager_delete_botdataentity:** deletes a record in the table mdl_local_botmanager_dataentity. This allows the user to restart the conversation in the **_Moodle Chatbot app_**.

## Installation
Obtain the plugin's ZIP file from [Moodle Chatbot Plugin Repository](https://github.com/psi-org/moodle-local_chatbot_plugin)

## Troubleshooting, Bugs, and Feedback
Please submit any bug or feedback at [Moodle Chatbot Plugin Issues](https://github.com/psi-org/moodle-local_chatbot_plugin/issues)
