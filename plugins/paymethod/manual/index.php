<?php

/**
 * @defgroup plugins_paymethod_manual
 */
 
/**
 * @file plugins/paymethod/manual/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_paymethod_manual
 * @brief Wrapper for manual payment plugin.
 *
 */

//$Id$

require_once('ManualPaymentPlugin.inc.php');

return new ManualPaymentPlugin();

?> 
