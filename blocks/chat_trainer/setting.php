<?php
    defined( 'MOODLE_INTERNAL' ) || die();

    if( $ADMIN->fulltree ){ //$ADMIN->fulltree
        $settings = new admin_settingpage(                'block_chat_trainer',
                                                          get_string( 'adminpageheading', 'block_chat_trainer' ) );

        $settings->add( new admin_setting_heading(        'block_chat_trainer/generalheading',
                                                          get_string( 'generalheading', 'block_chat_trainer' ), '' ) );

        $settings->add( new admin_setting_configcheckbox( 'block_chat_trainer/enabled',
                                                          get_string( 'shownotification', 'block_chat_trainer' ),
                                                          '',
                                                          '' ) );

        $settings->add( new admin_setting_configtext(     'block_chat_trainer/notificationmessage',
                                                          get_string( 'notificationmessage', 'block_chat_trainer' ),
                                                          '',
                                                          '' ) );

        $ADMIN->add( 'messaging', $settings );
    }
