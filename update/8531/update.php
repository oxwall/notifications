<?php

try
{
    Updater::getLanguageService()->deleteLangKey('notifications', 'setup_page_title');
    Updater::getLanguageService()->deleteLangKey('notifications', 'setup_page_heading');
    Updater::getLanguageService()->deleteLangKey('notifications', 'dashboard_menu_item');
}
catch ( Exception $e )
{
    $logger->addEntry(json_encode($e));
}

$pluginDir = dirname(dirname(dirname(__FILE__))) . DS;
Updater::getLanguageService()->importPrefixFromZip($pluginDir . 'langs.zip', 'notifications');