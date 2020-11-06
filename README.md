# LampStudyPortal

This Module was developed for the intended purpose of pulling in a subset of patient data from the Pattern Health API
for image adjudication & storage within REDCap.

This External Module supports the following functions:

- [x] Creates a backend endpoint for CRON tasks to run, pulling in high priority images and corresponding patient data uploaded to Pattern Health.
- [x] Provides a simple UI for image adjudication, in which research coordinators affiliated with the Stanford LAMP study can
    examine photos with COVID results.

- [ ] (*In progress*) Creates a backend endpoint for CRON tasks that pull in all patient and survey data, acting as a mirror
    of Pattern Health data within REDCap.


##Project Setup

The following settings are required upon setup of this EM:


1. `REDCAP API token` : Token generated from the API tab within a project
2. `Project Workflow` : Choice between Image adjudication and Patient Import
    - Selecting Image adjudication will set this project to pull in all data with open Provider tasks needing attention.
    This option also provides a simple UI for the adjudication process.
    - Selecting Patient Import will set this project to pull in all data pertaining to the LAMP study patients and use REDCap as a mirrored database
3. `Pattern Health group id`: The auto-generated group ID provided by pattern health for the study, **E.g: g--QpZ2RFCw3mPdwZlQdWSD1**
4. `Pattern Health Email`: Login credential email for Pattern Health
5. `Pattern Health Password`: Login credential password for Pattern Health

Note: These projects operate as CRON tasks and will update on a recurring basis.

## Instrument Configuration

The following fields are necessary to enable this EM (by variable name):

Variable name | Description
------------- | -------
`task_uuid` | Record ID field that denotes the task id of a journalEntryPhoto needed to be adjudicated
`patient_uuid` | Corresponding UUID of the patient the task belongs to
`activity_uuid` | Corresponding UUID of the activity within the task
`image_file`  | The image file the patient needs adjudicated
`full_json`  | READONLY field that stores the entire Provider task JSON
`created` | Photo creation date (uploaded)
`confidence` | Patient's response to confidence of image being COVID positive/negative
`status` | Provider task completion status. ```inProgress``` denotes awaiting adjudication, ```complete``` denotes completion
`adjudication_date` | Timestamp image was adjudicated by researcher
`provider_task_uuid` | ID of the corresponding provider task relative to the image task
`results` | Patient result of COVID positive / Negative
`provider_survey_uuid` | Survey ID of Provider task survey
`coordinator_user_id` | REDCap user ID for the individual submitting the adjudcation
