<?php
return [
    [
        'eventname'   => '\core\event\course_created',
        'callback'    => '\local_rainmake_backend\observers::on_course_created',
    ],
];
