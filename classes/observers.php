<?php
namespace local_rainmake_backend;

class observers {
    public static function on_course_created(\core\event\course_created $event) {
        $course = $event->get_record_snapshot('course', $event->objectid);
        debugging("Курс создан: {$course->fullname}", DEBUG_DEVELOPER);
        // Добавь сюда свою кастомную логику (например логирование или внешние вызовы)
    }
}
