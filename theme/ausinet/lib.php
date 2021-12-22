<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * lib.php
 * @package    theme_ausinet
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// global $THEMEConfig;/

function theme_ausinet_page_init($PAGE) {
    global $OUTPUT, $USER;

    $PAGE->requires->jquery();
    if ($PAGE->pagetype == 'mod-quiz-reviewquestion' || $PAGE->pagetype == 'mod-quiz-review' || $PAGE->pagetype == 'mod-quiz-report'){
        $PAGE->requires->css('/theme/ausinet/style/slick.css');
        $PAGE->requires->css('/theme/ausinet/style/slick-theme.css');
        $PAGE->requires->jquery_plugin('slick', 'theme_ausinet');
        $PAGE->requires->js('/theme/ausinet/javascript/attachment_slider.js');
    }

    if ($PAGE->pagetype == 'mod-quiz-attempt') {
        quiz_add_jsparam($PAGE);
    }
       
    $class = (is_siteadmin()) ? ' is-admin ':'';

    $userroles = get_user_roles($PAGE->context, $USER->id);
    $userrole = (!empty(current($userroles))) ? current($userroles)->shortname : '';
    $class .= $userrole;
    
    $class .= ' '.$PAGE->course->shortname;

    $PAGE->add_body_class($class);

    if (strpos($PAGE->pagetype, 'course-view') !== false) {


        $PAGE->add_body_class($PAGE->course->shortname);
        $managebutton = $OUTPUT->single_button(new \moodle_url('/report/progress/index_custom.php',
                array('course' => $PAGE->course->id)), get_string('completion', 'theme_ausinet'), 'get');
        $userroles = get_user_roles($PAGE->context, $USER->id);
        $userrole = current($userroles);
        $userrole = (isset($userrole->shortname))? $userrole->shortname : '';
        if ( is_siteadmin() || $userrole == 'editingteacher' || $userrole == 'non-editingteacher') {
            $managebutton .= $PAGE->button;
            $PAGE->set_button($managebutton);
        }


        
        // // print_object($users);
        // $ajaxurl = new \moodle_url('/theme/ausinet/ajax.php');
        // $restrictform = new \theme_ausinet\selectusers($ajaxurl, ['users' => $users]);
        $params['contextid'] = $PAGE->context->id;
        $PAGE->requires->data_for_js('customrestrict', $params, true);
        $PAGE->requires->js('/theme/ausinet/javascript/restrict_access.js');
    }

    if (!$PAGE->user_is_editing()) {
        removemycourses_flatnav();
    }



    if (optional_param('submitbutton', null, PARAM_RAW)) {
        $username = optional_param('username', null, PARAM_RAW);
        $PAGE->requires->string_for_js('login', 'moodle');
        $PAGE->requires->data_for_js('signup_user', $username, true);
    }

    
    // $themeparams['today'] = 
    // $PAGE->requires->data_for_js('themeparams', $themeparams);
    $PAGE->requires->js('/theme/ausinet/javascript/theme.js');

}



function quiz_add_jsparam($PAGE) {
    if ($attempt = optional_param('attempt', null, PARAM_INT)) {
        $cmid = $PAGE->cm->id;
        $attemptobj = quiz_create_attempt_handling_errors($attempt, $cmid);
        $userid = $attemptobj->get_userid();
        $user = core_user::get_user($userid);
        $params['studentname'] = fullname($user);
        //$params['startdate'] = date( 'Y-m-d H:i', $attemptobj->get_attempt()->timestart);
        $params['startdate'] = date( 'Y-m-d H:i');
        $completedate = $attemptobj->get_submitted_date();
        //$params['completiondate'] = ($completedate) ? date( 'Y-m-d H:i', $completedate) : '';
        $params['completiondate'] = date( 'Y-m-d H:i', $completedate);
        $params['date'] = date('Y-m-d', time());
        // $params['trainername'] = 


        $PAGE->requires->data_for_js('quizattempts', $params, true);
        $PAGE->requires->js_amd_inline('
            require(["jquery"], function() {
                               

                var attemptparams = ( typeof quizattempts != "undefined" ) ? quizattempts : {};
                if (attemptparams != "") {

                    $(".subquestion").parent("td").prev("td").each(function() { 
                        var td = $(this);
                        var st = $(this).text().trim().toLowerCase();
                        // console.log(st);
                        if (st == "student name") {
                            var studentname = attemptparams.studentname;
                            console.log(td);
                            td.next("td").find("input[type=text][name$=_answer]").val(studentname)
                        } else if (st == "start date") {
                            var startdate = attemptparams.startdate;
                            console.log(td);
                            td.next("td").find("input[type=text][name$=_answer]").val(startdate)
                        } else if (st == "completion date") {
                            var completiondate = attemptparams.completiondate;
                            console.log(td);
                            td.next("td").find("input[type=text][name$=_answer]").val(completiondate)
                        } 
                    })

                    $(".autofield").each(function() {
                        var field = $(this).data("field");
                        if (typeof quizattempts[field] != undefined ) {
                            $(this).find("input").val(quizattempts[field]);
                        }
                    });
                }
            })
        ');        
    }
}



function removemycourses_flatnav() {
    global $USER, $PAGE;
    $enrolcourses = enrol_get_my_courses();

    $PAGE->flatnav->remove('mycourses');
    if (!is_siteadmin()) {
        $PAGE->flatnav->remove('home');
    }
}

function theme_ausinet_remove_cloze_auto_grades($data) {
    global $CFG;
    require_once($CFG->dirroot.'/theme/ausinet/locallib.php');
    $clear_auto_grades = new \clear_auto_grades($data['attemptid'], $data['quizid'], $data['cmid']);    
}

function theme_ausinet_output_fragment_get_restrictuser_form($args) {
    global $PAGE;
    $params = ($args['formdata']) ? json_decode($args['formdata'], true) : [];   
    $params['users'] = get_enrolled_users($PAGE->context);    
    $ajaxurl = new \moodle_url('/theme/ausinet/ajax.php');
    $mform = new \theme_ausinet\selectusers($ajaxurl, $params);
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

function theme_ausinet_get_setting($setting, $format='') {
    global $PAGE, $CFG;
    require_once($CFG->dirroot . '/lib/weblib.php');
    static $theme;
    if (empty($theme)) {
        $theme = theme_config::load('ausinet');
    }

    if (empty($theme->settings->$setting)) {
        return false;
    }  else if ($format === 'format_text') {
        return format_text($theme->settings->$setting, FORMAT_PLAIN);
    } else if ($format === 'format_html') {
        return format_text($theme->settings->$setting, FORMAT_HTML, array('trusted' => true, 'noclean' => true));
    } else if ($format == 'file') {
        return $PAGE->theme->setting_file_url($setting, $setting);
    } else {
        return format_string($theme->settings->$setting);
    }
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_ausinet_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $theme = theme_config::load('ausinet');
    
    $available_filearea = ['logo', 'backgroundimage', 'bannerimage', 'aboutus_image'];

    if ($context->contextlevel == CONTEXT_SYSTEM && (in_array($filearea, $available_filearea) || strpos($filearea, 'categoryimg') !== false) ) {
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

/**
 * Get Course Enrolment Price.
 */
function ausinet_get_course_cost($courseid) {
    global $CFG, $OUTPUT;
    $data['payment_methods'] = array();
    $data['cost'] = null;
    $enrolplugin = enrol_get_instances($courseid, true);
    foreach ($enrolplugin as $key => $value) {
        $pix_icon = $OUTPUT->pix_icon('icon', $value->name, 'enrol_'.$value->enrol);
        $data['payment_methods'][] = array('method' => $value->name, 'id' => $value->id, 'img' => $pix_icon );
        if ($value->enrol == 'paypal' || isset($value->cost)) {
            $cost = $value->cost;
            $currency = $value->currency;
        }
    }

    if (isset($currency)) {
        $currency = ausinet_get_currency_symbol($currency);
    }
    if (isset($currency) && isset($cost)) {
        $data['cost'] = $currency . ' ' . $cost;
    }
    return $data;
}


function ausinet_get_currency_symbol($code) {

    $codes = array(
        'AUD' => '&#36;',
        'BRL' => '&#82;&#36;',
        'CAD' => '&#36;',
        'CHF' => '&#67;&#72;&#70;',
        'CZK' => '&#75;&#269;',
        'DKK' => '&#107;&#114;',
        'EUR' => '&#8364;',
        'GBP' => '&#163;',
        'HKD' => '&#36;',
        'HUF' => '&#70;&#116;',
        'ILS' => '&#8362;',
        'JPY' => '&#165;',
        'MXN' => '&#36;',
        'MYR' => '&#82;&#77;',
        'NOK' => '&#107;&#114;',
        'NZD' => '&#36;',
        'PHP' => '&#8369;',
        'PLN' => '&#122;&#322;',
        'RUB' => '&#8381;',
        'SEK' => '&#107;&#114;',
        'SGD' => '&#36;',
        'THB' => '&#3647;',
        'TRY' => '',
        'TWD' => '&#78;&#84;&#36;',
        'USD' => '&#36;',
    );

    return isset($codes[$code]) ? $codes[$code] : $code;
}

function ausinet_get_course_stats($course) {
    global $DB;

    $enrols = enrol_get_course_users($course->id);
    $list = get_array_of_activities($course->id);
    $mods = array_unique(array_column($list, 'mod'));    
    $meta = get_module_metadata($course, array_flip($mods) );
    $res = $act = 0;    
    $sections = $DB->count_records('course_sections', array('course' => $course->id),
                'section ASC', 'id,section,visible');
    foreach ($list as $key => $val) {
        if ( (isset($val->deletioninprogress) && $val->deletioninprogress) || $val->mod == 'label') {
            continue;
        }
        $archtype = plugin_supports('mod', $val->mod, FEATURE_MOD_ARCHETYPE);
        if ($archtype) {
            $res = $res + 1;
        } else {
            $act = $act + 1;
        }        
    }
    
    $stats['enrols'] = !empty($enrols) ? count($enrols) : '0';
    $stats['activities'] = $act;
    $stats['resources'] = $res;
    $stats['sections'] = $sections;
    return $stats;
}

