<?php

class NOTIFICATIONS_CMP_ConsoleItem extends BASE_CMP_ConsoleDropdownList
{
    public function __construct()
    {
        $label = OW::getLanguage()->text('notifications', 'console_item_label');

        parent::__construct( $label, NOTIFICATIONS_CLASS_ConsoleBridge::CONSOLE_ITEM_KEY );

        $this->addClass('ow_notification_list');
    }

    public function initJs()
    {
        parent::initJs();

        $staticUrl = OW::getPluginManager()->getPlugin('notifications')->getStaticUrl();
        OW::getDocument()->addScript($staticUrl . 'notifications.js');

        $js = UTIL_JsGenerator::newInstance();
        $js->addScript('OW.Notification = new OW_Notification({$key});', array(
            'key' => $this->getKey()
        ));

        OW::getDocument()->addOnloadScript($js);
    }
}