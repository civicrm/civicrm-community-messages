# CiviCRM Community Messages

CiviCRM includes a few places in the user-interface that display blurbs or
messages from civicrm.org.  This backend application hosts those
messages.  It's based on [Symfony Standard Edition (~2.2)](http://symfony.com/doc/2.2/index.html).

Content authors see: [Editing and updating messages](doc/messages.md)

## Installation (Buildkit)

If you've already configured [buildkit](https://github.com/civicrm/civicrm-buildkit), then:

```
## Add hostname, e.g. "127.0.0.1 messages.local"
$ vi /etc/hosts

## Download and install
$ civibuild create messages --url http://messages.local

## Restart Apache
```

## Installation (Manual)

```
git clone https://github.com/civicrm/civicrm-community-messages.git
cd civicrm-community-messages
cp app/config/parameters.yml.dist app/config/parameters.yml
vi app/config/parameters.yml
composer install
./app/console doctrine:schema:create
```

Then setup your http server (per preference).

## Configuration (CiviCRM)

By default, CiviCRM pulls messages from `https://alert.civicrm.org`. To
display messages from your own installation, put this in `civicrm.settings.php`:

```php
$civicrm_setting['CiviCRM Preferences']['communityMessagesUrl']
 = 'http://messages.local/alert?prot=1&ver={ver}&uf={uf}&sid={sid}&lang={lang}&co={co}';
```

## Troubleshooting

### 504 Gateway Timeout

Check the error log on www-prod:

```tail -f /var/log/nginx/alert.error.log```

Try clearing the symphony app cache:

```
sudo -i -u commsg
cd /var/www/alert.civicrm.org/app
./console cache:clear -e prod
```
