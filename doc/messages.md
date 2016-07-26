Updating Community Messages:
========================

Community messages are stored in a [Google Spreadsheet](https://docs.google.com/spreadsheets/d/1OnJXtxTaS3FfQRMHLffPETdDKk3OHmd1fxLc8zQt9PE/edit). All items in the spreadsheet will be shown to users on a random/rotating basis. Users will only be shown a message if they meet all the specified criteria.

Note: the order of the columns in the spreadsheet does not matter, but the names do. Do not rename any column header without also updating the code which depends on it.

Columns
-------
<dl>
  <h4>Criteria:</h4>

  <dt>live</dt>
  <dd>("yes" "no" or "test"). <b>Warning</b> when this column is "yes" the message will <i>automatically</i> be published (generally in about an hour).</dd>

  <dt>type</dt>
  <dd>("offers", "events", "asks", "releases" or leave blank). Allows sites to opt-out of certain types of messages.</dd>
  
  <dt>reg</dt>
  <dd>Has this site registered? ("yes"/"no" or leave blank for no filtering). This is currently difficult to determine and there are many registered sites this system doesn't know about.</dd>
  
  <dt>mem</dt>
  <dd>Are they a member? ("never", "yes", "new", "expiring", "grace", "past", or leave blank for no filtering). Note: "yes" encompasses "expiring" "new" and "grace".</dd>
  
  <dt>age</dt>
  <dd>How old is this site? Requires an operator (&lt; or &gt;) + relative date. Examples: "&lt; 2 months", "&gt; 1 year"</dd>
  
  <dt>ver</dt>
  <dd>Version of CiviCRM. Requires an operator (&lt;, &lt;=, &gt;, &gt;=, ==, !=) + version number. Examples: "&lt; 4.7", "&gt;= 4.6.5"</dd>
  
  <dt>cms</dt>
  <dd>Drupal, Wordpress, Joomla, or Backdrop.</dd>
  
  <dt>components</dt>
  <dd>(comma-separated e.g. "CiviMail,CiviMember") - if supplied, message will be hidden if any listed components are disabled.</dd>
  
  <dt>perms</dt>
  <dd>(comma-separated e.g. "access CiviCRM") - if supplied, only users with this permission will see the message. If left blank, defaults to "administer CiviCRM".</dd>
  
  <h4>Content:</h4>
  
  <dt>url</dt>
  <dd>Any part of the message surrounded by double-square brackets [[like this]] will become a link to this url.</dd>
  
  <dt>en</dt>
  <dd>e.g. "**Message title** Message body with [[link to something]]." <em>This is the only required field in the spreadsheet.</em></dd>
  
  <dt>fr, es, etc.</dt>
  <dd>To translate to another language add a new column to the spreadsheet with that language code as the header and it will be auto-detected by the messages app. If a language is missing, the messages app will default to English.</dd>
</dl>

Tokens
------

The following tokens can be used in constructing the url or the message.
Tokens like %%this%% will be output normally, and tokens like {{this}} will be encoded for use as a url argument.

| Token                        | Description                                                        |
| ---------------------------- | ------------------------------------------------------------------ |
| resourceUrl                  | Url to the local CiviCRM root (w/o trailing slash)                 |
| baseUrl                      | Base url of the website                                            |
| ver                          | Local CiviCRM version #                                            |
| uf                           | CMS used (Drupal, WordPress, etc.)                                 |
| php                          | Local PHP version                                                  |
| sid                          | Unique site identifier hash                                        |
| lang                         | Full language code e.g. en_US                                      |
| co                           | Country id (e.g. 1228 for USA)                                     |

The following will only work if we can identify the org and look them up in our own db. Since pingbacks are anonymous, we can only identify them if they have already registered or signed up for a membership through this app.

| Token                        | Description                                                        |
| ---------------------------- | ------------------------------------------------------------------ |
| contact_id                   | Organization id in civicrm.org/civicrm db                          |
| display_name                 | Organization name                                                  |
| membership_start_date        | Start date (members only)                                          |
| membership_end_date          | End date (members only)                                            |
| membership_status_id         | Id of membership status (members only)                             |
| membership_status            | Name of membership status (members only)                           |

Testing
-------

Any CiviCRM site can be used for testing messages. By setting the site_id to "test_mode" (`CRM.api3('Setting', 'create', {site_id: "test_mode"})` from the javascript console) the site will show messages where "live" is set to "yes" *or* "test". This setting also bypasses caching so that changes to the google spreadsheet will be visible in about 30 seconds instead of an hour.