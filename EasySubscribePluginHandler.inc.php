<?php
require_once(__DIR__ . '/vendor/autoload.php');
use Gregwar\Captcha\CaptchaBuilder;
use Securimage;

import('classes.handler.Handler');
class EasySubscribePluginHandler extends Handler
{
    public $contextId;
    public $plugin;
    public $reCaptchaEnabled;
    public $captchaType;

    function __construct($request)
    {
        parent::__construct();
        $this->contextId = $request->getContext()->getId();
        $this->plugin = PluginRegistry::getPlugin('generic', 'easysubscribeplugin');
        $this->reCaptchaEnabled = Config::getVar('captcha', 'captcha_on_register') && Config::getVar('captcha', 'recaptcha');
        $this->captchaType = $this->plugin->getSetting($this->contextId, 'captchaType');
    }

    public function index($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $captchaHtml = '';
        if ($this->reCaptchaEnabled) {
            $publicKey = Config::getVar('captcha', 'recaptcha_public_key');
            $captchaHtml = '<div class="g-recaptcha" data-sitekey="' . $publicKey . '"></div>';

            $templateMgr->addJavaScript('recaptcha', 'https://www.recaptcha.net/recaptcha/api.js?hl=' . substr(AppLocale::getLocale(), 0, 2));
        } elseif ($this->captchaType === 'securimage') {
            $captchaHtml = Securimage::getCaptchaHtml();
        } elseif ($this->captchaType === 'gregwar') {
            $builder = new CaptchaBuilder;
            $builder->build($width = 200, $height = 60, $font = null);
            $captchaHtml = '<p><img src="'. $builder->inline() . '"/><p><input type="text" name="captcha_code" required /></p>';

            $sessionManager = SessionManager::getManager();
            $session = $sessionManager->getUserSession();
            $session->setSessionVar('captcha_code',  $builder->getPhrase());
        }
        $templateMgr->assign(array(
            'captchaHtml' => $captchaHtml,
            'captchaEnabled' => true,
        ));
        return $templateMgr->display($this->plugin->getTemplateResource('subscribe.tpl'));
    }

    public function register($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $newEmail = $request->getUserVar('email');
        $confirmEmail = $request->getUserVar('email_confirm');
        $csrfToken = $request->getUserVar('csrfToken');
        $recaptcha = $request->getUserVar('g-recaptcha-response');
        $locale = $request->getUserVar('locale');

        $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
        $message = [];
        $messageString = '';
        $status = 'success';

        if (!$csrfToken) {
            $templateMgr->assign([
                'errorMsg' => 'plugins.generic.easySubscribe.page.register.error',
                'backLink' => $request->url(null, null, 'index'),
                'backLinkLabel' => 'plugins.generic.easySubscribe.page.subscribe.title',
            ]);
            $templateMgr->addJavaScript('subscribe', 'js/subscribe.js');

            return $templateMgr->display('frontend/pages/error.tpl');
        }

        if ($this->reCaptchaEnabled) {
            $secret = Config::getVar('captcha', 'recaptcha_private_key');
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $recaptcha);
            $responseData = json_decode($verifyResponse);
            $publicKey = Config::getVar('captcha', 'recaptcha_public_key');
            $captchaHtml = '<div class="g-recaptcha" data-sitekey="' . $publicKey . '"></div>';

            if (!$responseData->success) {
                $status = 'error';
                $templateMgr->addJavaScript('recaptcha', 'https://www.recaptcha.net/recaptcha/api.js?hl=' . substr(AppLocale::getLocale(), 0, 2));
                $message[] = __('plugins.generic.easySubscribe.form.captcha');
            }
        } elseif ($this->captchaType === 'securimage') {
                $image = new Securimage();
                $captchaHtml = Securimage::getCaptchaHtml();
    
                if ($image->check($_POST['captcha_code']) !== true) {
                    $status = 'error';
                    $message[] = __('plugins.generic.easySubscribe.form.captcha');
                }
            } elseif ($this->captchaType === 'gregwar') {
                $builder = new CaptchaBuilder;
                $builder->build($width = 200, $height = 60, $font = null);
                $captchaHtml = '<p><img src="'. $builder->inline() . '"/></p><p><input type="text" name="captcha_code" required /></p>';
    
                $sessionManager = SessionManager::getManager();
                $session = $sessionManager->getUserSession();
                $code = $session->getSessionVar('captcha_code');

                if (strtolower($_POST['captcha_code']) !== strtolower($code)) {
                    $status = 'error';
                    $message[] = __('plugins.generic.easySubscribe.form.captcha');
                    $session->setSessionVar('captcha_code',  $builder->getPhrase());
                }
            }

        $templateMgr->assign([
            'captchaHtml' => $captchaHtml,
            'captchaEnabled' => true
        ]);



        if ($newEmail !== $confirmEmail) {
            $status = 'error';
            $message[] = __('plugins.generic.easySubscribe.page.register.confirm.error');
        }

        if (!!$easyEmailDao->getByEmail($this->contextId, $newEmail) || !$newEmail) {
            $status = 'error';
            $message[] = $newEmail ? __('plugins.generic.easySubscribe.form.error.exists') : __('plugins.generic.easySubscribe.form.error.empty');
        }

        if ($status === 'success') {
            $easyEmail = $easyEmailDao->newDataObject();
            $easyEmail->setContextId((int) $this->contextId);
            $easyEmail->setEmail((string) $newEmail, null);
            $easyEmail->setLocale((string) $locale ? $locale : AppLocale::getLocale());
            $easyEmail->setActive((int) 0);
            $easyEmailDao->insertObject($easyEmail);
            $message[] = __('plugins.generic.easySubscribe.form.success');
            $this->subscribeNotification($easyEmail);
        }

        foreach ($message as $value) {
            $messageString .=  "<p>$value</p>";
        }

        $templateMgr->assign([
            'status' => $status,
            'message' => $messageString,
            'email' => $newEmail
        ]);

        return $templateMgr->display($this->plugin->getTemplateResource('subscribe.tpl'));
    }


    public function activate($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $email = $request->getUserVar('email');
        $id = $request->getUserVar('id');

        $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
        $message = '';

        $easyEmail = $easyEmailDao->getById($this->contextId, $id);

        if ($easyEmail && $easyEmail->getEmail() === $email) {
            $easyEmailDao->activate($this->contextId, $easyEmail);

            $message = __('plugins.generic.easySubscribe.activate.success');

            $templateMgr->assign([
                'status' => 'success',
                'message' => $message,
            ]);
        } else {
            $message = __('plugins.generic.easySubscribe.activate.error');
            $templateMgr->assign([
                'status' => 'error',
                'message' => $message,
            ]);
        }
        return $templateMgr->display($this->plugin->getTemplateResource('activate.tpl'));
    }

    public function unsubscribe($args, $request)
    {
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
        } else {
            $message = __('plugins.generic.easySubscribe.unsubscribe.error');
            $templateMgr->assign([
                'status' => 'error',
                'message' => $message,
            ]);
        }
        return $templateMgr->display($this->plugin->getTemplateResource('unsubscribe.tpl'));
    }

    protected function subscribeNotification($email)
    {
        import('lib.pkp.classes.mail.Mail');
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $locale = $email->getLocale();
        $siteName = $context->getName($locale);
        $basePath = $request->getBaseUrl();

        $activateUrl = $basePath . '/' . $context->getPath() . '/easysubscribe/activate'  . '?email=' . $email->getEmail() . '&id=' . $email->getId();

        $body = "";
        $body .= "<p>";
        $body .= $this->plugin->easyTranslate('plugins.generic.easySubscribe.letter.register.text', $locale);
        $body .= " <a href=\"$basePath\">$siteName</a></p>";

        $body .= "<p>";
        $body .= $this->plugin->easyTranslate("plugins.generic.easySubscribe.letter.activate.text", $locale);
        $body .= " <a href=\"$activateUrl\">";
        $body .= $this->plugin->easyTranslate('plugins.generic.easySubscribe.letter.activate.title', $locale);
        $body .= "</a></p>";

        $this->plugin->sendToSubscriber($body, $email, $request);
    }



    //! Функционал для тестирования. Нужно перенести в админ панель
    public function list($args, $request)
    {
        if (Validation::isLoggedIn()) {
            $templateMgr = TemplateManager::getManager($request);
            $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
            $emailsList = $easyEmailDao->getByContextId($this->contextId)->toArray();

            $templateMgr->assign([
                'emailsList' => $emailsList,
            ]);


            return $templateMgr->display($this->plugin->getTemplateResource('list.tpl'));
        }
    }
}
