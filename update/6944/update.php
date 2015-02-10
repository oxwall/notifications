<?php

$pluginDir = dirname(dirname(dirname(__FILE__))) . DS;
Updater::getLanguageService()->importPrefixFromZip($pluginDir . 'langs.zip', 'notifications');