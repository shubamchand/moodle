<?php 

require_once('../../config.php');

$PAGE->set_url('/theme/ausinet/xhtml.php');

$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();

$param = optional_param('block', '', PARAM_TEXT);
$block = ['courselisting' => '1'];
echo $OUTPUT->render_from_template('theme_ausinet/xhtml', $block);

/*
global $PAGE, $USER;
// $env = ['context' => $PAGE->context];
// $table = block_xp\local\shortcode\handler::xpladder('xpladder', [], '', $env, '');
// print_object($table);
// exit;
$world = block_xp\local\shortcode\handler::get_world_from_env($env);

$groupid = 0;
if (di::get('config')->get('context') == CONTEXT_COURSE) {
    $groupid = block_xp\local\shortcode\handler::get_group_id($world->get_courseid(), $USER->id);
}

$leaderboard = di::get('course_world_leaderboard_factory')->get_course_leaderboard($world, $groupid);
$pos = $leaderboard->get_position($USER->id);
if ($pos === null) {
            if (!$world->get_access_permissions()->can_manage()) {
                return;
            }
            $pos = 0;
        }

if (!empty($args['top'])) {
    // Show the top n users.
    if ($args['top'] === true) {
        $count = 10;
    } else {
        $count = max(1, intval($args['top']));
    }
    $limit = new limit($count, 0);

} else {
    // Determine what part of the leaderboard to show and fence it.
    $before = 2;
    $after = 4;
    $offset = max(0, $pos - $before);
    $count = $before + $after + 1;
    $limit = new limit($count + min(0, $pos - $before), $offset);
}

// Output the table.
$baseurl = $PAGE->url;
$table = new \block_xp\output\leaderboard_table($leaderboard, di::get('renderer'), [
    'fence' => $limit,
    'rankmode' => $world->get_config()->get('rankmode'),
    'identitymode' => $world->get_config()->get('identitymode'),
    'discardcolumns' => !empty($args['withprogress']) ? [] : ['progress']
], $USER->id);
$table->define_baseurl($baseurl);
// ob_start();
echo $table->out($count);*/

echo $OUTPUT->footer();