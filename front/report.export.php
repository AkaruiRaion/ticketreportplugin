<?php
/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * front/report.export.php
 *
 * Назначение файла:
 * Контроллер обработки отправленной формы. Проверяет права и CSRF-токен,
 * читает параметры формы, и только КООРДИНИРУЕТ вызовы двух сервисов:
 *   1. PluginTicketreportReport   — получение данных (весь SQL там)
 *   2. PluginTicketreportExcel    — построение и отдача XLSX-файла
 * Сам файл не содержит ни одного SQL-запроса и не работает с
 * PhpSpreadsheet напрямую — это и есть разделение ответственности (SRP).
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

if ($users_id <= 0 || $month < 1 || $month > 12 || $year < 2010) {
   Session::addMessageAfterRedirect(
      __('Некорректные параметры формы. Проверьте выбранные значения.', 'ticketreport'),
      false,
      ERROR
   );
   Html::back();
   exit;
}

// 1. Получение данных — исключительно через сервис Report
$reportService = new PluginTicketreportReport();
$rows = $reportService->getClosedTicketsByUserAndPeriod($users_id, $month, $year);

if(count($rows) == 0) {
   Session::addMessageAfterRedirect(
      __('У пользоваткеля нет закрытых заявок в выбранном месяце.', 'ticketreport'),
      false,
      ERROR
   );
   Html::back();
   exit;
}

// 2. Формирование и скачивание файла — исключительно через сервис Excel
$sheetTitle = sprintf('%02d-%d', $month, $year);
$filename   = sprintf('closed_tickets_report_user%d_%02d_%d.xlsx', $users_id, $month, $year);

$excelService = new PluginTicketreportExcel();
$excelService->build($rows, $sheetTitle);
$excelService->download($filename); // завершает выполнение скрипта (exit)
