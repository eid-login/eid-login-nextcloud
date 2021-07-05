# eID-Login for Nextcloud
This is the eID-Login app for the [Nextcloud](https://nextcloud.com) platform.
It has been developed by [ecsec](https://ecsec.de) on behalf of the [German Federal Office for Information Security](https://www.bsi.bund.de/).

The eID-Login App allows to use the German eID-card and similar electronic identity documents for secure and privacy-friendly login to Nextcloud. For this purpose, a so-called eID-Client, such as the AusweisApp2 or the Open eCard App and eID-Service are required. In the default configuration a suitable eID-Service is provided without any additional costs.

# Installation
The installation of the app can be done in the usual possible ways for Nextcloud. Please refer to the [Nextcloud documentation](https://docs.nextcloud.com/server/latest/admin_manual/apps_management.html).

# Configuration Options
If you want the app to skip XML Validation of a SAML Response add the following to you Nextcloud `config.php` file
```php
'eidlogin_skipxmlvalidation' => true
```
