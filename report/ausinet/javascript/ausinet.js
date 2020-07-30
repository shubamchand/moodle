

require(['jquery', 'core/chartjs-lazy'], function($, chart) {


	/**
	 * Used to show a small bar on the chart if the value is 0
	 *
	 * @type Object
	 */
	var showZeroPlugin = {
	    beforeRender: function (chartInstance) {
	        var datasets = chartInstance.config.data.datasets;

	        for (var i = 0; i < datasets.length; i++) {
	            var meta = datasets[i]._meta;
	            // It counts up every time you change something on the chart so
	            // this is a way to get the info on whichever index it's at
	            var metaData = meta[Object.keys(meta)[0]];
	            var bars = metaData.data;

	            for (var j = 0; j < bars.length; j++) {
	                var model = bars[j]._model;

	                if (metaData.type === "horizontalBar" && model.base === model.x) {
	                    model.x = model.base + 2;
	                } else if (model.base === model.y) {
	                    model.y = model.base - 2;
	                }
	            }
	        }

	    }
	};

	// Enabled by default
	Chart.pluginService.register(showZeroPlugin);


	var randomColor = function() {
		return '#'+ ('000000' + Math.floor(Math.random()*16777215).toString(16)).slice(-6);
	}

	function generateBG(element) {
		var backgroundColor = [];
		for (var i=0; i < (element.data).length; i++) {
			backgroundColor.push(randomColor());
		}
		return backgroundColor;
	}

	var barchartoptions = {
	    scales: {
	        xAxes: [{
	            gridLines: {
	                offsetGridLines: true
	            },
	            stepSize: 1,
	            ticks: {
	            	autoSkip: 0
	            }
	        }],
	        yAxes:[{
	        	ticks: {
	            	beginAtZero: true
	            }
	        }]
	    }
	};

	if ($('#enrolement-per-course-chart').length != '' ) {

		var element = $('#enrolement-per-course-chart');

		// console.log(enrolment);

		

		var myBarChart = new Chart(element, {
		    type: 'bar',
		    data: {
			    labels: enrolment.labels,
		    	datasets: enrolment.data
		    },
		    options: barchartoptions
		});
	}

	if ($('#enrolement-method-chart').length != '' ) {

		var element = $('#enrolement-method-chart');

		// console.log(enrolment_method.data);

		// var options = {
		//     scales: {
		//         xAxes: [{
		//             gridLines: {
		//                 offsetGridLines: true
		//             },
		//             stepSize: 1,
		//             ticks: {
		//             	autoSkip: 0
		//             }
		//         }]
		//     }
		// };


		var backgroundColor = [];

		for (var i=0; i < (enrolment_method.data).length; i++) {
			backgroundColor.push(randomColor());
		}

		// console.log(backgroundColor);
		var myBarChart = new Chart(element, {
		    type: 'pie',
		    data: {
		    	datasets: [{
		    		data: enrolment_method.data,
		    		backgroundColor: backgroundColor
		    	}],
			    labels: enrolment_method.labels
		    },
		    options: {
		    	responsive: true
		    }
		});
	}

/* Completion rate chart*/
	if ($('#completion_rate').length != '' ) {

		var element = $('#completion_rate');	

		load_doughnut_chart(element, completionrate);	
		
	}

	if ($('#module-progress-chart').length != '' ) {

		var element = $('#module-progress-chart');	

		load_doughnut_chart(element, module_progress);	
		
	}

	// function creates the doughnut chart. 
	// Param element, chart data variablename.
	function load_doughnut_chart(element, chartdata) {

		var backgroundColor = [];

		for (var i=0; i < (chartdata.data).length; i++) {
			backgroundColor.push(randomColor());
		}

		// console.log(backgroundColor);
		var myBarChart = new Chart(element, {
		    type: 'doughnut',
		    data: {
		    	datasets: [{
		    		data: chartdata.data,
		    		backgroundColor: backgroundColor,
		    		weight: 2444,
		    		hoverBorderWidth: 10
		    	}],
			    labels: chartdata.labels
		    },
		    options: {
		    	// cutoutPercentage: 0,
		    	responsive: true,		    	
		    }
		});
	}

	/* Modules Activities report chart */
	if (typeof module_activities != 'undefined') {
		
		var backgroundColor;
		for (var range in module_activities) {

			module_activities_chart(range);

		}
	}

	function module_activities_chart(range) {

		if ($('#module_activities_'+range).length != '' ) {
			var modulerange_elem = $('#module_activities_'+range);
			var range_data = module_activities[range]; 
			console.log(range_data);
			if (backgroundColor == '') backgroundColor = generateBG(range_data) ;

			var moduleChart = new Chart(modulerange_elem, {
			    type: 'bar',
			    data: {
			    	labels: range_data.labels,
		    		datasets: [{ label: '', data: range_data.data, backgroundColor: backgroundColor, borderColor: backgroundColor, barThickness:6 }],
			    },
			    options: barchartoptions
			});

		}
	}

	/*
	* User registration method compare chart.
	* Pie doughtnut chart
	*/
	if ($('#userregistration_method').length != '') {

		backgroundColor = generateBG(userregistration_method);
		var element = $('#userregistration_method');
		var myBarChart = new Chart(element, {
		    type: 'doughnut',
		    data: {
		    	datasets: [{
		    		data: userregistration_method.data,
		    		backgroundColor: backgroundColor
		    	}],
			    labels: userregistration_method.labels
		    },
		    options: {
		    	responsive: true
		    }
		});
	}
	// }

	// Visits chart.
	if ( $('#visits').length !='' ) {
		
		var visit_element = $('#visits');
		var myBarChart = new Chart(visit_element, {
		    type: 'line',
		    data: {
		    	datasets: [
		    	{
		    		label: 'Total Visits',
		    		data: visits.data.total,
		    		borderColor: randomColor(),
		    		fill:false	    		

		    	},
		    	{
		    		label: 'Course Visits',
		    		data: visits.data.course,
		    		borderColor: randomColor(),
		    		fill:false	    		

		    	},
		    	{	
		    		/*type: 'line',
		    		xAxisID: 'x-axis-2',*/
		    		label: 'Module Visits',
		    		data: visits.data.module,
		    		borderColor: randomColor(),
		    		fill:false
		    	}],
			    labels: visits.labels,
		    },
		    options: {
		    	responsive: true		    	
		    }
		});
	}

	// NEW REgistration chart.
	if ( $('#registration_chart').length !='' ) {
		// console.log(registration_chart.data);
		
		for (var i=0; i < (registration_chart.data).length; i++ ) {
			registration_chart.data[i]['backgroundColor'] = randomColor();
		}

		var registration_chart_element = $('#registration_chart');
		var myBarChart = new Chart(registration_chart_element, {
		    type: 'bar',
		    data: {
			    labels: registration_chart.labels,
		    	datasets: registration_chart.data
		    },
		    options: barchartoptions
		});
	}


	if ($('#unique-userlogin-chart').length != '') {
		var element = $('#unique-userlogin-chart');
		line_chart(element, unique_userlogin);
	}
	if ($('#enrollments-chart').length != '') {
		var element = $('#enrollments-chart');
		line_chart(element, enrollments, true);
	}

	if ($('#enrollments-chart').length != '') {
		var element = $('#enrollments-chart');
		line_chart(element, enrollments, true);
	}


	function line_chart(element, chartdata, skip=false) {
		// console.log(chartdata);
		var myBarChart = new Chart(element, {
		    type: 'line',
		    data: {
		    	datasets: chartdata.data,
		    	labels: chartdata.labels
		    },
		    options: {
		    	responsive: true,
		    	scales: {
		    		yAxes:[{
			        	ticks: {
			            	beginAtZero: true,			            	
			            }

			        }],
			        xAxes:[{
			        	ticks: {			            	
			            	autoSkip: skip
			            }
			        }]
		    	}		    	
		    }
		});		    	
	}


	// console.log(user_activities);
	/* Modules Activities report chart */
	if (typeof user_activities != 'undefined') {
		
		var backgroundColor;
		for (var range in user_activities) {

			user_activities_chart(range);

		}
	}

	function user_activities_chart(range) {

		if ($('#user_activities_'+range).length != '' ) {
			var modulerange_elem = $('#user_activities_'+range);
			var range_data = user_activities[range]; 
			console.log(range_data);
			if (backgroundColor == '') backgroundColor = generateBG(range_data) ;

			var userloginchart = line_chart(modulerange_elem, range_data);

		}
	}

})


// (function($) {

// 	$(window).load(function($) {
// 		alert();
// 		$("#assignment_table").DataTable();
// 	})

// }) (jQuery)