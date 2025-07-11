<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$lectureid = required_param('lectureid', PARAM_INT);
$type = required_param('type', PARAM_ALPHANUMEXT);

require_sesskey();

$response = ['success' => false];

$lecture = $DB->get_record('local_rainmake_backend_lectures', ['id' => $lectureid]);

if ($type === 'video') {
    $record = $DB->get_record('local_rainmake_backend_lecture_video', ['lectureid' => $lectureid]);

    if ($record) {
        $response['success'] = true;
        $response['data'] = [
            'fileuploaded' => true,
            'filename'     => $record->filename,
            'thumbnail'    => $record->thumbnail,
            'duration'     => $record->duration ? gmdate('i:s', $record->duration) : null,
            'url'          => $record->url,
            'lectureid'    => $lectureid
        ];
    } else {
        $response['success'] = true;
        $response['data'] = [
            'fileuploaded' => false,
            'lectureid' => $lectureid
        ];
    }
} else {
    $response['success'] = true;
    $response['data'] = [
        'description' => $lecture->description,
        'notes' => $lecture->notes,
        'lectureid' => $lectureid,
    ];
}

echo json_encode($response);
