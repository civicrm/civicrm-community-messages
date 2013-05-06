Key Notes
=========
 * The system can be disabled by customizing the 'communityMessagesUrl' setting.
 * We can target messages on the alert-server and/or on the CiviCRM instance.
   Target criteria include CiviCRM version, CMS name, user permissions, and
   active components.
 * The protocol isn't perfectly flexible. If we don't like it, we can update
   the protocol.
   The protocol supports versioning (note "&prot=1" in the request URL).
 * The URL includes a unique site identifier ("sid"). The server can make a
   decision about how to use this sid.
 * The messages can include "clean" links directly to civicrm.org -- or we
   could do click-through tracking. Don't need to decide now -- anything works.
 * If we need to bundle an image file with the markup, we can update the
   protocol (e.g. add image data to the CommunityMessage struct).

CommunityMessages URL
=====================

To receive the current community message, the CiviCRM instance sends an HTTP
request to the alert service:

 - __Example__: https://alert.civicrm.org/alert
   - ?prot=1
   - &ver=4.2.1
   - &uf=Drupal6
   - &sid=abc123def456
 - __Formula__: {$alertUrl}
   - ?prot={$protocolVersion}
    - &ver={$civiVersion}
    - &uf={$ufName}
    - &sid={$uniqueNonSensitiveCode}

The response is a JSON document conforming to the CommunityMessages struct (below).


CommunityMessages Example JSON Document
=======================================

(Note: This isn't strictly JSON -- it's formatted for legibility)

```javascript
  {
    ttl: 86400,
    retry: 1800,
    messages: [
      {
        markup: "<div>hello world</div>",
        perms: ["administer CiviCRM"]
      },
      {
        markup: "<div>CiviMail is the bomb, right? <img src='%%resourceUrl%%/i/bomb.png'/></div>",
        perms: ["administer CiviCRM"],
        components: ['CiviMail']
      }
    ]
  }
```

CommunityMessages Data Model
============================

```
struct CommunityMessages {
  /**
   * list of messages; any one of these can be displayed
   */
  array<CommunityMessage> messages;

  /**
   * Number of seconds before this data expires (soft expiration)
   */
  int $ttl;

  /**
   * Number of seconds to wait before attempting to download again
   */
  int $retry;
  
  /**
   * Time at which this data expires (soft expiration)
   * This value should be computed by the client.
   * After a new download, set to "current time + $ttl".
   * After a failed download, set to "current time + $retry".
   */
  int $expires;
}

struct CommunityMessage {
  /**
   * HTML markup; may include tokens:
   *  - %%resourceUrl%% -- raw resource URL
   *  - %%ver%% -- raw CiviCRM version
   *  - %%uf%% -- type of CMS
   *  - %%php%% -- PHP version number
   *  - %%sid%% -- anonymized site id
   *  - %%baseUrl%% -- web root
   *  - %%lang%% -- language (e.g. en_US)
   *  - %%co%% -- default country
   *  - {{resourceUrl}} -- url-encoded resource URL
   *  - {{ver}} -- url-encoded CiviCRM version
   *
   * Note: All tokens can use "%%...%%" or "{{...}}" notation.
   *
   * If omitted, then display a null message
   */
  string $markup;
  
  /**
   * List of permissions; this message will only be shown if user has at
   * least one of the listed permissions.
   *
   * If omitted, default to array('administer CiviCRM')
   */
  array<string> $perms;

  /**
   * List of component names; message will only be shown if site has at
   * least one of the components enabled
   *
   * If omitted, then show on any site
   */
  array<string> $components;
}
```

CommunityMessages Dashboard Pseudocode
======================================

```php
// When the CiviCRM instances renders dashboard, it should do
// something like...

if (empty($settings->get('communityMessagesUrl'))
  return;
$communityMessages = $cache->get('communityMessages')
if (empty($communityMessages) || $communityMessages['expires'] > now())
  get $communityMessages using $settings->get('communityMessagesUrl');
  if (get succeeded)
    $communityMessages['expires'] = time() + $communityMessages['ttl'];
  else
    $communityMessages['expires'] = time() + $communityMessages['retry'];

$messages = filter $communityMessages['messages'] based on role/component
if (empty($messages))
  return;

$message = pick_random($messages);
if (empty($message['markup']))
  return;

$vars = array(
  '%%resourceUrl%%' => '...',
);
$smarty->assign('communityMessage', strtr($message['markup'], $vars));
```
