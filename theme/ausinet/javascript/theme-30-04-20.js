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
 * theme.js
 * @package    theme_ausinet
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function($) {

// alert();

	if ($('body').hasClass('pagelayout-mydashboard')) {
		$('[data-pref="completed"]').on('click', function() {
			alert();
			set_block_myoverview_complete();
		})
	}

	var set_block_myoverview_complete = function() {
		var block = $('.block_myoverview');
		block.find('.progress-bar').each(function(element) {
		    var val = $(this).attr('aria-valuenow');
		    if (val == '100') {
		        $(this).parents('.dashboard-card').addClass('completed');
		    }
		});
	}


	// Add class on the body tag for no quiz-navigations.
	if( $("#page-mod-quiz-attempt").length != '') {

		if ($(".tag-no-quiz-navigation").length != '') {
			$('body').addClass('no-quiz-navigation');
		}
	}

	if ( $("#page-mod-feedback-complete").length != '' ) {
		// alert()
		$(".breadcrumb-item:last").hide();
	}

	// Set the JSEA question answers prefilled.
	if ($('.select-predefined').length != '' ) {
		$('.select-predefined .custom-select').each(function() {
		     $(this).find('option').each (function() {
		        var text = $(this).text();
		        if (text=='') { $(this).remove()}
		    });
		})
	}

	// Course view page changes.
	if ( $('body').hasClass('path-course-view') )  {
		// Make the mod_resource activity to open the files in new tab.
		$('li.modtype_resource').each( function() {
			$(this).find('.activityinstance').find('.activityicon').parent('a').attr('target', '_blank');
		})

		// remove the restrict access condition if this related to the feedback activity.
		if (!$('body').hasClass('is-admin')) {
			var res = $('#page-content').find('.isrestricted');		
			res.each(function() { 
			    var href = $(this).find('strong a').attr('href'); 
			    if (typeof href != 'undefined'  != -1 ) { 
			        $(this).hide(); 		
			    } 
			})
		}

		$('ul.remui-format-list').find('li.section').each(function() {
			if ($(this).find('.sectionname').next('.isrestricted').length != '') {
				$(this).find('.sectionname a').not('.quickeditlink').append('<i class="fa fa-lock"></i>');
			}
		})
	}

	if ($('body').hasClass('path-mod-questionnaire')) {

		$("#page-mod-questionnaire-complete").find('input[name=selectall]').click(function() {
		    var val = $(this).val();
		    if (val == 'yes') {
		        $('input[type=radio][value=y]').prop('checked', true);
		    } else if (val == 'no') {
		        $('input[type=radio][value=n]').prop('checked', true);
		    }		    
		})
	}

	require(['jquery', 'core/modal_factory'], function($, modal) {
		// alert();
		var trigger = $('#trigger_finish_popup');
		var html = $('.finish-mark-popup').html();
		$('.finish-mark-popup').remove();
		var gradeDialogue = modal.create({
			title: 'Finish Marking',
			body: html,
			footer: ''
		}, trigger).done(function(modal1) {

			$('body').delegate('.btn.grade-close','click', function() {
				modal1.hide();
			});
		});

	})

	$('body').delegate('form.ausinet-grade-all', 'submit', function(e) {
		e.preventDefault();
		var allform = $(this);
		var attempt = $(this).find('input[name="attempt"]').val();
		var cmid = $(this).find('input[name=cmid]').val();
		var grade = $(this).find('input[name=gradeall]:checked').val();
		var feedback = $(this).find('textarea[name=overallfeedback]').val();
		var notifystudent = $(this).find('input[name=notifystudent]').is(':checked');

		var slotList = [];
		$('.ausinet-essay-grade').each(function() {
			var form = $(this);
			var slot = form.find('input[name=slot]').val();
			// var formdata = JSON.stringify(form.serializeArray());
			slotList.push(slot);
		})
		var formData = { 
			'grade_essay' : true, 
			'slots': slotList, 
			'cmid': cmid, 
			'attempt': attempt, 
			'grade': grade,
			'overallfeedback': feedback,
			'notifystudent': notifystudent,
			'method': 'gradeall' 
		};
		var loader = '<div class="loader"><img src="'+M.cfg.wwwroot+'/pix/i/loading_small.gif"></div> ';
			allform.prepend(loader);
		$.ajax({
			url: M.cfg.wwwroot+'/theme/ausinet/ajax.php',
			method: 'POST',
			data: formData,
			success:function(response) {
				var response = JSON.parse(response);
				if (!response.error) {
					// allform.find('div.loader').html('Updated');					
					// form.find('div.loader').fadeOut(5000);
					window.location.reload();
				}
			}
		})
	});

	var getComments = function(form) {

		var itemid = form.find('input[name$=itemid]').val();
		var comment = form.find('div[id$=comment_ideditable]').html();
		var commentdata = {'comment': comment, 'itemid': itemid};
		return commentdata;
	}

	$('form.ausinet-essay-grade').on('submit', function(e) {
		e.preventDefault();
		ausinet_essay_grade($(this));
	})

	/*$('.ausinet-essay-grade').find('input[name=grade][type=radio]').click(function() {
		var radio = $(this).val();
		var form = $(this).parents('form');		
		ausinet_essay_grade(form)
	})
*/
	function ausinet_essay_grade(form) {
		var cmid = form.data('cmid');
		var attempt = form.find('input[name="attempt"]').val();
		var grade = form.find('input[name=grade]:checked').val();
		var slotList = [];
		var itemid = form.find('input[name$=itemid]').val();
		var comment = form.find('div[id$=comment_ideditable]').html();

		var commentdata = {'comment': comment, 'itemid': itemid};
		var slot = form.find('input[name=slot]').val();
			// var formdata = JSON.stringify(form.serializeArray());
		slotList.push(slot);

		require(['core/config', 'core/ajax'], function(config, ajax) {
			var gradeurl = config.wwwroot+'/theme/ausinet/ajax.php';
			var loader = '<div class="loader"><img src="'+M.cfg.wwwroot+'/pix/i/loading_small.gif"></div> ';
			form.find('.comment-question').after(loader);

			$.ajax({
				url: gradeurl,
				method: "POST",
				data: { 
					slots: slotList, 
					comment: commentdata,
					attempt: attempt, 
					cmid: cmid, 
					grade: grade,
					'grade_essay': true
				},
				success:function(response) {
					var response = JSON.parse(response);
					// console.log(response.navpanel);						
					if (!response.error) {
						form.find('div.loader').html('Updated');
						if (response.navpanel != '') {
							var navpanel = response.navpanel;							
							var navcontent = $(navpanel).filter('.qn_buttons').html();
							$('body').find('#mod_quiz_navblock .qn_buttons').html(navcontent);
						}
						// form.find('div.loader').fadeOut(5000);
						setTimeout(function(){form.find('div.loader').remove();}, 5000);
					}
				}
			})
			/*var promises = ajax.call([{
				methodname: 'theme_ausinet_grade_question',
	            args: data
			}]);

			promises[0].done(function(response) {
				console.log(response);
			})*/
		})
	}

	$('.path-mod-quiz .comment-question a').click(function() {
		var commentField = $(this).parents('form').find('.comment-fields');
		var question = $(this).parents('form').parents('.info').parents('.que');
		if (commentField.hasClass('hide')) {
			commentField.removeClass('hide');
			question.addClass('comment-active');
		} else {
			commentField.addClass('hide');		
			question.removeClass('comment-active');				
		}
	})

	$('#selectallgrade').find('input[name=grade]').click(function() {
	    var grade = $(this).val();
	    gradeall(grade);
	});

	function gradeall(grade ) {
	    $('.ausinet-essay-grade').each(function() { 
	    	$(this).find('input[name=grade][value='+grade+']').trigger('click');
	    });
	    $('form.ausinet-essay-grade').each(function() {
	    	ausinet_essay_grade($(this));
	    });
	}

	/* Auto complete dropdown for progress report student selector*/

	/*if ( $('#page-report-progress-index_custom').length != '' ) {

		require(['jqueryui'], function(ui) {
			$('.student-selector').find('select[name=user]').autocomplete({});
		})
	}*/

	function change_continue_button() {
		var loginstr = (!M.str.hasOwnProperty('moodle') || !M.str['moodle'].hasOwnProperty('login')) ? 'Log in' : M.str.moodle.login ;
		$('.continuebutton').find('form button').html(loginstr);
		$('.continuebutton').find('form').attr('action', M.cfg.wwwroot+'/login/index.php');
	}

	if ($('#page-login-signup').find('#notice').length != '') {
		var resendBtn = '<div id="send-confirm-mail"> <a href="javascript:void(0);" class="btn btn-primary" >';
		resendBtn += ' Resend Email </a> </div>';
		if ($('#send-confirm-mail').length == '')
			$('.continuebutton').prepend(resendBtn);
		// chane the login button link and text to login.
		change_continue_button();
		$('body').delegate('#send-confirm-mail', 'click', function() {
			$.ajax({
				url: M.cfg.wwwroot+'/theme/ausinet/ajax.php',
				method: 'POST',
				data: {'resend': true, 'user': signup_user},
				success:function(response) {
					if (!response.error) {
						$(this).prepend('Mail Resend successfully');
					}
				}
			});
		});
	}

	if ($('#page-login-forgot_password').length != '') {
		change_continue_button();
	}

	// console.log(quizattempt);


	// console.log(quizattempt);

	// var quizattempt = ( typeof quizattempt != 'undefined' ) ? quizattempt : {};


	// if (quizattempt != '') {

	// 	$('.subquestion').parent('td').prev('td').each(function() { 
	// 		var td = $(this);
	// 	    var st = $(this).text().trim().toLowerCase();
	// 	    // console.log(st);
	// 	    if (st == 'student name') {
	// 	    	var studentname = quizattempt.studentname;
	// 	    	console.log(td);
	// 	        td.next('td').find('input[type=text][name$=_answer]').val(studentname)
	// 	    }
	// 	})
	// }
		



})(jQuery);