<?php

/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * front/report.export.php
 *
 * Назначение файла:
 * Контроллер обработки отправленной формы. Проверяет права,
 * читает параметры формы, и только КООРДИНИРУЕТ вызовы двух сервисов:
 *   1. PluginTicketreportReport   — получение данных (весь SQL там)
 *   2. PluginTicketreportExcel    — построение и отдача XLSX-файла
 * Сам файл не содержит ни одного SQL-запроса и не работает с PhpSpreadsheet напрямую.
 * -------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('plugin_ticketreport', READ);

if (!isset($_POST['generate'])) {
   Html::back();
   exit;
}

$users_id = (int) ($_POST['users_id'] ?? 0);
$month    = (int) ($_POST['month'] ?? 0);
$year     = (int) ($_POST['year'] ?? 0);
$groups_id = (int) ($_POST['groups_id'] ?? 0);
$status = (int) ($_POST['status'] ?? 0);

$allowed_group_ids = [6, 7, 8];
$currentMonth = (int) date('n');
$currentYear = (int) date('Y');

if ($groups_id > 0 && !in_array($groups_id, $allowed_group_ids, true)) {
   Session::addMessageAfterRedirect(
      __('Выбрана недопустимая группа.', 'ticketreport'),
      false,
      ERROR
   );
   Html::back();
   exit;
}

if ($users_id <= 0 && $groups_id <= 0) {
   Session::addMessageAfterRedirect(
      __('Не выбран пользователь или группа.', 'ticketreport'),
      false,
      ERROR
   );
   Html::back();
   exit;
}

if ($month < 1 || $month > 12 || $year < 2010) {
   Session::addMessageAfterRedirect(
      __('Некорректные параметры формы. Проверьте выбранные значения.', 'ticketreport'),
      false,
      ERROR
   );
   Html::back();
   exit;
}

if ($year == $currentYear && $month > $currentMonth) {
   Session::addMessageAfterRedirect(
      __('Некорректные параметры формы. Проверьте выбранные значения.', 'ticketreport'),
      false,
      ERROR
   );
   Html::back();
   exit;
}

if ($status < 0 || $status > 2) {
   Session::addMessageAfterRedirect(
      __('Некорректный статус заявки.', 'ticketreport'),
      false,
      ERROR
   );
   Html::back();
   exit;
}

if ($status === 0) {
   Session::addMessageAfterRedirect(
      __('Не выбран статус заявки.', 'ticketreport'),
      false,
      ERROR
   );
   Html::back();
   exit;
}



$reportService = new PluginTicketreportReport();

// 1. Один конкретный пользователь
if ($users_id > 0) {
   // Если одновременно выбрана группа, проверяем, что пользователь действительно входит в неё.
   if ($groups_id > 0) {
      $groupUsers = $reportService->getUsersByGroup($groups_id);
      $groupUserIds = [];

      foreach ($groupUsers as $groupUser) {
         $groupUserIds[] = (int) $groupUser['id'];
      }

      if (!in_array($users_id, $groupUserIds, true)) {
         Session::addMessageAfterRedirect(
            __('Выбранный пользователь не входит в выбранную группу.', 'ticketreport'),
            false,
            ERROR
         );
         Html::back();
         exit;
      }
   }

   // Получаем заявки пользователя.
   $rows = $reportService->getClosedTicketsByUserAndPeriod(
      $users_id,
      $month,
      $year,
      $status
   );

   if (count($rows) === 0) {
      if ($status === 1) {
         Session::addMessageAfterRedirect(
            __('У пользователя нет активных заявок в выбранном месяце.', 'ticketreport'),
            false,
            ERROR
         );
      } else {
         Session::addMessageAfterRedirect(
            __('У пользователя нет закрытых заявок в выбранном месяце.', 'ticketreport'),
            false,
            ERROR
         );
      }
      Html::back();
      exit;
   }

   // Определяем имя пользователя для названия листа.
   $user = new User();

   if (!$user->getFromDB($users_id)) {
      Session::addMessageAfterRedirect(
         __('Не удалось найти выбранного пользователя.', 'ticketreport'),
         false, 
         ERROR
      );
      Html::back();
      exit;
   }

   $userName = formatUserName(
      $users_id,
      $user->fields['name'],
      $user->fields['realname'],
      $user->fields['firstname']
   );

   $excelService = new PluginTicketreportExcel();

   $excelService->build(
      $rows,
      $userName
   );

   $filename = sprintf(
      'tickets_report_user%d_%02d_%d.xlsx',
      $users_id,
      $month,
      $year
   );

   $excelService->download($filename);
}

/*
 * Режим 2: выбрана группа и "Все пользователи".
 */
if ($users_id === 0 && $groups_id > 0) {

   $groupUsers = $reportService->getUsersByGroup($groups_id);

   if (empty($groupUsers)) {
      Session::addMessageAfterRedirect(
         __('В выбранной группе нет пользователей.', 'ticketreport'),
         false,
         ERROR
      );
      Html::back();
      exit;
   }

   $userIds = [];

   foreach ($groupUsers as $groupUser) {
      $userIds[] = (int) $groupUser['id'];
   }

   // Один запрос получает заявки сразу по всей группе.
   $rowsByUser = $reportService->getClosedTicketsByUsersAndPeriod(
      $userIds,
      $month,
      $year,
      $status
   );

   $reports = [];

   foreach ($groupUsers as $groupUser) {
      $groupUserId = (int) $groupUser['id'];

      $reports[] = [
         'title' => $groupUser['name'],
         'rows'  => isset($rowsByUser[$groupUserId])
            ? $rowsByUser[$groupUserId]
            : [],
      ];
   }

   $excelService = new PluginTicketreportExcel();
   $excelService->buildMultiple($reports);

   $filename = sprintf(
      'tickets_report_group%d_%02d_%d.xlsx',
      $groups_id,
      $month,
      $year
   );

   $excelService->download($filename);
}
