<?php
 $capabilities = array (
    'block/chat_trainer:overview' => array (
        'riskbitmask'   => RISK_PERSONAL,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_BLOCK,
        'archetypes'    => array (
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'manager'           => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW
        )
    ),

    'block/chat_trainer:showbar' => array (
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_BLOCK,
        'archetypes'    => array (
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'student'           => CAP_ALLOW,
        )
    ),

    'block/chat_trainer:addinstance' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/chat_trainer:myaddinstance' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

     'block/chat_trainer:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        )
    )
);