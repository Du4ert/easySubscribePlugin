<?php
import('classes.handler.Handler');
class EasySubscribePluginHandler extends Handler {
    public $contextId;
    public $plugin;
    public $captchaEnabled;

    function __construct($request) {
        parent::__construct();
        $this->contextId = $request->getContext()->getId();
        $this->plugin = PluginRegistry::getPlugin('generic', 'easysubscribeplugin');
        $this->captchaEnabled = Config::getVar('captcha', 'captcha_on_register') && Config::getVar('captcha', 'recaptcha');
    }

    public function index($args, $request) {
        $templateMgr = TemplateManager::getManager($request);
		
        if ($this->captchaEnabled) {
			$publicKey = Config::getVar('captcha', 'recaptcha_public_key');
			$reCaptchaHtml = '<div class="g-recaptcha" data-sitekey="' . $publicKey . '"></div>';
			$templateMgr->assign(array(
				'reCaptchaHtml' => $reCaptchaHtml,
				'captchaEnabled' => true,
			));
            $templateMgr->addJavaScript('recaptcha', 'https://www.recaptcha.net/recaptcha/api.js?hl=' . substr(AppLocale::getLocale(),0,2));
		}
        return $templateMgr->display($this->plugin->getTemplateResource('subscribe.tpl'));
    }


    public function register($args, $request) {
        $templateMgr = TemplateManager::getManager($request);
        $newEmail = $request->getUserVar('email');
        $confirmEmail = $request->getUserVar('email_confirm');
        $csrfToken = $request->getUserVar('csrfToken');
        $recaptcha = $request->getUserVar('g-recaptcha-response');
        
        $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
        $message = '';

        if (!$csrfToken) {
            $templateMgr->assign([
                'errorMsg' => 'plugins.generic.easySubscribe.page.register.error',
                'backLink' => $request->url(null, null, 'index'),
                'backLinkLabel' => 'plugins.generic.easySubscribe.page.subscribe.title',
            ]);
            $templateMgr->addJavaScript('subscribe', 'js/subscribe.js');

            return $templateMgr->display('frontend/pages/error.tpl');
        }

        if($this->captchaEnabled)
        {
            $secret = Config::getVar('captcha', 'recaptcha_private_key');
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$recaptcha);
            $responseData = json_decode($verifyResponse);
            if(!$responseData->success) {
                if ($this->captchaEnabled) {
                    $publicKey = Config::getVar('captcha', 'recaptcha_public_key');
                    $reCaptchaHtml = '<div class="g-recaptcha" data-sitekey="' . $publicKey . '"></div>';
                    $templateMgr->assign(array(
                        'reCaptchaHtml' => $reCaptchaHtml,
                        'captchaEnabled' => true,
                    ));
                    $templateMgr->addJavaScript('recaptcha', 'https://www.recaptcha.net/recaptcha/api.js?hl=' . substr(AppLocale::getLocale(),0,2));
                }
                $message = __('plugins.generic.easySubscribe.form.captcha');
                $templateMgr->assign([
                    'status' => 'error',
                    'message' => $message,
                    'email' => $newEmail
                ]);
                return $templateMgr->display($this->plugin->getTemplateResource('subscribe.tpl'));
            }
         }
        
        if ($newEmail !== $confirmEmail) {
            $message = __('plugins.generic.easySubscribe.page.register.confirm.error');
            $templateMgr->assign([
                'status' => 'error',
                'message' => $message,
                'email' => $newEmail
            ]);
            return $templateMgr->display($this->plugin->getTemplateResource('subscribe.tpl'));
        } 

        if (!$easyEmailDao->getByEmail($this->contextId, $newEmail) && !!$newEmail) {
            $easyEmail = $easyEmailDao->newDataObject();
            $easyEmail->setEmail((string) $newEmail, null);
            $easyEmail->setContextId((int) $this->contextId);
            $easyEmailDao->insertObject($easyEmail);
            $message = __('plugins.generic.easySubscribe.form.success');
            $templateMgr->assign([
                'status' => 'success',
                'message' => $message,
                'email' => $newEmail
            ]);
        } 
        else {
        $message = $newEmail ? __('plugins.generic.easySubscribe.form.error.exists') : __('plugins.generic.easySubscribe.form.error.empty');
        $templateMgr->assign([
            'status' => 'error',
            'message' => $message,
            'email' => $newEmail
		]);
        }
        return $templateMgr->display($this->plugin->getTemplateResource('subscribe.tpl'));
    }

    public function unsubscribe($args, $request) {
        $templateMgr = TemplateManager::getManager($request);
        $email = $request->getUserVar('email');
        $id = $request->getUserVar('id');
        
        $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
        $message = '';

        $easyEmail = $easyEmailDao->getById($this->contextId, $id);

        if ($easyEmail && $easyEmail->getEmail() === $email) {
            $easyEmailDao->deleteById($id);

            $message = __('plugins.generic.easySubscribe.unsubscribe.success');

            $templateMgr->assign([
                'status' => 'success',
                'message' => $message,
            ]);
        } 
        else {
        $message = __('plugins.generic.easySubscribe.unsubscribe.error');
        $templateMgr->assign([
            'status' => 'error',
            'message' => $message,
		]);
        }
        return $templateMgr->display($this->plugin->getTemplateResource('unsubscribe.tpl'));
    }

    //! Функционал для тестирования. Нужно перенести в админ панель
    // public function list($args, $request) {
    //     $templateMgr = TemplateManager::getManager($request);
    //     $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
    //     $emailsList = $easyEmailDao->getByContextId($this->contextId)->toArray();

    //     $templateMgr->assign([
    //         'emailsList' => $emailsList,
    //     ]);
    

    //     return $templateMgr->display($this->plugin->getTemplateResource('list.tpl'));
    // }

}