<?php
defined('MOODLE_INTERNAL') || die();

function local_rainmake_backend_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): ?bool
{
    global $USER;
    require_login();

    $fs = get_file_storage();

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    if ($filearea === 'courseimage' || $filearea === 'lecture_video' || $filearea === 'lecture_files') {
        $file = $fs->get_file($context->id, 'local_rainmake_backend', $filearea, $itemid, $filepath, $filename);

        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    } else if ($filearea === 'temp_courseimage') {
        // Temporary course image - only accessible by the owner
        $usercontext = context_user::instance($USER->id);
        $file = $fs->get_file($usercontext->id, 'local_rainmake_backend', $filearea, $itemid, $filepath, $filename);

        if (!$file || $file->is_directory()) {
            return false;
        }

        // Verify the file belongs to the current user
        if ($file->get_contextid() != $usercontext->id) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    }

    return false;
}
