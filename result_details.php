<?php
require('../../config.php');

$id = required_param('id', PARAM_INT);       // Course module ID
$userid = required_param('userid', PARAM_INT); // User ID

$cm = get_coursemodule_from_id('fluencytrack', $id, 0, false, MUST_EXIST);
$instance = $DB->get_record('fluencytrack', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($cm->course, true, $cm);
if (!has_capability('mod/fluencytrack:viewdashboard', $context)) {
    redirect(new moodle_url('/mod/fluencytrack/view.php', ['id' => $id]));
}

$PAGE->set_context($context);
$PAGE->set_title("Submission Details");
$PAGE->set_heading("Submission Details");

echo $OUTPUT->header();

// Get student submission
$submission = $DB->get_record('fluencytrack_submissions', [
    'fluencytrackid' => $instance->id,
    'userid' => $userid
], '*', MUST_EXIST);

// Get user info
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
$fs = get_file_storage();

$files = $fs->get_area_files($context->id, 'mod_fluencytrack', 'audiofile', 0, '', false);
$file = reset($files);
$audiourl = '';

if ($file) {
    $audiourl = moodle_url::make_pluginfile_url(
        $context->id,
        'mod_fluencytrack',
        'audiofile',
        0,
        '/',
        $file->get_filename()
    );
}

// Prepare data for template
$data = [
    'userfullname' => fullname($user),
    'fluencyscore' => $submission->fluencyscore,
    'transcript' => $submission->transcript,
    'grammarfeedback' => $submission->grammarfeedback,
    'submitted' => true,
    'fileurl' => $audiourl,
    'filename' => $file ? $file->get_filename() : '',
    'id' => $id
];

echo $OUTPUT->render_from_template('mod_fluencytrack/details', $data);

echo $OUTPUT->footer();
