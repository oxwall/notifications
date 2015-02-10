<?php

$queryList = array();
$queryList[] = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "notifications_send_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

$queryList[] = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "notifications_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `schedule` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

foreach ( $queryList as $query )
{
    try
    {
        Updater::getDbo()->query($query);
    }
    catch ( Exception $e )
    {}
}

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'notifications');