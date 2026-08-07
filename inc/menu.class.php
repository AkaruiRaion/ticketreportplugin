<?php
/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * inc/menu.class.php
 *
 * Назначение файла:
 * Описывает пункт меню "Отчёты", который GLPI добавляет в общее меню
 * (раздел "Плагины") после установки и активации плагина. Класс не
 * содержит ни SQL, ни логики формирования отчёта — только конфигурацию
 * пункта меню, как того требует архитектура плагинов GLPI.
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginTicketreportMenu extends CommonGLPI {

   static function getMenuName() {
      return __('Отчёты заявок', 'ticketreport');
   }

   static function getMenuContent() {
      $menu = [
         'title' => self::getMenuName(),
         'page'  => '/plugins/ticketreport/front/report.php',
         'icon'  => 'fas fa-file-excel',
      ];

      return $menu;
   }
}
