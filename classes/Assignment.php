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
                $users[] = $this->DB->get_record('user', array('id' => $userid->user_id), 'firstname, lastname');
            }
            $task->course = $this->DB->get_record('course', array('id' => $task->course_id), 'fullname, shortname');
            $task->files = array_values($this->DB->get_records('assignment_tasks_files', array('task_id' => $task->id), null, 'filepath'));
            foreach ($task->files as $file) {
                $file->size = round(filesize($this->CFG->dirroot . "/" . $file->filepath) / (1024 * 1024), 1);
                $file->name = basename($this->CFG->dirroot . "/" . $file->filepath);
            }
            $task->users = $users;
        }
        return $tasks;
    }
}
