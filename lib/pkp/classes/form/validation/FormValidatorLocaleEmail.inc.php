<?php

/**
 * @file classes/form/validation/FormValidatorLocaleEmail.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorLocaleEmail
 * @ingroup form_validation
 * @see FormValidatorLocale
 *
 * @brief Form validation check for email addresses.
 */

import('lib.pkp.classes.form.validation.FormValidatorLocale');
import('lib.pkp.classes.validation.ValidatorEmail');

class FormValidatorLocaleEmail extends FormValidatorLocale {
	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $requiredLocale string The symbolic name of the required locale
	 */
	function FormValidatorLocaleEmail(&$form, $field, $type, $message, $requiredLocale = null) {
		$validator = new ValidatorEmail();
		parent::FormValidatorLocale($form, $field, $type, $message, $requiredLocale, $validator);
	}
}

?>
