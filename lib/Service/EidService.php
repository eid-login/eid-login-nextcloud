<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Service;

use Psr\Log\LoggerInterface;
use OC\Authentication\Token\IToken;
use OCA\EidLogin\Db\EidUserMapper;
use OCA\EidLogin\Db\EidUser;
use OCA\EidLogin\Db\EidAttribute;
use OCA\EidLogin\Db\EidAttributeMapper;
use OCA\EidLogin\Db\EidContinueData;
use OCA\EidLogin\Db\EidContinueDataMapper;
use OCA\EidLogin\Db\EidResponseData;
use OCA\EidLogin\Db\EidResponseDataMapper;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\ISession;
use OCP\IUser;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IL10N;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Auth;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Utils;

/**
 * Class EidService handling the eid management.
 *
 * @package OCA\EidLogin\Service
 */
class EidService {

	/** @var ISession */
	private $session;
	/** @var IUserSession */
	private $userSession;
	/** @var IUserManager */
	private $userManager;
	/** @var EidUserMapper */
	private $userMapper;
	/** @var EidAttributeMapper */
	private $attributeMapper;
	/** @var EidContinueDataMapper */
	private $continueDataMapper;
	/** @var EidResponseDataMapper */
	private $responseDataMapper;
	/** @var LoggerInterface */
	private $logger;
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** * @var SamlService */
	private $samlService;
	/** @var String */
	private $uid;
	
	/** @var String */
	public const CHARS_RANDOM = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	/** @var String */
	public const COOKIE_NAME = 'nc_eidlogin';
	/** @var String */
	public const KEY_COOKIE = 'nc_eidlogin_cookie';
	/** @var String */
	public const KEY_NAMEID = 'nc_eidlogin_nameid';
	/** @var String */
	public const KEY_FLOW = 'nc_eidlogin_flow';
	/** @var String */
	public const KEY_REDIRECT = 'nc_eidlogin_redirect';
	/** @var String */
	public const KEY_UID = 'nc_eidlogin_uid';
	/** @var String */
	public const KEY_STATE_TOKEN = 'client.flow.v2.state.token'; // see https://github.com/nextcloud/server/blob/stable19/core/Controller/ClientFlowLoginV2Controller.php
	/** @var String */
	public const KEY_LOGIN_TOKEN = 'client.flow.v2.login.token'; // see https://github.com/nextcloud/server/blob/stable19/core/Controller/ClientFlowLoginV2Controller.php
	/** @var String */
	public const KEY_NO_PW_LOGIN = 'nc_eidlogin_no_pw_login';
	/** @var String */
	public const FLOW_CREATE = 'createeid';
	/** @var String */
	public const FLOW_LOGIN = 'logineid';

	/**
	 * @param IUserSession $userSession,
	 * @param ISession $session,
	 * @param IUserManager $userManager,
	 * @param EidUserMapper $userMapper,
	 * @param EidAttributeMapper $attributeMapper,
	 * @param EidContinueDataMapper $continueDataMapper,
	 * @param EidResonseDataMapper $resonseDataMapper,
	 * @param LoggerInterface $logger
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $l10n
	 * @param SamlService $samlService
	 */
	public function __construct(
			IUserSession $userSession,
			ISession $session,
			IUserManager $userManager,
			EidUserMapper $userMapper,
			EidAttributeMapper $attributeMapper,
			EidContinueDataMapper $continueDataMapper,
			EidResponseDataMapper $responseDataMapper,
			LoggerInterface $logger,
			IConfig $config,
			IURLGenerator $urlGenerator,
			IL10N $l10n,
			SamlService $samlService
		) {
		$this->session = $session;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->userMapper = $userMapper;
		$this->attributeMapper = $attributeMapper;
		$this->continueDataMapper = $continueDataMapper;
		$this->responseDataMapper = $responseDataMapper;
		$this->logger = $logger;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->samlService = $samlService;
		$this->uid = '';
		if (!is_null($this->userSession->getUser())) {
			$this->uid = $this->userSession->getUser()->getUid();
		}
	}

	/**
	 * Set the users uid
	 *
	 * @param String $uid
	 */
	public function setUid($uid='') : void {
		$this->uid = $uid;
	}

	/**
	 * Get the users uid
	 *
	 * @return String
	 */
	public function getUid() : String {
		return $this->uid;
	}

	/**
	 * Test if the user has an eid already.
	 *
	 * @param String $uid The uid of the user, for which to test. If null is given the current user is used.
	 *
	 * @throws \Exception If the user to work which could not be determined
	 * @return bool True if an eid exists
	 */
	public function checkEid($uid='') : bool {
		if ($uid==='') {
			if ($this->uid==='') {
				throw new \Exception('$uid of EidService is null, could not determine user for which to checkEid!');
			}
			$uid = $this->uid;
		}
		$count = $this->userMapper->countByUid($uid);
		if (intval($count) === 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set if password based login for current should be disabled.
	 *
	 * @param bool $noPwLogin The value to set
	 * @throws \Exception If the user to work which could not be determined
	 */
	public function setNoPwLogin($noPwLogin=false) : void {
		if ($this->uid==='') {
			throw new \Exception('$uid of EidService is null, could not determine user for which to set no_pw_login!');
		}
		if ($noPwLogin) {
			$this->config->setUserValue($this->uid, 'eidlogin', 'no_pw_login', 'true');
		} else {
			$this->config->deleteUserValue($this->uid, 'eidlogin', 'no_pw_login');
		}
	}

	/**
	 * Check if password based login for current user is disabled.
	 *
	 * @throws \Exception If the user to work which could not be determined
	 * @return bool True if password based login for current user is disabled
	 */
	public function checkNoPwLogin() : bool {
		if ($this->uid==='') {
			throw new \Exception('$uid of EidService is null, could not determine user for which to set no_pw_login!');
		}
		$noPwLogin = $this->config->getUserValue($this->uid, 'eidlogin', 'no_pw_login', 'false');
		if ($noPwLogin === 'true') {
			return true;
		}

		return false;
	}

	/**
	 * Start a eID Flow
	 *
	 * @param string $flow the flow we are starting
	 * @param string $redirectUrl the url to redirect to after the flow
	 *
	 * @return RedirectResponse the redirect response to start the flow
	 * @throws \Exception If invalid flow param is given
	 */
	public function startEidFlow($flow='', $redirectUrl='/') {
		if ($this->config->getAppValue('eidlogin', "activated", "")==="") {
			throw new \Exception('app not active');
		}
		if ($flow !== self::FLOW_CREATE && $flow !== self::FLOW_LOGIN) {
			throw new \Exception('invalid flow');
		}
		if ($redirectUrl === '') {
			throw new \Exception('missing redirectUrl');
		}
		if ($this->uid === '' && $flow === self::FLOW_CREATE) {
			throw new \Exception('$uid of EidService is null, could not determine user for which to start create SAML Flow!');
		}
		// the requestId
		$reqId = "eidlogin_".\OC::$server->getSecureRandom()->generate(24, self::CHARS_RANDOM);
		// the cookieId
		$cookieId = "eidlogin_".\OC::$server->getSecureRandom()->generate(24, self::CHARS_RANDOM);
		setcookie(self::COOKIE_NAME, $cookieId,  time()+60*5, '/', '', true, true);
		// data we need to continue when returning
		$continue = [
			self::KEY_FLOW => $flow,
			self::KEY_UID => $this->uid,
			self::KEY_COOKIE => $cookieId,
			self::KEY_REDIRECT => $redirectUrl
		];
		// if we are in a ClientLoginFlow2, we need the tokens from the session
		if ($this->session->exists(self::KEY_STATE_TOKEN)) {
			$continue[self::KEY_STATE_TOKEN] = $this->session->get(self::KEY_STATE_TOKEN);
		}
		if ($this->session->exists(self::KEY_LOGIN_TOKEN)) {
			$continue[self::KEY_LOGIN_TOKEN] = $this->session->get(self::KEY_LOGIN_TOKEN);
		}
		// save continue data
		$continue = json_encode($continue);
		$eidContinueData = new EidContinueData();
		$eidContinueData->setUid($reqId);
		$eidContinueData->setValue($continue);
		$eidContinueData->setTime(time());
		$this->continueDataMapper->insert($eidContinueData);
		// create url for redirect
		$url = null;
		// if we have an TR-03130 flow we need another redirect step,
		// to let the eID-Client fetch the tc token from us, not the eID-Server directly
		if ($this->samlService->checkForTr03130()) {
			$url = $this->urlGenerator->linkToRouteAbsolute('eidlogin.eid.tcToken', ['id' => urlencode($reqId)]);
			$url = "http://127.0.0.1:24727/eID-Client?tcTokenURL=".urlencode($url);
		} else {
			$url = $this->createAuthnReqUrl($reqId);
		}
		$resp = new RedirectResponse($url);

		return $resp;
	}

	/**
	 * Create an url from an SAML authnRequest
	 *
	 * @param String id The id for the authnRequest
	 *
	 * @return String The url
	 * @throws Exception If the login method of Auth returns null
	 */
	public function createAuthnReqUrl(String $id) : String {
		$settings = $this->samlService->getSamlSettings();
		$auth = new Auth($settings);
		$url = $auth->login(null, [], false, false, true, true, null, $id);
		if (!is_null($url)) {
			$this->logger->debug($auth->getLastRequestXML());
		} else {
			throw new \Exception('auth->login returned null as return value!');
		}

		return $url;
	}

	/**
	 * Process an incoming SAML Assertion.
	 *
	 * @param IRequest $request The request
	 *
	 * @return String a redirect url where to go next.
	 */
	public function processSamlResponse(IRequest $request) {
		// empty session nameID value if present
		if (array_key_exists(EidService::KEY_NAMEID, $_SESSION) && !is_null($_SESSION[EidService::KEY_NAMEID])) {
			unset($_SESSION[EidService::KEY_NAMEID]);
		}
		// defaults
		$samlSettings = $this->samlService->getSamlSettings();
		$redirectUrl = $this->urlGenerator->getBaseUrl();
		$response = null;
		$responseAsXML = null;
		$eidContinueData = null;
		// setup SAML Toolkit
		$auth = new Auth($samlSettings);
		try {
			// create response, check InResponseTo
			$response = $auth->createResponse();
			$responseAsXML = $response->getXMLDocument();
			$inResponseTo = null;
			if ($responseAsXML->documentElement->hasAttribute('InResponseTo')) {
				$inResponseTo = $responseAsXML->documentElement->getAttribute('InResponseTo');
			} else {
				throw new \Exception('missing inResponseTo Attribute in SAML Response');
			}
			// check for valid algorithms
			if ($this->samlService->checkForTr03130()) {
				$responseAsXMLenc = $response->getXMLDocument(true);
				$encMethodList = Utils::query($responseAsXMLenc, '/samlp:Response/saml:EncryptedAssertion/xenc:EncryptedData/xenc:EncryptionMethod');
				// check we have the DOMElement methods avail
				foreach ($encMethodList as $encMethod) {
					if (!method_exists($encMethod, 'hasAttribute')) {
						throw new \Exception('Missing hasAttribute Method on object'.print_r($encMethod, true));
					}
					if (!method_exists($encMethod, 'getAttribute')) {
						throw new \Exception('Missing getAttribute Method on object'.print_r($encMethod, true));
					}
				}
				if (count($encMethodList)!=1) {
					throw new \Exception('Expected one EncryptionMethod Node as child of EncryptedData but found '.count($encMethodList));
				}
				if (!$encMethodList[0]->hasAttribute('Algorithm')) {
					throw new \Exception('Found a EncryptionMethod Node for EncryptedData but missing Algorithm Attribute');
				}
				if (!in_array($encMethodList[0]->getAttribute('Algorithm'), $samlSettings['alg']['encryption']['data'])) {
					throw new \Exception('Found a EncryptionMethod Node for Encrypted Data with invalid Algorithm Attribute: '.$encMethodList[0]->getAttribute('Algorithm'));
				}
				$encMethodList = Utils::query($responseAsXMLenc, '/samlp:Response/saml:EncryptedAssertion/xenc:EncryptedData/ds:KeyInfo/xenc:EncryptedKey/xenc:EncryptionMethod');
				if (count($encMethodList)!=1) {
					throw new \Exception('Expected one EncryptionMethod Node as child of EncryptedKey but found '.count($encMethodList));
				}
				if (!$encMethodList[0]->hasAttribute('Algorithm')) {
					throw new \Exception('Found a EncryptionMethod Node for EncryptedKey but missing Algorithm Attribute');
				}
				if (!in_array($encMethodList[0]->getAttribute('Algorithm'), $samlSettings['alg']['encryption']['key'])) {
					throw new \Exception('Found a EncryptionMethod Node for EncryptedKey with invalid Algorithm Attribute: '.$encMethodList[0]->getAttribute('Algorithm'));
				}
			} else {
				$responseAsXMLenc = $response->getXMLDocument();
				$signMethodList = Utils::query($responseAsXMLenc, '/samlp:Response/saml:Assertion/ds:Signature/ds:SignedInfo/ds:SignatureMethod');
				if (count($signMethodList) === 1) {
					if (!$signMethodList[0]->hasAttribute('Algorithm')) {
						throw new \Exception('Found a SignatureMethodNode but missing Algorithm Attribute');
					}
					if (!in_array($signMethodList[0]->getAttribute('Algorithm'), $samlSettings['alg']['signing'])) {
						throw new \Exception('Found a SignatureMethodNode with invalid Algorithm Attribute: '.$signMethodList[0]->getAttribute('Algorithm'));
					}
				} elseif (count($signMethodList) > 1) {
					throw new \Exception('Expected max one SignatureMethod Node but found '.count($signMethodList));
				}
			}
			// load continue data and delete it from db
			$eidContinueData = $this->continueDataMapper->findByUid($inResponseTo);
			$this->continueDataMapper->deleteByUid($inResponseTo);
			// check the continue data is not older than 5 min
			$time = $eidContinueData->getTime();
			$limit = time()-300;
			if ($time < $limit) {
				throw new \Exception('eid continue data found for inResponseTo: '.$inResponseTo.' is expired');
			}
		} catch (\Exception $e) {
			$this->logger->error("error creating SAML Response for user ".$this->uid.": ".$e->getMessage());

			return $redirectUrl;
		}
		// process the response and gather it`s data
		$errors = [];
		$eid = null;
		$attributesAsXML = [];
		try {
			$auth->processCreatedResponse($response);
			// fetch errors
			$errors = $auth->getErrors();
			if (count($errors) === 0) {
				// fetch eid
				if ($this->samlService->checkForTr03130()) {
					// we must verify the external signature
					if (!array_key_exists('SigAlg', $_GET)) {
						throw new \Exception("Missing SigAlg param");
					}
					if (!in_array($_GET['SigAlg'], $samlSettings['alg']['signing'])) {
						throw new \Exception("Invalid SigAlg param ".filter_var($_REQUEST['SigAlg'],FILTER_SANITIZE_SPECIAL_CHARS));
					}
					Utils::validateBinarySign('SAMLResponse', $_GET, $samlSettings['idp']);
					$attributes = $response->getAttributes();
					if (array_key_exists('RestrictedID', $attributes) && count($attributes['RestrictedID'])===1) {
						$eid = $attributes['RestrictedID'][0];
					}
				} else {
					$eid = $response->getNameId();
				}
				// fetch attributes
				$attributesAsXML = $response->getAttributesAsXML();
			}
		} catch (\Exception $e) {
			$this->logger->info("error processing SAML Response for user ".$this->uid.": ".$e->getMessage());
			$errors[] = $e->getMessage();
		}
		// build response data
		$responseId = \OC::$server->getSecureRandom()->generate(24, self::CHARS_RANDOM);
		$responseData = [
			'isAuthenticated' => $auth->isAuthenticated(),
			'lastErrorException' => $auth->getLastErrorException(),
			'errors' => $errors,
			'status' => Utils::getStatus($responseAsXML),
			'eid' => $eid,
			'attributes' => $attributesAsXML
		];
		$continueData = get_object_vars(json_decode($eidContinueData->getValue()));
		$responseData = array_merge($responseData, $continueData);
		// if we have an TR-03130 flow we need another redirect step,
		// for this we save the response data to the db
		if ($this->samlService->checkForTr03130()) {
			$responseData = json_encode($responseData);
			$eidResponseData = new EidResponseData();
			$eidResponseData->setUid($responseId);
			$eidResponseData->setValue($responseData);
			$eidResponseData->setTime(time());
			$this->responseDataMapper->insert($eidResponseData);
			$redirectUrl = $this->urlGenerator->linkToRouteAbsolute('eidlogin.eid.resume', ['id' => urlencode($responseId)]);
		// otherwise process data now
		} else {
			$redirectUrl = $this->processSamlResponseData($request, $responseId, $responseData);
		}

		return $redirectUrl;
	}

	/**
	 * Process the data from a SAML Response.
	 * If only an responseId is given, the data is fetched from the db.
	 *
	 * @param IRequest The current request
	 * @param String|null The id of the response
	 * @param Array|null The data of the response
	 *
	 * @return String The url where to go next
	 */
	public function processSamlResponseData(IRequest $request, ?String $responseId = null, ?array $responseData = null) {
		// defaults
		$redirectUrl = $this->urlGenerator->getBaseUrl();
		$errMsgCreate = $this->l10n->t('Creation of eID connection failed! Please ensure the used eID-Card is valid.');
		$errMsgLogin = $this->l10n->t('Log in with eID failed! Please ensure the used eID-Card is valid.');
		// check for needed responseData
		if ($responseId === null) {
			throw new \Exception('processSamlResponseData - missing responseId');
		}
		if ($responseData === null) {
			try {
				$eidResponseData = $this->responseDataMapper->findByUid($responseId);
				$this->responseDataMapper->deleteByUid($responseId);
				$responseData = get_object_vars(json_decode($eidResponseData->getValue()));
			} catch (\Exception $e) {
				$this->logger->info('processSamlResponseData - could not find responseData for responseId: '.$responseId);

				return $redirectUrl;
			}
		}
		// get continue stuff from response data
		$this->uid = $responseData[self::KEY_UID];
		$redirectUrl = $responseData[self::KEY_REDIRECT];
		$flow = $responseData[self::KEY_FLOW];
		$cookieIdFromResponseData = $responseData[self::KEY_COOKIE];
		// check if correct cookie value is set
		if (!array_key_exists(self::COOKIE_NAME,$_COOKIE)) {
			$this->logger->error('processResponseData could not find needed cookie');
			$this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

			return $redirectUrl;
		}
		$cookieIdFromCookie = filter_var($_COOKIE[self::COOKIE_NAME], FILTER_SANITIZE_STRING);
		setcookie(self::COOKIE_NAME, '',  time()-1, '/', '', true, true);
		if ($cookieIdFromCookie != $cookieIdFromResponseData) {
			$this->logger->error('processResponseData could not find correct cookieId in cookie');
			$this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

			return $redirectUrl;
		}
		// do we have errors or an unauthenticated saml state?
		if (count($responseData['errors'])!==0 || !$responseData['isAuthenticated']) {
			// make error message more specific
			$msg = '';
			if (is_array($responseData['status'])) {
				$msg = $responseData['status']['msg'];
			}
			preg_match('/.*cancel.*/', $msg, $res);
			if (count($res)>0) {
				$errMsgLogin = $this->l10n->t('Log in with eID aborted');
				$errMsgCreate = $this->l10n->t('Creation of eID connection aborted');
			}
			$this->logger->info('processResponseData found errors or user not authenticated - errors:'.print_r($responseData['errors'], true).', saml status msg: '.$msg);
			$this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

			return $redirectUrl;
		}
		// fetch eid
		$eid = $responseData['eid'];
		if (is_null($eid)) {
			$this->logger->error('missing eid in SAML response');
			$this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

			return $redirectUrl;
		}
		// user is creating an eID connection from it's settings page
		if ($flow === self::FLOW_CREATE) {
			try {
				$this->createEid($eid, $responseData['attributes']);
			} catch (\Exception $e) {
				$errMsgCreate = $e->getMessage();
				$this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);
			}

			return $redirectUrl;
		}
		// user is logging in via eID connection from the login page
		if ($flow === self::FLOW_LOGIN) {
			try {
				// get user by eid mapping table of app
				$eidUser = $this->userMapper->findByEid($eid);
			} catch (\Exception $e) {
				$this->logger->info('processSamlResponseData got error '.$e->getMessage());
				// save nameId to session, to prevent another saml flow when creating the eid connection
				$_SESSION[self::KEY_NAMEID]=$eid;
				$errMsgLogin = $this->l10n->t('eID-Login is not yet set up for your account');
				$this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);

				return $redirectUrl;
			}
			try {
				$uid = $eidUser->getUid();
				$user = $this->userManager->get($uid);
				if (!($user instanceof IUser)) {
					throw new \Exception('user with eid '.$eid.' and uid '.$uid.' is not present');
				}
				//https://github.com/nextcloud/server/blob/stable19/lib/private/Authentication/Login/UserDisabledCheckCommand.php
				if (!$user->isEnabled()) {
					//https://github.com/nextcloud/server/blob/stable19/core/Controller/LoginController.php
					$this->session->set('loginMessages', [ ['userdisabled'], [] ]);
					throw new \Exception('user with eid '.$eid.' and uid '.$uid.' is not not enabled');
				}
				//https://github.com/nextcloud/server/blob/stable19/lib/private/Authentication/Login/CompleteLoginCommand.php
				$this->userSession->completeLogin($user, ['loginName' => $uid, 'password' => '']);
				//https://github.com/nextcloud/server/blob/stable19/lib/private/Authentication/Login/CreateSessionTokenCommand.php
				$tokenType = IToken::REMEMBER;
				$rememberLogin = true;
				if ((int)$this->config->getSystemValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15) === 0) {
					$tokenType = IToken::DO_NOT_REMEMBER;
					$rememberLogin = false;
				}
				$this->userSession->createSessionToken($request, $uid, $uid, null, $tokenType);
				//https://github.com/nextcloud/server/blob/stable19/lib/private/Authentication/Login/SetUserTimezoneCommand.php
				//TODO also send the users timezone and process it here
				//https://github.com/nextcloud/server/blob/stable19/lib/private/Authentication/Login/FinishRememberedLoginCommand.php
				if ($rememberLogin && $this->config->getSystemValue('auto_logout', false) === false) {
					$this->userSession->createRememberMeToken($user);
				}
				// if we are in a ClientLoginFlow2, we need the tokens in the session
				if (array_key_exists(self::KEY_STATE_TOKEN, $responseData) && !is_null($responseData[self::KEY_STATE_TOKEN])) {
					$this->session->set(self::KEY_STATE_TOKEN, $responseData[self::KEY_STATE_TOKEN]);
				}
				if (array_key_exists(self::KEY_LOGIN_TOKEN, $responseData) && !is_null($responseData[self::KEY_LOGIN_TOKEN])) {
					$this->session->set(self::KEY_LOGIN_TOKEN, $responseData[self::KEY_LOGIN_TOKEN]);
				}
			} catch (\Exception $e) {
				$this->logger->info('processSamlResponse in login flow got error '.$e->getMessage());
				$this->setErrorMsg($flow, $errMsgLogin, $errMsgCreate);
				$redirectUrl = $this->urlGenerator->getBaseURL();
			}
		}

		return $redirectUrl;
	}

	/**
	 * Create the eid connection of an user.
	 *
	 * @param String $eid The eid of the current user, for which the connection should be created.
	 * @param Array $attributes The eid attributes of the current user.
	 *
	 * @throws \Exception If an error occurs, message can be given to user!
	 */
	public function createEid($eid, $attributes=[]) : void {
		if ($this->uid==='') {
			$this->logger->error('$uid of EidService is null, could not determine user for which to createEid!');
			throw new \Exception($this->l10n->t('Creation of eID connection failed'));
		}
		// do we already have an eID connection
		if ($this->checkEid()) {
			throw new \Exception($this->l10n->t('An eID connection already exists'));
		}
		// does an connection of the eID to an account already exists?
		$eidUserPresent = null;
		try {
			$eidUserPresent = $this->userMapper->findByEid($eid);
		} catch (\Exception $e) {
		}
		if (!is_null($eidUserPresent)) {
			throw new \Exception($this->l10n->t('The eID is already connected to another account'));
		}
		// ok create
		try {
			$eidUser = new EidUser();
			$eidUser->setUid($this->uid);
			$eidUser->setEid($eid);
			$this->userMapper->insert($eidUser);
			foreach ($attributes as $name => $values) {
				if (count($values)===0) {
					continue;
				}
				$valueCount = 0;
				foreach ($values as $value) {
					$currentName = $name;
					if ($valueCount > 0) {
						$currentName .= '_'.strval($valueCount);
					}
					$eidAttribute = new EidAttribute();
					$eidAttribute->setUid($this->uid);
					$eidAttribute->setName($currentName);
					$eidAttribute->setValue($value);
					$this->attributeMapper->insert($eidAttribute);
					$valueCount++;
				}
			}
			$this->config->setUserValue($this->uid, 'eidlogin', 'saml_result', 'success');
			$this->config->setUserValue($this->uid, 'eidlogin', 'saml_msg', $this->l10n->t('eID connection has been created'));
			$this->logger->info("eid connection of user with uid ".$this->uid." created successfully");
		} catch (\Exception $e) {
			$this->logger->error('tried to create eID connection but exception occured: '.$e->getMessage());
			throw new \Exception($this->l10n->t('Creation of eID connection failed'));
		}
	}

	/**
	 * Delete the eid connection and attributes of a user.
	 *
	 * @param String $uid The uid of the user, for which the connection should be deleted. If null is given the current user is used.
	 *
	 * @throws \Exception If the user to work which could not be determined
	 */
	public function deleteEid(string $uid='') : void {
		if ($uid==='') {
			if ($this->uid==='') {
				throw new \Exception('$uid of EidService is null, could not determine user for which to deleteEid!');
			}
			$uid = $this->uid;
		}
		try {
			$this->userMapper->deleteByUid($uid);
			$this->attributeMapper->deleteByUid($uid);
			$this->config->deleteUserValue($uid, 'eidlogin', 'no_pw_login');
			$this->logger->info("eid connection of user with uid ".$uid." deleted successfully");
		} catch (\Exception $e) {
			throw new \Exception("failed to delete eid connection of user with uid ".$uid.": ".$e->getMessage());
		}

		return;
	}

	/**
	 * Delete the eid connections and attributes of all users and current continueData and responseData
	 *
	 * @return bool True on success, false in case of error
	 */
	public function deleteEids() : bool {
		try {
			foreach($this->userMapper->findAll() as $eidUser) {
				$this->config->deleteUserValue($eidUser->getId(), 'eidlogin', 'no_pw_login');
			}
			$this->userMapper->deleteAll();
			$this->attributeMapper->deleteAll();
			$this->continueDataMapper->deleteAll();
			$this->responseDataMapper->deleteAll();
			$this->logger->info("eid connection and attributes of all users and continueDate and responseData deleted successfully");

			return true;
		} catch (\Exception $e) {
			$this->logger->error("failed to delete eid connection of all users: ".$e->getMessage());
			return false;
		}
	}

	/**
	 * Set error messages, which one are used is determined by the type of flow we are in.
	 *
	 * @param String $flow The flow we are in
	 * @param String $errMsgLogin The msg for the login flow
	 * @param String $errMsgCreate The msg for the create flow
	 */
	private function setErrorMsg($flow=self::FLOW_LOGIN, $errMsgLogin='', $errMsgCreate='') : void {
		if ($flow === self::FLOW_LOGIN) {
			// will be shown on login page
			$loginMessages = $this->session->get('loginMessages');
			$loginMessages[1] = [$errMsgLogin];
			$this->session->set('loginMessages', $loginMessages);
		}

		// will be shown on settings page
		if ($flow === self::FLOW_CREATE) {
			$this->config->setUserValue($this->uid, 'eidlogin', 'saml_result', 'error');
			$this->config->setUserValue($this->uid, 'eidlogin', 'saml_msg', $errMsgCreate);
		}
	}
}
