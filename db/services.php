<?php
$functions = [
    'local_rainmake_backend_create_empty_course' => [
        'classname'   => 'local_rainmake_backend\external\create_empty_course_endpoint',
        'methodname'  => 'execute',
        'classpath'   => 'local/rainmake_backend/classes/external',
        'description' => 'Creates empty course and attach it to careerpath',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'local_rainmake_backend_get_course_data' => [
        'classname'   => 'local_rainmake_backend\external\get_course_data',
        'methodname'  => 'execute',
        'classpath'   => 'local/rainmake_backend/classes/external',
        'description' => 'Returns basic course info',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_rainmake_backend_get_new_courses' => [
        'classname'   => 'local_rainmake_backend\external\get_new_courses',
        'methodname'  => 'execute',
        'classpath'   => 'local/rainmake_backend/classes/external',
        'description' => 'Returns courses that are not in the current career path',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_rainmake_backend_get_course_new_user_trainers' => [
        'classname'   => 'local_rainmake_backend\external\get_course_new_user_trainers',
        'methodname'  => 'execute',
        'classpath'   => 'local/rainmake_backend/classes/external',
        'description' => 'Returns courses that are not in the current career path',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_rainmake_backend_course_user_unenroll' => [
        'classname'   => 'local_rainmake_backend\external\course_user_unenroll',
        'methodname'  => 'execute',
        'classpath'   => 'local/rainmake_backend/classes/external',
        'description' => 'unenroll a user from course',
        'type'        => 'read',
        'ajax'        => true,
    ],
];
