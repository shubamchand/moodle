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
 * @package   block_chat_trainer
 * @copyright 2012 Dakota Duff
 */
const MESSAGE_CONVERSATION_TYPE_GROUP = 2;
const MESSAGE_ACTION_READ = 1;
const MESSAGE_ACTION_DELETED = 2;
/**
 * Returns CSV values from provided array
 * @param array $array The array to implode
 * @return string $string
 */
function block_chat_trainer_array2str($array) {
    if (count($array)) {
        $string = implode(',', $array);
    } else {
        $string = null;
    }
    return $string;
}

function block_chat_form(){
    global $CFG;
    require_once($CFG->dirroot.'/blocks/chat_trainer/chat_trainer_form.php');
        //Instantiate simplehtml_form 
        $mform = new chat_trainer_form();
		//Form processing and displaying is done here
		if ($mform->is_cancelled()) {
			//Handle form cancel operation, if cancel button is present on form
		} else if ($fromform = $mform->get_data()) {
			//In this case you process validated data. $mform->get_data() returns data posted in form.
		} else {
			// this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
			// or on the first display of the form.

			//Set default data (if any)
			$mform->set_data($toform);

			//displays the form
			return $mform->render();
		}


}

function block_chat_message(){
    
}

function block_chat_list(){

}

function block_conversation_list(){
        global $CFG, $USER, $COURSE, $DB, $OUTPUT, $PAGE;
        
        $type = MESSAGE_CONVERSATION_TYPE_GROUP;
        $userid = $USER->id;
        $mergeself = false;
        $context = $PAGE->context;
      
      

        // We need to know which conversations are favourites, so we can either:
        // 1) Include the 'isfavourite' attribute on conversations (when $favourite = null and we're including all conversations)
        // 2) Restrict the results to ONLY those conversations which are favourites (when $favourite = true)
        // 3) Restrict the results to ONLY those conversations which are NOT favourites (when $favourite = false).
        $service = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($userid));
        $favouriteconversations = $service->find_favourites_by_type('core_message', 'message_conversations');
        $favouriteconversationids = array_column($favouriteconversations, 'itemid');
        if ($favourites && empty($favouriteconversationids)) {
        return []; // If we are aiming to return ONLY favourites, and we have none, there's nothing more to do.
        }

       

        // CONVERSATIONS AND MOST RECENT MESSAGE.
        // Include those conversations with messages first (ordered by most recent message, desc), then add any conversations which
        // don't have messages, such as newly created group conversations.
        // Because we're sorting by message 'timecreated', those conversations without messages could be at either the start or the
        // end of the results (behaviour for sorting of nulls differs between DB vendors), so we use the case to presort these.

        // If we need to return ONLY favourites, or NO favourites, generate the SQL snippet.
        $favouritesql = "";
        $favouriteparams = [];
        if (null !== $favourites && !empty($favouriteconversationids)) {
        list ($insql, $favouriteparams) =
                $DB->get_in_or_equal($favouriteconversationids, SQL_PARAMS_NAMED, 'favouriteids', $favourites);
        // $favouritesql = " AND mc.id {$insql} ";
        }

        // If we need to restrict type, generate the SQL snippet.
        $typesql = "";
        $typeparams = [];
        if (!is_null($type)) {
            $typesql = " AND mc.type = :convtype ";
            $typeparams = ['convtype' => $type];
        }

        if (has_capability('moodle/course:update', $context) || has_capability('mod/quiz:create', $context) || has_capability('mod/quiz:manage', $context) || has_capability('moodle/question:add', $context)) {
            $sql = trainer_sql($typesql,$favouritesql);
        } else {
            $sql = user_sql($typesql,$favouritesql);
        }


        
        // echo '<pre>';
        // echo $sql;
        // exit;

        $params = array_merge($favouriteparams, $typeparams, ['userid' => $userid, 'action' => \core_message\api::MESSAGE_ACTION_DELETED,
            'userid2' => $userid, 'userid3' => $userid, 'userid4' => $userid, 'convaction' => \core_message\api::CONVERSATION_ACTION_MUTED]);
        $conversationset = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
        $conversations = [];
        $selfconversations = []; // Used to track conversations with one's self.
        $members = [];
        $individualmembers = [];
        $groupmembers = [];
        $selfmembers = [];
        foreach ($conversationset as $conversation) {
            $conversations[$conversation->id] = $conversation;
            $members[$conversation->id] = [];
        }
        $conversationset->close();

        // echo '<pre>';
        // print_r($conversations);
        // exit;

        foreach ($conversations as $conversation) {
            if (!is_null($conversation->useridfrom)) {
                $members[$conversation->id][$conversation->useridfrom] = $conversation->useridfrom;
                $groupmembers[$conversation->useridfrom] = $conversation->useridfrom;
            }
        }

        if (!empty($individualmembers) || !empty($groupmembers) || !empty($selfmembers)) {
            require_once($CFG->dirroot . "/message/classes/helper.php");
            // Now, we want to remove any duplicates from the group members array. For individual members we will
            // be doing a more extensive call as we want their contact requests as well as privacy information,
            // which is not necessary for group conversations.
            $diffgroupmembers = array_diff($groupmembers, $individualmembers);
            // echo '<pre>';
            // print_r($diffgroupmembers);
            // echo $userid;
            // exit;
            // $individualmemberinfo = \core_message\helper::get_member_info($userid, $individualmembers, true, true);
            $groupmemberinfo = \core_message\helper::get_member_info($userid, $diffgroupmembers);
            
            // $selfmemberinfo = helper::get_member_info($userid, $selfmembers);

            // Don't use array_merge, as we lose array keys.
            $memberinfo = $groupmemberinfo;

            if (empty($memberinfo)) {
                return [];
            }

            // Update the members array with the member information.
            $deletedmembers = [];
            foreach ($members as $convid => $memberarr) {
                foreach ($memberarr as $key => $memberid) {
                    if (array_key_exists($memberid, $memberinfo)) {
                        // If the user is deleted, remember that.
                        if ($memberinfo[$memberid]->isdeleted) {
                            $deletedmembers[$convid][] = $memberid;
                        }

                        $members[$convid][$key] = clone $memberinfo[$memberid];

                        if ($conversations[$convid]->conversationtype == $type) {
                            // Remove data we don't need for group.
                            $members[$convid][$key]->requirescontact = null;
                            $members[$convid][$key]->canmessage = null;
                            $members[$convid][$key]->contactrequests = [];
                        }
                    } else { // Remove all members and individual conversations where we could not get the member's information.
                        unset($members[$convid][$key]);

                        // If the conversation is an individual conversation, then we should remove it from the list.
                        
                    }
                }
            }
        }

        // print_r($members);
            // echo $userid;
            // exit;
       // Now, create the final return structure.
       $arrconversations = [];
       foreach ($conversations as $conversation) {

           $conv = new \stdClass();
           $conv->id = $conversation->id;

           // Name should be formatted and depends on the context the conversation resides in.
           // If not set, the context is always context_user.
           if (is_null($conversation->contextid)) {
               $convcontext = \context_user::instance($userid);
               // We'll need to check the capability to delete messages for all users in context system when contextid is null.
               $contexttodeletemessageforall = \context_system::instance();
           } else {
               $convcontext = \context::instance_by_id($conversation->contextid);
               $contexttodeletemessageforall = $convcontext;
           }
           $conv->name = format_string($conversation->conversationname, true, ['context' => $convcontext]);

           $conv->subname = $convextrafields[$conv->id]['subname'] ?? null;
           $conv->imageurl = $convextrafields[$conv->id]['imageurl'] ?? null;
           $conv->type = $conversation->conversationtype;
           $conv->membercount = $membercounts[$conv->id]->membercount;
           $conv->isfavourite = in_array($conv->id, $favouriteconversationids);
           $conv->isread = isset($unreadcounts[$conv->id]) ? false : true;
           $conv->unreadcount = isset($unreadcounts[$conv->id]) ? $unreadcounts[$conv->id]->unreadcount : null;
           $conv->ismuted = $conversation->ismuted ? true : false;
           $conv->members = $members[$conv->id];

           // Add the most recent message information.
           $conv->messages = [];
           // Add if the user has to allow delete messages for all users in the conversation.
           $conv->candeletemessagesforallusers = has_capability('moodle/site:deleteanymessage',  $contexttodeletemessageforall);
           if ($conversation->smallmessage) {
               $msg = new \stdClass();
               $msg->id = $conversation->messageid;
               $msg->text = message_format_message_text($conversation);
               $msg->useridfrom = $conversation->useridfrom;
               $msg->timecreated = $conversation->timecreated;
               $conv->messages[] = $msg;
           }

           $arrconversations[] = $conv;
       }
       return $arrconversations;
        
        // }
        // If there are no conversations found, then return early.
        // if (empty($conversations)) {
        //     return [];
        // }
       
       

}

    function user_sql($typesql,$favouritesql){
        $sql = "SELECT m.id as messageid, mc.id as id, mc.name as conversationname, mc.type as conversationtype, m.useridfrom,
                       m.smallmessage, m.fullmessage, m.fullmessageformat, m.fullmessagetrust, m.fullmessagehtml, m.timecreated,
                       mc.component, mc.itemtype, mc.itemid, mc.contextid, mca.action as ismuted
                  FROM {message_conversations} mc
            INNER JOIN {message_conversation_members} mcm
                    ON (mcm.conversationid = mc.id AND mcm.userid = :userid3)
            LEFT JOIN (
                          SELECT m.conversationid, MAX(m.id) AS messageid
                            FROM {messages} m
                      INNER JOIN (
                                      SELECT m.conversationid, MAX(m.timecreated) as maxtime
                                        FROM {messages} m
                                  INNER JOIN {message_conversation_members} mcm
                                          ON mcm.conversationid = m.conversationid
                                   LEFT JOIN {message_user_actions} mua
                                          ON (mua.messageid = m.id AND mua.userid = :userid AND mua.action = :action)
                                       WHERE mua.id is NULL
                                         AND mcm.userid = :userid2
                                    GROUP BY m.conversationid
                                 ) maxmessage
                               ON maxmessage.maxtime = m.timecreated AND maxmessage.conversationid = m.conversationid
                         GROUP BY m.conversationid
                       ) lastmessage
                    ON lastmessage.conversationid = mc.id
            LEFT JOIN {messages} m
                   ON m.id = lastmessage.messageid
            LEFT JOIN {message_conversation_actions} mca
                   ON (mca.conversationid = mc.id AND mca.userid = :userid4 AND mca.action = :convaction)
                WHERE mc.id IS NOT NULL
                  AND mc.enabled = 1 $typesql $favouritesql
              ORDER BY (CASE WHEN m.timecreated IS NULL THEN 0 ELSE 1 END) DESC, m.timecreated DESC, id DESC";
        return $sql;
    }

    function trainer_sql($typesql,$favouritesql){
        $sql = "SELECT m.useridfrom, m.id as messageid, mc.id as id, mc.name as conversationname, mc.type as conversationtype, m.useridfrom,
                       m.smallmessage, m.fullmessage, m.fullmessageformat, m.fullmessagetrust, m.fullmessagehtml, m.timecreated,
                       mc.component, mc.itemtype, mc.itemid, mc.contextid, mca.action as ismuted
                  FROM {message_conversations} mc
            INNER JOIN {message_conversation_members} mcm
                    ON (mcm.conversationid = mc.id)
            LEFT JOIN (
                          SELECT m.conversationid, MAX(m.id) AS messageid, m.useridfrom
                            FROM {messages} m
                      INNER JOIN (
                                      SELECT m.conversationid, MAX(m.timecreated) as maxtime
                                        FROM {messages} m
                                  INNER JOIN {message_conversation_members} mcm
                                          ON mcm.conversationid = m.conversationid
                                   LEFT JOIN {message_user_actions} mua
                                          ON (mua.messageid = m.id AND mua.userid = m.useridfrom AND mua.action = :action)
                                       WHERE mua.id is NULL
                                         AND mcm.userid = m.useridfrom
                                    GROUP BY m.conversationid
                                 ) maxmessage
                               ON maxmessage.maxtime = m.timecreated AND maxmessage.conversationid = m.conversationid
                         GROUP BY m.conversationid
                       ) lastmessage
                    ON lastmessage.conversationid = mc.id
            LEFT JOIN {messages} m
                   ON m.id = lastmessage.messageid
            LEFT JOIN {message_conversation_actions} mca
                   ON (mca.conversationid = mc.id AND mca.userid = lastmessage.useridfrom AND mca.action = :convaction)
                WHERE mc.id IS NOT NULL
                  AND mc.enabled = 1 and itemid IS NULL and contextid IS NULL$typesql $favouritesql
              ORDER BY (CASE WHEN m.timecreated IS NULL THEN 0 ELSE 1 END) DESC, m.timecreated DESC, id DESC";
              return $sql;

    }
    function conversationListOutput($conversations){
        global $CFG, $USER,$PAGE;
        $output = '<ul id="message-list" class="chat-group collapse show">';
        if(empty($conversations)){
                $output .='<li data-userid="'.$USER->id.'" class="trainer-chat-group-nomessage left clearfix message send clickable d-flex flex-column p-2 mx-1 position-relative rounded mb-2 mt-2" data-chat_heading="New conversation">No conversation!</li>';
        }
        foreach($conversations as $conversation){
            
            $members = getMembersAsArray($conversation);
            // $formated_conversation_name = formate_converation_name($members);/
            $formated_conversation_name = $conversation->name;
            $last_message = $conversation->messages[0];
            $last_message_from = $members[$last_message->useridfrom]['fullname'];
            $member_ids = array_keys($members);
            $ago_time = time_elapsed_string(date('Y-m-d H:i:s',$last_message->timecreated));
            $output .='<li data-is_in_conversation="'.is_conversation_member($USER->id,$members).'" data-formatedcname="'.$formated_conversation_name.'" data-owner="'.$conversation->useridfrom.'" data-users="'.implode(',',$member_ids).'" data-userid="'.$USER->id.'" class="trainer-chat-group left clearfix message send clickable d-flex flex-column p-2 mx-1 position-relative rounded mb-2 mt-2" data-chat_heading="'.$formated_conversation_name.'" data-target="#'.$conversation->id.'" data-parent="#message-list" data-conversationid="'.$conversation->id.'"  data-toggle="collapse">';
            $output .='<small><i>'.$conversation->name.'</i><i class="pull-right">'.$ago_time.'</i></small><p> <span class="trainer_last_message color-red">'.$last_message->text.'<small><i class="pull-right">'.$last_message_from.'</i></small></p>';
            $output .= '</li>';
        }
        $output .='</ul>';
        return $output;

    }


    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
    
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
    
        $string = array(
            'y' => 'y',
            'm' => 'M',
            'w' => 'w',
            'd' => 'd',
            'h' => 'h',
            'i' => 'm',
            's' => 's',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . '' . $v . ($diff->$k > 1 ? '' : '');
            } else {
                unset($string[$k]);
            }
        }
        // return json_encode($string);
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(',', $string) . '' : 'just now';
    }
    

    function formate_converation_name($members){
        $name = '';
        if(empty($members)){

        } else {
            foreach($members as $member){
                $name_pieces = explode(' ',$member['fullname']);
                if(count($name_pieces)>1){
                    $name .= $name_pieces[0];
                } else {
                    $name .= $member['fullname'];
                }
            }
        }
        return $name;
    }

    function is_conversation_member($userid,$members){
        if(isset($members[$userid])){
            return 'yes';
        } else return 'no';

    }

    function getMembersAsArray($conversation){
        if(empty($conversation->members)){
            return [];
        } else {
            $members = [];
            foreach($conversation->members as $key=>$member){
                $members[$key]['id'] = $key;
                $members[$key]['fullname'] = $member->fullname;
            }
            return $members;
        }


    }
    function conversationListOutput_backup($conversations){
        global $CFG, $USER;
        $output = '<div id="message-list" class="chat-group collapse show">';
        if(empty($conversations)){
            $output = '<p>No message to show</p>';
            // $output .='<button data-userid="'.$USER->id.'" class="trainer-chat-group left clearfix message send clickable d-flex flex-column p-2 mx-1 position-relative rounded mb-2 mt-2" data-chat_heading="New conversation" data-target="#mymessage3" data-conversationid="new"  data-toggle="collapse" class="collapse pull-right show" id="new-conversation">Start new conversation</button>';
        }
        foreach($conversations as $conversation){
           $output .='<button data-userid="'.$USER->id.'"  class="trainer-chat-group left clearfix message send clickable d-flex flex-column p-2 mx-1 position-relative rounded mb-2 mt-2" data-chat_heading="'.$conversation->name.'" data-target="#'.$conversation->id.'" data-parent="#message-list" data-conversationid="'.$conversation->id.'"  data-toggle="collapse">'.$conversation->smallmessage.'</button>';
        }
        $output .='</div>';
        return $output;

    }

    function conversationListMessageOutput($conversations){
        global $CFG, $USER;
        $output = '';
        foreach($conversations as $conversation){
        $output .= '<ul class="chat-list-ul collapse" id="'.$conversation->id.'">';
        $output .= '</ul>';
        }
        return $output;

    }




/**
     * Gets extra fields, like image url and subname for any conversations linked to components.
     *
     * The subname is like a subtitle for the conversation, to compliment it's name.
     * The imageurl is the location of the image for the conversation, as might be seen on a listing of conversations for a user.
     *
     * @param array $conversations a list of conversations records.
     * @return array the array of subnames, index by conversation id.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    function get_linked_conversation_extra_fields(array $conversations) : array {
        global $DB, $PAGE;

        $renderer = $PAGE->get_renderer('core');

        $linkedconversations = [];
        foreach ($conversations as $conversation) {
            if (!is_null($conversation->component) && !is_null($conversation->itemtype)) {
                $linkedconversations[$conversation->component][$conversation->itemtype][$conversation->id]
                    = $conversation->itemid;
            }
        }
        if (empty($linkedconversations)) {
            return [];
        }

        // TODO: MDL-63814: Working out the subname for linked conversations should be done in a generic way.
        // Get the itemid, but only for course group linked conversation for now.
        $extrafields = [];
        if (!empty($linkeditems = $linkedconversations['core_group']['groups'])) { // Format: [conversationid => itemid].
            // Get the name of the course to which the group belongs.
            list ($groupidsql, $groupidparams) = $DB->get_in_or_equal(array_values($linkeditems), SQL_PARAMS_NAMED, 'groupid');
            $sql = "SELECT g.*, c.shortname as courseshortname
                      FROM {groups} g
                      JOIN {course} c
                        ON g.courseid = c.id
                     WHERE g.id $groupidsql";
            $courseinfo = $DB->get_records_sql($sql, $groupidparams);
            foreach ($linkeditems as $convid => $groupid) {
                if (array_key_exists($groupid, $courseinfo)) {
                    $group = $courseinfo[$groupid];
                    // Subname.
                    $extrafields[$convid]['subname'] = format_string($courseinfo[$groupid]->courseshortname);

                    // Imageurl.
                    $extrafields[$convid]['imageurl'] = $renderer->image_url('g/g1')->out(false); // default image.
                    if ($url = get_group_picture_url($group, $group->courseid, true)) {
                        $extrafields[$convid]['imageurl'] = $url->out(false);
                    }
                }
            }
        }
        return $extrafields;
    }

function block_conversation(){

}



/**
 * Construct the tree of ungraded items
 * @param array $course The array of ungraded items for a specific course
 * @return string $text
 */
function block_chat_trainer_tree($courses) {
    global $CFG, $DB, $OUTPUT, $SESSION;

    // Grading image.
    $gradeimg = $CFG->wwwroot.'/blocks/chat_trainer/pix/check_mark.png';
    // Define text variable.
    $text = '';
    $text .= '<div>';
  
    $text .= '</div>';

    return $text;
}
