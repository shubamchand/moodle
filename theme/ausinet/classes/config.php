<?php 

namespace theme_ausinet;

class config {

	public function get_header_config() {
		$logourl = $this->get_logo_url();
		
		$config = array(
			'logourl' => $logourl,
			'displayLogo' => ($logourl) ? true : false,
		);

		return $config;
	}



	public function get_logo_url() {
		return theme_ausinet_get_setting('logo', 'file');
	}

	public function get_footer_config() {	
		global $PAGE;
		if ( !empty($PAGE->layout_options['nofooter'] ) ) {
			$config['footer'] = '0';
		} else {
			$config['footer'] = '1';
		}
		$config['copyright'] = theme_ausinet_get_setting('copyright');
		$config['footerlink'] = theme_ausinet_get_setting('footerlink');
		return $config;
	}

	public function get_homebanner_config() {
		global $CFG;
		$configs = ['togglebanner', 'bannercaption', 'bannerdescription', 'bannerlink'];
		foreach ($configs as $config) {
			$data[$config] = theme_ausinet_get_setting($config);
		}		
		$data['bannertext'] = (isloggedin()) ? get_string('readmore', 'theme_ausinet') : get_string('login');
		if (!isloggedin())
			$data['bannerlink'] = $CFG->wwwroot.'/login/index.php';
		
		$data['bannerimage'] = theme_ausinet_get_setting('bannerimage', 'file');
		return $data;
	}

	public function get_aboutus_config() {
		$configs = ['toggleaboutus', 'aboutus_title', 'aboutus_content', 'aboutus_link'];
		foreach($configs as $config) {
			$data[$config] = theme_ausinet_get_setting($config);
		}
		$data['aboutus_image'] = theme_ausinet_get_setting('aboutus_image', 'file');
		return $data;
	}
}


?>