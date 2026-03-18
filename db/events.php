<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\course_created',
        'callback'    => '\local_rainmake_backend\observers::on_course_created',
    ],
    [
        'eventname'   => '\core\event\user_created',
        'callback'    => '\local_rainmake_backend\observers::on_user_created',
    ],
];
