<?php

require_once(dirname(__FILE__).'/../lib/db.php');

/**
* Database upgrade script for Moodle's db-independent XMLDB.
* @ignore
* @package sloodle
*/


// This file keeps track of upgrades to
// the sloodle module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

// Function call
// public function set_attributes  ($type, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $default=null, $previous=null)
// public function add_field($name, $type, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $default=null, $previous=null)


defined('MOODLE_INTERNAL') || die();


function xmldb_sloodle_upgrade($oldversion=0)
{
    global $CFG, $THEME, $DB;
    $result = true;

    $dbman = $DB->get_manager();
    
    // Note: any upgrade to Sloodle 0.3 is a major process, due to the huge change of architecture.
    // As such, the only data worth preserving is the avatar table ('sloodle_users').
    
    // All other tables will be dropped and re-inserted.
    
    // Is this an upgrade from pre-0.3?
    if ($oldversion < 2008052800) {
        // Drop all other tables
        echo "Dropping old tables<br />";
        // (We can ignore failed drops)
        
        /// Drop 'sloodle' table
        $table = new xmldb_table('sloodle');
        $dbman->drop_table($table);
        
        /// Drop 'sloodle_config' table
        $table = new xmldb_table('sloodle_config');
        
        /// Drop 'sloodle_active_object' table
        $table = new xmldb_table('sloodle_active_object');
        $dbman->drop_table($table);
        
        /// Drop 'sloodle_classroom_setup_profile' table
        $table = new xmldb_table('sloodle_classroom_setup_profile');
        $dbman->drop_table($table);
        
        /// Drop 'sloodle_classroom_setup_profile_entry' table
        $table = new xmldb_table('sloodle_classroom_setup_profile_entry');
        $dbman->drop_table($table);
        
        // Insert all the new tables
        echo "Inserting new tables...<br />";
        
        /// Insert 'sloodle' table
        echo " - sloodle<br />";
        $table = new xmldb_table('sloodle');

        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',     XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('course',       XMLDB_TYPE_INTEGER, '10',     XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('type',         XMLDB_TYPE_CHAR,    '50',     null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('name',         XMLDB_TYPE_CHAR,    '255',    null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('intro',        XMLDB_TYPE_TEXT,    'medium', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',     XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',     XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_controller' table
        echo " - sloodle_controller<br />";
        $table = new xmldb_table('sloodle_controller');

        $table->add_field('id',        XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('sloodleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('enabled',   XMLDB_TYPE_INTEGER, '1',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('password',  XMLDB_TYPE_CHAR,    '9',  null,           null,          null,           null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('sloodleid', XMLDB_INDEX_UNIQUE, array('sloodleid'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_distributor' table
        echo " - sloodle_distributor<br />";
        $table = new xmldb_table('sloodle_distributor');

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('sloodleid',   XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('channel',     XMLDB_TYPE_CHAR,    '36', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_distributor_entry' table
        echo " - sloodle_distributor_entry<br />";
        $table = new xmldb_table('sloodle_distributor_entry');

        $table->add_field('id',            XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('distributorid', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('name',          XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_course' table
        echo " - sloodle_course<br />";
        $table = new xmldb_table('sloodle_course');

        $table->add_field('id',               XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('course',           XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('autoreg',          XMLDB_TYPE_INTEGER, '1',   XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('autoenrol',        XMLDB_TYPE_INTEGER, '1',   XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('loginzonepos',     XMLDB_TYPE_CHAR,    '255', null,           null,          null,           null, null);
        $table->add_field('loginzonesize',    XMLDB_TYPE_CHAR,    '255', null,           null,          null,           null, null);
        $table->add_field('loginzoneregion',  XMLDB_TYPE_CHAR,    '255', null,           null,          null,           null, null);
        $table->add_field('loginzoneupdated', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_pending_avatars' table
        echo " - sloodle_pending_avatar<br />";
        $table = new xmldb_table('sloodle_pending_avatars');

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('uuid',        XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('avname',      XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('lst',         XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('uuid', XMLDB_INDEX_NOTUNIQUE, array('uuid'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_active_object' table
        echo " - sloodle_active_object<br />";
        $table = new xmldb_table('sloodle_active_object');

        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('controllerid', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);
        $table->add_field('userid',       XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);
        $table->add_field('uuid',         XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('password',     XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('name',         XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('type',         XMLDB_TYPE_CHAR,    '50',  null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('timeupdated',  XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('uuid', XMLDB_INDEX_UNIQUE, array('uuid'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_object_config' table
        echo " - sloodle_object_config<br />";
        $table = new xmldb_table('sloodle_object_config');

        $table->add_field('id',     XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('object', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);
        $table->add_field('name',   XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('value',  XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('object-name', XMLDB_INDEX_UNIQUE, array('object', 'name'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_login_notifications' table
        echo " - sloodle_login_notifications<br />";
        $table = new xmldb_table('sloodle_login_notifications');

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('destination', XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('avatar',      XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('username',    XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('password',    XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_layout' table
        echo " - sloodle_layout<br />";
        $table = new xmldb_table('sloodle_layout');

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('course',      XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('name',        XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course-name', XMLDB_INDEX_UNIQUE, array('course', 'name'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_layout_entry' table
        echo " - sloodle_layout_entry<br />";
        $table = new xmldb_table('sloodle_layout_entry');

        $table->add_field('id',       XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('layout',   XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('name',     XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('position', XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('rotation', XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);

        $table->add_key('primary',  XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('layout', XMLDB_INDEX_NOTUNIQUE, array('layout'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_loginzone_allocation' table
        echo " - sloodle_loginzone_allocation<br />";
        $table = new xmldb_table('sloodle_loginzone_allocation');

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('course',      XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('userid',      XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('position',    XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);

        $table->add_key('primary',  XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->add_index('userid', XMLDB_INDEX_UNIQUE, array('userid'));
        $dbman->create_table($table);
        
        /// Insert 'sloodle_user_object' table
        echo " - sloodle_user_object<br />";
        $table = new xmldb_table('sloodle_user_object');

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('avuuid',      XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('objuuid',     XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('objname',     XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('password',    XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('authorised',  XMLDB_TYPE_INTEGER, '1',   XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('objuuid', XMLDB_INDEX_UNIQUE, array('objuuid'));
        $dbman->create_table($table);
        
        /// Upgrade sloodle_users table
        echo "Upgrading sloodle_users table...<br />";
        $table = new xmldb_table('sloodle_users');
        
        echo " - dropping old fields<br />";
        // Drop the loginzone fields (we don't care about success or otherwise... not all fields will be present in all versions)
        $field = new xmldb_field('loginposition');
        drop_field($table, $field);
        $field = new xmldb_field('loginpositionexpires');
        drop_field($table, $field);
        $field = new xmldb_field('loginpositionregion');
        drop_field($table, $field);
        $field = new xmldb_field('loginsecuritytoken');
        drop_field($table, $field);
        // Drop the old 'online' field (was going to be a boolean, but was never used)
        $field = new xmldb_field('online');
        drop_field($table, $field);
        
        // Add the new 'lastactive' field
        echo " - adding lastactive field<br />";
        $field = new xmldb_field('lastactive');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'avname');
        $dbman->add_field($table, $field);
        
        /// Purge redundant avatar entries
        echo "Purging redundant avatar entries...<br />";
        $sql = " DELETE FROM {$CFG->prefix}sloodle_users WHERE userid = 0 OR uuid = '' OR avname = '' ";
        execute_sql($sql);
    }
    
    
    if ($oldversion < 2009020201) {
        /// Define table sloodle_presenter_entry to be created
        $table = new xmldb_table('sloodle_presenter_entry');

        /// Adding fields to table sloodle_presenter_entry
        $table->add_field('id',        XMLDB_TYPE_INTEGER, '10',     XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null,  null);
        $table->add_field('sloodleid', XMLDB_TYPE_INTEGER, '10',     XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null,  null);
        $table->add_field('source',    XMLDB_TYPE_TEXT,    'medium', null,           XMLDB_NOTNULL, null,           null,  null);
        $table->add_field('type',      XMLDB_TYPE_CHAR,    '255',    null,           XMLDB_NOTNULL, null,           'web', null);
        $table->add_field('ordering',  XMLDB_TYPE_INTEGER, '10',     XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null,  null);

        /// Adding keys to table sloodle_presenter_entry
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table sloodle_presenter_entry
        $table->add_index($CFG->prefix.'sloopresentr_slo_ix', XMLDB_INDEX_NOTUNIQUE, array('sloodleid'));
        $table->add_index($CFG->prefix.'sloopresentr_typ_ix', XMLDB_INDEX_NOTUNIQUE, array('type'));

        /// Launch create table for sloodle_presenter_entry
        $dbman->create_table($table);
    }


    if ($oldversion < 2009020701) {
        /// Define table sloodle_layout_entry_config to be created
        $table = new xmldb_table('sloodle_layout_entry_config');

        /// Adding fields to table sloodle_layout_entry_config
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('layout_entry', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, null,          null,           null, null);
        $table->add_field('name',         XMLDB_TYPE_CHAR,    '255', null,           null,          null,           null, null);
        $table->add_field('value',        XMLDB_TYPE_CHAR,    '255', null,           null,          null,           null, null);

        /// Adding keys to table sloodle_layout_entry_config
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table for sloodle_layout_entry_config
        $dbman->create_table($table);
    }


    // Add a name field to the Presenter entries.
    if ($oldversion < 2009031002) {
        /// Define field name to be added to sloodle_presenter_entry
        $table = new xmldb_table('sloodle_presenter_entry');
        $field = new xmldb_field('name');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'sloodleid');

        /// Launch add field name
        $dbman->add_field($table, $field);
    }


    // Add the SLOODLE Presenter table (we previously only had entries, but no data about the Presenter itself.)
    if ($oldversion < 2009031003) {
        /// Define table sloodle_presenter to be created
        $table = new xmldb_table('sloodle_presenter');

        /// Adding fields to table sloodle_presenter
        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null,  null);
        $table->add_field('sloodleid',   XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null,          null,           null,  null);
        $table->add_field('framewidth',  XMLDB_TYPE_INTEGER, '4',  XMLDB_UNSIGNED, null,          null,           '512', null);
        $table->add_field('frameheight', XMLDB_TYPE_INTEGER, '4',  XMLDB_UNSIGNED, null,          null,           '512', null);

        /// Adding keys to table sloodle_presenter
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sloodleid', XMLDB_KEY_UNIQUE, array('sloodleid'));

        /// Launch create table for sloodle_presenter
        $dbman->create_table($table);

        // For sake of people who installed a test version of SLOODLE 0.4, we need to automatically create secondary Presenter instances.
        // These would normally be created when creating an instance of the module, but this table didn't exist during test versions.
        // Go through all SLOODLE modules with type "Presenter" and add an empty secondary entry on their behalf, with default values.
        $sloodlerecords = sloodle_get_records('sloodle', 'type', 'presenter');
        if (!$sloodlerecords) $sloodlerecords = array();
        foreach ($sloodlerecords as $sr) {
            // Construct a default presenter instance for it
            $presenterrecord = new stdClass();
            $presenterrecord->sloodleid = $sr->id;
            sloodle_insert_record('sloodle_presenter', $presenterrecord);
        }
    }


    if ($oldversion < 2009110500) {
        echo "Converting Presenter slide type IDs... ";
        // Standardize any Presenter slides to use type names "image", "web", and "video".
        // The slide plugins for 1.0 initially used class names, like SloodlePluginPresenterSlideImage.
        // That's laborious and necessary, so we're reverting back to the original type names.
        $allslides = sloodle_get_records('sloodle_presenter_entry');
        $numupdated = 0;
        if ($allslides) {
            foreach ($allslides as $slide) {
                // Update the type name if necessary
                $updated = true;
                switch (strtolower($slide->type)) {
                    // Image slides
                    case 'sloodlepluginpresenterslideimage': case 'presenterslideimage':
                        $slide->type = 'image';
                        break;
                    // Web slides
                    case 'sloodlepluginpresenterslideweb': case 'presenterslideweb':
                        $slide->type = 'web';
                        break;
                    // Video slides
                    case 'sloodlepluginpresenterslidevideo': case 'presenterslidevideo':
                        $slide->type = 'video';
                        break;
                    // Unrecognised type
                    default:
                        $updated = false;
                        break;
                }
                
                // Update the database record
                if ($updated) {
                    sloodle_update_record('sloodle_presenter_entry', $slide);
                    $numupdated++;
                }
            }
        }
        echo "{$numupdated} slide(s) updated.<br />";
    }


    if (!$dbman->table_exists(new xmldb_table('sloodle_currency_types'))) {     
        $table = new xmldb_table('sloodle_currency_types');
         echo "creating new currency table for site wide virtual currency<br />";               
        $field = new xmldb_field('id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, '0',  null);    
        $table->addField($field);
        $field = new xmldb_field('name');
        $field->set_attributes(XMLDB_TYPE_CHAR,    '50', null,           XMLDB_NOTNULL, null,           null, 'id');
        $table->addField($field);        
        $field = new xmldb_field('timemodified');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  'units');
        $table->addField($field);   
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);           

        $newCurrency = new stdClass();
        $newCurrency->name="Credits";
        if (sloodle_insert_record('sloodle_currency_types',$newCurrency))echo "Added Credits currency: OK<br />";
    }
    

    if ($oldversion < 2010110311) {      
        $table = new xmldb_table('sloodle_users'); 
         // Add the new 'profilepic' field
         echo " - adding \'profilepic\' field<br />";
         $field = new xmldb_field('profilepic');
         $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, '', 'avname');
         $dbman->add_field($table,$field);                    
    }


    if ( $oldversion < 2010110501) {
        /// Define field httpinurl to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('httpinurl');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'timeupdated');

        /// Launch add field httpinurl
        $dbman->add_field($table, $field);
    }


    if ($oldversion < 2010121703) {
        /// Define field layoutentryid to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('layoutentryid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'httpinurl');

        /// Launch add field layoutentryid
        $dbman->add_field($table, $field);

        /// Define field rezzeruuid to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('rezzeruuid');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'layoutentryid');

        /// Launch add field rezzeruuid
        $dbman->add_field($table, $field);

        /// Define field position to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('position');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'rezzeruuid');

        /// Launch add field position
        $dbman->add_field($table, $field);

        /// Define field rotation to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('rotation');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'position');

        /// Launch add field rotation
        $dbman->add_field($table, $field);

        /// Define field region to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('region');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'rotation');

        /// Launch add field region
        $dbman->add_field($table, $field);

        /// Define index rezzeruuid (not unique) to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $index = new XMLDBIndex('rezzeruuid');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('rezzeruuid'));

        /// Launch add index rezzeruuid
        $dbman->add_index($table, $index);

        /// Define index layoutentryid (not unique) to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $index = new XMLDBIndex('layoutentryid');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('layoutentryid'));

        /// Launch add index layoutentryid
        $dbman->add_index($table, $index);
    }


    if ($oldversion < 2011062700) {
        /// Define table sloodle_award_rounds to be created
        $table = new xmldb_table('sloodle_award_rounds');

        /// Adding fields to table sloodle_award_rounds
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('timestarted',  XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('timeended',    XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);
        $table->add_field('name',         XMLDB_TYPE_CHAR,    '255', null,           null,          null,           null, null);
        $table->add_field('controllerid', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, null,          null,           null, null);

        /// Adding keys to table sloodle_award_rounds
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table for sloodle_award_rounds
        $dbman->create_table($table);
    }


    if ($oldversion < 2011062700) {
        /// Define table sloodle_award_points to be created
        $table = new xmldb_table('sloodle_award_points');

        /// Adding fields to table sloodle_award_points
        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('userid',      XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);
        $table->add_field('currencyid',  XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);
        $table->add_field('amount',      XMLDB_TYPE_INTEGER, '10', null,           XMLDB_NOTNULL, null,           null, null);
        $table->add_field('timeawarded', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);
        $table->add_field('roundid',     XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);

        /// Adding keys to table sloodle_award_points
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table for sloodle_award_points
        $dbman->create_table($table);
    }


    if ($oldversion < 2011070500 ) {
        /// Define field description to be added to sloodle_award_points
        $table = new xmldb_table('sloodle_award_points');
        $field = new xmldb_field('description');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'roundid');

        /// Launch add field description
        $dbman->add_field($table, $field);
        /// Define key poiroufk (foreign) to be added to sloodle_award_points
        $table = new xmldb_table('sloodle_award_points');
        $key = new XMLDBKey('poiroufk');
        $key->set_attributes(XMLDB_KEY_FOREIGN, array('roundid'), 'sloodle_award_rounds', array('id'));

        /// Launch add key poiroufk
        $dbman->add_key($table, $key);
        /// Define key poiusefk (foreign) to be added to sloodle_award_points
        $table = new xmldb_table('sloodle_award_points');
        $key = new XMLDBKey('poiusefk');
        $key->set_attributes(XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        /// Launch add key poiusefk
        $dbman->add_key($table, $key);
        /// Define key poicurfk (foreign) to be added to sloodle_award_points
        $table = new xmldb_table('sloodle_award_points');
        $key = new XMLDBKey('poicurfk');
        $key->set_attributes(XMLDB_KEY_FOREIGN, array('currencyid'), 'sloodle_award_currency', array('id'));

        /// Launch add key poicurfk
        $dbman->add_key($table, $key);
        /// Define field courseid to be added to sloodle_award_rounds
        $table = new xmldb_table('sloodle_award_rounds');
        $field = new xmldb_field('courseid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'controllerid');

        /// Launch add field courseid
        $dbman->add_field($table, $field);
    }


    if ($oldversion < 2011070501) {
        /// Define field imageurl to be added to sloodle_currency_types
        $table = new xmldb_table('sloodle_currency_types');
        $field = new xmldb_field('imageurl');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'timemodified');

        /// Launch add field imageurl
        $dbman->add_field($table, $field);
        /// Define field displayorder to be added to sloodle_currency_types
        $table = new xmldb_table('sloodle_currency_types');
        $field = new xmldb_field('displayorder');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'imageurl');

        /// Launch add field displayorder
        $dbman->add_field($table, $field);
    }


    if ($oldversion < 2011070900) {
        /// Define field mediakey to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('mediakey');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'httpinurl');

        /// Launch add field mediakey
        $dbman->add_field($table, $field);
        /// Define field lastmessagetimestamp to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('lastmessagetimestamp');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'mediakey');

        /// Launch add field lastmessagetimestamp
        $dbman->add_field($table, $field);
        /// Define field httpinpassword to be added to sloodle_active_object
        $table = new xmldb_table('sloodle_active_object');
        $field = new xmldb_field('httpinpassword');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, 'lastmessagetimestamp');

        /// Launch add field httpinpassword
        $dbman->add_field($table, $field);
    }


    // Basic SLOODLE Tracker tables

    // Sloodle 1.2 snuck in in the middle of development and messed up the normal order.
    // This should be OK as long as we don't get another release in between...
    // If we absolutely have to, leaving the space up to 2009073000 to denote 1.2-series releases.
    if (!$dbman->table_exists(new xmldb_table('sloodle_activity_tool')) ) { 
        /// Insert 'sloodle_activity_tool' table                                                                                       
        echo " - sloodle_activity_tool<br />";                                                                                       
        $table = new xmldb_table('sloodle_activity_tool');                                                                         

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);     
        $table->add_field('trackerid',   XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);              
        $table->add_field('uuid',        XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);                          
        $table->add_field('description', XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);            
        $table->add_field('taskname',    XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);                
        $table->add_field('name',        XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);                      
        $table->add_field('type',        XMLDB_TYPE_CHAR,    '50',  null,           XMLDB_NOTNULL, null,           null, null);                     
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);  
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));                                                          
        $table->add_index('uuid', XMLDB_INDEX_UNIQUE, array('uuid'));                                                         

        $dbman->create_table($table);                                                                               
    }
                                                                                                                  

    /// Insert 'sloodle_activity_tracker' table                                                                                  
    if (!$dbman->table_exists(new xmldb_table('sloodle_activity_tracker')) ) { 
        echo " - sloodle_activity_tracker<br />";                                                                              
        $table = new xmldb_table('sloodle_activity_tracker');                                                                    

        $table->add_field('id',          XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('trackerid',   XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);     
        $table->add_field('objuuid',     XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);                  
        $table->add_field('avuuid',      XMLDB_TYPE_CHAR,    '255', null,           XMLDB_NOTNULL, null,           null, null);                    
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10',  XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           '0',  null);     
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));                                                          
                                                                                                
        $dbman->create_table($table);         
    }


    if (!$dbman->table_exists(new xmldb_table('sloodle_tracker')) ) { 
        /// Define table sloodle_tracker to be created
        $table = new xmldb_table('sloodle_tracker');

        /// Adding fields to table sloodle_tracker
        $table->add_field('id',        XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('sloodleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,           null, null);

        /// Adding keys to table sloodle_tracker
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sloodleid', XMLDB_KEY_UNIQUE, array('sloodleid'));

        /// Launch create table for sloodle_tracker
        $dbman->create_table($table);
    }


    if ($oldversion < 2011071101) {
        // needed by moodle 2 (but should already have been in <=1.9)
        // see http://docs.moodle.org/dev/Text_formats_2.0
        $table = new xmldb_table('sloodle');
        $field = new xmldb_field('introformat');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, 'intro');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }


    if ($oldversion < 2011072301) {
        /// Define field controllerid to be added to sloodle_layout
        $table = new xmldb_table('sloodle_layout');
        $field = new xmldb_field('controllerid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'timeupdated');

        /// Launch add field controllerid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }


    if ($oldversion < 2016092801) {
        //
        $table = new xmldb_table('sloodle_award_points');
        $field = new xmldb_field('tomoney');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'roundid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
   
        $table = new xmldb_table('sloodle_activity_tool');
        $field = new xmldb_field('award');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('roundid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'award');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('sloodle_activity_tracker');
        $field = new xmldb_field('award');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'avuuid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('sloodle_tracker');
        $field = new xmldb_field('autosend');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'sloodleid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('currency');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'autosend');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('sloodle_users');
        $index = new xmldb_index('uuid', XMLDB_INDEX_NOTUNIQUE, array('uuid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $index = new xmldb_index('uuid', XMLDB_INDEX_UNIQUE, array('uuid'));
        $dbman->add_index($table, $index);
    }


    return $result; 
}
