/*
	CUSTOM JS - PROGRESS REPORT
*/

$(document).ready(function(){
	
    $(".smode").val("single");
	
	$(".change_status").click(function(e){
		e.preventDefault();
		$(".popupbg, .custompopup").fadeIn();
		var student = $(this).attr("data-userfullname");
		var activity = $(this).attr("data-activityname");
		var changecompl = $(this).attr("data-changecompl");
		var note = $(this).attr("data-note");
		reset_data(activity);
		$(".cp_student").html("<b>Add note for student : </b>"+student);
		$(".changecompl").val(changecompl);
		$(".note").val(note);
		var ids = changecompl.split("-");
		var last = ids.reverse();
		var activities = ["<b>Below activities under this section will be affected: </b><br>"];
		var scount = $("a.section"+last[0]).length;
		var srs = 1;
		$("a.section"+last[0]).each(function(){
			var newone = $(this).text();
			newone = newone.trim();
			if(srs<scount){
				newone += ", ";
			}
			activities.push(newone);
			srs++;
		});
		get_mode_data(activity, activities);
		
	});
	
	$(".btn_cancelpopup").click(function(e){
		e.preventDefault();
		$(".popupbg, .custompopup").fadeOut();
	});
	
	$(".smode").change(function(){
		var v = $(this).val();
		if(v=="single"){
			$(".popup_multi_acti").hide();
			$(".popup_single_acti").show();
		}
		else{
			$(".popup_single_acti").hide();
			$(".popup_multi_acti").show();
		}
	})
	
	function get_mode_data(activity, activities){
		$(".popup_single_acti").html("<b>Below activity will be affected: </b><br>"+activity);
		$(".popup_multi_acti").html(activities);
	}
	
	function reset_data(activity){
		$(".cp_student").empty();
		$(".popup_multi_acti").empty();
		$(".popup_single_acti").empty();
		$(".smode").val("single");
		$(".popup_single_acti").html("<b>Below activity will be affected: </b><br>"+activity);
		$(".popup_multi_acti").hide();
		$(".popup_single_acti").show();
	}
	
});