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
 * Settings configuration for admin setting section
 * @package    theme_ausinet
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (is_siteadmin()) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingausinet', get_string('configtitle', 'theme_ausinet'));
    $ADMIN->add('themes', new admin_category('theme_ausinet', 'Ausinet'));

    /* Header Settings */
    $temp = new admin_settingpage('theme_ausinet_header', get_string('headerheading', 'theme_ausinet'));

    // Logo file setting.
    $name = 'theme_ausinet/logo';
    $title = get_string('logo', 'theme_ausinet');
    $description = get_string('logodesc', 'theme_ausinet');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);


    // Display banner.
    $name = 'theme_ausinet/togglebanner';
    $title = get_string('togglebanner', 'theme_ausinet');
    $description = get_string('togglebannerdesc', 'theme_ausinet');
    $yes = get_string('yes');
    $no = get_string('no');
    $default = 1;
    $choices = array(1 => $yes , 0 => $no);
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $temp->add($setting);

    // Logo file setting.
    $name = 'theme_ausinet/bannerimage';
    $title = get_string('bannerimage', 'theme_ausinet');
    $description = get_string('bannerimagedesc', 'theme_ausinet');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'bannerimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Slide Caption.
    $name = 'theme_ausinet/bannercaption';
    $title = get_string('bannercaption', 'theme_ausinet');
    $description = get_string('bannercaptiondesc', 'theme_ausinet');
    $default = get_string('bannercaptiondefault', 'theme_ausinet') ;
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Slide Description Text.
    $name = 'theme_ausinet/bannerdescription';
    $title = get_string('bannerdesc', 'theme_ausinet');
    $description = get_string('bannerdesctext', 'theme_ausinet');
    $default = get_string('bannerdescdefault', 'theme_ausinet');
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $temp->add($setting);

    // banner Link.
    $name = 'theme_ausinet/bannerlink';
    $title = get_string('bannerlink', 'theme_ausinet');
    $description = get_string('bannerlinkdesc', 'theme_ausinet');
    $default = 'javascript:void(0)';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // require_once($CFG->libdir . '/coursecatlib.php');
    $coursecats = core_course_category::make_categories_list();



    // Marketing Spot 1 Enable or disable.
    $name = 'theme_ausinet/toggleaboutus';
    $title = get_string('toggleaboutus', 'theme_ausinet');
    $description = get_string('marketingSpot1_statusdesc', 'theme_ausinet');
    /* $yes = get_string('yes');
    $no = get_string('no');*/
    $default = 1;
    // $choices = array(1 => $yes , 0 => $no);
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    // $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);
    // Marketing Spot 1 Enable or disable.


    // Marketing Spot 1 Title.
    $name = 'theme_ausinet/aboutus_title';
    $title = get_string('aboutus_title', 'theme_ausinet');
    $description = get_string('mspottitledesc', 'theme_ausinet', array('msno' => '1'));
    $default = 'lang:aboutus';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    // $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Marketing Spot 1 Description.
    $name = 'theme_ausinet/aboutus_content';
    $title = get_string('description');
    $description = get_string('mspotdescdesc', 'theme_ausinet', array('msno' => '1'));
    $default = 'lang:aboutusdesc';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing spot 1 Media content.
    $name = 'theme_ausinet/aboutus_image';
    $title = get_string('media', 'theme_ausinet');
    $description = get_string('mspotmediadesc', 'theme_ausinet', array('msno' => '1'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'aboutus_image');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);
    // $settings->add($temp);

    $settings->add($temp);

     /* Footer Settings start */
    $temp = new admin_settingpage('theme_ausinet_footer', get_string('footerheading', 'theme_ausinet'));

        // Copyright.
    $name = 'theme_ausinet_copyrightheading';
    $heading = get_string('copyrightheading', 'theme_ausinet');
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Copyright setting.
    $name = 'theme_ausinet/copyright';
    $title = get_string('copyright', 'theme_ausinet');
    $description = get_string('copyrightdesc', 'theme_ausinet');
    $default = 'lang:copyrightdefault';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Footer block 3 link
    $name = 'theme_ausinet/footerlink';
    $title = get_string('links', 'theme_ausinet');
    $description = get_string('footerlink_desc', 'theme_ausinet', array('blockno' => '3'));
    $default = get_string('footerlinkdefault', 'theme_ausinet');
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    // $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $settings->add($temp);


           /* Category Image */
    $temp = new admin_settingpage('theme_ausinet_categoryimg', get_string('categoryimgheading', 'theme_ausinet'));

    // $coursecats =  core_course_category::top();
    // print_r($coursecats);
    // Go through all categories and create the necessary settings.
    foreach ($coursecats as $key => $value) {

        if ($record = $DB->get_record('course_categories', array('id' => $key, 'parent' => '0', 'visible' => '1'))) {
        // Category Icons for each category.
            $name = 'theme_ausinet/categoryimg';
            $title = $value;
            $description = get_string('categoryimgcategory', 'theme_ausinet', array('category' => $value));
            $default = 'categoryimg'.$key;
            $setting = new admin_setting_configstoredfile($name . $key, $title, $description, $default);
            $setting->set_updatedcallback('theme_reset_all_caches');
            $temp->add($setting);
        }
    }
    unset($coursecats);
    
    $settings->add($temp);

}