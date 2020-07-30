<?php

namespace theme_ausinet\output;

class block_myoverview_renderer extends \block_myoverview\output\renderer {

	public function render_main(\block_myoverview\output\main $main) {
		$data =  $main->export_for_template($this);		
        return $this->render_from_template('block_myoverview/main', $data);
    }
}