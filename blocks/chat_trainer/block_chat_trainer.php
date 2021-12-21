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
 * Completion Progress block definition
 *
 * @package    block_chat_trainer
 * @copyright  2016 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Completion Progress block class
 *
 * @copyright 2016 Michael de Raadt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_chat_trainer extends block_base {

    /**
     * Sets the block title
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_chat_trainer');
    }

    /**
     *  we have global config/settings data
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }


    public function instance_allow_multiple() {
        return false;
      }
    
    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view'    => true,
            'site'           => true,
            'mod'            => true,
            'page'            => true,
            'my'             => true
        );
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        $showempty = true;
        global $CFG, $USER, $COURSE, $DB, $OUTPUT, $PAGE;
         $this->instance->defaultregion = BLOCK_POS_LEFT;
         $this->instance->defaultweight = 0;
        $DB->update_record('block_instances', $this->instance);
        require_once($CFG->dirroot.'/message/externallib.php');
        require_once($CFG->dirroot . "/message/lib.php");
        require_once($CFG->dirroot . "/message/classes/api.php");
        require_once($CFG->dirroot.'/blocks/chat_trainer/lib.php');
        
        $PAGE->requires->jquery();
        $PAGE->requires->js('/blocks/chat_trainer/javascript/chat_trainer.js');
        $PAGE->requires->js('/blocks/chat_trainer/md/src/ajaxcalls.js');
        $PAGE->requires->js_call_amd('block/block_chat_trainer');

        // If content has already been generated, don't waste time generating it again.
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        $blockinstancesonpage = array();

        if (!isloggedin() or isguestuser()) {
            return $this->content;
        }
        // $conversations = \core_message\api::get_conversations_trainer_chat($USER->id,1,50,\core_message\api::MESSAGE_CONVERSATION_TYPE_GROUP);
        $conversations = block_conversation_list();
        $conversationListOutput = conversationListOutput($conversations);
        $conversationsListHolderOutput = conversationListMessageOutput($conversations);
        $context = $PAGE->context;
        $show_new_converation_link = '';
        $roles = get_user_roles($context, $usreid = $USER->id, true); 
       
        if (has_capability('moodle/course:update', $context) || has_capability('mod/quiz:create', $context) || has_capability('mod/quiz:manage', $context) || has_capability('moodle/question:add', $context)) {
        } else {
            $show_new_converation_link .='<a href="javascript:void(0)" data-chat_heading="New conversation" data-target="#mymessage3" data-conversationid="new"  data-toggle="collapse" class="collapse show" id="new-conversation"> New conversation</a>';
        }
 
        $html =<<<HTML
            <div class="row">
                <div class="col-md-5">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <a href="javascript:void(0)" data-target="#message-list" data-toggle="collapse" class="back-to-list collapse pull-right"> Back</a>
                            $show_new_converation_link
                            <h4 id="chat-heading"></h4>
                        </div>
                    <div class="panel-collapse expand" id="collapseOne">
                        <div class="panel-body">
                            <div class="chat"  >
                                    $conversationListOutput
                                    <div class="accordion-group">
                                        $conversationsListHolderOutput
                                    </div>
                            </div>
                        </div>
                        <div class="panel-footer collapse" id="chat-trainer-chatbox">
                            <span id="typing_indicator_0" class="typing-indicator" data-userid="$usreid"></span>
                            <div class="input-group">
                                
                                <textarea id="trainer-chat-btn-input"  class="form-control input-sm" placeholder="Type your message here..." ></textarea>
                                <span class="input-group-btn">
                                    <button class="btn btn-warning" id="trainer-btn-chat" data-aurl="">
                                        Send</button>
                                </span>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        HTML;

        $this->content->text = $html;
        return $this->content;
    }
}
