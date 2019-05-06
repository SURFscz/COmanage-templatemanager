# comanage-templatemanager
This is a plugin for the [COmanage Registry](https://www.internet2.edu/products-services/trust-identity/comanage/) application as provided and maintained by the [Internet2](https://www.internet2.edu/) foundation.

This project has the following deployment goals:
- create a Plugin for COmanage that provides an API call to allow creating COs based on a template CO
- allow out-of-scope configuration for this new CO using script based interfacea
- allow automatic enrollment into this newly created CO

COmanage TemplateManager Plugin
====================================
This plugin provides API calls to allow creating a CO based on a pre-configured template CO. Additional actions and settings can be configured next to the general CO template. This plugin supports a specific API key, which allows calls outside the basic API-user interface supplied by COmanage. This restricts access to instantiating this specific template to specific key users and prevents having to distribute the generic API user password to third parties.

Setup
=====
The provisioning plugin must be installed in the `local/Plugin` directory of the COmanage installation. Optionally, you can install it in the `app/AvailablePlugins` directory and link to it from the `local/Plugin` directory.

After installation, clear the cache and run the Cake database update script as per the COmanage instructions:
```
app/Console/cake cache
app/Console/cake database
```

This plugin provides a `TemplateManager` configuration option for COs. Use this configuration option to set various options as described below. Only COs of status 'Template' are eligible for duplication using this plugin (see the `instantiate` API call below).

Configuration
=============
The plugin requires a configuration file called `templatemanager.php` containing the following configuration:
```
<?php

$config=array(
  'templatemanager' => array(
    'script_root' => '<directory path for allowed scripts>'
  )
);
```

Once enabled, the plugin configuration can be found as `TemplateManager` under the regular CO configuration screen. Configuration offers several input fields;
- Secret API Key: fill this with a random string that needs to be supplied along with the API calls
- Actions: select the API endpoints that are allowed for this CO. COs created from a template will only have the `enroll` action available
- Script: enter the name of a preinstalled script available in the `script_root` path as defined above. This script receives the new CO ID as first and only parameter
- Description: default description to use for newly created COs
- Enrollments: select the valid enrollment flows that are allowed to be invoked through the `enroll` endpoint
- From: enter a default `from` address when an invitation for a self-signup enrollment flow is sent out
- Template: select a default template to use when sending out invitations
- Subject: enter a default subject to use when sending out invitations. Standard substitutions for CO_NAME and INVITE_URL apply
- Message body: enter a default message body to use when sending out invitations.Standard substitutions for CO_NAME and INVITE_URL apply

The `Script` option allows running a specific script after configuration. This script must be provisioned to a specific run directory on the COmanage webserver (defined in the configuration file). Only scripts inside this directory are viable options. The script is provided with the ID of the new CO. No other arguments are supplied, but the scripts can query relevant database information while running.

The `Enrollments` attribute determines the possible flows for this template. Which flow is kicked off exactly is determined in the API call. For self signup enrollments, an invitation email is sent using the message template specified in the `Template`, `Subject` and/or `Message Body` attributes. This is either a complete message template or a name of an exisiting message template in the CO. This can be overridden in the API call below.

API Calls
=========
The API calls follow the regular version 1 API interface of COmanage.

Instantiate
-----------
The `instantiate` endpoint allows instantiation of a CO template. Its interface can be found at:
```
<base url>/template_manager/template_manager/instantiate.json
```
Following the guidelines of the version 1 API, the JSON object looks as follows:
```
{
  "RequestType": "TemplateManager",
  "Version" : "1.0",
  "TemplateManagers": [{
    "Version" : "1.0",
    "CoId": "<template CO id>",
    "Key": "<template CO api key>",
    "Name": "<new name of the CO>",
    "Description": "<optional description>"
  }]
}
```
where:
- the `Key` field should contain the CO template specific API key
- the `CoId` field should contain the CO template ID (required)
- the `Name` field should contain a unique, non-existing CO name
- the optional `Description` field contains the CO description. Otherwise the template description is copied.

This call reports the following status codes:
- 403 Forbidden: for mismatch in API key or authentication, templates without configuration or COs for which the status is not `Template`.
- 500 Internal Error: for problems duplicating or post-configuring the new CO. An internal error message is supplied as well.
- 201 Added: for a succesful duplication and configuration

On succes, a status message is reported in JSON format:
```
{
  "ResponseType":"NewObject",
  "Version":"1.0",
  "ObjectType":"Co",
  "Id":"4"
}
```
The `Id` mentioned in this message is the ID of the newly configured CO. This CO was duplicated using the standard COmanage duplication method. Then the name and description were adjusted with data as provided, status is set to Active and finally the specified script was run to configure processes out of scope of COmanage. The newly created CO has a TemplateManager configuration that is copied from the original template, including the API key. This means that anyone with access to the original template can use the same key to access to newly created CO. The new TemplateManager configuration has its actions restricted to `enroll` only. This ensures that the newly created CO cannot, in turn, be used as a template without intervention of a CO platform admin or CO admin, using the regular COmanage webinterface.

Please note that the `instantiate` endpoint only works on COs with a status `Template`. Only CmpAdmins can set that status.

Enroll
------
The `enroll` endpoint allows kicking off specific enrollment flows for a CO for which they are defined in the accompanying template. Its interface can be found at
```
<base url>/template_manager/template_manager/enroll.json
```
Following the guidelines of the version 1 API, the JSON object looks as follows:
```
{
  "RequestType": "TemplateManager",
  "Version" : "1.0",
  "TemplateManagers": [{
    "Version" : "1.0",
    "CoId": "<template CO id>",
    "Key": "<template CO api key>",
    "org_identity": <oid-enrollment object>,
    "invitation": <invite-enrollment object>,
    "signup": <signup-enrollment object>,
  }]
}
```
where:
- the optional `org_identity` attribute contains an oid-enrollment object, with attributes, as defined below.
- the optional `invitation` attribute contains an invitation enrollment specification with attributes, as defined below.
- the optional `signup` attribute contains a signup enrollment specification, as defined below.

Only one of `org_identity`, `invitation` or `signup` objects should be specified in the request. If more than one are given, there is a coded preference for `signup`, then `invitation` and finally `org_identity`.

The oid-enrollment object is defined as follows:
```
  "org_identity": {
    "id": "<ID of the OrgIdentity or login identifier>",
    "attributes": {
      "<label>": "<value>",
      ...
    }
  }
```
where:
- the `id` parameter contains either a database id (numeric) of the OrgIdentity to copy, or the login identifier linked to the OrgIdentity
- the `attributes` parameter contains a list of attributes to override on the CoPerson object. By default all OrgIdentity values are copied to the CoPerson or CoPersonRole, but by setting values to `null` or `false`, these values are skipped. If another value is used, that value is entered in the relevant CoPerson or CoPersonRole record.

The attributes parameter supports the following attributes:
- honorific
- family
- given
- suffix
- middle
- mail
- title
- affiliation
- o
- ou

The invite-enrollment object is defined as follows:
```
  "invitation": {
    "name": "<name of the enrollment flow>",
    "attributes": {
      "<label>": "<value>",
      ...
    }
  }
```
where:
- the `name` attribute of the enrollment should match one of the enabled enrollments in the template settings
- the `label` value in the 'attributes' parameter should match a label of an enrollment attribute. The `value` is then entered as value for that enrollment attribute.

The signup-enrollment object is defined as follows:
```
  "signup": {
    "name": "<name of the enrollment flow>",
    "email": "<email address to send the invite to>",
    "template": "<optional template or template name to use>",
    "subject": "<optional email message subject>",
    "from": "<optional from address>"
  }
```
where:
- the `name` attribute of the enrollment should match one of the enabled enrollments in the template settings
- the optional `email` attribute is used to send a message to containing the link to the self signup enrollment
- the optional `template` attribute defines a complete email message template, or contains a name of a message template of the CO to be used for sending a self signup invitation. This overrides any CO template settings.


Tests
=====
This plugin comes without tests.


Disclaimer
==========
This plugin is provided AS-IS without any claims whatsoever to its functionality.


 