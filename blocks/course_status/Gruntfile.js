/**
 * Gruntfile primarily for running stylelint.
 *
 * This file configures tasks to be run by Grunt
 * http://gruntjs.com/ for the current plugin.
 *
 *
 * Requirements:
 * -------------
 * nodejs, npm, grunt-cli.
 *
 * Installation:
 * -------------
 * node and npm: instructions at http://nodejs.org/
 *
 * grunt-cli: `[sudo] npm install -g grunt-cli`
 *
 * node dependencies: run `npm install` in the root directory.
 *
 * Usage:
 * ------
 * Call tasks from the plugin root directory.
 * Default behaviour (calling only `grunt`) runs the default tasks to lint js and css, then the watch task.
 *
 * Porcelain tasks:
 * ----------------
 * The nice user interface intended for everyday use. Provide a
 * high level of automation and convenience for specific use-cases.
 *
 * grunt localjsfiles   Run eslint on the only js file in this plugin at present - Gruntfile.js.
 * grunt css            Run stylelint on the only css file in this plugin at present - styles.css.
 *
 *
 * @package block
 * @subpackage course_status
 * @author M Solanki - {@link https://moodle.org/user/profile.php?id=2227655}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* jshint node: true, browser: false */
/* eslint-env node */

module.exports = function(grunt) {

    // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
    require("grunt-load-gruntfile")(grunt);
    grunt.loadGruntfile("../../Gruntfile.js");

    var decachephp = '../../admin/cli/purge_caches.php';

    grunt.mergeConfig = grunt.config.merge;

    grunt.mergeConfig({
        exec: {
            decache: {
                cmd: 'php "' + decachephp + '"',
                callback: function(error) {
                    // Warning: Be careful when executing this task.  It may give
                    // file permission errors accessing Moodle because of the directory permissions
                    // for configured Moodledata directory if this is run as root.
                    // The exec process will output error messages.
                    // Just add one to confirm success.
                    if (!error) {
                        grunt.log.writeln("Moodle theme cache reset.");
                    }
                }
            }
        },
        watch: {
            eslint: {
                files: ["*.js"],
                tasks: ["eslint"],
            },
        },
        eslint: {
            // Even though warnings dont stop the build we don't display warnings by default because
            // at this moment we've got too many core warnings.
            options: {quiet: !grunt.option('show-lint-warnings')},
            // Check YUI module source files.
            localjsfiles: {src: ['Gruntfile.js']}
        },
        stylelint: {
            localcss: {
                src: ['*.css'],
                options: {
                    configOverrides: {
                        rules: {
                            // These rules have to be disabled in .stylelintrc for scss compat.
                            "at-rule-no-unknown": true,
                        }
                    }
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-stylelint');

    // Register tasks.
    grunt.registerTask('localjsfiles', ['eslint:localjsfiles']);
    grunt.registerTask('css', ['stylelint:localcss']);
    grunt.registerTask("default", ["stylelint", "eslint", "watch"]);
};
