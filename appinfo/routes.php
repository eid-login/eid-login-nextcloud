<?php
/**
 * Route definitions for the Nextcloud eID-Login app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
return [
	'routes' => [
		['name' => 'settings#fetchIdp', 'url' => '/settings/fetchidp/{url}', 'verb' => 'GET'],
		['name' => 'settings#save', 'url' => '/settings/save', 'verb' => 'POST'],
		['name' => 'settings#reset', 'url' => '/settings/reset', 'verb' => 'GET'],
		['name' => 'settings#toggleActivated', 'url' => '/settings/toggleactivated', 'verb' => 'GET'],
		['name' => 'settings#toggleNoPwLogin', 'url' => '/settings/togglenopwlogin', 'verb' => 'GET'],
		['name' => 'settings#prepareRollover', 'url' => '/settings/preparerollover', 'verb' => 'GET'],
		['name' => 'settings#executeRollover', 'url' => '/settings/executerollover', 'verb' => 'GET'],
		['name' => 'eid#deleteEid', 'url' => '/eid/deleteeid', 'verb' => 'GET'],
		['name' => 'eid#createEid', 'url' => '/eid/createeid', 'verb' => 'GET'],
		['name' => 'eid#tcToken', 'url' => '/eid/tctoken/{id}', 'verb' => 'GET'],
		['name' => 'eid#loginEid', 'url' => '/eid/logineid', 'verb' => 'GET'],
		['name' => 'eid#resume', 'url' => '/eid/resume/[id}', 'verb' => 'GET'],
		['name' => 'saml#meta', 'url' => '/saml/meta', 'verb' => 'GET'],
		['name' => 'saml#acsPost', 'url' => '/saml/acs', 'verb' => 'POST'],
		['name' => 'saml#acsRedirect', 'url' => '/saml/acs', 'verb' => 'GET'],
	]
];
