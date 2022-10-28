<?php

/**
 * @file EasySubscribePlugin.inc.php
 *
 * Copyright (c) 2017-2021 Simon Fraser University
 * Copyright (c) 2017-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EasySubscribePlugin
 * @brief Plugin class for the EasySubscribe plugin.
 */
import('lib.pkp.classes.plugins.GenericPlugin');

class EasySubscribePlugin extends GenericPlugin
{
	const GROUP_READERS_ID = 17; //! найти, где определяется id и переприсвоить
	const ISSUE_IGNORE_LIST = ['just_accepted'];
	/**
	 * @copydoc GenericPlugin::register()
	 */
	public function register($category, $path, $mainContextId = NULL)
	{
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			// Register the static pages DAO.
			HookRegistry::register('PluginRegistry::loadCategory', [$this, 'updateSchema']);
			// import('plugins.generic.easySubscribe.classes.EasyEmailDAO');
			$this->import('classes/EasyEmailDAO');
			$easyEmailDao = new EasyEmailDAO();
			DAORegistry::registerDAO('EasyEmailDAO', $easyEmailDao);
			$this->import('EasySubscribeBlockPlugin');
			PluginRegistry::register('blocks', new EasySubscribeBlockPlugin($this), $this->getPluginPath());

			HookRegistry::register('LoadHandler', array($this, 'setPageHandler'));


			HookRegistry::register('APIHandler::endpoints', array($this, 'bulkNotificationCallback'));			//* рассылка по пользователям для читателей и подписчиков

			HookRegistry::register('Announcement::add', array($this, 'announcementAddCallback'));				//* публикация объявления

			HookRegistry::register('IssueGridHandler::publishIssue', array($this, 'publishIssueCallback'));		//* публикация выпуска
		}
		return $success;
	}

	/**
	 * Hook callback: register pages for each sushi-lite method
	 * This URL is of the form: orcidapi/{$orcidrequest}
	 * @see PKPPageRouter::route()
	 */

	public function setPageHandler($hookName, $params)
	{
		$page = $params[0];
		if ($page === 'easysubscribe') {
			$this->import('EasySubscribePluginHandler');
			define('HANDLER_CLASS', 'EasySubscribePluginHandler');
			return true;
		}
		return false;
	}

	/**
	 * Provide a name for this plugin
	 *
	 * The name will appear in the Plugin Gallery where editors can
	 * install, enable and disable plugins.
	 *
	 * @return string
	 */
	public function getDisplayName()
	{
		// return __('plugins.generic.easySubscribe.displayName');
		return 'Easy subscribe plugin';
	}

	/**
	 * Provide a description for this plugin
	 *
	 * The description will appear in the Plugin Gallery where editors can
	 * install, enable and disable plugins.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		// return __('plugins.generic.easySubscribe.description');
		return 'Easy subscribe description*';
	}


	/**
	 * Add a settings action to the plugin's entry in the
	 * plugins list.
	 *
	 * @param Request $request
	 * @param array $actionArgs
	 * @return array
	 */
	public function getActions($request, $actionArgs)
	{

		// Get the existing actions
		$actions = parent::getActions($request, $actionArgs);

		// Only add the settings action when the plugin is enabled
		if (!$this->getEnabled()) {
			return $actions;
		}

		// Create a LinkAction that will make a request to the
		// plugin's `manage` method with the `settings` verb.
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$linkAction = new LinkAction(
			'settings',
			new AjaxModal(
				$router->url(
					$request,
					null,
					null,
					'manage',
					null,
					[
						'verb' => 'settings',
						'plugin' => $this->getName(),
						'category' => 'generic'
					]
				),
				$this->getDisplayName()
			),
			__('manager.plugins.settings'),
			null
		);
		$linkAction2 = new LinkAction(
			'list',
			new AjaxModal(
				$router->url(
					$request,
					null,
					null,
					'manage',
					null,
					[
						'verb' => 'list',
						'plugin' => $this->getName(),
						'category' => 'generic'
					]
				),
				$this->getDisplayName()
			),
			'list',
			null
		);

		// Add the LinkAction to the existing actions.
		// Make it the first action to be consistent with
		// other plugins.
		array_unshift($actions, $linkAction, $linkAction2);

		return $actions;
	}

	/**
	 * Show and save the settings form when the settings action
	 * is clicked.
	 *
	 * @param array $args
	 * @param Request $request
	 * @return JSONMessage
	 */
	public function manage($args, $request)
	{
		switch ($request->getUserVar('verb')) {
			case 'settings':

				// Load the custom form
				$this->import('EasySubscribeSettingsForm');
				$form = new EasySubscribeSettingsForm($this);

				// Fetch the form the first time it loads, before
				// the user has tried to save it
				if (!$request->getUserVar('save')) {
					$form->initData();
					return new JSONMessage(true, $form->fetch($request));
				}

				// Validate and save the form data
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();
					return new JSONMessage(true);
				}
			case 'list':
				// Load the custom form
				$this->import('EasySubscribeListForm');
				$form = new EasySubscribeListForm($this);

				// Fetch the form the first time it loads, before
				// the user has tried to save it
				if (!$request->getUserVar('save')) {
					$form->initData();
					return new JSONMessage(true, $form->fetch($request));
				}
		}
		return parent::manage($args, $request);
	}

	public function easyTranslate($key, $locale) {
		if ($locale === AppLocale::getLocale()) {
			return __($key);
		} else {
			$easyTranslation = new LocaleFile($locale, $this->getPluginPath() . '/locale/' . $locale . '/locale.po');
			return $easyTranslation->translate($key);
		}
	}


	public function sendToSubscriber($body, $email, $request)
	{
		$context = $request->getContext();

		import('lib.pkp.classes.mail.Mail');
		$locale = $email->getLocale();
		$siteName = $context->getName($locale);
        $basePath = $request->getBaseUrl();
		$fromEmail = $context->getData('supportEmail');
		$fromName = $context->getData('supportName', $locale);
		$unsubscribeUrl = $basePath . '/' . $context->getPath() . '/easysubscribe/unsubscribe' . '?email=' . $email->getEmail() . '&id=' . $email->getId();

		$subject = $this->easyTranslate('plugins.generic.easySubscribe.letter.subject', $locale) . " " . $context->getName($locale);


		$header = "<small>" ;
		$header .= $this->easyTranslate('plugins.generic.easySubscribe.letter.header', $locale) . " ";
		$header .= $siteName;
		$header .= "</small>";

		$footer = "<small>";
		$footer .= $this->easyTranslate('plugins.generic.easySubscribe.letter.unsubscribe.text', $locale);
		$footer .= " <a href=\"$unsubscribeUrl\">";
		$footer .= $this->easyTranslate('plugins.generic.easySubscribe.letter.unsubscribe.title', $locale);
		$footer .= "</a></small>";

		$mail = new Mail();
		$mail->setFrom($fromEmail, $fromName);
		$mail->setRecipients([
			[
				'name' => '',
				'email' => $email->getEmail(),
			],
		]);
		$mail->setSubject($subject);
		$mail->setBody($body . "<p>$header <br/> $footer</p>");
		$mail->send();

		return true;
	}


	public function announcementAddCallback($hookName, $params)
	{
		$announcement = $params[0];

		if (!$announcement->getData('sendEmail')) {
			return false;
		}

		$request = Application::get()->getRequest();
		$context = $request->getContext();

		$easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
		$emailsList = $easyEmailDao->getActiveByContextId($context->getId())->toArray();

		foreach ($emailsList as $email) {
			$locale = $email->getLocale();
			$title = $announcement->getTitle($locale);
			$descriptionShort = $announcement->getDescriptionShort($locale);
			$url = $request->getBaseUrl() . '/' . $context->getPath() . '/announcement/view/' . $announcement->getData('id');

			$body  = "<p>";
			$body .= $this->easyTranslate('plugins.generic.easySubscribe.letter.announcement.title', $locale) . ' ';
			$body .= $title;
			$body .= "</p>";
			$body .= "<p>$descriptionShort</p><p>";
			$body .= $this->easyTranslate('plugins.generic.easySubscribe.letter.announcement.link', $locale) . ' ';
			$body .= '<a href="' . $url . '">' . $url . '</a></p>';

			$this->sendToSubscriber($body, $email, $request);
		}

		return true;
	}

	public function bulkNotificationCallback($hookName, $params)
	{
		$handler = $params[1];
		if (empty($_POST)) {
			return false;
		}
		$groupIds = $_POST['userGroupIds'];
		$targetGroupId = EasySubscribePlugin::GROUP_READERS_ID;

		if (get_class($handler) === 'PKPEmailHandler' && in_array($targetGroupId, $groupIds)) {
			$request = Application::get()->getRequest();
			$context = $request->getContext();

			$body = "<p><strong>";
			$body .= $_POST['subject'];
			$body .= "</strong></p>";
			$body .= "<p>";
			$body .= $_POST['body'];
			$body .= "</p>";

			$easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
			$emailsList = $easyEmailDao->getActiveByContextId($context->getId())->toArray();

			foreach ($emailsList as $email) {
				$locale = $email->getLocale();

				$this->sendToSubscriber($body, $email, $request);
			}

			return true;
		}

		return false;
	}

	public function publishIssueCallback($hookName, $params)
	{
		if ($_REQUEST['sendIssueNotification']) {
			$request = Application::get()->getRequest();
			$context = $request->getContext();

			$issue = $params[0];
			$issueUrl = $issue->getData('urlPath');

			if (in_array($issueUrl, EasySubscribePlugin::ISSUE_IGNORE_LIST)) {
				return false;
			}

			$easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
			$emailsList = $easyEmailDao->getActiveByContextId($context->getId())->toArray();

			foreach ($emailsList as $email) {
				$locale = $email->getLocale();

				$siteName = $context->getName($locale);
				$issueTitle = $issue->getIssueIdentification([], $locale);
				$url = $request->getBaseUrl() . '/' . $context->getPath() . '/issue/view/' . $issue->getData('id');

				$body = "<p>";
				$body .= $this->easyTranslate('plugins.generic.easySubscribe.letter.issue.title', $locale) . " ";
				$body .= $issueTitle;
				$body .= "</p><p>";
				$body .= $this->easyTranslate('plugins.generic.easySubscribe.letter.issue.link', $locale);
				$body .= " <a href='$url'>$url</a>";
				$body .= "</p>";

				$this->sendToSubscriber($body, $email, $request);
			}

			return true;
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getInstallMigration()
	 */
	function getInstallMigration()
	{
		$this->import('EasySubscribeSchemaMigration');
		return new EasySubscribeSchemaMigration();
	}

	/**
	 * Called during the install process to install the plugin schema,
	 * if applicable.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function updateSchema($hookName, $args)
	{
		$migration = $this->getInstallMigration();
		if ($migration && !$migration->check()) {
			$migration->up();
		}
		return false;
	}
}
