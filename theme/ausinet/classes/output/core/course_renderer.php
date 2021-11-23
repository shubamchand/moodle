<?php 

namespace theme_ausinet\output\core;

use moodle_url;
use lang_string;
use html_writer;

class course_renderer extends \core_course_renderer {

        /**
     * Renders part of frontpage with a skip link (i.e. "My courses", "Site news", etc.)
     *
     * @param string $skipdivid
     * @param string $contentsdivid
     * @param string $header Header of the part
     * @param string $contents Contents of the part
     * @return string
     */
    protected function frontpage_part($skipdivid, $contentsdivid, $header, $contents, $classes='') {
        if (strval($contents) === '') {
            return '';
        }
        $output = \html_writer::link('#' . $skipdivid,
            get_string('skipa', 'access', \core_text::strtolower(strip_tags($header))),
            array('class' => 'skip-block skip'));

        // Wrap frontpage part in div container.
        $output .= \html_writer::start_tag('div', array('id' => $contentsdivid, 'class' => $classes));
        $output .= \html_writer::start_tag('div', array('class' => 'container'));
        
        $output .= $this->heading($header);

        // $output .= \html_writer::start_tag('div', array('class' => 'row'));
        
        $output .= $contents;   

        // $output .= \html_writer::end_tag('div');

        $output .= \html_writer::end_tag('div');

        // End frontpage part div container.
        $output .= \html_writer::end_tag('div');

        $output .= \html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => $skipdivid));
        return $output;
    }

    /**
     * Outputs contents for frontpage as configured in $CFG->frontpage or $CFG->frontpageloggedin
     *
     * @return string
     */
    public function frontpage() {
        global $CFG, $SITE;

        $output = '';

        if (isloggedin() and !isguestuser() and isset($CFG->frontpageloggedin)) {
            $frontpagelayout = $CFG->frontpageloggedin;
        } else {
            $frontpagelayout = $CFG->frontpage;
        }

        foreach (explode(',', $frontpagelayout) as $v) {
            switch ($v) {
                // Display the main part of the front page.
                case FRONTPAGENEWS:
                    if ($SITE->newsitems) {
                        // Print forums only when needed.
                        require_once($CFG->dirroot .'/mod/forum/lib.php');
                        if (($newsforum = forum_get_course_forum($SITE->id, 'news')) &&
                                ($forumcontents = $this->frontpage_news($newsforum))) {
                            $newsforumcm = get_fast_modinfo($SITE)->instances['forum'][$newsforum->id];
                            $output .= $this->frontpage_part('skipsitenews', 'site-news-forum',
                                $newsforumcm->get_formatted_name(), $forumcontents);
                        }
                    }
                    break;

                case FRONTPAGEENROLLEDCOURSELIST:
                    $mycourseshtml = $this->frontpage_my_courses();
                    if (!empty($mycourseshtml)) {
                        $output .= $this->frontpage_part('skipmycourses', 'frontpage-course-list', get_string('mycourses'), $mycourseshtml, 'enrolled-course frontpage-course-list-enrolled');
                    }
                    break;

                case FRONTPAGEALLCOURSELIST:
                    $availablecourseshtml = $this->frontpage_available_courses();
                    $output .= $this->frontpage_part('skipavailablecourses', 'frontpage-available-course-list',
                        get_string('availablecourses'), $availablecourseshtml);
                    break;

                case FRONTPAGECATEGORYNAMES:
                    $output .= $this->frontpage_part('skipcategories', 'frontpage-category-names',
                        get_string('categories'), $this->frontpage_categories_list());
                    break;

                case FRONTPAGECATEGORYCOMBO:
                    $output .= $this->frontpage_part('skipcourses', 'frontpage-category-combo',
                        get_string('courses'), $this->frontpage_combo_list());
                    break;

                case FRONTPAGECOURSESEARCH:
                    $output .= $this->box($this->course_search_form('', 'short'), 'mdl-align');
                    break;

            }
            $output .= '<br />';
        }

        return $output;
    }
    
    /**
     * Returns HTML to print list of available courses for the frontpage
     *
     * @return string
     */
    function frontpage_available_courses() {

        global $CFG;

        $chelper = new \coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->
                set_courses_display_options(array(
                    'recursive' => true,
                    'limit' => $CFG->frontpagecourselimit,
                    'viewmoreurl' => new moodle_url('/course/index.php'),
                    'viewmoretext' => new lang_string('fulllistofcourses')));

        $chelper->set_attributes(array('class' => 'frontpage-course-list-all'));
        $courses = \core_course_category::top()->get_courses($chelper->get_courses_display_options());
        $totalcount = \core_course_category::top()->get_courses_count($chelper->get_courses_display_options());
        if (!$totalcount && !$this->page->user_is_editing() && has_capability('moodle/course:create', \context_system::instance())) {
            // Print link to create a new course, for the 1st available category.
            return $this->add_new_course_button();
        }
        return $this->available_courses($chelper, $courses, $totalcount);    
    }

    function available_courses(\coursecat_helper $chelper, $courses, $totalcount = null) {
        if (!empty($courses)) {
            $data = [];
            $attributes = $chelper->get_and_erase_attributes('courses');
            $content = \html_writer::start_tag('div', $attributes);
            foreach ($courses as $course) {
                $classes = '';
                $data[] = $this->available_coursebox($chelper, $course, $classes);
            }
            // print_object($data);
            // gnuwings
            $check = (isloggedin()) ? true : false;
            $content .= $this->render_template('theme_ausinet/available_courses', ['courses' => $data, 'check' => $check]);
            // gnuwings
            $content .= \html_writer::end_tag('div');
            return $content;
        }
    }

    function get_courseimage($course) {
        global $CFG, $OUTPUT;
        if (!empty($course)) {
            // $course = \core_course_category::get_course($course->id);
            $data['imgurl']  = $OUTPUT->image_url('no-image', 'theme');
            foreach ($course->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                if (!$isimage) {
                    $data['imgurl'] = $noimgurl;
                } else {
                   $data['imgurl'] = file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                    $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                }
            }
            return $data['imgurl'];
        }
    }

    function available_coursebox(\coursecat_helper $chelper, $course, $additionalclasses = '') {
        global $CFG, $USER;
        $classes = trim('coursebox clearfix '. $additionalclasses);
        $content = \html_writer::start_tag('div', array(
            'class' => $classes,
            'data-courseid' => $course->id,
            'data-type' => self::COURSECAT_TYPE_COURSE,
        ));

        $category = \core_course_category::get($course->category, IGNORE_MISSING);
        $data['category'] = format_string($category->name);
        $data['category_link'] = new \moodle_url('/course/index.php', array('categoryid' => $category->id));//->out();
        // print_object($course);exit;
          // course name
        $coursename = $chelper->get_course_formatted_name($course);
        $data['name'] =  $coursename;
         //gnuwings. Link activates only when user loggedin.
        if (isloggedin()) {
			$coursenamelink = \html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)),
                                           $coursename, array('class' => $course->visible ? '' : 'dimmed'));
		} else {
			$coursenamelink = $coursename;
		}
        // gnuwings
        $cost = ausinet_get_course_cost($course->id);
        $stats = ausinet_get_course_stats($course);
        $data = array_merge( $stats, $data);
        $data['imgurl'] = '';
        $data['imgurl'] = $this->get_courseimage($course);
        $data['link'] = new \moodle_url('/course/view.php', array('id' => $course->id));
        $data['coursename'] = $coursenamelink;
        $data['shortname'] = format_string($course->shortname);
        $data['startdate'] = userdate($course->startdate, '%d/%m/%Y, %I:%M %p', '', false);
        $data['enddate'] = userdate($course->enddate,'%d/%m/%Y, %I:%M %p', '', false);
        $data['cost'] = !empty($cost['cost']) ? $cost['cost'] : '';
        if (is_siteadmin()) {
            $data['ladder_url'] = $CFG->wwwroot.'/blocks/xp/index.php/ladder/'.$course->id;
        } else {
            $data['ladder_url'] = new \moodle_url('/course/user.php', array('mode' => 'grade', 'id' => $course->id, 'user' => $USER->id));
        }
        // print_object($data);
        // $data
        $data['optionsblock'] = (isloggedin()) ? true : false;
        $data['enrolurl'] = new \moodle_url('/enrol/index.php', ['id' => $course->id]);
        $data['infourl'] = new \moodle_url('/course/info.php', ['id' => $course->id]);

        return $data;
    }


    /**
     * Returns HTML to print list of courses user is enrolled to for the frontpage
     *
     * Also lists remote courses or remote hosts if MNET authorisation is used
     *
     * @return string
     */
    public function frontpage_my_courses() {
        global $USER, $CFG, $DB;

        if (!isloggedin() or isguestuser()) {
            return '';
        }

        $output = '<div class="row"> <div class="slider" style="width:100%;" >';
        $courses  = enrol_get_my_courses('summary, summaryformat');
        $rhosts   = array();
        $rcourses = array();
        if (!empty($CFG->mnet_dispatcher_mode) && $CFG->mnet_dispatcher_mode==='strict') {
            $rcourses = get_my_remotecourses($USER->id);
            $rhosts   = get_my_remotehosts();
        }

        if (!empty($courses) || !empty($rcourses) || !empty($rhosts)) {

            $chelper = new \coursecat_helper();
            $totalcount = count($courses);
            if (count($courses) > $CFG->frontpagecourselimit) {
                // There are more enrolled courses than we can display, display link to 'My courses'.
                $courses = array_slice($courses, 0, $CFG->frontpagecourselimit, true);
                $chelper->set_courses_display_options(array(
                        'viewmoreurl' => new moodle_url('/my/'),
                        'viewmoretext' => new lang_string('mycourses')
                    ));
            } else if (\core_course_category::top()->is_uservisible()) {
                // All enrolled courses are displayed, display link to 'All courses' if there are more courses in system.
                $chelper->set_courses_display_options(array(
                        'viewmoreurl' => new moodle_url('/course/index.php'),
                        'viewmoretext' => new lang_string('fulllistofcourses')
                    ));
                $totalcount = $DB->count_records('course') - 1;
            }
            $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->
                    set_attributes(array('class' => 'frontpage-course-list-enrolled'));
            foreach ($courses as $course) {             
                $course = new \core_course_list_element($course);
                $course_details = $this->available_coursebox($chelper, $course, $totalcount);
                $course_details['progress'] = $this->get_user_course_progress($course, $USER->id);
                $output .= $this->frontpage_my_courses_html($course_details);
            }

            // MNET
            if (!empty($rcourses)) {
                // at the IDP, we know of all the remote courses
                $output .= \html_writer::start_tag('div', array('class' => 'courses'));
                foreach ($rcourses as $course) {
                    $output .= $this->frontpage_remote_course($course);
                }
                $output .= \html_writer::end_tag('div'); // .courses
            } elseif (!empty($rhosts)) {
                // non-IDP, we know of all the remote servers, but not courses
                $output .= \html_writer::start_tag('div', array('class' => 'courses'));
                foreach ($rhosts as $host) {
                    $output .= $this->frontpage_remote_host($host);
                }
                $output .= \html_writer::end_tag('div'); // .courses
            }
        }
        $output .= '</div></div>';
        return $output;
    }

    function frontpage_my_courses_html($course) {
        return '<div class="col-lg-4">
            <div class="course-block">
                <div class="img-block">
                    <img src="'.$course["imgurl"].'" alt="Attachment 3.8 release art.jpg" style="max-width: 100%">
                </div>
                <div class="content-block">
                    <h3>'.$course["coursename"].'</h3>
                    <div class="report-block">
                        <div class="progress-block">                           
                                '.$course["progress"].'                            
                        </div>                        
                    </div>
                    <div class="reports">
                        <a href="#"><i class="fa fa-bolt"></i></a>
                        <a href="'.$course["ladder_url"].'"><i class="fa fa-trophy"></i></a>
                        <a href="#"><i class="fa fa-bar-chart"></i></a>
                    </div>
                    <div class="btn-block">
                        <a href="'.$course['link'].'">'.get_string('readmore', 'theme_ausinet').'</a>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>';
    }

    function mycourse_content($course, $type ='') {
        global $CFG;
        if ($type == 'remote') {
            $url = new moodle_url('/auth/mnet/jump.php', array(
                'hostid' => $course->hostid,
                'wantsurl' => '/course/view.php?id='. $course->remoteid
            ));
            $coursename = format_string($course->fullname);
            $data['coursename'] = \html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $coursename, array('class' => $course->visible ? '' : 'dimmed'));
            $data['imgurl'] = $this->get_courseimage($course);
        }
    }

    function frontpage_categories_list() {
        global $PAGE;
      /*  $displayOption = $PAGE->theme->settings->categories_displaytype;
        if ($displayOption == '0') {
            return parent::frontpage_categories_list();
        } else  {*/
            return $this->list_categories();
        // }
    }

    public function list_categories() {
        global $CFG, $PAGE, $OUTPUT;
        $content = '';
        $i = '0';
        $displayOption = 1; // $PAGE->theme->settings->categories_displaytype;
        $url = "";

        $rcoursecats = \core_course_category::make_categories_list('', '', 1);
        $catesData = [];
        if (!empty($rcoursecats)) {
            foreach ($rcoursecats as $key => $cats) {
                $courseArr = $this->ausinet_get_db_record($key);
                if (!empty($courseArr)) {
                    $catesData[$key] = $courseArr;
                }
            }
            // print_object($rcoursecats);

            $content .= \html_writer::start_tag('div', array('class' => 'frontpage-categories'));
            foreach ($catesData as $key => $data) {
                // $content .= \html_writer::start_tag('div', array('class' => 'row'));
                    if (!empty($data)) {
                        // echo $rcoursecats->id;
                        $url = new \moodle_url('course/index.php', array('categoryid' => $data->id));
                        $imgsrc = $PAGE->theme->setting_file_url('categoryimg'.$data->id, 'categoryimg'.$data->id);
                        if (empty($imgsrc)) {
                            // $imgsrc = $OUTPUT->image_url('no-image', 'theme');
                        }
                        $readmore = get_string ('readmore', 'theme_ausinet');
                        // $content .= \html_writer::start_tag('div', array('class' => 'col-md-4'));
                        $content .= \html_writer::start_tag('div', array('class' => 'category loaded'));
                        $content .= \html_writer::start_tag('div', array('class' => 'info'));
                        $content .= \html_writer::start_tag('div', array('class' => 'category-img'));
                        $content .= \html_writer::start_tag('a', array('href' => $url));
                        if (!empty($imgsrc)) {
                            $content .= \html_writer::empty_tag('img', array('src' => $imgsrc, 'alt' => $data->name ));
                        }
                        $content .= \html_writer::end_tag('a');
                        $content .= \html_writer::end_tag('div');

                        $content .= \html_writer::start_tag('h3', array('class' => 'categoryname'));
                        $content .= \html_writer::link($url, format_string($data->name));
                        $content .= \html_writer::start_tag('span', array('class' => 'numberofcourse', 'title' => 'Number of courses'));
                        $content .= \core_course_category::get($data->id)->get_courses_count();
                        $content .= '<span>'.get_string('courses').'</span>';
                        $content .= \html_writer::end_tag('span');
                        $content .= \html_writer::end_tag('h3');
                        $content .= \html_writer::end_tag('div');


                        $content .= \html_writer::start_tag('div', array('class' => 'read-more'));
                        $content .= \html_writer::link($url, $readmore );
                        $content .= \html_writer::end_tag('div');
                        $content .= \html_writer::end_tag('div');
                        // $content .= \html_writer::end_tag('div');
                    }
                // }
                // $content .= \html_writer::end_tag('div');
            }
            $view = get_string ('view');
            
            /*$content .= \html_writer::tag('div', '', array('class' => 'clearfix'));
            $content .= \html_writer::end_tag('div');*/
            
            $content .= \html_writer::end_tag('div');
            $content .= \html_writer::start_tag('div', array('class' => 'category-button'));
            $content .= \html_writer::tag('a', $view, array('class' => ' btn btn-primary', 'href' => new moodle_url('course/index.php') ));
        }
        return $content;
    }

    public function ausinet_get_db_record($cat_id) {
        global $DB;
        if ($record = $DB->get_record('course_categories', array('id' => $cat_id, 'parent' => '0', 'visible' => '1'))) {
            if(!empty($record))
                return $record;
            else
                return "";
        }
        return "";
    }

    function get_user_course_progress($courseid, $userid) {
        if (class_exists('\report_ausinet\course_report')) {
            $course_report = new \report_ausinet\course_report($courseid, $userid);
            $progress = $course_report->course_user_progress();
            if (!is_null($progress)) {
                return $this->render_template('block_myoverview/progress-bar', ['progress' => (int)$progress]);
            }
        }       
    }


    /**
     * Renders HTML to display particular course category - list of it's subcategories and courses
     *
     * Invoked from /course/index.php
     *
     * @param int|stdClass|core_course_category $category
     */
    public function course_category($category) {
        global $CFG;
        $usertop = \core_course_category::user_top();
        if (empty($category)) {
            $coursecat = $usertop;
        } else if (is_object($category) && $category instanceof \core_course_category) {
            $coursecat = $category;
        } else {
            $coursecat = \core_course_category::get(is_object($category) ? $category->id : $category);
        }
        $site = get_site();
        $output = '';
        $output_data = [];

        if ($coursecat->can_create_course() || $coursecat->has_manage_capability()) {
            // Add 'Manage' button if user has permissions to edit this category.
            $managebutton = $this->single_button(new \moodle_url('/course/management.php',
                array('categoryid' => $coursecat->id)), get_string('managecourses'), 'get');
            $this->page->set_button($managebutton);
        }

       /* if (\core_course_category::is_simple_site()) {
            // There is only one category in the system, do not display link to it.
            $strfulllistofcourses = get_string('fulllistofcourses');
            $this->page->set_title("$site->shortname: $strfulllistofcourses");
        } else if (!$coursecat->id || !$coursecat->is_uservisible()) {
            $strcategories = get_string('categories');
            $this->page->set_title("$site->shortname: $strcategories");
        } else {*/
            $strfulllistofcourses = get_string('fulllistofcourses');
            $this->page->set_title("$site->shortname: $strfulllistofcourses");

            // Print the category selector
            $categorieslist = \core_course_category::make_categories_list();
            if (count($categorieslist) > 1) {

                $select_options = \core_course_category::make_categories_list();
                $select_options = array_merge(['0' => get_string('all')], $select_options );
                $output .= \html_writer::start_tag('div', array('class' => 'categorypicker'));
                $select = new \single_select(new \moodle_url('/course/index.php'), 'categoryid',
                        $select_options, $coursecat->id, null, 'switchcategory');
                $select->set_label(get_string('categories').':');
                $output .= $this->render($select);
                $output .= \html_writer::end_tag('div'); // .categorypicker
            }
        // }

        // Set category picker. add the ouput as picker, because output doesn't contain other contents still here.
        $output_data['categorypicker'] = $output;

        // Print current category description
        $chelper = new \coursecat_helper();
        if ($description = $chelper->get_category_formatted_description($coursecat)) {
            $output_data['category_description'] = $this->box($description, array('class' => 'generalbox info'));
        }

        // Prepare parameters for courses and categories lists in the tree
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_AUTO)
                ->set_attributes(array('class' => 'category-browse category-browse-'.$coursecat->id));

        $coursedisplayoptions = array();
        $catdisplayoptions = array();
        $browse = optional_param('browse', null, PARAM_ALPHA);
        $perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $baseurl = new \moodle_url('/course/index.php');
        if ($coursecat->id) {
            $baseurl->param('categoryid', $coursecat->id);
        }
        if ($perpage != $CFG->coursesperpage) {
            $baseurl->param('perpage', $perpage);
        }
        $coursedisplayoptions['limit'] = $perpage;
        $catdisplayoptions['limit'] = $perpage;
        if ($browse === 'courses' || !$coursecat->get_children_count()) {
            $coursedisplayoptions['offset'] = $page * $perpage;
            $coursedisplayoptions['paginationurl'] = new \moodle_url($baseurl, array('browse' => 'courses'));
            $catdisplayoptions['nodisplay'] = true;
            $catdisplayoptions['viewmoreurl'] = new \moodle_url($baseurl, array('browse' => 'categories'));
            $catdisplayoptions['viewmoretext'] = new \lang_string('viewallsubcategories');
        } else if ($browse === 'categories' || !$coursecat->get_courses_count()) {
            $coursedisplayoptions['nodisplay'] = true;
            $catdisplayoptions['offset'] = $page * $perpage;
            $catdisplayoptions['paginationurl'] = new \moodle_url($baseurl, array('browse' => 'categories'));
            $coursedisplayoptions['viewmoreurl'] = new \moodle_url($baseurl, array('browse' => 'courses'));
            $coursedisplayoptions['viewmoretext'] = new \lang_string('viewallcourses');
        } else {
            // we have a category that has both subcategories and courses, display pagination separately
            $coursedisplayoptions['viewmoreurl'] = new \moodle_url($baseurl, array('browse' => 'courses', 'page' => 1));
            $catdisplayoptions['viewmoreurl'] = new \moodle_url($baseurl, array('browse' => 'categories', 'page' => 1));
        }
        $coursedisplayoptions['recursive'] = true;
        $chelper->set_courses_display_options($coursedisplayoptions)->set_categories_display_options($catdisplayoptions);
        // Add course search form.
        $output_data['search_form'] = $this->course_search_form();

        // // Display course category tree.
        // $output .= $this->coursecat_tree($chelper, $coursecat);
        $courselists = array();
        $category_list = \core_course_category::make_categories_list();
        $category_list[0] = 'ALL';
        $courselist = array();
        $courses = array();
        if (!$coursecat->id) {
            $courses = \core_course_category::get(0)->get_courses( $chelper->get_courses_display_options());
            // print_object($courses);
            $totalcount = \core_course_category::get(0)->get_courses_count(array('recursive' => true));
            $totalcount++;
        } else {
            $courses = \core_course_category::get($coursecat->id)->get_courses($chelper->get_courses_display_options());
            $totalcount = \core_course_category::get($coursecat->id)->get_courses_count($chelper->get_courses_display_options());
        }

        $courselist = $courses;

        $paginationurl = new \moodle_url($baseurl, array('browse' => 'courses'));
        $chelper->set_courses_display_options(array(
            'limit' => $perpage,
            'offset' => ((int)$page) * $perpage,
            'paginationurl' => $paginationurl,
        ));


        $output_data['course_content'] = $this->coursecat_courses($chelper, $courselist, $totalcount, "ausinet", '');

        // Add action buttons
        $output = $this->container_start('buttons');
        if ($coursecat->is_uservisible()) {
            $context = get_category_or_system_context($coursecat->id);
            if (has_capability('moodle/course:create', $context)) {
                // Print link to create a new course, for the 1st available category.
                if ($coursecat->id) {
                    $url = new \moodle_url('/course/edit.php', array('category' => $coursecat->id, 'returnto' => 'category'));
                } else {
                    $url = new \moodle_url('/course/edit.php',
                        array('category' => $CFG->defaultrequestcategory, 'returnto' => 'topcat'));
                }
                $output .= $this->single_button($url, get_string('addnewcourse'), 'get');
            }
            ob_start();
            print_course_request_buttons($context);
            $output .= ob_get_contents();
            ob_end_clean();
        }
        $output .= $this->container_end();

        $ouput_data['extra_buttons'] = $output;

        $output = $this->render_template('theme_ausinet/courselisting', $output_data);

        return $output;
    }

    /**
     * Renders the list of courses
     *
     * This is internal function, please use {@link core_course_renderer::courses_list()} or another public
     * method from outside of the class
     *
     * If list of courses is specified in $courses; the argument $chelper is only used
     * to retrieve display options and attributes, only methods get_show_courses(),
     * get_courses_display_option() and get_and_erase_attributes() are called.
     *
     * @param coursecat_helper $chelper various display options
     * @param array $courses the list of courses to display
     * @param int|null $totalcount total number of courses (affects display mode if it is AUTO or pagination if applicable),
     *     defaulted to count($courses)
     * @return string
     */
    protected function coursecat_courses(\coursecat_helper $chelper, $courses, $totalcount = null, $theme = 'ausinet', $subcategory = '', $sidebar='') {
        global $CFG, $PAGE;
        if ($totalcount === null) {
            $totalcount = count($courses);
        }
        // if (!$totalcount) {
        //     // Courses count is cached during courses retrieval.
        //     return '';
        // }

        if ($chelper->get_show_courses() == self::COURSECAT_SHOW_COURSES_AUTO) {
            // In 'auto' course display mode we analyse if number of courses is more or less than $CFG->courseswithsummarieslimit
            if ($totalcount <= $CFG->courseswithsummarieslimit) {
                $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED);
            } else {
                $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_COLLAPSED);
            }
        }

        // prepare content of paging bar if it is needed
        $paginationurl = $chelper->get_courses_display_option('paginationurl');
        $paginationallowall = $chelper->get_courses_display_option('paginationallowall');
        if ($totalcount > count($courses)) {
            // there are more results that can fit on one page
            if ($paginationurl) {
                // the option paginationurl was specified, display pagingbar
                $perpage = $chelper->get_courses_display_option('limit', $CFG->coursesperpage);
                $page = $chelper->get_courses_display_option('offset') / $perpage;
                $pagingbar = $this->paging_bar($totalcount, $page, $perpage,
                        $paginationurl->out(false, array('perpage' => $perpage)));
                if ($paginationallowall) {
                    $pagingbar .= \html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => 'all')),
                            get_string('showall', '', $totalcount)), array('class' => 'paging paging-showall'));
                }
            } else if ($viewmoreurl = $chelper->get_courses_display_option('viewmoreurl')) {
                // the option for 'View more' link was specified, display more link
                $viewmoretext = $chelper->get_courses_display_option('viewmoretext', new lang_string('viewmore'));
                $morelink = \html_writer::tag('div', html_writer::link($viewmoreurl, $viewmoretext),
                        array('class' => 'paging paging-morelink'));
            }
        } else if (($totalcount > $CFG->coursesperpage) && $paginationurl && $paginationallowall) {
            // there are more than one page of results and we are in 'view all' mode, suggest to go back to paginated view mode
            $pagingbar = \html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => $CFG->coursesperpage)),
                get_string('showperpage', '', $CFG->coursesperpage)), array('class' => 'paging paging-showperpage'));
        }

        // display list of courses
        $attributes = $chelper->get_and_erase_attributes('courses');
        $content = \html_writer::start_tag('div', $attributes);

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }

        $coursecount = 0;
        $courses_list = [];
        foreach ($courses as $course) {
            $coursecount ++;
            $classes = ($coursecount%2) ? 'odd' : 'even';
            if ($coursecount == 1) {
                $classes .= ' first';
            }
            if ($coursecount >= count($courses)) {
                $classes .= ' last';
            }
            if ($theme == 'ausinet') {
                
                $courses_list[] = $this->available_coursebox($chelper, $course, $classes);

                // $courses[] = \theme_ausinet\custom_blocks::courselisting_blocks($coursedetails);
            } else {
                $content .= $this->coursecat_coursebox($chelper, $course, $classes);
            }
        }

        if ($theme == 'ausinet') {
            $data = [
                'pagingbar' => (isset($pagingbar)) ? $pagingbar: '',
                'courses' => $courses_list,
                'attributes' => $attributes,
            ];

            return $this->render_template('theme_ausinet/course_row', $data);
         
        }

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }
        if (!empty($morelink)) {
            $content .= $morelink;
        }

        $content .= \html_writer::end_tag('div'); // .courses
        return $content;
    }

  
    /**
     * Course listing closed tags.
     * @return type|string
     */
    public function sce_courselisting_footer() {

        $footer = \html_writer::end_tag('div');
        // $footer .= html_writer::end_tag('div');
        $footer .= \html_writer::end_tag('div');
        return $footer;
    }


     /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, \cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }

        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        // $completion = $completioninfo->is_complete();
        // $completionclass = ($completion) ? 'completed' : '';

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer '));

        // This div is used to indent the content.
        $output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url)
        $cmname = $this->course_section_cm_name($mod, $displayoptions);

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));
            $output .= $cmname;


            // Module can put text after the link (e.g. forum unread)
            $output .= $mod->afterlink;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance
        }

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->url;
        if (empty($url)) {
            $output .= $contentpart;
        }

        $modicons = '';

        // $modicons .= $this->lock_unlock_form(\context_course::instance($course->id));
        if ($this->page->user_is_editing()) {
    
            if ($mod->modname !='label') {            
                $modicons .= html_writer::link('javascript:void(0);', '<i class="fa fa-lock "></i>', ['class' => 'ra-popup', 'data-type' => 'module',  'data-cmid' => $mod->id] );
            }

            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        if (!empty($url)) {
            $output .= $contentpart;
        }

        
        $modicons .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);


        if (!empty($modicons)) {
            $output .= html_writer::span($modicons, 'actions');
        }

        // Show availability info (if module is not available).
        $output .= $this->course_section_cm_availability($mod, $displayoptions);


        $output .= html_writer::end_tag('div'); // $indentclasses

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }

    function lock_unlock_form($coursecontext) {
        
       /* $users = get_enrolled_users($coursecontext);
        // print_object($users);
        $ajaxurl = new \moodle_url('/theme/ausinet/ajax.php');
        $mform = new \theme_ausinet\selectusers($ajaxurl, ['users' => $users]);*/
    }


    /*
    * Render template for the course blocks.
    *
    */
    function render_template($template, $data) {
        global $OUTPUT; 

        return $OUTPUT->render_from_template($template, $data);
    }
}


