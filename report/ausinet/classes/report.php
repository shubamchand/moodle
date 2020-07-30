<?php 


namespace report_ausinet;

class report {

	public $ranges;

	public function __construct() {
		
		$this->ranges = array('today', 'lastday', 'week', 'month', 'year');
		// $this->coursereport = new \report_ausinet\course_report;
		// $this->sitedatelables();
	}

	public function randam_color() {
   		return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
	}

	public function get_count($table) {
		global $DB;
		return $DB->count_records($table);
	}

	public function get_ranges() {
		$this->ranges = array('today', 'lastday', 'week', 'month', 'year');
		return $this->ranges;
	}

	public function get_date_labels($from='') {
		global $SITE;

		$fromdate = (!empty($from)) ? $from : $SITE->timecreated;
		$enddate = strtotime('today');
		$nextdate = $fromdate;
		$labels = [];
		while ($nextdate <= $enddate ) {
			$nextdate = strtotime('+1 day', $nextdate);
			$labels[] = date('Y-m-d', $nextdate);
			// echo 'asdf';
		}
		// echo $nextdate = strtotime('+1 day', $fromdate);

		return $labels;
	}



	public function get_date_range($time) {
		switch($time) {
			case 'lastday':
				$timestart = strtotime('yesterday');
				$timeend = strtotime('today') - 1;
				break;
			case 'week':
				$timestart = strtotime('-7 day');
				$timeend = strtotime('today') - 1;
				break;
			case 'month':
				$timestart = strtotime('-1 month');
				$timeend = strtotime('today') - 1;
				break;
			case 'year':
				$timestart = strtotime('-1 year'); 
				$timeend = strtotime('today') - 1;
				break;
			default:				
				$timestart = strtotime('today');
				$timeend = time();
				break;
		}
		return [ 'timestart' => $timestart, 'timeend' => $timeend ];
	}


	public function add_js_data($variable, $data) {
		global $PAGE;
		$PAGE->requires->data_for_js($variable, $data);
	}
	

	/*public static function get($func) {
		
		$self = new \report_ausinet\report;

		return (method_exists($self, $func)) ? $self->{$func}() : '';
	}*/

	/**
	* PARAM : $variable => name of the javascript variable| string.
	* PARAM 2:  $reportdata => $reports data set | array.
	* PARAM 3: $template templatename|string ex: course_report, site_report, user_report.
	* PARAM $: $daterange It's have daterange chart|array.
	*/
	public function compare_chart($variable, $reportdata, $template='blocks', $daterange=false ) {
		global $OUTPUT;

		if ($daterange) {
			foreach ($reportdata as $range => $data) {
				$dataset = array_map(function($value) { return (int)$value; }, array_values($data));
				$report[$range] = array('data' => $dataset,  'labels' => array_map('ucfirst', array_keys($data)) );
			}
			
		} else {
			$dataset = array_map(function($value) { return (int) $value; }, array_values($reportdata));
			$report = array('data' => $dataset,  'labels' => array_map('ucfirst', array_keys($reportdata)) );
		}
		
		$this->add_js_data($variable, $report );
		
		return $this->render_block($template, array($variable => 1));
		
	}

	// param1 => Record
	// param2 => multiple lineseries ex: [tiemcreated => module, timecreated => course].
	// Param3 => x.
	// Gerenate the line chart.
	public function generate_line_chartdata($records, $series=array(), $fill=true) {
		if (!empty($series)) {
			$records = array_values($records);
			// print_object($records);			
			$report = $x = $counts = [];
			foreach ($series as $xkey => $ykey) {
				$x = array_column($records, $xkey);

				$y = array_column($records, $ykey);
				$combined_xy = array_combine($x, $y);
				$combined_xy = $this->linechart_convert_xy($combined_xy);
				$counts = $this->sum_report_array($combined_xy, 'y');
						
				$report[] = [ 
					'data' => array_values($combined_xy), 
					'label' => ucfirst($xkey), 
					'borderColor' => $this->randam_color(),
					'fill' => $fill
				];
				$counts[$xkey] = $counts;
			}
			// set last series x value as chart label.
			$lineseries = ['data' => array_values($report), 'labels' => $x, 'counts' => $counts];

			return $lineseries;
		}
	}

	public function linechart_convert_xy( $combined_xy =array() ) {
		if (is_array($combined_xy) ) {
			// uksort($combined_xy, array($this, 'sort_dates')); // Sorting based on the date timecreated.
			array_walk($combined_xy, function(&$value, $key) {
				$value = ['x' => $key, 'y' => $value];
			});
		}
		return $combined_xy;
	}

	public function line_chart($variable, $reportdata, $template='blocks', $daterange=false ) {
	
		$this->add_js_data($variable, $reportdata );	
		
		return $this->render_block($template, array($variable => 1, 'counts' => isset($reportdata['counts']) ? $reportdata['counts'] : '' ));
	}

	public function sort_dates($a, $b) {
		$t1 = strtotime($a);
    	$t2 = strtotime($b);
    	return $t1 - $t2;
	}

	public function barchart_data($record, $dataset=[]) {

		foreach ($dataset as $field) {
			$result[] = array( 'label' => $field, 'data' => array_column(array_values($record), $field), 'backgroundColor' => 'blue' );
		}

		return $result;
	}


	public function chart_series($dataset, $title) {
		$series = new \core\chart_series($title, $dataset);
		return $series;
	}

	public function get_count_records($records, $key) {
		if ($records) {
			foreach ($records as $id => $record) {
				$report[$record->{$key}] = $record->count;
			}
			return $report;
		}
		return [];
	}

	public function sum_report($dataset) {
		return array_sum( array_values($dataset) );
	}

	public function sum_report_array($dataset, $search=false) {
		$result= [];
		if (is_array($search)) {
			 foreach ($search as $field) {
			 	$result[$field] = array_sum(array_column(array_values($dataset), $field ) );
			 }
		} elseif ($search) {
			foreach ($dataset as $key => $data) {
				$result[$key] = array_sum(array_column(array_values($data), $search ) );
			}
		} else  {
			$result = array_map(function($values) { return array_sum(array_values($values) ); } , $dataset);
		}

		return $result;
	}

/*	public function get_enrolled_sql() {

		"SELECT DISTINCT eu4_u.id
              FROM {user} eu4_u
            JOIN {user_enrolments} ej4_ue ON ej4_ue.userid = eu4_u.id
JOIN {enrol} ej4_e ON (ej4_e.id = ej4_ue.enrolid AND ej4_e.courseid = :ej4_courseid)
JOIN {role_assignments} eu4_ra3
                    ON ( eu4_ra3.userid = eu4_u.id
                    AND eu4_ra3.roleid IN (5)
                    AND eu4_ra3.contextid IN (1,3,62,63,137,230) )
             WHERE 1 = 1 AND ej4_ue.status = :ej4_active AND ej4_e.status = :ej4_enabled AND ej4_ue.timestart < :ej4_now1 AND (ej4_ue.timeend = 0 OR ej4_ue.timeend > :ej4_now2) AND (eu4_u.id <> :eu4_guestid) AND eu4_u.deleted = 0"
	}
*/

	public function table_report($headers, $records, $class='') {
		$table = $this->get_tablehead($headers, $class);
		foreach ($records as $key => $dataset) {
			$row = new \html_table_row();
			foreach ($dataset as $key => $data) {
				$row->cells[] = $data;
			}
			$table->data[] = $row;
		}
		return \html_writer::table($table);
	}

	function get_tablehead($headers, $class) {
		$table = new \html_table(['class' => $class]);
		$table->head = $headers;
		return $table;
	}

	public function render_block($template, $data) {
		global $OUTPUT;
		return $OUTPUT->render_from_template('report_ausinet/'.$template, $data);
	}
}