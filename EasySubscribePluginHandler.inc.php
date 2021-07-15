<?php
import('classes.handler.Handler');
class EasySubscribePluginHandler extends Handler {
    public $contextId;
    public $plugin;

    function __construct($request) {
        parent::__construct();
        $this->contextId = $request->getContext()->getId();
        $this->plugin = PluginRegistry::getPlugin('generic', 'easysubscribeplugin');
    }

    public function index($args, $request) {
        $templateMgr = TemplateManager::getManager($request);
        return $templateMgr->display($this->plugin->getTemplateResource('subscribe.tpl'));
    }


    public function register($args, $request) {
        $templateMgr = TemplateManager::getManager($request);
        $newEmail = $request->getUserVar('email');
        
        $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
        $message = '';

        if (!$easyEmailDao->getByEmail($this->contextId, $newEmail) && !!$newEmail) {
            $easyEmail = $easyEmailDao->newDataObject();
            $easyEmail->setEmail((string) $newEmail, null);
            $easyEmail->setContextId((int) $this->contextId);
            $easyEmailDao->insertObject($easyEmail);
            $message = __('plugins.generic.easySubscribe.form.success');
            $templateMgr->assign([
                'status' => 'success',
                'message' => $message
            ]);
        } 
        else {
        $message = $newEmail ? __('plugins.generic.easySubscribe.form.error.exists') : __('plugins.generic.easySubscribe.form.error.empty');
        $templateMgr->assign([
            'status' => 'error',
            'message' => $message,
		]);
        }
        return $templateMgr->display($this->plugin->getTemplateResource('subscribe.tpl'));
    }

    public function list($args, $request) {
        $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
        $emailsList = $easyEmailDao->getByContextId($this->contextId)->toArray();

        $output = '';
        
        foreach ($emailsList as $email) {
            $output .= '<p>' . $email->getData('email') . '</p>';
        }

        return $this->printData($output);
    }

    private function printData($data) {
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
    }

    public function sendToSubscribers($subject, $body) {
        import('lib.pkp.classes.mail.Mail');
        $fromEmail = $this->$context->getData('contactEmail');
        $fromName = $this->$context->getData('contactName');

        $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
        $emailsList = $easyEmailDao->getByContextId($this->contextId)->toArray();

		foreach ($emailsList as $email) {
					$mail = new Mail();
					$mail->setFrom($fromEmail, $fromName);
					$mail->setRecipients([
						[
							'name' => 'Dear subscriber',
							'email' => $email,
						],
					]);
					$mail->setSubject($subject);
					$mail->setBody($body);
					$mail->send();
				}

        return $subject . $body;
    }
}