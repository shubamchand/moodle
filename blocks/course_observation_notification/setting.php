<?php
    defined( 'MOODLE_INTERNAL' ) || die();

    if( $ADMIN->fulltree ){ //$ADMIN->fulltree
        $settings = new admin_settingpage(                'block_course_observation_notification',
                                                          get_string( 'adminpageheading', 'block_course_observation_notification' ) );

        $settings->add( new admin_setting_heading(        'block_course_observation_notification/generalheading',
                                                          get_string( 'generalheading', 'block_course_observation_notification' ), '' ) );

        $settings->add( new admin_setting_configcheckbox( 'block_course_observation_notification/enabled',
                                                          get_string( 'shownotification', 'block_course_observation_notification' ),
                                                          '',
                                                          '' ) );

        $settings->add( new admin_setting_configtext(     'block_course_observation_notification/notificationmessage',
                                                          get_string( 'notificationmessage', 'block_course_observation_notification' ),
                                                          '',
                                                          '' ) );

        $ADMIN->add( 'messaging', $settings );
    }
