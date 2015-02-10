<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Notifications
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.notifications.controllers
 * @since 1.0
 */
class NOTIFICATIONS_CTRL_Notifications extends OW_ActionController
{
    /**
     *
     * @var NOTIFICATIONS_BOL_Service
     */
    private $service;
    private $userId;

    public function __construct()
    {
        parent::__construct();

        $this->service = NOTIFICATIONS_BOL_Service::getInstance();
        $this->userId = OW::getUser()->getId();
    }

    public function settings()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $contentMenu = new BASE_CMP_PreferenceContentMenu();
        $contentMenu->getElement('email_notifications')->setActive(true);
        $this->addComponent('contentMenu', $contentMenu);

        OW::getDocument()->setHeading(OW::getLanguage()->text('notifications', 'setup_page_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_mail');
        OW::getDocument()->setTitle(OW::getLanguage()->text('notifications', 'setup_page_title'));

        $actions = $this->service->collectActionList();
        $settings = $this->service->findRuleList($this->userId);

        $form = new NOTIFICATIONS_SettingForm();
        $this->addForm($form);

        $processActions = array();

        foreach ( $actions as $action )
        {
            $field = new CheckboxField($action['action']);
            $field->setValue(!empty($action['selected']));

            if ( isset($settings[$action['action']]) )
            {
                $field->setValue((bool) $settings[$action['action']]->checked);
            }

            $form->addElement($field);

            $processActions[] = $action['action'];
        }

        if ( OW::getRequest()->isPost() )
        {
            $result = $form->process($_POST, $processActions, $settings);
            if ( $result )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('notifications', 'settings_changed'));
            }
            else
            {
                OW::getFeedback()->warning(OW::getLanguage()->text('notifications', 'settings_not_changed'));
            }

            $this->redirect();
        }

        $tplActions = array();

        foreach ( $actions as $action )
        {
            if ( empty($tplActions[$action['section']]) )
            {
                $tplActions[$action['section']] = array(
                    'label' => $action['sectionLabel'],
                    'icon' => empty($action['sectionIcon']) ? '' : $action['sectionIcon'],
                    'actions' => array()
                );
            }

            $tplActions[$action['section']]['actions'][$action['action']] = $action;
        }



        $this->assign('actions', $tplActions);
    }

    public function unsubscribe( $params )
    {
        if ( isset($_GET['confirm-result']) && $_GET['confirm-result'] === "0" )
        {
            throw new RedirectException(OW_URL_HOME);
        }
        
        $code = $params['code'];
        $userId = $this->service->findUserIdByUnsubscribeCode($code);
        $lang = OW::getLanguage();

        if ( empty($userId) )
        {
            throw new RedirectAlertPageException($lang->text('notifications', 'unsubscribe_code_expired'));
        }

        if ( empty($_GET['confirm-result']) )
        {
            throw new RedirectConfirmPageException($lang->text('notifications', 'unsubscribe_confirm_msg'));
        }
        
        $activeActions = $this->service->collectActionList();
        $rules = $this->service->findRuleList($userId);

        $action = $params['action'] == 'all' ? null : $params['action'];

        foreach ( $activeActions as $actionInfo )
        {
            if ( $params['action'] != 'all' && $actionInfo['action'] != $params['action'] )
            {
                continue;
            }

            if ( empty($rules[$actionInfo['action']]) )
            {
                $rule = new NOTIFICATIONS_BOL_Rule();
                $rule->action = $actionInfo['action'];
                $rule->userId = $userId;
            }
            else
            {
                $rule = $rules[$actionInfo['action']];
            }

            $rule->checked = false;

            $this->service->saveRule($rule);
        }

        throw new RedirectAlertPageException($lang->text('notifications', 'unsubscribe_completed'));
    }

    public function test()
    {

        /* OW::getConfig()->addConfig('notifications', 'schedule_dhour', '00', 'Schedule hour');
          OW::getConfig()->addConfig('notifications', 'schedule_wday', '1', 'Schedule week day'); */

        require_once dirname(dirname(__FILE__)) . DS . 'cron.php';

        $cron = new NOTIFICATIONS_Cron();
        //$cron->run();
        $cron->deleteExpired();
        exit;
    }

    public function apiUnsubscribe( $params )
    {
        if ( empty($params['emails']) || !is_array($params['emails']) )
        {
            throw new InvalidArgumentException('Invalid email list');
        }

        foreach ( $params['emails'] as $email )
        {
            $user = BOL_UserService::getInstance()->findByEmail($email);

            if ( $user === null )
            {
                throw new LogicException('User with email ' . $email . ' not found');
            }

            $userId = $user->getId();

            $activeActions = $this->service->collectActionList();
            $rules = $this->service->findRuleList($userId);

            $action = empty($params['action']) ? null : $params['action'];

            foreach ( $activeActions as $actionInfo )
            {
                if ( $action !== null && $actionInfo['action'] != $action )
                {
                    continue;
                }

                if ( empty($rules[$actionInfo['action']]) )
                {
                    $rule = new NOTIFICATIONS_BOL_Rule();
                    $rule->action = $actionInfo['action'];
                    $rule->userId = $userId;
                }
                else
                {
                    $rule = $rules[$actionInfo['action']];
                }

                $rule->checked = false;

                $this->service->saveRule($rule);
            }
        }
    }
}

class NOTIFICATIONS_SettingForm extends Form
{

    public function __construct()
    {
        parent::__construct('notificationSettingForm');

        $language = OW::getLanguage();

        $field = new RadioField('schedule');

        $field->addOption(NOTIFICATIONS_BOL_Service::SCHEDULE_IMMEDIATELY, $language->text('notifications', 'schedule_immediately'));
        $field->addOption(NOTIFICATIONS_BOL_Service::SCHEDULE_AUTO, $language->text('notifications', 'schedule_automatic'));
        $field->addOption(NOTIFICATIONS_BOL_Service::SCHEDULE_NEVER, $language->text('notifications', 'schedule_never'));

        $schedule = NOTIFICATIONS_BOL_Service::getInstance()->getSchedule(OW::getUser()->getId());
        $field->setValue($schedule);
        $this->addElement($field);

        $btn = new Submit('save');
        $btn->setValue($language->text('notifications', 'save_setting_btn_label'));

        $this->addElement($btn);
    }

    public function process( $data, $actions, $dtoList )
    {
        $userId = OW::getUser()->getId();
        $result = 0;
        $service = NOTIFICATIONS_BOL_Service::getInstance();

        if ( !empty($data['schedule']) )
        {
            $result += (int) $service->setSchedule($userId, $data['schedule']);

            unset($data['schedule']);
        }

        foreach ( $actions as $action )
        {
            /* @var $dto NOTIFICATIONS_BOL_Rule */
            if ( empty($dtoList[$action]) )
            {
                $dto = new NOTIFICATIONS_BOL_Rule();
                $dto->userId = $userId;
                $dto->action = $action;
            }
            else
            {
                $dto = $dtoList[$action];
            }

            $checked = (int) !empty($data[$action]);

            if ( !empty($dto->id) && $dto->checked == $checked )
            {
                continue;
            }

            $dto->checked = $checked;
            $result++;

            $service->saveRule($dto);
        }

        return $result;
    }
}

