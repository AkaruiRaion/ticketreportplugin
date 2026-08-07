<?php
/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * front/report.php
 *
 * Назначение файла:
 * Отображает HTML-форму отчёта (пользователь / месяц / год / кнопка).
 * Файл отвечает ТОЛЬКО за представление (View) и проверку прав доступа.
 * Логика получения данных и формирования Excel-файла находится в
 * inc/report.class.php и inc/excel.class.php, вызываемых из
 * front/report.export.php.
 * -------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

// Доступ только для авторизованных пользователей с правом plugin_ticketreport
Session::checkLoginUser();
Session::checkRight('plugin_ticketreport', READ);

Html::header(
   __('Отчёты', 'ticketreport'),
   $_SERVER['PHP_SELF'],
   'plugins',
   'PluginTicketreportMenu'
);

echo "<div class='center'>";
echo "<form name='ticketreport_form' method='post' action='" . $CFG_GLPI["root_doc"] . "/plugins/ticketreport/front/report.export.php'>";
echo "<table class='tab_cadre' style='width:600px;'>";
echo "<tr><th colspan='2'>" . __('Отчёт по закрытым заявкам пользователя', 'ticketreport') . "</th></tr>";

// --- Пользователь ---
echo "<tr class='tab_bg_1'>";
echo "<td style='width:200px;'>" . __('Пользователь') . "</td>";
echo "<td>";
User::dropdown([
   'name'     => 'users_id',
   'value'    => 0,
   'right'    => 'all',
   'comments' => false,
]);
echo "</td></tr>";

// --- Месяц ---
$months = [
   1  => __('Январь'),
   2  => __('Февраль'),
   3  => __('Март'),
   4  => __('Апрель'),
   5  => __('Май'),
   6  => __('Июнь'),
   7  => __('Июль'),
   8  => __('Август'),
   9  => __('Сентябрь'),
   10 => __('Октябрь'),
   11 => __('Ноябрь'),
   12 => __('Декабрь'),
];

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Месяц') . "</td>";
echo "<td>";
Dropdown::showFromArray('month', $months, ['value' => (int) date('n')]);
echo "</td></tr>";

// --- Год (текущий год и 5 предыдущих) ---
$currentYear = (int) date('Y');
$years = [];
for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
   $years[$y] = $y;
}

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Год') . "</td>";
echo "<td>";
Dropdown::showFromArray('year', $years, ['value' => $currentYear]);
echo "</td></tr>";

// --- Кнопка отправки ---
echo "<tr class='tab_bg_2'>";
echo "<td colspan='2' class='center'>";
$token = Session::getNewCSRFToken();

// echo "<pre>";
// echo "TOKEN = $token\n";
// echo session_id() . "\n";
// echo "SESSION TOKENS:\n";
// print_r(array_keys($_SESSION['glpicsrftokens']));
// echo "</pre>";

echo "<input type='hidden' name='_glpi_csrf_token' value='$token'>";
//echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo "<input type='submit' name='generate' class='submit' value=\""
   . __s('Сформировать отчёт', 'ticketreport') . "\">";
echo "</td></tr>";

echo "</table>";
echo "</form>";
//Html::closeForm();
echo "</div>";

Html::footer();
