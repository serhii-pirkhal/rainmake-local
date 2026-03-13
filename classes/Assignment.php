<?php

namespace local_rainmake_backend;
require_once(__DIR__ . '/../../../config.php');
require_login();

class Assignment
{
    private \moodle_database $DB;
    private \stdClass $CFG;

    public function __construct()
    {
        global $DB;
        global $CFG;
        $this->DB = $DB;
        $this->CFG = $CFG;
    }

    public function getTasksCount(): int
    {
        return $this->DB->count_records('assignment_tasks');
    }

    public function getTasks($page, $perPage): array
    {
        $limit = $page * $perPage - $perPage;
        $tasks = $this->DB->get_records('assignment_tasks', null, null, '*', $limit, $perPage);
        foreach ($tasks as $task) {
            $users = array();
            $userids = array_values($this->DB->get_records('assignment_tasks_students', array('task_id' => $task->id)));
            foreach ($userids as $userid) {
                $u = $this->DB->get_record('user', array('id' => $userid->user_id), 'id, firstname, lastname, picture');
                if (!$u) {
                    continue;
                }
                $profileimageurl = '';
                global $PAGE;
                if (!empty($u->picture) && $PAGE) {
                    $userpicture = new \user_picture($u);
                    $userpicture->size = 100;
                    $profileimageurl = $userpicture->get_url($PAGE)->out(false);
                }
                $users[] = (object)[
                    'id' => (int)$u->id,
                    'firstname' => $u->firstname,
                    'lastname' => $u->lastname,
                    'profileimageurl' => $profileimageurl,
                    'initials' => self::user_initials($u),
                ];
            }
            $task->course = $this->DB->get_record('course', array('id' => $task->course_id), 'fullname, shortname');
            $task->files = array_values($this->DB->get_records('assignment_tasks_files', array('task_id' => $task->id), null, 'filepath'));
            foreach ($task->files as $file) {
                $file->size = round(filesize($this->CFG->dirroot . "/" . $file->filepath) / (1024 * 1024), 1);
                $file->name = basename($this->CFG->dirroot . "/" . $file->filepath);
            }
            $task->users = $users;
            $task->user_ids = implode(',', array_map(function($u) { return $u->id; }, $users));
            // Aggregate data for template.
            $task->studentscount = count($users);
            $task->filescount = count($task->files);
            $task->hasfeedback = !empty(trim((string)($task->feedback ?? '')));
        }
        return $tasks;
    }

    private static function user_initials(\stdClass $u): string {
        $f = trim($u->firstname ?? '');
        $l = trim($u->lastname ?? '');
        if ($f !== '' && $l !== '') {
            return strtoupper(mb_substr($f, 0, 1) . mb_substr($l, 0, 1));
        }
        if ($f !== '') {
            return strtoupper(mb_substr($f, 0, 2));
        }
        return '?';
    }
}
