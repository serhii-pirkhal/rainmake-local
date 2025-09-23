<?php

namespace local_rainmake_backend\external;

use context_course;
use core\output\user_picture;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class get_course_new_user_trainers extends external_api
{

    public static function execute_parameters(){
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
        ]);
    }

    public static function execute($courseid) {
        global $DB;

        $users = $DB->get_records('user', ['deleted' => 0]);
        $context = context_course::instance($courseid);
        $cUsers = get_enrolled_users($context);

        $users = array_filter($users, function($user) use ($cUsers) {
            foreach ($cUsers as $cUser) {
                if($cUser->id == $user->id){
                    return false;
                }
            }
            return true;
        });

        $result = [];
        foreach ($users as $user) {
            $context = \context_user::instance($user->id);

            $pictureurl = \moodle_url::make_pluginfile_url(
                $context->id,
                'user',
                'icon',
                null,
                '/',
                'f2'
            )->out(false);

            $result[] = [
                'id'        => $user->id,
                'username'  => $user->username,
                'firstname' => $user->firstname,
                'lastname'  => $user->lastname,
                'email'     => $user->email,
                'picture' => $pictureurl,
            ];
        }

        return $result;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id'        => new external_value(PARAM_INT, 'ID of the user'),
                'username'  => new external_value(PARAM_TEXT, 'Username'),
                'firstname' => new external_value(PARAM_TEXT, 'Firstname'),
                'lastname'  => new external_value(PARAM_TEXT, 'Lastname'),
                'email'     => new external_value(PARAM_TEXT, 'Email'),
                'picture' => new external_value(PARAM_TEXT, 'Picture'),
            ])
        );
    }
}

