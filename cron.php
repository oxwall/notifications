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
 * Notifications Cron
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.notifications
 * @since 1.0
 */
class NOTIFICATIONS_Cron extends OW_Cron
{
    /**
     *
     * @var NOTIFICATIONS_BOL_Service
     */
    private $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = NOTIFICATIONS_BOL_Service::getInstance();

        $this->addJob('expireUnsubscribe', 60 * 60);
        $this->addJob('deleteExpired', 60 * 60);

        $this->addJob('fillSendQueue', 10);
    }

    /**
     *  Return run interval in minutes
     *
     * @return int
     */
    public function getRunInterval()
    {
        return 1;
    }

    public function expireUnsubscribe()
    {
        $this->service->deleteExpiredUnsubscribeCodeList();
    }

    public function deleteExpired()
    {
        $this->service->deleteExpiredNotification();
    }


    public function fillSendQueue()
    {
        if ( $this->service->getSendQueueLength() == 0 )
        {
            $this->service->fillSendQueue(24 * 3600);
        }
    }

    public function run()
    {
        $users = $this->service->findUserIdListForSend(100);

        if ( empty($users) )
        {
            return;
        }

        $listEvent = new BASE_CLASS_EventCollector('notifications.send_list', array(
            'userIdList' => $users
        ));

        OW::getEventManager()->trigger($listEvent);

        $notifications = array();
        foreach ( $listEvent->getData() as $notification )
        {
            $itemEvent = new OW_Event('notifications.on_item_send', $notification, $notification['data']);
            OW::getEventManager()->trigger($itemEvent);

            $notification['data'] = $itemEvent->getData();

            $notifications[$notification['userId']][] = $notification;
        }

        foreach ( $notifications as $userId => $notificationList )
        {
            $this->service->sendPermittedNotifications($userId, $notificationList);
        }
    }
}