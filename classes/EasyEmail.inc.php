<?php

/**
 * @file classes/EasayEmail.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.EasayEmails
 * @class EasayEmail
 * Data object representing a static page.
 */

class EasyEmail extends DataObject {

	//
	// Get/set methods
	//

	/**
	 * Get context ID
	 * @return string
	 */
	function getContextId(){
		return $this->getData('contextId');
	}

	/**
	 * Set context ID
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}


	/**
	 * Set subscriber Email
	 * @param string string
	 * @param locale
	 */
	function setEmail($email) {
		return $this->setData('email', $email);
	}

	/**
	 * Get subscriber Email
	 * @param locale
	 * @return string
	 */
	function getEmail() {
		return $this->getData('email');
	}
}

