<?php

$queryList = array();
$queryList[] = "ALTER TABLE  `" . OW_DB_PREFIX . "notifications_notification` CHANGE  `entityId`  `entityId` VARCHAR( 64 ) NOT NULL";

foreach ( $queryList as $query )
{
    try
    {
        Updater::getDbo()->query($query);
    }
    catch ( Exception $e )
    {}
}