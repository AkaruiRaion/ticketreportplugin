<?php

/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * hook.php
 *
 * Назначение файла:
 * Функции установки/удаления плагина. Плагин не создаёт собственных таблиц
 * в БД (данные берутся из стандартных таблиц GLPI), поэтому install/
 * uninstall отвечают только за регистрацию отдельного права доступа
 * "plugin_ticketreport" и за автоматическую выдачу этого права профилю
 * Super-Admin, чтобы администратор сразу получил доступ к разделу.
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Установка плагина.
 */
function plugin_ticketreport_install()
{

   // Регистрируем новое право доступа плагина.
   // По умолчанию право не выдано ни одному профилю (значение 0).
   ProfileRight::addProfileRights([
      'plugin_ticketreport'
   ]);

   // Автоматически выдаём право READ профилю "Super-Admin",
   // чтобы администратор сразу видел пункт меню "Отчёты".
   $profile = new Profile();
   $superadmins = $profile->find(['name' => 'Super-Admin']);

   foreach ($superadmins as $data) {
      ProfileRight::updateProfileRights((int) $data['id'], [
         'plugin_ticketreport' => READ,
      ]);
   }

   return true;
}

/**
 * Удаление плагина.
 */
function plugin_ticketreport_uninstall()
{

   // Удаляем зарегистрированное право из всех профилей
   ProfileRight::deleteProfileRights([
      'plugin_ticketreport'
   ]);

   return true;
}
