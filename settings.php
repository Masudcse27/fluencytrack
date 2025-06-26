<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('modsettingfluencytrack', get_string('pluginname', 'mod_fluencytrack'));

    $settings->add(new admin_setting_configtext(
        'mod_fluencytrack/assemblyai_api_key',
        get_string('assemblyai_api_key', 'mod_fluencytrack'),
        get_string('assemblyai_api_key_desc', 'mod_fluencytrack'),
        '25f52db7ddeb418cafbccabc63038230'
    ));

    $settings->add(new admin_setting_configtext(
        'mod_fluencytrack/assemblyai_api_endpoint',
        get_string('assemblyai_api_endpoint', 'mod_fluencytrack'),
        get_string('assemblyai_api_endpoint_desc', 'mod_fluencytrack'),
        'https://api.assemblyai.com/v2'
    ));

    $settings->add(new admin_setting_configtext(
        'mod_fluencytrack/languagetool_api_endpoint',
        get_string('languagetool_api_endpoint', 'mod_fluencytrack'),
        get_string('languagetool_api_endpoint_desc', 'mod_fluencytrack'),
        'https://api.languagetool.org/v2/check'
    ));

    $ADMIN->add('modsettings', $settings);
}
