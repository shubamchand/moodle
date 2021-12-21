/*global $:false */
/*exported togglecollapseall */
/* The above disables warnings for undefined '$' and unused 'togglecollapseall'. */
/*
 * Collapse/Expand all courses/assessments. If we are in the course,
 * then only collapse/expand all assessments.
 */
// added by nirmal to fix the js toggling issue specific to the module
function togglecollapseallObservation(iscoursecontext) {
    if($('.block_course_observation_notification').find('dl').hasClass('expanded')) {
        $('.block_course_observation_notification').find('.toggle').removeClass('open');
        if (!iscoursecontext) {
            $('.block_course_observation_notification').find('dd').addClass('block_course_observation_notification_hide');
        }
        $('.block_course_observation_notification').find('dd ul').addClass('block_course_observation_notification_hide');
        $('.block_course_observation_notification').find('dl').removeClass('expanded')
    } else {
        $('.block_course_observation_notification').find('.toggle').addClass('open');
        if (!iscoursecontext) {
            $('.block_course_observation_notification').find('dd').removeClass('block_course_observation_notification_hide');
        }
        $('.block_course_observation_notification').find('dd ul').removeClass('block_course_observation_notification_hide');
        $('.block_course_observation_notification').find('dl').addClass('expanded');
    }
}
