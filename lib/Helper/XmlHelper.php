<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2023
 */

namespace OCA\EidLogin\Helper;

trait XmlHelper {

	/**
	 * Call the given function with a modified XML entity loader and return the
	 * result (from https://github.com/nextcloud/user_saml).
	 *
	 * @returns mixed returns the result of the callable parameter
	 */
	public function callWithXmlEntityLoader(callable $func) {
		libxml_set_external_entity_loader(static function ($public, $system) {
			return $system;
		});
		$result = $func();
		libxml_set_external_entity_loader(static function () {
			return null;
		});
		return $result;
	}

}
