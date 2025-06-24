<?php
require('../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID
$search = optional_param('search', '', PARAM_TEXT); // Search by username or name

$cm = get_coursemodule_from_id('fluencytrack', $id, 0, false, MUST_EXIST);
$instance = $DB->get_record('fluencytrack', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($cm->course, true, $cm);

if (!has_capability('mod/fluencytrack:viewdashboard', $context)) {
    redirect(new moodle_url('/mod/fluencytrack/view.php', ['id' => $id]));
}

$PAGE->set_url('/mod/fluencytrack/teacher_dashboard.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title("Teacher Dashboard - {$instance->name}");
$PAGE->set_heading("Teacher Dashboard - {$instance->name}");

global $DB, $OUTPUT;

// Get submissions
$params = ['fluencytrackid' => $instance->id];
$sql = "SELECT s.*, u.id as userid, u.firstname, u.lastname, u.username
        FROM {fluencytrack_submissions} s
        JOIN {user} u ON s.userid = u.id
        WHERE s.fluencytrackid = :fluencytrackid";

if (!empty($search)) {
    $sql .= " AND (u.username LIKE :search1 OR u.firstname LIKE :search2 OR u.lastname LIKE :search3)";
    $params['search1'] = "%{$search}%";
    $params['search2'] = "%{$search}%";
    $params['search3'] = "%{$search}%";
}

$sql .= " ORDER BY s.timecreated DESC";
$submissions = $DB->get_records_sql($sql, $params);

$fs = get_file_storage();
$records = [];

foreach ($submissions as $submission) {
    // Get user's audio file
    $usercontext = context_user::instance($submission->userid);
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

    $records[] = [
        'userfullname' => fullname($submission),
        'fluencyscore' => $submission->fluencyscore,
        'audiourl' => $audiourl,
        'submitted' => userdate($submission->timecreated),
        'detailurl' => '#'
    ];
}

echo $OUTPUT->header();

// Render filter form
$filterdata = [
    'id' => $id,
    'searchvalue' => s($search)
];
echo $OUTPUT->render_from_template('mod_fluencytrack/teacher_filterform', $filterdata);

// Render dashboard table
$templatedata = [
    'hasresults' => !empty($records),
    'records' => $records
];
echo $OUTPUT->render_from_template('mod_fluencytrack/teacher_dashboard', $templatedata);

echo $OUTPUT->footer();
