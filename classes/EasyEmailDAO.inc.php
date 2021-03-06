<?php

/**
 * @file classes/EasyEmailDAO.inc.php
 *
 *
 * @package plugins.generic.EasyEmail
 * @class EasyEmailDAO
 * Operations for retrieving and modifying EasyEmail objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.easySubscribe.classes.EasyEmail');

class EasyEmailDAO extends DAO {

	/**
	 * Get a static page by ID
	 * @param $easyEmailId int Static page ID
	 * @param $contextId int Optional context ID
	 */
	function getById($contextId = null, $easyEmailId) {
		$params = [(int) $easyEmailId];
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM easysubscribe_emails WHERE easysubscribe_email_id = ?'
			. ($contextId?' AND context_id = ?':''),
			$params
		);
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get a set of easy email by context ID
	 * @param $contextId int
	 * @param $rangeInfo Object optional
	 * @return DAOResultFactory
	 */
	function getByContextId($contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM easysubscribe_emails WHERE context_id = ?',
			[(int) $contextId],
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow');
	}

		/**
	 * Get a set of easy email by context ID
	 * @param $contextId int
	 * @param $rangeInfo Object optional
	 * @return DAOResultFactory
	 */
	function getActiveByContextId($contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM easysubscribe_emails WHERE context_id = ? AND active = 1',
			[(int) $contextId],
			$rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Get a email by email address.
	 * @param $contextId int Context ID
	 * @param $email string Email
	 * @return EasyEmail
	 */
	function getByEmail($contextId, $email) {
		$result = $this->retrieve(
			'SELECT * FROM easysubscribe_emails WHERE context_id = ? AND email = ?',
			[(int) $contextId, $email]
		);
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}


	/**
	 * Insert a email to list.
	 * @param $easyEmail EasyEmail
	 * @return int Inserted static page ID
	 */
	function insertObject($easyEmail) {
		$this->update(
			'INSERT INTO easysubscribe_emails (context_id, email, locale, active) VALUES (?, ?, ?, ?)',
			[(int) $easyEmail->getContextId(), $easyEmail->getEmail(), $easyEmail->getLocale(), 0]
		);

		$easyEmail->setId($this->getInsertId());

		return $easyEmail->getId();
	}

	/**
	 * Update the database with a static page object
	 * @param $easyEmail EasyEmail
	 */
	function updateObject($easyEmail) {
		$this->update(
			'UPDATE	easysubscribe_emails
			SET	email = ?, locale = ?, active = ?
			WHERE	easysubscribe_email_id = ? AND context_id = ?',
			[
				$easyEmail->getEmail(),
				$easyEmail->getLocale(),
				(int) $easyEmail->getActive(),
				(int) $easyEmail->getId(),
				(int) $easyEmail->getContextId()
			]
		);
	}

	/**
	 * Update active status to 1
	 * @param $contextId INT $easyEmail EasyEmail
	 */
	function activate($contextId, $easyEmail) {
		$easyEmail->setActive(1);
		$easyEmail->setContextId($contextId);
		$this->updateObject($easyEmail);
	}

	/**
	 * Update active status to 0
	 * @param $contextId INT $easyEmail EasyEmail
	 */
	function deactivate($contextId, $easyEmail) {
		$easyEmail->setActive(0);
		$easyEmail->setContextId($contextId);
		$this->updateObject($easyEmail);
	}

	/**
	 * Delete a static page by ID.
	 * @param $easyEmailId int
	 */
	function deleteById($easyEmailId) {
		$this->update(
			'DELETE FROM easysubscribe_emails WHERE easysubscribe_email_id = ?',
			[(int) $easyEmailId]
		);
	}

	/**
	 * Delete a static page object.
	 * @param $easyEmail EasyEmail
	 */
	function deleteObject($easyEmail) {
		$this->deleteById($easyEmail->getId());
	}

	/**
	 * Generate a new static page object.
	 * @return EasyEmail
	 */
	function newDataObject() {
		return new EasyEmail();
	}

	/**
	 * Return a new easy email object from a given row.
	 * @return EasyEmail
	 */
	function _fromRow($row) {
		$easyEmail = $this->newDataObject();
		$easyEmail->setId($row['easysubscribe_email_id']);
		$easyEmail->setEmail($row['email']);
		$easyEmail->setLocale($row['locale']);
		$easyEmail->setActive($row['active']);

		return $easyEmail;
	}

	/**
	 * Get the insert ID for the last inserted easy email.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('easysubscribe_emails', 'easysubscribe_email_id');
	}

}

