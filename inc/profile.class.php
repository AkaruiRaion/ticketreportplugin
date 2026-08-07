<?php
/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * inc/profile.class.php
 *
 * Назначение файла:
 * Добавляет на страницу "Администрирование > Профили" отдельную вкладку
 * "Отчёты по заявкам", позволяющую администратору выдать/забрать право
 * доступа к разделу отчётов у любого профиля (не только у Super-Admin,
 * которому право выдаётся автоматически при установке плагина).
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginTicketreportProfile extends Profile {

   static function getTypeName($nb = 0) {
      return __('Отчёты по заявкам', 'ticketreport');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Profile' && isset($item->fields['id']) && $item->fields['id']) {
         return self::createTabEntry(self::getTypeName());
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         self::showForProfile((int) $item->getField('id'));
      }
      return true;
   }

   /**
    * Отображает форму выдачи права доступа для конкретного профиля.
    * Само сохранение обрабатывается в front/profile.form.php.
    */
   static function showForProfile(int $profiles_id) {
      global $DB;

      $current = 0;
      $iterator = $DB->request([
         'SELECT' => 'rights',
         'FROM'   => 'glpi_profilerights',
         'WHERE'  => [
            'profiles_id' => $profiles_id,
            'name'        => 'plugin_ticketreport',
         ],
      ]);
      foreach ($iterator as $row) {
         $current = (int) $row['rights'];
      }

      echo "<div class='firstbloc'>";
      echo "<form method='post' action='/plugins/ticketreport/front/profile.form.php'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . self::getTypeName() . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Доступ к разделу «Отчёты»', 'ticketreport') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('right', [
         0    => __('Нет доступа'),
         READ => __('Разрешено (чтение)'),
      ], ['value' => $current]);
      echo "</td></tr>";
      echo "<tr><td colspan='2' class='center'>";
      echo Html::hidden('id', ['value' => $profiles_id]);
      echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
      echo "<input type='submit' name='update' class='submit' value=\"" . __s('Сохранить') . "\">";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }
}
