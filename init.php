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
$plugin = OW::getPluginManager()->getPlugin('notifications');

OW::getRouter()->addRoute(new OW_Route('notifications-settings', 'email-notifications', 'NOTIFICATIONS_CTRL_Notifications', 'settings'));
OW::getRouter()->addRoute(new OW_Route('notifications-unsubscribe', 'email-notifications/unsubscribe/:code/:action', 'NOTIFICATIONS_CTRL_Notifications', 'unsubscribe'));

NOTIFICATIONS_CLASS_ConsoleBridge::getInstance()->init();
NOTIFICATIONS_CLASS_EmailBridge::getInstance()->init();

function notifications_preference_menu_item( BASE_CLASS_EventCollector $event )
{
    $router = OW_Router::getInstance();
    $language = OW::getLanguage();

    $menuItems = array();

    $menuItem = new BASE_MenuItem();

    $menuItem->setKey('email_notifications');
    $menuItem->setLabel($language->text( 'notifications', 'dashboard_menu_item'));
    $menuItem->setIconClass('ow_ic_mail');
    $menuItem->setUrl($router->urlForRoute('notifications-settings'));
    $menuItem->setOrder(3);

    $event->add($menuItem);
}

OW::getEventManager()->bind('base.preference_menu_items', 'notifications_preference_menu_item');

    
function notifications_add_console_item( BASE_CLASS_EventCollector $event )
{
    $event->add(array('label' => OW::getLanguage()->text('notifications', 'console_menu_label'), 'url' => OW_Router::getInstance()->urlForRoute('notifications-settings')));
}

OW::getEventManager()->bind('base.add_main_console_item', 'notifications_add_console_item');