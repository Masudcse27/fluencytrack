# Fluency Track
FluencyTrack is a Moodle activity mod plugin that lets students submit audio recordings to assess their speaking fluency and grammar. Using AI, it generates a fluency score (0–100), provides grammar feedback, and displays transcripts. The plugin automatically calculates the final grade based on the configured maximum grade, stores it using Moodle's Gradebook API, and ensures role-based access to results.

## Features
- Audio Upload: Students can record and upload audio responses.
- Fluency Score (0–100) is calculated and stored.
- Grammar Feedback is generated based on transcript analysis.
- Transcript & Feedback: Displays transcript and grammar corrections.
- Teacher Dashboard: Teachers can view all submissions, playback audio, and see scores and feedback.
- Gradebook Integration: Fluency score is automatically pushed to Moodle’s gradebook.
## Installation
### Install by downloading the ZIP file
- Install by downloading the ZIP file from Moodle plugins directory
- Download zip file from GitHub
- Unzip the zip file in /path/to/moodle/mod/fluencytrack folder or upload the zip file in the install plugins options from site administration : Site Administration -> Plugins -> Install Plugins -> Upload zip file
- In your Moodle site (as admin), Visit site administration to finish the installation.

### Install using git clone

Go to Moodle Project `root/mod/` directory and clone code by using following commands:

```
https://github.com/Masudcse27/fluencytrack.git

```

## Configuration

After installing the plugin, you can add this plugin as activity in your course:
- Go to your course 
- Enable edit mode
- Add Fluency Track as a new activity
- Complete the form

<img width="960" alt="Mod plugin form" src="https://github.com/Masudcse27/readme_images/blob/main/fluency_track/fluency_track_1.png">

## Settings
To update the plugin settings, navigate to plugin settings: Site Administration->Plugins-> Plugins overview -> Fluency tracker settings
- Go to Site Administrations plugins section.
- Find the plugins overview and go to the plugins overview
- Find the Fluency Track plugin and select the settings to configure your plugin settings.
### AI Service API Settings
The admin configures AI API keys and endpoint for generating transcripts from audio, checking grammar, and providing feedback.

<img width="960" alt="AI API configuration" src="https://github.com/Masudcse27/readme_images/blob/main/fluency_track/fluency_track_2.png">

## Attempted Activity
Students upload the audio

<img width="960" alt="Upload audio file form" src="https://github.com/Masudcse27/readme_images/blob/main/fluency_track/fluency_track_3.png">

Students get the feedback, fluency score, and grade

<img width="960" alt="Student feedback" src="https://github.com/Masudcse27/readme_images/blob/main/fluency_track/fluency_track_4.png">

Report
Admins and teachers can view the report:

<img width="960" alt="Activity Report" src="https://github.com/Masudcse27/readme_images/blob/main/fluency_track/fluency_track_5.png">

Admins and teachers can view individual student reports:

<img width="960" alt="individual student reports" src="https://github.com/Masudcse27/readme_images/blob/main/fluency_track/fluency_track_6.png">

## Grade Book
The student's grade for this activity is also added to the grade book.

<img width="960" alt="grade book report" src="https://github.com/Masudcse27/readme_images/blob/main/fluency_track/fluency_track_6.png">




