<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_fluencytrack_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025061903) {  // Use appropriate version number.

        $table = new xmldb_table('fluencytrack');
        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '100.00', 'timemodified');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025061903, 'mod', 'fluencytrack');
    }

    return true;
}
