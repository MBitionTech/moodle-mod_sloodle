<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';


class multiplefileupload_form extends moodleform
{
    var $insert_select = null;


	function definition() 
	{
		global $USER, $CFG;

        $maxfiles  = 20;
        $filetypes = array('.jpg', '.gif', '.png', '.mov', '.mpg'); 

        //
		$mform = $this->_form;                // MoodleQuickForm
		$mform->setDisableShortforms(true);

        // hidden elements
		$mform->addElement('hidden', 'sloodlemultiupdate', 1);
		$mform->setType('sloodlemultiupdate',  PARAM_INT);

		$mform->addElement('hidden', 'id', 1);
		$mform->setType('id',  PARAM_INT);

		$mform->addElement('hidden', 'mode', 'addfiles');
		$mform->setType('mode',  PARAM_ALPHA);

		$mform->addElement('hidden', 'maxfiles', $maxfiles);
		$mform->setType('maxfiles',  PARAM_INT);

		$mform->addElement('header', 'add_templ', get_string('presenter:bulkupload', 'sloodle'), null);
        //
        $options = array('end'=>-1);
        $this->insert_select = $mform->addElement('select', 'insertnum', get_string('presenter:insertto', 'sloodle'), $options);
        $mform->setDefault('insertnum', -1);

		$fmoption = array('subdirs'=>0, 'maxfiles'=>$maxfiles, 'accepted_types'=>$filetypes);
		$mform->addElement('filemanager', 'picfile', get_string('presenter:uploadmultifile','sloodle'), null, $fmoption);
		$mform->addHelpButton('picfile', 'presenter:uploadmultifile', 'sloodle');

        // buttons
		//$mform->addElement('submit', 'add_item', get_string('add_item', 'apply'));
		$this->add_action_buttons();
	}
}

