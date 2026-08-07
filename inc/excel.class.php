<?php
/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * inc/excel.class.php
 *
 * Назначение файла:
 * Сервис-класс, отвечающий ИСКЛЮЧИТЕЛЬНО за формирование Excel (XLSX)
 * файла на основе уже готового массива данных (принцип единственной
 * ответственности — SRP). Класс ничего не знает о базе данных GLPI и не
 * содержит ни одного SQL-запроса — он получает на вход простой массив.
 * Используется библиотека PhpOffice/PhpSpreadsheet.
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

// Подключаем автозагрузчик Composer (библиотека PhpSpreadsheet)
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PluginTicketreportExcel {

   /** @var Spreadsheet */
   private $spreadsheet;

   public function __construct() {
      $this->spreadsheet = new Spreadsheet();
   }

   /**
    * Строит лист Excel по переданным данным.
    * Структура:  | Дата | Наименование заявки | ID заявки |
    *
    * @param array  $rows       Данные, полученные из PluginTicketreportReport
    * @param string $sheetTitle Название листа (например "07-2026")
    *
    * @return self Для fluent-вызова build()->download()
    */
   public function build(array $rows, string $sheetTitle = 'Report'): self {
      $sheet = $this->spreadsheet->getActiveSheet();
      $sheet->setTitle(substr($this->sanitizeSheetTitle($sheetTitle), 0, 31));

      // Заголовок таблицы
      $headers = ['№', 'Дата закрытия', 'Наименование заявки', 'ID заявки'];
      $sheet->fromArray($headers, null, 'A1');

      $headerStyle = $sheet->getStyle('A1:D1');
      $headerStyle->getFont()->setBold(true);
      $headerStyle->getFill()
         ->setFillType(Fill::FILL_SOLID)
         ->getStartColor()->setRGB('D9D9D9');
      $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

      // Данные
      $line = 2;
      $num = 1;
      foreach ($rows as $row) {
         $sheet->setCellValueExplicit('A' . $line, $num, DataType::TYPE_NUMERIC);
         $sheet->setCellValueExplicit('B' . $line, $row['closedate'], DataType::TYPE_STRING);
         $sheet->setCellValueExplicit('C' . $line, $row['name'], DataType::TYPE_STRING);
         $sheet->setCellValueExplicit('D' . $line, $row['id'], DataType::TYPE_NUMERIC);
         $line++;
         $num++;
      }

      // Автоширина колонок
      foreach (['A', 'B', 'C'] as $col) {
         $sheet->getColumnDimension($col)->setAutoSize(true);
      }

      return $this;
   }

   /**
    * Отдаёт сформированный файл пользователю на скачивание и завершает
    * выполнение скрипта.
    *
    * @param string $filename Имя файла, например "report_2026_07.xlsx"
    */
   public function download(string $filename): void {
      if (ob_get_length()) {
         ob_end_clean();
      }

      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="' . $filename . '"');
      header('Cache-Control: max-age=0');

      $writer = new Xlsx($this->spreadsheet);
      $writer->save('php://output');
      exit;
   }

   /**
    * Excel запрещает в названии листа символы \ / ? * [ ] и длину > 31.
    */
   private function sanitizeSheetTitle(string $title): string {
      return preg_replace('/[\\\\\/\?\*\[\]]/', '_', $title);
   }
}
