<?php

$queryList = array();
$queryList[] = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "notifications_notification` (
  `id` int(11) NOT NULL auto_increment,
  `entityType` varchar(255) NOT NULL,
  `entityId` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `userId` int(11) NOT NULL,
  `pluginKey` varchar(255) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  `viewed` int(11) NOT NULL default '0',
  `sent` tinyint(4) NOT NULL default '0',
  `active` tinyint(4) NOT NULL default '1',
  `data` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `entityType` (`entityType`,`entityId`,`userId`),
  KEY `timeStamp` (`timeStamp`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$queryList[] = "DROP TABLE `" . OW_DB_PREFIX . "notifications_cron_job`, `" . OW_DB_PREFIX . "notifications_queue`, `" . OW_DB_PREFIX . "notifications_schedule`;";

foreach ( $queryList as $query )
{
    try
    {
        Updater::getDbo()->query($query);
    }
    catch ( Exception $e )
    {}
}

Updater::getConfigService()->deleteConfig('notifications', 'schedule_dhour');
Updater::getConfigService()->deleteConfig('notifications', 'schedule_wday');

//Remove setting route
$plugin = BOL_PluginService::getInstance()->findPluginByKey('notifications');

if ( $plugin !== null )
{
    $plugin->setAdminSettingsRoute(null);
    BOL_PluginService::getInstance()->savePlugin($plugin);
}

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'notifications');