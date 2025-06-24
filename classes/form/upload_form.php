<?php
namespace mod_fluencytrack\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class upload_form extends \moodleform
{

    public function definition()
    {
        $mform = $this->_form;

        // Hidden field to keep the course module id
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('filepicker', 'audiofile', get_string('uploadaudio', 'mod_fluencytrack'), null, [
            'accepted_types' => ['audio'],
            'maxbytes' => 0
        ]);
        $mform->addRule('audiofile', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
