/*global $:false */
/*exported togglecollapseall */
/* The above disables warnings for undefined '$' and unused 'togglecollapseall'. */
/*
 * Collapse/Expand all courses/assessments. If we are in the course,
 * then only collapse/expand all assessments.
 */
// updated / added by nirmal to fix the js toggling issue specific to the module
function togglecollapseall(iscoursecontext) {
    if($('.block_grade_me').find('dl').hasClass('expanded')) {
        $('.block_grade_me').find('.toggle').removeClass('open');
        if (!iscoursecontext) {
            $('.block_grade_me').find('dd').addClass('block_grade_me_hide');
        }
        $('.block_grade_me').find('dd ul').addClass('block_grade_me_hide');
        $('.block_grade_me').find('dl').removeClass('expanded')
    } else {
        $('.block_grade_me').find('.toggle').addClass('open');
        if (!iscoursecontext) {
            $('.block_grade_me').find('dd').removeClass('block_grade_me_hide');
        }
        $('.block_grade_me').find('dd ul').removeClass('block_grade_me_hide');
        $('.block_grade_me').find('dl').addClass('expanded');
    }
}
