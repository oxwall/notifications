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
 * Data Access Object for `notifications_rule` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.notifications.bol
 * @since 1.0
 */
class NOTIFICATIONS_BOL_SendQueueDao extends OW_BaseDao
{
    /**sendQueueDao
     * Singleton instance.
     *
     * @var NOTIFICATIONS_BOL_SendQueueDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return NOTIFICATIONS_BOL_SendQueueDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'NOTIFICATIONS_BOL_SendQueue';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'notifications_send_queue';
    }

    public function fillData( $period, $defaultSchedules )
    {
        $usersDao = BOL_UserDao::getInstance();
        $scheduleDao = NOTIFICATIONS_BOL_ScheduleDao::getInstance();

        $query = "REPLACE INTO " . $this->getTableName() . " (`userId`, `timeStamp`) SELECT DISTINCT u.id, UNIX_TIMESTAMP() FROM " . $usersDao->getTableName() . " u
                    LEFT JOIN " . $scheduleDao->getTableName() . " s ON u.id = s.userId
                    WHERE (IF( s.schedule IS NULL, :ds, s.schedule )=:as  AND u.activityStamp < :activityStamp ) OR IF( s.schedule IS NULL, :ds, s.schedule )=:is ORDER BY u.activityStamp DESC";

        return $this->dbo->query($query, array(
            'activityStamp' => time() - $period,
            'ds' => $defaultSchedules,
            'is' => NOTIFICATIONS_BOL_Service::SCHEDULE_IMMEDIATELY,
            'as' => NOTIFICATIONS_BOL_Service::SCHEDULE_AUTO
        ));
    }

    public function findList( $count )
    {
        $example = new OW_Example();
        $example->setLimitClause(0, $count);
        $example->setOrder('timeStamp DESC');

        return $this->findListByExample($example);
    }
}