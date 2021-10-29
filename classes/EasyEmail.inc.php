<?php

/**
 * @file classes/EasyEmail.inc.php
 *
 * @package plugins.generic.EasyEmails
 * @class EasyEmail
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
		return $this->getData('context_id');
	}

	/**
	 * Set context ID
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('context_id', $contextId);
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

	/**
	 * Get subscriber Email active
	 * @param locale
	 * @return string
	 */
	function getActive() {
		return $this->getData('active');
	}

	/**
	 * Set subscriber Email active
	 * @param locale
	 * @return string
	 */
	function setActive($status) {
		return $this->setData('active', $status);
}

	/**
	 * Get subscriber prefered locale
	 * @param locale
	 * @return string
	 */
	function getLocale() {
		return $this->getData('locale');
	}

	/**
	 * Set subscriber prefered locale
	 * @param locale
	 * @return string
	 */
	function setLocale($locale) {
		return $this->setData('locale', $locale);
}
}