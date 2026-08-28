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
echo "<form id='ticketreport_form' name='ticketreport_form' class='ticketreport-form' method='post' action='" . $CFG_GLPI["root_doc"] . "/plugins/ticketreport/front/report.export.php'>";
echo "<table class='tab_cadre' style='width:600px;'>";
echo "<tr><th colspan='2'>" . __('Отчёт по закрытым заявкам пользователя', 'ticketreport') . "</th></tr>";

// --- Группа ---
$allowed_group_ids = [6, 7, 8];
$condition = '`id` IN (' . implode(',', array_map('intval', $allowed_group_ids)) . ')';

echo "<tr class='tab_bg_1'>";
echo "<td style='width:200px;'>" . __('Группа') . "</td>";
echo "<td>";

Group::dropdown([
    'name'      => 'groups_id',
    'value'     => 0,
    'emptylabel' => __('Выберите группу'),
    'condition' => $condition,
    'display'   => true,
    'comments'  => false,
]);

echo "</td>";
echo "</tr>";

// --- Пользователь ---
echo "<tr class='tab_bg_1'>";
echo "<td style='width:200px;'>" . __('Пользователь') . "</td>";
echo "<td id='users_container'>";

User::dropdown([
    'name'     => 'users_id',
    'value'    => 0,
    'width' => '50%',
    'right'    => 'all',
    'comments' => false,
    'display'  => true
]);

echo "</td></tr>";

// --- Статусы заявки ---
$statuses = [
    0 => __('Выберите статус'),
    1 => __('В работе (назначена или запланирована)'),
    2 => __('Решена или закрыта'),
];

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Статус') . "</td>";
echo "<td>";
Dropdown::showFromArray('status', $statuses);
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

echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo "<input type='submit' name='generate' class='submit' value=\""
    . __s('Сформировать отчёт', 'ticketreport') . "\">";
echo "</td></tr>";

echo "</table>";
Html::closeForm();
echo "</div>";

echo <<<HTML
<script>
$(document).on('change', 'select[name="groups_id"]', function() {

    var groupsId = parseInt($(this).val(), 10) || 0;

    $.ajax({
        url: '{$CFG_GLPI['root_doc']}/plugins/ticketreport/front/user.dropdown.php',
        type: 'GET',
        data: {
            groups_id: groupsId
        },
        success: function(html) {
            $('#users_container').html(html);
        },
        error: function(xhr) {
            console.log(xhr.responseText);
        }
    });

});

$(document).on('submit', '#ticketreport_form', function(e) {
        setTimeout(function() {
            window.location.reload();
        }, 1500);
});
</script>
HTML;

Html::footer();
