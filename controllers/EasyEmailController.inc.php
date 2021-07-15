<?php
/**
 * @file EasyEmailController.inc.php

 */

class EasyEmailController 
{
	const GROUP_READERS_ID = 17; //! найти, где определяется id и переприсвоить
	const ISSUE_IGNORE_LIST = ['just_accepted'];


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
    
}
