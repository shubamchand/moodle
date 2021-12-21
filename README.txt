localhost
admin
AusinetDarwin2!

https://testdev.electricalcourses.net.au/grade/report/user/index.php?id=4&userid=3&group

Done changes in 
C:\xampp\htdocs\moodle\report\outline\locallib.php // changed the status for the items and enabled OPTIONAL for the activity type (mod-types) - restrict mods to view line no 37 - shubham
C:\xampp\htdocs\moodle\report\outline\user.php //filtered required items in completion/ outline report - line no 136 //to filter the required items in outline reports - Shubham 25/11/2021

28/11/2021
https://testdev.electricalcourses.net.au/grade/report/overview/lib.php //changed the restriction of user to view grade reports 
https://testdev.electricalcourses.net.au/grade/report/user/lib.php // added the feedback from trainer/admin to the grade report table and enabled for teacher/trainer line no - 686
https://testdev.electricalcourses.net.au/grade/report/user/index.php - //changed the url to view grade table for particular user with feedbacks - Shubham 01/12/2021 - line no - 34

30/11/2021
 https://testdev.electricalcourses.net.au/grade/report/overview/lib.php // changed the link to see the table with feedback - Shubham 30/11/2021 - line no - 286
course/user.php - //added the url redirection to show grades with comments/feedback - Shubham 30/11/2021 line no - 54
public_html/lib/navigationlib.php - line no 2816 //changed URL to navigate to the grade table with feedback 30/11/2021

02/12/2021
/lib/moodlelib.php ///custom email to support@ausinet.com.au - line no - 6380

grade/report/overview/lib.php - //changed URL for grade table - 30/11/2021 - line no - 340

05/12/2021
ini_set('memory_limit', '5G');// added to allocate more memory for the page - Shubham 05/12/2021 - public_html/lib/classes/string_manager_standard.php (electricalcourses)

19/12/2021
/ausinet.net.au/public_html/course/format/remuiformat/templates/list_general_section_edit.mustache - changed class to section as it was not visible in view page - SC 19/12/2021

20/12/2021 - line no 1298 - mod/observation/observation.class.php
if (is_enrolled($coursecontext, $enrols, '', true)) { // if condition to check if the users are enrolled and active - Shubham 20/12/2021
                $users[$enrols->id] = fullname($enrols);
            }

21/12/2021
mod/observation.templates/completepage.mustache - line no 70 - added this section to display submit button on top of th form with center align - Shubham 21/12/2021

lib/myprofilelib.php - Line 168
//Added next two fileds in profile page - 21/12/2021 Shubham
    if (!isset($hiddenfields['usi']) && $user->usi) {
        $node = new core_user\output\myprofile\node('contact', 'usi', get_string('usi'), null, null, $user->usi);
        $tree->add_node($node);
    }

    if (!isset($hiddenfields['usi']) && $user->employer) {
        $node = new core_user\output\myprofile\node('contact', 'employer', get_string('employer'), null, null, $user->employer);
        $tree->add_node($node);
    }
//End

user/editlib.php - Added USI and Employer in user registration form - line no 295 
lang/en/moodle.php - Added string for USI and Employer - Line no 2154 - $string['usi'] = 'USI number'; and line no 740 - $string['employer'] = 'Employer';
