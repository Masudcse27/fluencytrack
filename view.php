<?php
require('../../config.php');
require_once('classes/form/upload_form.php');
use mod_fluencytrack\form\upload_form;
use mod_fluencytrack\api\assemblyai;
use mod_fluencytrack\api\languagetool;

$id = required_param('id', PARAM_INT); // Course module ID (cmid)

$cm = get_coursemodule_from_id('fluencytrack', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('fluencytrack', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($cm->course, true, $cm);

$PAGE->set_url('/mod/fluencytrack/view.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($instance->name));


global $DB, $USER;

$existingsubmission = $DB->get_record('fluencytrack_submissions', [
    'fluencytrackid' => $instance->id,
    'userid' => $USER->id
]);

$renderdata = [];
$renderdata['id'] = $id;
$renderdata['submitted'] = false;

if (!$existingsubmission) {
    $mform = new upload_form(null, ['id' => $id]);
    $mform->set_data(['id' => $id]);

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/course/view.php', ['id' => $cm->course]));
    } else if ($data = $mform->get_data()) {
        $fs = get_file_storage();
        $draftitemid = file_get_submitted_draft_itemid('audiofile');
        file_save_draft_area_files($draftitemid, $context->id, 'mod_fluencytrack', 'audiofile', 0);

        $files = $fs->get_area_files($context->id, 'mod_fluencytrack', 'audiofile', 0, 'itemid, filepath, filename', false);
        $file = reset($files);
        $filepath = $file->copy_content_to_temp();

        $transcript = assemblyai::transcribe($filepath);
        $grammarfeedback = languagetool::check($transcript);
        $grammarIssueCount = substr_count($grammarfeedback, '✏️');
        $fluencyScore = languagetool::estimate_fluency($transcript, $grammarIssueCount, $filepath);

        $submission = new stdClass();
        $submission->fluencytrackid = $instance->id;
        $submission->userid = $USER->id;
        $submission->audiofile = $file->get_filename();
        $submission->transcript = $transcript;
        $submission->grammarfeedback = $grammarfeedback;
        $submission->fluencyscore = $fluencyScore;
        $submission->timecreated = time();
        $DB->insert_record('fluencytrack_submissions', $submission);

        $existingsubmission = $submission;
    } else {
        echo $OUTPUT->header();
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }
}

echo $OUTPUT->header();
echo "Upload Successful";
echo $OUTPUT->footer();
