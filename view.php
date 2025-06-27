<?php
require('../../config.php');
require_once('classes/form/upload_form.php');
require_once($CFG->libdir . '/gradelib.php');
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

if (has_capability('mod/fluencytrack:viewdashboard', $context)) {
    redirect(new moodle_url('/mod/fluencytrack/teacher_dashboard.php', ['id' => $id]));
}


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

        $scaledgrade = ($fluencyScore / 100) * $instance->grade;
        $grade = [
            'userid' => $USER->id,
            'rawgrade' => $scaledgrade
        ];
        fluencytrack_grade_item_update($instance, $grade);

        $existingsubmission = $submission;
    } else {
        echo $OUTPUT->header();
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }
}
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_fluencytrack', 'audiofile', 0, 'itemid, filepath, filename', false);
$file = reset($files);
$fileurl = '';
if ($file) {
    $fileurl = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    );
}

$grades = grade_get_grades($instance->course, 'mod', 'fluencytrack', $instance->id, $USER->id);
$gradeitem = $grades->items[0] ?? null;
$gradevalue = null;
if ($gradeitem && isset($gradeitem->grades[$USER->id])) {
    $gradevalue = $gradeitem->grades[$USER->id]->grade;
}
$grade_item = grade_item::fetch([
    'iteminstance' => $instance->id,
    'itemmodule' => 'fluencytrack',
    'courseid' => $cm->course
]);
$maxgrade = $grade_item ? $grade_item->grademax : 100;
$renderdata['grade'] = $gradevalue !== null ? round($gradevalue, 2)."/".round($maxgrade, 2) : 'Not graded';

$renderdata['submitted'] = true;
$renderdata['transcript'] = $existingsubmission->transcript;
$renderdata['grammarfeedback'] = $existingsubmission->grammarfeedback;
$renderdata['fluencyscore'] = $existingsubmission->fluencyscore;
$renderdata['fileurl'] = $fileurl;
$renderdata['filename'] = $file ? $file->get_filename() : '';
$renderdata['id'] = $cm->course;


echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_fluencytrack/student_result', $renderdata);
echo $OUTPUT->footer();
