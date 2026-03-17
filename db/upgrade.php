<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_rainmake_backend_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025063001) {

        // === Create sessions table ===
        $table = new xmldb_table('local_rainmake_backend_sessions');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('coursefk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

            $dbman->create_table($table);
        }

        // === Create lectures table ===
        $table = new xmldb_table('local_rainmake_backend_lectures');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('sessionfk', XMLDB_KEY_FOREIGN, ['sessionid'], 'local_rainmake_backend_sessions', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025063001, 'local', 'rainmake_backend');
    }
    if ($oldversion < 2025090103) {
        // === Create sessions table ===
        $table = new xmldb_table('local_rainmake_backend_practices');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'quiz', 'otherfield');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('coursefk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

            $dbman->create_table($table);
        }
        $table = new xmldb_table('local_rainmake_backend_practice_questions');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('practice_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('question', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('options', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('sessionfk', XMLDB_KEY_FOREIGN, ['practice_id'], 'local_rainmake_backend_sessions', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025090103, 'local', 'rainmake_backend');
    }
    if ($oldversion < 2025090800) {
        $table = new xmldb_table('local_rainmake_backend_practice_question_options');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('question_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('is_correct', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('questionfk', XMLDB_KEY_FOREIGN, ['question_id'], 'courselocal_rainmake_backend_practice_questions', ['id']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_rainmake_backend_practice_questions');
        $field = new xmldb_field('options');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025090800, 'local', 'rainmake_backend');
    }
    if ($oldversion < 2025091001) {
        $table = new xmldb_table('local_rainmake_backend_careerpath_courses');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('careerpath_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('coursefk', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);
            $table->add_key('careerpathfk', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025091001, 'local', 'rainmake_backend');
    }

    if ($oldversion < 2025092400) {
        $table = new xmldb_table('local_rainmake_backend_course_types');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('type', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('coursefk', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025092400, 'local', 'rainmake_backend');
    }

    if ($oldversion < 2025100500) {
        $table = new xmldb_table('local_rainmake_backend_practice_answers');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('question_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('option', XMLDB_TYPE_TEXT , null, null, null, null, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('practice_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('question_id', XMLDB_KEY_FOREIGN, ['question_id'], 'local_rainmake_backend_practice_questions', ['id']);
        $table->add_key('course_id', XMLDB_KEY_FOREIGN, ['course_id'], 'local_rainmake_backend_practice', ['course_id']);
        $table->add_key('practice_id', XMLDB_KEY_FOREIGN, ['practice_id'], 'local_rainmake_backend_practice_questions', ['practice_id']);

        if (!$DB->get_manager()->table_exists($table)) {
            $DB->get_manager()->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2025100500, 'local', 'rainmake_backend');
    }

    // Ensure local_rainmake_backend_course_types exists even if it was missed or dropped.
    if ($oldversion < 2026031500) {
        $table = new xmldb_table('local_rainmake_backend_course_types');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('type', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('coursefk', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026031500, 'local', 'rainmake_backend');
    }

    // Create assignment_tasks_courses join table for multi-course task assignment.
    if ($oldversion < 2026031700) {
        $table = new xmldb_table('assignment_tasks_courses');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('task_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fk_task', XMLDB_KEY_FOREIGN, ['task_id'], 'assignment_tasks', ['id']);
            $table->add_key('fk_course', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026031700, 'local', 'rainmake_backend');
    }

    // Refresh external functions/services from db/services.php (e.g. newly added AJAX endpoints).
    if ($oldversion < 2026031701) {
        require_once($CFG->dirroot . '/lib/externallib.php');
        external_update_services('local_rainmake_backend');

        upgrade_plugin_savepoint(true, 2026031701, 'local', 'rainmake_backend');
    }

    // Store the exact selected curriculum items (course/module/lecture) per assigned task.
    if ($oldversion < 2026031702) {
        $table = new xmldb_table('assignment_tasks_curriculum');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('task_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('item_type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'course');
            $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('module_id', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('lecture_id', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('subtitle', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fk_task', XMLDB_KEY_FOREIGN, ['task_id'], 'assignment_tasks', ['id']);
            $table->add_key('fk_course', XMLDB_KEY_FOREIGN, ['course_id'], 'course', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026031702, 'local', 'rainmake_backend');
    }

    return true;
}
