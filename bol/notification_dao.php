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
 * Data Access Object for `notifications_notification` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package notifications.bol
 * @since 1.0
 */
class NOTIFICATIONS_BOL_NotificationDao extends OW_BaseDao
{

    const VIEWED_NOTIFICATIONS_COUNT = 50;

    /**
     * Constructor.
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Singleton instance.
     *
     * @var NOTIFICATIONS_BOL_NotificationDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return NOTIFICATIONS_BOL_NotificationDao
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
        return 'NOTIFICATIONS_BOL_Notification';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'notifications_notification';
    }

    public function findNotificationList( $userId, $beforeStamp, $ignoreIds, $count )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);
        $example->andFieldLessOrEqual('timeStamp', $beforeStamp);

        if ( !empty($ignoreIds) )
        {
            $example->andFieldNotInArray('id', $ignoreIds);
        }

        $example->setLimitClause(0, $count);
        $example->setOrder('viewed, timeStamp DESC');

        return $this->findListByExample($example);
    }

    public function findNewNotificationList( $userId, $afterStamp = null )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('viewed', false);
        // TODO: uncomment
        if ( $afterStamp )
        {
            $example->andFieldGreaterThan('timeStamp', $afterStamp);
        }
        $example->setOrder('timeStamp DESC');

        return $this->findListByExample($example);
    }

    public function findNotificationListForSend( $userIdList )
    {
        if ( empty($userIdList) )
        {
            return array();
        }

        $example = new OW_Example();

        $example->andFieldInArray('userId', $userIdList);
        $example->andFieldEqual('viewed', 0);
        $example->andFieldEqual('sent', 0);

        return $this->findListByExample($example);
    }

    public function findNotificationCount( $userId, $viewed = null, $exclude = null )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);

        if ( $viewed !== null )
        {
            $example->andFieldEqual('viewed', (int) (bool) $viewed);
        }

        if ( $exclude )
        {
            $example->andFieldNotInArray('id', $exclude);
        }

        return $this->countByExample($example);
    }

    public function findNotification( $entityType, $entityId, $userId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('entityType', $entityType);
        $example->andFieldEqual('entityId', $entityId);

        return $this->findObjectByExample($example);
    }

    public function markViewedByIds( array $ids, $viewed = true )
    {
        if ( empty($ids) )
        {
            return;
        }

        $in = implode(',', $ids);

        $query = "UPDATE " . $this->getTableName() . " SET `viewed`=:viewed WHERE id IN ( " . $in . " )";

        $this->dbo->query($query, array(
            'viewed' => $viewed ? 1 : 0
        ));
    }

    public function markViewedByUserId( $userId, $viewed = true )
    {
        if ( !$userId )
        {
            return;
        }

        $query = "UPDATE " . $this->getTableName() . " SET `viewed` = :viewed WHERE userId = :userId";

        $this->dbo->query($query, array('viewed' => $viewed ? 1 : 0, 'userId' => $userId));
    }

    public function markSentByIds( array $ids, $sent = true )
    {
        if ( empty($ids) )
        {
            return;
        }

        $in = implode(',', $ids);

        $query = "UPDATE " . $this->getTableName() . " SET `sent`=:sent WHERE id IN ( " . $in . " )";

        $this->dbo->query($query, array(
            'sent' => $sent ? 1 : 0
        ));
    }

    public function saveNotification( NOTIFICATIONS_BOL_Notification $notification )
    {
        if ( empty($notification->id) )
        {
            $dto = $this->findNotification($notification->entityType, $notification->entityId, $notification->userId);

            if ( $dto != null )
            {
                $notification->id = $dto->id;
            }
        }

        $this->save($notification);
    }

    public function deleteExpired()
    {
        $query = "SELECT userId FROM " . $this->getTableName() . " WHERE  viewed=1 GROUP BY userId HAVING count(id) > :ql LIMIT :limit";
        $userIdList = $this->dbo->queryForColumnList($query, array(
            'ql' => self::VIEWED_NOTIFICATIONS_COUNT,
            'limit' => 1000
        ));

        $expiredItemsIdList = array();

        foreach ( $userIdList as $userId )
        {
            $expiredItemsIdListByUser = $this->findExpiredNotificationsIdListByUserId($userId);
            $expiredItemsIdList = array_merge($expiredItemsIdList, $expiredItemsIdListByUser);

            if ( count($expiredItemsIdList) > 2000 )
            {
                $this->deleteByIdList($expiredItemsIdList);
                $expiredItemsIdList = array();
            }
        }

        if ( !empty($expiredItemsIdList) )
        {
            $this->deleteByIdList($expiredItemsIdList);
        }
    }

    public function findExpiredNotificationsIdListByUserId( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('viewed', 1);
        $example->setOrder('timeStamp DESC');
        $example->setLimitClause(self::VIEWED_NOTIFICATIONS_COUNT, 1000);

        return $this->findIdListByExample($example);
    }

    public function deleteNotification( $entityType, $entityId, $userId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('entityType', $entityType);
        $example->andFieldEqual('entityId', $entityId);

        $this->deleteByExample($example);
    }

    public function deleteNotificationByEntity( $entityType, $entityId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('entityType', $entityType);
        $example->andFieldEqual('entityId', $entityId);

        $this->deleteByExample($example);
    }

    public function deleteNotificationByPluginKey( $pluginKey )
    {
        $example = new OW_Example();

        $example->andFieldEqual('pluginKey', $pluginKey);

        $this->deleteByExample($example);
    }

    public function setNotificationStatusByPluginKey( $pluginKey, $status )
    {
        $query = "UPDATE " . $this->getTableName() . " SET `active`=:s WHERE pluginKey=:pk";

        $this->dbo->query($query, array(
            's' => (int) $status,
            'pk' => $pluginKey
        ));
    }

}