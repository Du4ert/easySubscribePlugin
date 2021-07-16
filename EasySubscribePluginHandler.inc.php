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
        $csrfToken = $request->getUserVar('csrfToken');
        
        $easyEmailDao = DAORegistry::getDAO('EasyEmailDAO');
        $message = '';

        if (!$csrfToken) {
            $templateMgr->assign([
                'errorMsg' => 'plugins.generic.easySubscribe.page.register.error',
                'backLink' => $request->url(null, null, 'index'),
                'backLinkLabel' => 'plugins.generic.easySubscribe.page.subscribe.title',
            ]);

            return $templateMgr->display('frontend/pages/error.tpl');
        }
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