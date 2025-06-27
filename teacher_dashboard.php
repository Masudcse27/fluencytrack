<?php
require('../../config.php');
require_once($CFG->libdir . '/gradelib.php');
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

// $grades = grade_get_grades($instance->course, 'mod', 'fluencytrack', $instance->id);
// $gradeitem = $grades->items[0] ?? null;

$fs = get_file_storage();
$records = [];
// var_dump($grades->items[0]);
// die();
$grade_item = grade_item::fetch([
    'iteminstance' => $instance->id,
    'itemmodule' => 'fluencytrack',
    'courseid' => $cm->course
]);
$maxgrade = $grade_item ? $grade_item->grademax : 100;
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

    $individualgrades = grade_get_grades($instance->course, 'mod', 'fluencytrack', $instance->id, $submission->userid);
    $gradeitem = $individualgrades->items[0] ?? null;
    $usergrade = null;

    if ($gradeitem && isset($gradeitem->grades[$submission->userid])) {
        $usergrade = $gradeitem->grades[$submission->userid]->grade;
    }

    $gradeformatted = $usergrade !== null
        ? round($usergrade, 2) . ' / ' . round($maxgrade, 2)
        : 'Not graded';
    $records[] = [
        'userfullname' => fullname($submission),
        'fluencyscore' => $submission->fluencyscore,
        'audiourl' => $audiourl,
        'grade' => $gradeformatted,
        'submitted' => userdate($submission->timecreated),
        'detailurl' => (new moodle_url('/mod/fluencytrack/result_details.php', [
            'id' => $id,
            'userid' => $submission->userid
        ]))->out()
    ];
}

echo $OUTPUT->header();

// Render filter form
$filterdata = [
    'id' => $id,
    'course_id' => $cm->course,
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
