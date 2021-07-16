<?php

/**
 * @file plugins/blocks/makeSubmission/EasySubscribeBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EasySubscribeBlockPlugin
 * @ingroup plugins_blocks_makeSubmission
 *
 * @brief Class for the "Make a Submission" block plugin
 */



import('lib.pkp.classes.plugins.BlockPlugin');

class EasySubscribeBlockPlugin extends BlockPlugin {
	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.easySubscribe.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.easySubscribe.description');
	}

	/**
	 * @copydoc BlockPlugin::getContents()
	 */
	function getContents($templateMgr, $request = null) {
		$context = $request->getContext();
		if (!$context) {
			return '';
		}
		return parent::getContents($templateMgr);
	}
}
