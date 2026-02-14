<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_sandbox_ddl_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026020601) {
        // Bloque anterior (ya ejecutado, no hará nada).
        $table = new xmldb_table('sandbox_ddl_notes');
        // ... (lo que ya tenías)
        upgrade_plugin_savepoint(true, 2026020601, 'local', 'sandbox_ddl');
    }

    if ($oldversion < 2026020602) {
        // Nuevo bloque — este es el que se ejecutará.
        $table = new xmldb_table('sandbox_ddl_notes');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field('priority', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        $table->add_index('idx_userid_status', XMLDB_INDEX_NOTUNIQUE, ['userid', 'status']);
        $table->add_index('idx_priority', XMLDB_INDEX_NOTUNIQUE, ['priority']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026020602, 'local', 'sandbox_ddl');
    }

    return true;
}