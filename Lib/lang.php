<?php
/**
 * COmanage Registry Yoda Plugin Language File
 *
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_template_manager_texts['en_US'] = array(
  // Titles, per-controller
  'pl.ct.templatemanager' => 'Template Manager',
  'pl.fd.templatemanager.api_key' => 'Secret API Key',
  'pl.fd.templatemanager.api_key.descr' => 'Secret key to combine with the CO ID when calling on the API',
  'pl.fd.templatemanager.actions' => 'Actions',
  'pl.fd.templatemanager.actions.descr' => 'Select the active API end-points',
  'pl.fd.templatemanager.enroll' => 'Enrollments',
  'pl.fd.templatemanager.enroll.descr' => 'Select allowed enrollment flows',
  'pl.fd.templatemanager.script' => 'Script',
  'pl.fd.templatemanager.script.descr' => 'Enter the name of a preinstalled script in the designated location for configuration out-of-scope of COmanage',
  'pl.fd.templatemanager.description' => 'Description',
  'pl.fd.templatemanager.description.descr' => 'Default description for new COs if none is supplied',
  'pl.fd.templatemanager.name' => 'Script',
  'pl.fd.templatemanager.name.descr' => 'Default name for new COs if none is supplied. Please note that there can be no two COs with the same name.',
  'pl.fd.templatemanager.template_body' => 'Message body',
  'pl.fd.templatemanager.template_body.descr' => 'Default message body to send out for self-signup invitations',
  'pl.fd.templatemanager.template_id' => 'Template',
  'pl.fd.templatemanager.template_id.descr' => 'Default template to use for self-signup invitations',
  'pl.fd.templatemanager.subject' => 'Subject',
  'pl.fd.templatemanager.subject.descr' => 'Default subject to use for self-signup invitations.',
  'pl.fd.templatemanager.from' => 'From',
  'pl.fd.templatemanager.from.descr' => 'Default from address to use when sending self-signup invitations',
  'pl.fd.templatemanager.settings' => 'Settings',
  'pl.fd.templatemanager.settings.descr' => 'JSON settings as defined in the README',
);
