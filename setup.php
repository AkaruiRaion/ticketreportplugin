<?php
/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * setup.php
 *
 * Назначение файла:
 * Обязательная точка входа плагина GLPI. Здесь GLPI получает метаданные
 * плагина (версия, требования к окружению) и здесь регистрируются все
 * хуки: пункт меню, поддержка CSRF, вкладка управления правами на
 * странице профилей. Файл НЕ содержит бизнес-логики и SQL-запросов.
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

define('PLUGIN_TICKETREPORT_VERSION', '1.0.0');

// Диапазон совместимых версий GLPI
define('PLUGIN_TICKETREPORT_MIN_GLPI', '9.4');
define('PLUGIN_TICKETREPORT_MAX_GLPI', '11.0');

/**
 * Инициализация плагина.
 * Вызывается GLPI на каждой странице, если плагин установлен и активирован.
 */
function plugin_init_ticketreport() {
   global $PLUGIN_HOOKS;

   // Обязательно для плагинов, использующих HTML-формы с CSRF-токеном
   $PLUGIN_HOOKS['csrf_compliant']['ticketreport'] = true;

   // Регистрируем класс, добавляющий вкладку "Отчёты по заявкам"
   // на странице Администрирование > Профили, чтобы администратор
   // мог выдавать право доступа к отчётам другим профилям.
   Plugin::registerClass('PluginTicketreportProfile', [
      'addtabon' => ['Profile'],
   ]);

   // Пункт меню "Отчёты" появляется только у пользователей,
   // имеющих право plugin_ticketreport (READ).
   if (Session::haveRight('plugin_ticketreport', READ)) {
      $PLUGIN_HOOKS['menu_toadd']['ticketreport'] = ['plugins' => 'PluginTicketreportMenu'];
   }
}

/**
 * Метаданные плагина (обязательная функция для GLPI 9.4).
 */
function plugin_version_ticketreport() {
   return [
      'name'         => 'Отчёты по закрытым заявкам (TicketReport)',
      'version'      => PLUGIN_TICKETREPORT_VERSION,
      'author'       => 'AkaruiRaion',
      'license'      => 'GPLv2+',
      'homepage'     => '',
      'requirements' => [
         'glpi' => [
            'min' => PLUGIN_TICKETREPORT_MIN_GLPI,
            'max' => PLUGIN_TICKETREPORT_MAX_GLPI,
         ],
         'php' => [
            'min' => '7.2',
         ],
      ],
   ];
}

/**
 * Проверка окружения перед установкой плагина.
 * Проверяет версию PHP и наличие библиотеки PhpSpreadsheet (vendor/).
 */
function plugin_ticketreport_check_prerequisites() {
   if (version_compare(PHP_VERSION, '7.2.0', '<')) {
      echo 'Этому плагину требуется PHP версии 7.2 или выше.';
      return false;
   }

   if (!is_readable(__DIR__ . '/vendor/autoload.php')) {
      echo 'Не найден файл vendor/autoload.php. Выполните команду '
         . '"composer install" в директории плагина (требуется библиотека '
         . 'phpoffice/phpspreadsheet) перед установкой плагина.';
      return false;
   }

   return true;
}

/**
 * Проверка конфигурации плагина, отображается на странице списка плагинов.
 */
function plugin_ticketreport_check_config($verbose = false) {
   return true;
}
