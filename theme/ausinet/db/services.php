<?php 


$functions = array(
    'theme_ausinet_restrict_users' => array(
        'classname'   => 'theme_ausinet\external',
        'methodname'  => 'restrict_users',
        'description' => 'Confirm a user account.',
        'type'        => 'write',
        'ajax'          => true,
        'loginrequired' => false,
    ),
);