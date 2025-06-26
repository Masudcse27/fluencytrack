<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add a new fluencytrack instance.
 *
 * @param stdClass $data
 * @param mod_fluencytrack_mod_form $mform
 * @return int new instance id
 */
function fluencytrack_add_instance($data, $mform) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    // Insert into DB
    $data->id = $DB->insert_record('fluencytrack', $data);

    // Save uploaded audio file from draft area to plugin file area
    $context = context_module::instance($data->coursemodule);
    file_save_draft_area_files(
        $data->audiofile,      // draft item id from the form
        $context->id,         // context id
        'mod_fluencytrack', // component
        'audiofile',          // file area
        0,                    // itemid, 0 for general module files
        ['subdirs' => 0]      // options
    );

    return $data->id;
}

/**
 * Update an existing fluencytrack instance.
 *
 * @param stdClass $data
 * @param mod_fluencytrack_mod_form $mform
 * @return bool
 */
function fluencytrack_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    // Update DB record
    $result = $DB->update_record('fluencytrack', $data);

    // Save uploaded audio file from draft area
    $context = context_module::instance($data->coursemodule);
    file_save_draft_area_files(
        $data->audiofile,      // draft item id
        $context->id,
        'mod_fluencytrack',
        'audiofile',
        0,
        ['subdirs' => 0]
    );

    return $result;
}

/**
 * Delete an fluencytrack instance.
 *
 * @param int $id
 * @return bool
 */
function fluencytrack_delete_instance($id) {
    global $DB;

    if (!$instance = $DB->get_record('fluencytrack', ['id' => $id])) {
        return false;
    }

    // Delete main record
    $DB->delete_records('fluencytrack', ['id' => $id]);

    // Delete related submission data (optional)
    $DB->delete_records('fluencytrack_submissions', ['fluencytrackid' => $id]);

    // Delete all files from filearea
    $context = context_module::instance($instance->coursemodule);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_fluencytrack', 'audiofile');

    return true;
}

/**
 * File serving callback for serving uploaded audio files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function mod_fluencytrack_pluginfile(
    stdClass $course,
    stdClass $cm,
    context $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    global $USER;

    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea !== 'audiofile') {
        return false;
    }

    $fs = get_file_storage();

    // File path and name extraction from args
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    // IMPORTANT: Moodle files are always saved with filepath = '/'
    // Force $filepath to '/' if anything else
    if ($filepath !== '/') {
        $filepath = '/';
    }

    $file = $fs->get_file(
        $context->id,
        'mod_fluencytrack',
        'audiofile',
        0,
        $filepath,
        $filename
    );

    if (!$file || $file->is_directory()) {
        return false;
    }

    // Send file to browser
    send_stored_file($file, 0, 0, $forcedownload, $options);

    return true;
}

function fluencytrack_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        default:
            return null;
    }
}

function fluencytrack_grade_item_update($fluencytrack, $grades = null) {
    require_once($GLOBALS['CFG']->libdir.'/gradelib.php');

    $params = [
        'itemname' => clean_param($fluencytrack->name, PARAM_NOTAGS),
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => $fluencytrack->grade,
        'grademin' => 0,
    ];

    return grade_update('mod/fluencytrack', $fluencytrack->course, 'mod', 'fluencytrack',
                        $fluencytrack->id, 0, $grades, $params);
}