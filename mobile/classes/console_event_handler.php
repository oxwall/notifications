<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.notifications.mobile.classes
 * @since 1.6.0
 */
class NOTIFICATIONS_MCLASS_ConsoleEventHandler
{
    /**
     * Class instance
     *
     * @var NOTIFICATIONS_MCLASS_ConsoleEventHandler
     */
    private static $classInstance;

    const CONSOLE_PAGE_KEY = 'notifications';
    const CONSOLE_SECTION_KEY = 'notifications';

    /**
     * Returns class instance
     *
     * @return NOTIFICATIONS_MCLASS_ConsoleEventHandler
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function collectSections( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        if ( $params['page'] == self::CONSOLE_PAGE_KEY )
        {
            $event->add(array(
                'key' => self::CONSOLE_SECTION_KEY,
                'component' => new NOTIFICATIONS_MCMP_ConsoleSection(),
                'order' => 3
            ));
        }
    }

    public function countNewItems( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        if ( $params['page'] == self::CONSOLE_PAGE_KEY )
        {
            $service = NOTIFICATIONS_BOL_Service::getInstance();
            $event->add(
                array(self::CONSOLE_SECTION_KEY => $service->findNotificationCount(OW::getUser()->getId(), false))
            );
        }
    }

    public function getNewItems( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        if ( $params['page'] == self::CONSOLE_PAGE_KEY )
        {
            $event->add(
                array(self::CONSOLE_SECTION_KEY => new NOTIFICATIONS_MCMP_ConsoleNewItems($params['timestamp']))
            );
        }
    }

    public function init()
    {
        $em = OW::getEventManager();
        $em->bind(
            MBOL_ConsoleService::EVENT_COLLECT_CONSOLE_PAGE_SECTIONS,
            array($this, 'collectSections')
        );

        $em->bind(
            MBOL_ConsoleService::EVENT_COUNT_CONSOLE_PAGE_NEW_ITEMS,
            array($this, 'countNewItems')
        );

        $em->bind(
            MBOL_ConsoleService::EVENT_COLLECT_CONSOLE_PAGE_NEW_ITEMS,
            array($this, 'getNewItems')
        );
    }
}