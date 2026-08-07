<?php

/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * inc/report.class.php
 *
 * Назначение файла:
 * Сервис-класс, отвечающий ИСКЛЮЧИТЕЛЬНО за получение данных из БД GLPI
 * (принцип единственной ответственности — SRP). Здесь нет HTML, нет
 * экспорта в Excel — только безопасная выборка данных через query builder
 * DBmysql (без «сырых» SQL-строк, без конкатенации пользовательского
 * ввода в запрос).
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginTicketreportReport
{

   /**
    * Получить список заявок пользователя, закрытых в указанном месяце/году.
    *
    * Пользователь определяется как "заявитель" (requester) заявки, т.е.
    * выбираются заявки, поданные выбранным пользователем. При необходимости
    * можно заменить CommonITILActor::REQUESTER на CommonITILActor::ASSIGN,
    * если требуется отчёт по заявкам, ЗАКРЫТЫМ исполнителем.
    *
    * @param int $users_id ID пользователя GLPI
    * @param int $month    Месяц (1-12)
    * @param int $year     Год (например, 2026)
    *
    * @return array Массив строк вида:
    *               [ ['id' => int, 'name' => string, 'closedate' => string], ... ]
    */
   public function getClosedTicketsByUserAndPeriod(int $users_id, int $month, int $year): array
   {
      global $DB;

      $result = [];

      // Базовая валидация входных параметров
      if ($users_id <= 0 || $month < 1 || $month > 12 || $year < 1970) {
         return $result;
      }

      // Границы выбранного месяца
      $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
      $end   = date('Y-m-d 23:59:59', strtotime($start . ' +1 month -1 day'));

      $criteria = [
         'SELECT'     => [
            'glpi_tickets.id AS id',
            'glpi_tickets.name AS name',
            'glpi_tickets.closedate AS closedate',
         ],
         'FROM'       => 'glpi_tickets',
         'INNER JOIN' => [
            'glpi_tickets_users' => [
               'FKEY' => [
                  'glpi_tickets_users' => 'tickets_id',
                  'glpi_tickets'       => 'id',
               ],
            ],
         ],
         'WHERE'      => [
            'glpi_tickets_users.users_id' => $users_id,
            'glpi_tickets_users.type'     => CommonITILActor::REQUESTER,
            'glpi_tickets.is_deleted'     => 0,
            ['glpi_tickets.closedate' => ['>=', $start]],
            ['glpi_tickets.closedate' => ['<=', $end]],
         ],
         'ORDER'      => 'glpi_tickets.closedate ASC',
      ];

      $iterator = $DB->request($criteria);

      foreach ($iterator as $row) {
         $result[] = [
            'id'        => (int) $row['id'],
            'name'      => (string) $row['name'],
            'closedate' => (string) $row['closedate'],
         ];
      }

      return $result;
   }
}
