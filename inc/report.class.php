<?php

/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * inc/report.class.php
 *
 * Назначение файла:
 * Сервис-класс, отвечающий ИСКЛЮЧИТЕЛЬНО за получение данных из БД GLPI.
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginTicketreportReport
{

   /**
    * Получить пользователей выбранной группы.
    *
    * @param int $groups_id ID группы GLPI
    *
    * @return array Массив:
    *               [[ 'id'   => int, 'name' => string, ], ... ]
    */
   public function getUsersByGroup(int $groups_id): array
   {
      $result = [];

      if ($groups_id <= 0) {
         return $result; 
      }

      $users = Group_User::getGroupUsers($groups_id);

      foreach ($users as $user) {
         if (!isset($user['id'])) {
            continue;
         }

         $user_id = (int) $user['id'];

         if ($user_id <= 0) {
            continue;
         }

         $result[] = [
            'id'   => $user_id,
            'name' => formatUserName(
               $user_id,
               isset($user['name']) ? $user['name'] : '',
               isset($user['realname']) ? $user['realname'] : '',
               isset($user['firstname']) ? $user['firstname'] : ''
            ),
         ];
      }

      return $result;
   }

   /**
    * Получить список заявок одного пользователя, закрытых
    * в указанном месяце/году.
    *
    *
    * @param int $users_id ID пользователя GLPI
    * @param int $month    Месяц (1-12)
    * @param int $year     Год
    *
    * @return array
    */
   public function getClosedTicketsByUserAndPeriod(int $users_id, int $month, int $year, int $status): array
   {
      $reports = $this->getClosedTicketsByUsersAndPeriod([$users_id], $month, $year, $status);

      return isset($reports[$users_id]) ? $reports[$users_id] : [];
   }

   /**
    * Получить закрытые заявки сразу по нескольким пользователям.
    *
    * Результат сгруппирован по ID пользователя, поэтому его можно
    * напрямую использовать для построения отдельных листов Excel.
    *
    * @param array $users_ids Массив ID пользователей
    * @param int   $month
    * @param int   $year
    *
    * @return array
    *         [
    *            123 => [
    *               ['id' => 1, 'name' => '...', 'closedate' => '...'],
    *               ...
    *            ],
    *            456 => [
    *               ...
    *            ],
    *         ]
    */
   public function getClosedTicketsByUsersAndPeriod(array $users_ids, int $month, int $year, int $status): array
   {
      global $DB;

      $result = [];

      $users_ids = array_values(array_unique(array_map('intval', $users_ids)));
      $users_ids = array_filter($users_ids, function ($id) {
         return $id > 0;
      });

      if (empty($users_ids) || $month < 1 || $month > 12 || $year < 1970) {
         return $result;
      }

      // Границы выбранного месяца.
      $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
      $end   = date('Y-m-d 23:59:59', strtotime($start . ' +1 month -1 day'));

      $statusCondition = '';
      $dateCondition = [];

      if ($status === 1) {
         $statusCondition = [
            'glpi_tickets.status' => [
               CommonITILObject::ASSIGNED,
               CommonITILObject::PLANNED
            ]
         ];

         $dateCondition = [
            ['glpi_tickets.date_mod' => ['>=', $start]],
            ['glpi_tickets.date_mod' => ['<=', $end]],
         ];
      } elseif ($status === 2) {
         $statusCondition = [
            'glpi_tickets.status' => [
               CommonITILObject::SOLVED,
               CommonITILObject::CLOSED
            ]
         ];

         $dateCondition = [
            ['glpi_tickets.date_mod' => ['>=', $start]],
            ['glpi_tickets.date_mod' => ['<=', $end]],
         ];
      }

      $criteria = [
         'SELECT'     => [
            'glpi_tickets.id AS id',
            'glpi_tickets.name AS name',
            'glpi_tickets.date_mod AS date_mod',
            'glpi_tickets_users.users_id AS users_id',
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
            'glpi_tickets_users.users_id' => $users_ids,
            'glpi_tickets_users.type'     => CommonITILActor::ASSIGN,
            'glpi_tickets.is_deleted'     => 0,
            $statusCondition,
            $dateCondition
         ],
         'ORDER'      => [
            'glpi_tickets_users.users_id ASC',
            'glpi_tickets.closedate ASC',
         ],
      ];

      $iterator = $DB->request($criteria);

      foreach ($iterator as $row) {
         $user_id = (int) $row['users_id'];

         if (!isset($result[$user_id])) {
            $result[$user_id] = [];
         }

         $result[$user_id][] = [
            'id'        => (int) $row['id'],
            'name'      => (string) $row['name'],
            'closedate' => (string) $row['closedate'],
         ];
      }

      // Создаём пустой массив и для пользователей без заявок,
      // чтобы каждый пользователь группы получил отдельный лист.
      foreach ($users_ids as $user_id) {
         if (!isset($result[$user_id])) {
            $result[$user_id] = [];
         }
      }

      return $result;
   }
}
