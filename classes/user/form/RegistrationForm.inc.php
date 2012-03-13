<?php

/**
 * @defgroup user_form
 */

/**
 * @file classes/user/form/RegistrationForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationForm
 * @ingroup user_form
 *
 * @brief Form for user registration.
 */

// $Id$


import('lib.pkp.classes.form.Form');

class RegistrationForm extends Form {

	/** @var boolean user is already registered with another journal */
	var $existingUser;

	/** @var AuthPlugin default authentication source, if specified */
	var $defaultAuth;

	/** @var boolean whether or not captcha is enabled for this form */
	var $captchaEnabled;

	/** @var boolean whether or not implicit authentication is used */
	var $implicitAuth;

	/**
	 * Constructor.
	 */
	function RegistrationForm() {
		parent::Form('user/register.tpl');
		$this->implicitAuth = Config::getVar('security', 'implicit_auth');

		if ($this->implicitAuth) {
			// If implicit auth - it is always an existing user
			$this->existingUser = 1;
		} else {
			$this->existingUser = Request::getUserVar('existingUser') ? 1 : 0;

			import('lib.pkp.classes.captcha.CaptchaManager');
			$captchaManager = new CaptchaManager();
			$this->captchaEnabled = ($captchaManager->isEnabled() && Config::getVar('captcha', 'captcha_on_register'))?true:false;

			// Validation checks for this form
			$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
			$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));

			if ($this->existingUser) {
				// Existing user -- check login
				$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.login.loginError', create_function('$username,$form', 'return Validation::checkCredentials($form->getData(\'username\'), $form->getData(\'password\'));'), array(&$this)));
			} else {
				// New user -- check required profile fields
				$site =& Request::getSite();

				$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
				$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
				$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
				$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
				$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
				$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
				$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
				$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
				$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailsDoNotMatch', create_function('$email,$form', 'return $email == $form->getData(\'confirmEmail\');'), array(&$this)));
				$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(), true));
				if ($this->captchaEnabled) {
					$this->addCheck(new FormValidatorCaptcha($this, 'captcha', 'captchaId', 'common.captchaField.badCaptcha'));
				}

				$authDao =& DAORegistry::getDAO('AuthSourceDAO');
				$this->defaultAuth =& $authDao->getDefaultPlugin();
				if (isset($this->defaultAuth)) {
					$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', create_function('$username,$form,$auth', 'return (!$auth->userExists($username) || $auth->authenticate($username, $form->getData(\'password\')));'), array(&$this, $this->defaultAuth)));
				}
			}
		}

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$site =& Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$journal =& Request::getJournal();

		if ($this->captchaEnabled) {
			import('lib.pkp.classes.captcha.CaptchaManager');
			$captchaManager = new CaptchaManager();
			$captcha =& $captchaManager->createCaptcha();
			if ($captcha) {
				$templateMgr->assign('captchaEnabled', $this->captchaEnabled);
				$this->setData('captchaId', $captcha->getId());
			}
		}

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());

		$templateMgr->assign('privacyStatement', $journal->getLocalizedSetting('privacyStatement'));
		$templateMgr->assign('allowRegReader', $journal->getSetting('allowRegReader'));
		$templateMgr->assign('enableOpenAccessNotification', $journal->getSetting('enableOpenAccessNotification'));
		$templateMgr->assign('allowRegAuthor', $journal->getSetting('allowRegAuthor'));
		$templateMgr->assign('allowRegReviewer', $journal->getSetting('allowRegReviewer'));
		$templateMgr->assign('source', Request::getUserVar('source'));

		$site =& Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());

		$templateMgr->assign('helpTopicId', 'user.registerAndProfile');
		parent::display();
	}

	function getLocaleFieldNames() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getLocaleFieldNames();
	}

	/**
	 * Initialize default data.
	 */
	function initData() {
		$this->setData('registerAsReader', 1);
		$this->setData('existingUser', $this->existingUser);
		$this->setData('userLocales', array());
		$this->setData('sendPassword', 1);

		$interestDao =& DAORegistry::getDAO('InterestDAO');
		// Get all available interests to populate the autocomplete with
		if ($interestDao->getAllUniqueInterests()) {
			$existingInterests = $interestDao->getAllUniqueInterests();
		} else $existingInterests = null;
		$this->setData('existingInterests', $existingInterests);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array(
			'username', 'password', 'password2',
			'salutation', 'firstName', 'middleName', 'lastName',
			'gender', 'initials', 'country',
			'affiliation', 'email', 'confirmEmail', 'userUrl', 'phone', 'fax', 'signature',
			'mailingAddress', 'biography', 'interests', 'interestsKeywords', 'userLocales',
			'registerAsReader', 'openAccessNotification', 'registerAsAuthor',
			'registerAsReviewer', 'existingUser', 'sendPassword'
		);
		if ($this->captchaEnabled) {
			$userVars[] = 'captchaId';
			$userVars[] = 'captcha';
		}

		$this->readUserVars($userVars);

		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}

		if ($this->getData('username') != null) {
			// Usernames must be lowercase
			$this->setData('username', strtolower($this->getData('username')));
		}

		$interests = $this->getData('interestsKeywords');
		if ($interests != null && is_array($interests)) {
			// The interests are coming in encoded -- Decode them for DB storage
			$this->setData('interestsKeywords', array_map('urldecode', $interests));
		}
	}

	/**
	 * Register a new user.
	 */
	function execute() {
		$requireValidation = Config::getVar('email', 'require_validation');

		if ($this->existingUser) { // If using implicit auth - we hardwire that we are working on an existing user
			// Existing user in the system
			$userDao =& DAORegistry::getDAO('UserDAO');

			if ($this->implicitAuth) { // If we are using implicit auth - then use the session username variable - rather than data from the form
				$sessionManager =& SessionManager::getManager();
				$session =& $sessionManager->getUserSession();

				$user =& $userDao->getUserByUsername($session->getSessionVar('username'));
			} else {
				$user =& $userDao->getUserByUsername($this->getData('username'));
			}

			if ($user == null) {
				return false;
			}

			$userId = $user->getId();

		} else {
			// New user
			$user = new User();

			$user->setUsername($this->getData('username'));
			$user->setSalutation($this->getData('salutation'));
			$user->setFirstName($this->getData('firstName'));
			$user->setMiddleName($this->getData('middleName'));
			$user->setInitials($this->getData('initials'));
			$user->setLastName($this->getData('lastName'));
			$user->setGender($this->getData('gender'));
			$user->setAffiliation($this->getData('affiliation'), null); // Localized
			$user->setSignature($this->getData('signature'), null); // Localized
			$user->setEmail($this->getData('email'));
			$user->setUrl($this->getData('userUrl'));
			$user->setPhone($this->getData('phone'));
			$user->setFax($this->getData('fax'));
			$user->setMailingAddress($this->getData('mailingAddress'));
			$user->setBiography($this->getData('biography'), null); // Localized
			$user->setDateRegistered(Core::getCurrentDate());
			$user->setCountry($this->getData('country'));

			$site =& Request::getSite();
			$availableLocales = $site->getSupportedLocales();

			$locales = array();
			foreach ($this->getData('userLocales') as $locale) {
				if (Locale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
					array_push($locales, $locale);
				}
			}
			$user->setLocales($locales);

			if (isset($this->defaultAuth)) {
				$user->setPassword($this->getData('password'));
				// FIXME Check result and handle failures
				$this->defaultAuth->doCreateUser($user);
				$user->setAuthId($this->defaultAuth->authId);
			}
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));

			if ($requireValidation) {
				// The account should be created in a disabled
				// state.
				$user->setDisabled(true);
				$user->setDisabledReason(Locale::translate('user.login.accountNotValidated'));
			}

			$userDao =& DAORegistry::getDAO('UserDAO');
			$userDao->insertUser($user);
			$userId = $user->getId();
			if (!$userId) {
				return false;
			}

			// Insert the user interests
			import('lib.pkp.classes.user.InterestManager');
			$interestManager = new InterestManager();
			$interestManager->insertInterests($userId, $this->getData('interestsKeywords'), $this->getData('interests'));

			$sessionManager =& SessionManager::getManager();
			$session =& $sessionManager->getUserSession();
			$session->setSessionVar('username', $user->getUsername());

		}

		$journal =& Request::getJournal();
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		// Roles users are allowed to register themselves in
		$allowedRoles = array('reader' => 'registerAsReader', 'author' => 'registerAsAuthor', 'reviewer' => 'registerAsReviewer');

		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		if (!$journalSettingsDao->getSetting($journal->getId(), 'allowRegReader')) {
			unset($allowedRoles['reader']);
		}
		if (!$journalSettingsDao->getSetting($journal->getId(), 'allowRegAuthor')) {
			unset($allowedRoles['author']);
		}
		if (!$journalSettingsDao->getSetting($journal->getId(), 'allowRegReviewer')) {
			unset($allowedRoles['reviewer']);
		}

		foreach ($allowedRoles as $k => $v) {
			$roleId = $roleDao->getRoleIdFromPath($k);
			if ($this->getData($v) && !$roleDao->roleExists($journal->getId(), $userId, $roleId)) {
				$role = new Role();
				$role->setJournalId($journal->getId());
				$role->setUserId($userId);
				$role->setRoleId($roleId);
				$roleDao->insertRole($role, 0);

			}
		}

		if (!$this->existingUser) {
			import('classes.mail.MailTemplate');
			if ($requireValidation) {
				// Create an access key
				import('lib.pkp.classes.security.AccessKeyManager');
				$accessKeyManager = new AccessKeyManager();
				$accessKey = $accessKeyManager->createKey('RegisterContext', $user->getId(), null, Config::getVar('email', 'validation_timeout'));

				// Send email validation request to user
				$mail = new MailTemplate('USER_VALIDATE');
				$mail->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
				$mail->assignParams(array(
					'userFullName' => $user->getFullName(),
					'activateUrl' => Request::url($journal->getPath(), 'user', 'activateUser', array($this->getData('username'), $accessKey))
				));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
				unset($mail);
			}
			if ($this->getData('sendPassword')) {
				// Send welcome email to user
				$mail = new MailTemplate('USER_REGISTER');
				$mail->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
				$mail->assignParams(array(
					'username' => $this->getData('username'),
					'password' => String::substr($this->getData('password'), 0, 30), // Prevent mailer abuse via long passwords
					'userFullName' => $user->getFullName()
				));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
				unset($mail);
			}
		}

		if (isset($allowedRoles['reader']) && $this->getData('openAccessNotification')) {
			$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
			$userSettingsDao->updateSetting($userId, 'openAccessNotification', true, 'bool', $journal->getId());
		}
	}

}

?>
