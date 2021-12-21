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

	if (!M.cfg.developerdebug) {
		var disablecopyelement = $('.pagelayout-incourse .que .qtext, .path-mod-questionnaire .qn-question, .path-mod-observation .qn-question');
		if (disablecopyelement.length >= 1) {
			// disablecopyelement.attr('onmousedown', "return false");
			disablecopyelement.attr('onselectstart', "return false");			
			disablecopyelement.on('mousedown', function(e) { 
				if(e.target.localName == 'select' || e.target.localName == 'input' ) { 
					return true; 
				} 
				return false; 
			})
		}		
	}

	function disablerightclick(e) {

		e = (e || window.event);
		if (e.button == 2 || e.keyCode == 123) {
			alert(status);
			return false;
		}
	}

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

		require(['core/config'], function(config) {
			var date_element = $("#page-mod-questionnaire-complete").find('#phpesp_response input[type=date]');
			if (date_element.val() == '') {
				var date = new Date().toLocaleString('en-US', {timeZone: config.usertimezone}).split(' ')
				var day = date[0].split('/')[1];
				var month = date[0].split('/')[0];
				var year = date[0].split('/')[2].replace(',', '');
				if (month.length < 2) { 
	    			month = '0' + month;
	    		}
			    if (day.length < 2) {
			        day = '0' + day;
			    }				   
				date_element.val(year+'-'+month+'-'+day);
			}
		});

		$("#page-mod-questionnaire-complete").find('input[name=selectall]').click(function() {
		    var val = $(this).val();
		    if (val == 'yes') {
		        $('input[type=radio][value=y]').prop('checked', true);
		    	var result = $('.qn-answer').find('label');
		    	result.each(function(){		    		
			        if ($(this).text() == 'Competent') {
			        	$(this).prev('input[type=radio]').prop('checked', true);
			        }
		    	})

		    } else if (val == 'no') {
		        $('input[type=radio][value=n]').prop('checked', true);
		        var result = $('.qn-answer').find('label');
		    	result.each(function(){		    		
			        if ($(this).text() == 'Not Competent') {
			        	$(this).prev('input[type=radio]').prop('checked', true);
			        }
		    	})
		    }		    
		})		
	}

	if ($('body').hasClass('path-mod-observation')) {

		require(['core/config'], function(config) {

			var date_element = $("#page-mod-observation-complete").find('#phpesp_response input[type=date]');
			if (date_element.val() == '') {			
				var date = new Date().toLocaleString('en-US', {timeZone: config.usertimezone}).split(' ')
				var day = date[0].split('/')[1];
				var month = date[0].split('/')[0];
				var year = date[0].split('/')[2].replace(',', '');
				if (month.length < 2) { 
	    			month = '0' + month;
	    		}
			    if (day.length < 2) {
			        day = '0' + day;
			    }				   
				date_element.val(year+'-'+month+'-'+day);
			}		
		})

		if ($('body').hasClass('student')) {
			$('form').find('input').attr('disabled', 'disabled');
			$('form').find('textarea').attr('disabled', 'disabled');			
		}

		$("#page-mod-observation-complete").find('input[name=selectall]').click(function() {
		    var val = $(this).val();
		    if (val == 'yes') {
		        $('input[type=radio][value=y]').prop('checked', true);
		    	var result = $('.qn-answer').find('label');
		    	result.each(function(){		    		
			        if ($(this).text() == 'Competent' || $(this).text().toLowerCase() == 'satisfactory') {
			        	$(this).prev('input[type=radio]').prop('checked', true);
			        }
		    	})

		    } else if (val == 'no') {
		        $('input[type=radio][value=n]').prop('checked', true);
		        var result = $('.qn-answer').find('label');
		    	result.each(function(){		    		
			        if (($(this).text() == 'Not Competent') || $(this).text().toLowerCase() == 'not satisfactory' ) {
			        	$(this).prev('input[type=radio]').prop('checked', true);
			        }
		    	})
		    }		    
		})		
	}

	require(['jquery', 'core/modal_factory'], function($, modal) {
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
		
	require(['jquery', 'core/modal_factory'], function($, modal) {
		var trigger = $('.reportquestion');
		var html = $('.report-question-popup').html();
		$('.report-question-popup').remove();
		html = "<div class='test2'><form id='test2' class='form-report-question'>"+html+"</form></div>";
		//console.log(html);
		var gradeDialogue = modal.create({
			title: 'Report this question',
			body: html,
			footer: ''
		}, trigger).done(function(modal1) {
			$('body').delegate('.btn.grade-close','click', function() {
				modal1.hide();
			});
		});
	})

	$('body').delegate('form.form-report-question', 'submit', function(e) {
		e.preventDefault();
		$(".loader").remove();
		var rq_msg = $(this).find('.rq_msg').val();
		var qno = $('#reportqno').val();
		var cmid = $(this).find('input[name="cmid"]').val();
		var attempt = $(this).find('input[name="attempt"]').val();
		var fdata = {
			'report_question' : true, 
			'rq_msg': rq_msg,
			'qno': qno,
			'cmid': cmid,
			'attempt': attempt,
		};
		//console.log(fdata);
		var loader = '<div class="loader"><img src="'+M.cfg.wwwroot+'/pix/i/loading_small.gif"> Please wait...</div> ';
		$('.rq_msg').after(loader);
		var posturl = M.cfg.wwwroot+'/theme/ausinet/ajax.php';
		$.ajax({
			url: posturl,
			method: 'POST',
			data: fdata,
			success:function(response) {
				var response = JSON.parse(response);
				//console.log(response);
				if (!response.error) {
					//window.location.href = response.returnurl;
					$('.form-report-question').find('div.loader').html('Question reported successfully');					
					$('.form-report-question').find('div.loader').fadeOut(5000);
					setTimeout(function(){ 
						$('.form-report-question').find('button.grade-close').click();
						$('.form-report-question').find('textarea').val('');
					}, 500);
				}
				else{
					$('.form-report-question').find('div.loader').html('Error while reporting');					
					$('.form-report-question').find('div.loader').fadeOut(5000);
				}
			}
		})
	});
		
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
		//console.log(formData);
		var loader = '<div class="loader"><img src="'+M.cfg.wwwroot+'/pix/i/loading_small.gif"></div> ';
			allform.prepend(loader);
		$.ajax({
			url: M.cfg.wwwroot+'/theme/ausinet/ajax.php',
			method: 'POST',
			data: formData,
			success:function(response) {
				var response = JSON.parse(response);
				//console.log(response);
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
			var loader = '<div class="loader"><img src="'+M.cfg.wwwroot+'/pix/i/loading_small.gif"> saving...</div> ';
			form.find('.grade-section').after(loader);

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
						form.find('div.loader').html('Saved successfully');
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
		})
	}

	$('.path-mod-quiz .comment-question a').click(function() {
		var gradeParent = $(this).parents('form').find('.comment-grade-parent'); // added by Baljit
		var commentField = $(this).parents('form').find('.comment-fields');
		var question = $(this).parents('form').parents('.info').parents('.que');
		if (commentField.hasClass('hide')) {
			commentField.removeClass('hide');
			question.addClass('comment-active');
			gradeParent.addClass('bsk'); // added by Baljit
			var cbox = commentField.find('.editor_atto_content'); // added by Baljit
			cbox.removeAttr('style'); // added by Baljit
		} else {
			commentField.addClass('hide');		
			question.removeClass('comment-active');				
			gradeParent.removeClass('bsk');	//added by Baljit			
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
	// added by Rudra
	/*require(['jquery', 'core/modal_factory'], function($, modal) {
		var trigger = $('.infoquestion1');
		
		var html = $('.info-question-popup').html();
		
		html = "<div id='info_box' class='test2'>"+html+"</div>";
		
		var gradeDialogue1 = modal.create({
			title: 'Question 1',
			body: html,
			footer: '',
            
			large:true
		}, trigger).done(function(modal1) {
			$('body').delegate('.btn.grade-close','click', function() {
				modal1.hide();
			});
		});
	})*/
	//EOF Rudra
 	require(['jquery', 'core/modal_factory'], function($, ModalFactory) {

		$('.infoquestion1').on('click', function() {
		
			var id= $(this).attr('id');
			console.log(id);
			var html = $('#info_'+id).html();
			
			html = "<div id='info_box' class='test2'>"+html+"</div>";
			ModalFactory.create({
			// type: ModalFactory.types.CANCEL,
				title: 'Question '+id,
				body: html,
				large:true
			})
			.then(function(modal) {
			$('body').delegate('.btn.grade-close','click', function() {
					modal.hide();
				});
				
				modal.show();
			})
		});
		// added by nirmal for compliance
		$('.compliancequestion1').on('click', function() {
		
			var id= $(this).attr('id');
			console.log(id);
			var html = $('#compliance_'+id).html();
			
			html = "<div id='info_box' class='test2'>"+html+"</div>";
			ModalFactory.create({
			// type: ModalFactory.types.CANCEL,
				title: 'Compliance for question '+id,
				body: html,
				large:true
			})
			.then(function(modal) {
			$('body').delegate('.btn.grade-close','click', function() {
					modal.hide();
				});
				
				modal.show();
			})
		});


	});
	
})(jQuery)