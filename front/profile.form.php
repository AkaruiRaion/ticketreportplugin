<?php
/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * front/profile.form.php
 *
 * Назначение файла:
 * Обработчик формы вкладки "Отчёты по заявкам" на странице профиля
 * (inc/profile.class.php). Позволяет администратору (право profile:UPDATE)
 * назначить/забрать право доступа plugin_ticketreport у выбранного
 * профиля пользователей.
 * -------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('profile', UPDATE);

//Session::checkCSRF($_POST);

if (isset($_POST['update']) && isset($_POST['id'])) {
   $profiles_id = (int) $_POST['id'];
   $right       = (int) ($_POST['right'] ?? 0);

   ProfileRight::updateProfileRights($profiles_id, [
      'plugin_ticketreport' => $right,
   ]);

   Session::addMessageAfterRedirect(__('Права успешно обновлены', 'ticketreport'));
}

Html::back();
