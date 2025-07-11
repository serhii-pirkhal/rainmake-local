<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/rainmake_backend:accessvideo' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
    ],
];