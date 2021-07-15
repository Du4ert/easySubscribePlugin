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
			import('plugins.generic.easySubscribe.classes.EasyEmailDAO');
			$easyEmailDao = new EasyEmailDAO();
			DAORegistry::registerDAO('EasyEmailDAO', $easyEmailDao);

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
	function setRegistrationHandler($hookName, $params)
	{
		$page = $params[0];
		if ($this->getEnabled() && $page == 'easysubscribe') {
			$this->import('RegistrationHandler');
			define('HANDLER_CLASS', 'RegistrationHandler');
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
	 * Enable the settings form in the site-wide plugins list
	 *
	 * @return string
	 */
	public function isSitePlugin()
	{
		return true;
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

		// Add the LinkAction to the existing actions.
		// Make it the first action to be consistent with
		// other plugins.
		array_unshift($actions, $linkAction);

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
		}
		return parent::manage($args, $request);
	}




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


	public function mailSendCallback($hookName, $params)
	{


		return false;
	}

	public function announcementAddCallback($hookName, $params)
	{
		$announcement = $params[0];

		$sendEmail = $announcement->getData('sendEmail');

		if (!$sendEmail) {
			return false;
		}

		$request = Application::get()->getRequest();
		$context = $request->getContext();

		$subject = 'Новое уведомление с сайта ' . $context->getLocalizedName('ru_RU');
		$body = '';
		$title = $announcement->getLocalizedTitle('ru_RU');
		$descriptionShort = $announcement->getLocalizedDescriptionShort('ru_RU');
		$url = $request->getBaseUrl() . '/' . $context->getPath() . '/announcement/view/' . $announcement->getData('id');

		$body .= '<h1>Новое объявление:</h1>';
		$body .= "<h2>$title</h2>";
		$body .= "$descriptionShort";
		$body .= 'Подробнее по ссылке: <a href="';
		$body .= $url;
		$body .= '">';
		$body .= $url;
		$body .= "</a>";

		$this->sendToSubscribers($subject, $body, $context);

		return true;
	}

	public function bulkNotificationCallback($hookName, $params)
	{
		$handler = $params[1];
		$groupIds = $_POST['userGroupIds'];
		$targetGroupId = EasySubscribePlugin::GROUP_READERS_ID;

		if (get_class($handler) === 'PKPEmailHandler' && in_array($targetGroupId, $groupIds)) {
			$context = Application::get()->getRequest()->getContext();
			$subject = $_POST['subject'];
			$body = $_POST['body'];


			$this->sendToSubscribers($subject, $body, $context);
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

			$title = '<p>Опубликован новый выпуск: ' . $issue->getLocalizedData('title') . '</p>';
			$url = $request->getBaseUrl() . '/' . $context->getPath() . '/issue/view/' . $issue->getData('id');

			$subject = 'Новое уведомление с сайта ' . $context->getLocalizedName('ru_RU');

			$body = '<p>';
			$body .= $title;
			$body .= '<br/>';
			$body .= "Доступен по ссылке: <a href='$url'>$url</a>";
			$body .= '</p>';


			$this->sendToSubscribers($subject, $body, $context);
			return true;
		}
		return false;
	}

	public function sendToSubscribers($subject, $body, $context)
	{
		import('lib.pkp.classes.mail.Mail');
		$fromEmail = $context->getData('contactEmail');
		$fromName = $context->getData('contactName');

		$easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
		$emailsList = $easyEmailDao->getByContextId($context->getId())->toArray();

		foreach ($emailsList as $email) {
			$mail = new Mail();
			$mail->setFrom($fromEmail, $fromName);
			$mail->setRecipients([
				[
					'name' => '',
					'email' => $email->getData('email'),
				],
			]);
			$mail->setSubject($subject);
			$mail->setBody($body);
			$mail->send();
		}

		return $subject . $body;
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
