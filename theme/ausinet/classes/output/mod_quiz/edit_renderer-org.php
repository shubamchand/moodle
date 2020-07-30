<?php 
namespace theme_ausinet\output\mod_quiz;

use moodle_url;
use \mod_quiz\structure;
use \html_writer;

class edit_renderer extends \mod_quiz\output\edit_renderer {



	public function add_menu_actions( $structure, $page, \moodle_url $pageurl,
            \question_edit_contexts $contexts, array $pagevars) {
		
        
		
        $actions = $this->edit_menu_actions($structure, $page, $pageurl, $pagevars);
        if (empty($actions)) {
            return '';
        }
        $menu = new \action_menu();
        $menu->set_alignment(\action_menu::TR, \action_menu::TR);
        $menu->set_constraint('.mod-quiz-edit-content');
        $trigger = html_writer::tag('span', get_string('add', 'quiz'), array('class' => 'add-menu'));
        $menu->set_menu_trigger($trigger);
        // The menu appears within an absolutely positioned element causing width problems.
        // Make sure no-wrap is set so that we don't get a squashed menu.
        $menu->set_nowrap_on_items(true);

        // Disable the link if quiz has attempts.
        if (!$structure->can_be_edited()) {            
            return $this->render($menu);
        }

        $link = '<div class="ausinet-question-add"> ';
        foreach ($actions as $action) {
            if ($action instanceof \action_menu_link) {
                $action->add_class('add-menu');
            }
            $menu->add($action);
            $link .= $this->render($action);
            // print_object($action);
        }
        $menu->attributes['class'] .= '  page-add-actions commands';

        $link .= '</div>';
        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;
        // print_object($menu);
// render_action_menu()
        return $link.$this->render($menu);
    }
}
