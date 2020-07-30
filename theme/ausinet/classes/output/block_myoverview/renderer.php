<?php

namespace theme_ausinet\output\block_myoverview;

class renderer extends \block_myoverview\ouput\renderer {

	public function render_main(main $main) {
		global $CFG;
		$data = $main->export_for_template($this);
		$data['siteurl'] = $CFG->wwwroot;
        return $this->render_from_template('block_myoverview/main', $data);
    }
}