<?php

/**
 * -------------------------------------------------------------------------
 * TicketReport plugin for GLPI
 * -------------------------------------------------------------------------
 * inc/excel.class.php
 *
 * Назначение файла:
 * Сервис-класс, отвечающий ИСКЛЮЧИТЕЛЬНО за формирование Excel (XLSX).
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PluginTicketreportExcel
{

   /** @var Spreadsheet */
   private $spreadsheet;

   public function __construct()
   {
      $this->spreadsheet = new Spreadsheet();
   }

   /**
    * Строит один лист Excel по переданным данным.
    *
    * @param array  $rows       Данные одного пользователя
    * @param string $sheetTitle Название листа
    *
    * @return self
    */
   public function build(array $rows, string $sheetTitle = 'Report'): self
   {
      $sheet = $this->spreadsheet->getActiveSheet();

      $sheet->setTitle($this->getUniqueSheetTitle($sheetTitle));
      $this->fillSheet($sheet, $rows);

      return $this;
   }

   /**
    * Строит одну книгу Excel с отдельным листом для каждого пользователя.
    *
    * Формат:
    * [
    *    [
    *       'title' => 'Иванов Иван',
    *       'rows'  => [...],
    *    ],
    *    ...
    * ]
    *
    * @param array $reports
    *
    * @return self
    */
   public function buildMultiple(array $reports): self
   {
      if (empty($reports)) {
         return $this;
      }

      $first = true;

      foreach ($reports as $report) {
         $title = isset($report['title']) ? (string) $report['title'] : 'Report';
         $rows  = isset($report['rows']) && is_array($report['rows'])
            ? $report['rows']
            : [];

         if ($first) {
            $sheet = $this->spreadsheet->getActiveSheet();
            $sheet->setTitle($this->getUniqueSheetTitle($title));
            $first = false;
         } else {
            $sheet = $this->spreadsheet->createSheet();
            $sheet->setTitle($this->getUniqueSheetTitle($title));
         }

         $this->fillSheet($sheet, $rows);
      }

      // Делаем первый лист активным.
      $this->spreadsheet->setActiveSheetIndex(0);

      return $this;
   }

   /**
    * Заполняет конкретный лист.
    *
    * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
    * @param array $rows
    */
   private function fillSheet($sheet, array $rows): void
   {
      $headers = [
         '№',
         'Дата закрытия/изменения',
         'Описание заявки',
         'ID заявки'
      ];

      $sheet->fromArray($headers, null, 'A1');

      // ОФОРМЛЕНИЕ ЗАГОЛОВКА

      $headerStyle = $sheet->getStyle('A1:D1');

      $headerStyle->getFont()->setBold(true)->setSize(12);
      $headerStyle->getFill()
         ->setFillType(Fill::FILL_SOLID)
         ->getStartColor()
         ->setRGB('333333');
      $headerStyle->getFont()->getColor()->setRGB('FFFFFF');
      $headerStyle->getAlignment()
         ->setHorizontal(Alignment::HORIZONTAL_CENTER)
         ->setVertical(Alignment::VERTICAL_CENTER)
         ->setWrapText(true);

      $sheet->getRowDimension(1)->setRowHeight(30);

      // ДАННЫЕ

      if (empty($rows)) {
         $sheet->mergeCells('A2:D2');
         $sheet->setCellValue(
            'A2',
            __('За выбранный период закрытых заявок нет.', 'ticketreport')
         );

         $sheet->getStyle('A2:D2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

         $sheet->getRowDimension(2)->setRowHeight(30);
      } else {
         $line = 2;
         $num = 1;

         foreach ($rows as $row) {
            $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
               new \DateTime($row['closedate'])
            );

            $sheet->setCellValueExplicit(
               'A' . $line,
               $num,
               DataType::TYPE_NUMERIC
            );

            $sheet->setCellValue('B' . $line, $excelDate);
            $sheet->getStyle('B' . $line)
               ->getNumberFormat()
               ->setFormatCode('dd.mm.yyyy');

            $sheet->setCellValueExplicit(
               'C' . $line,
               $row['name'],
               DataType::TYPE_STRING
            );

            $sheet->setCellValueExplicit(
               'D' . $line,
               $row['id'],
               DataType::TYPE_NUMERIC
            );

            // Чередование цветов строк.
            if ($num % 2 === 0) {
               $sheet->getStyle('A' . $line . ':D' . $line)
                  ->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()
                  ->setRGB('F2F2F2');
            }

            $line++;
            $num++;
         }

         $lastRow = $line - 1;

         // ОФОРМЛЕНИЕ ТЕЛА ТАБЛИЦЫ

         $bodyStyle = $sheet->getStyle('A2:D' . $lastRow);
         $bodyStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

         $sheet->getStyle('C2:C' . $lastRow)
            ->getAlignment()
            ->setWrapText(true);

         $sheet->getStyle('A2:A' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

         $sheet->getStyle('B2:B' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

         $sheet->getStyle('D2:D' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

         $sheet->getStyle('A1:D' . $lastRow)->applyFromArray([
            'borders' => [
               'allBorders' => [
                  'borderStyle' => Border::BORDER_THIN,
                  'color' => [
                     'rgb' => '888888'
                  ]
               ]
            ]
         ]);

         for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
            $sheet->getRowDimension($rowNumber)->setRowHeight(30);
         }

         $sheet->setAutoFilter('A1:D' . $lastRow);
      }

      // Ширина колонок.
      $sheet->getColumnDimension('A')->setWidth(10);
      $sheet->getColumnDimension('B')->setWidth(25);
      $sheet->getColumnDimension('C')->setWidth(90);
      $sheet->getColumnDimension('D')->setWidth(18);

      // Доп. настройки.
      $sheet->freezePane('A2');
      $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
      $sheet->getPageSetup()->setOrientation(
         \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
      );
      $sheet->getPageSetup()->setFitToWidth(1);
      $sheet->getPageSetup()->setFitToHeight(0);
   }

   /**
    * Отдаёт сформированный файл пользователю на скачивание
    * и завершает выполнение скрипта.
    *
    * @param string $filename
    */
   public function download(string $filename): void
   {
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
    * Excel запрещает в названии листа символы \ / ? * [ ]
    * и длину более 31 символа.
    */
   private function sanitizeSheetTitle(string $title): string
   {
      $title = preg_replace('/[\\\\\/\?\*\[\]:]/', '_', $title);
      $title = trim($title);

      if ($title === '') {
         $title = 'Report';
      }

      return $title;
   }

   /**
    * Возвращает допустимое и уникальное имя листа.
    */
   private function getUniqueSheetTitle(string $title): string
   {
      $base = mb_substr($this->sanitizeSheetTitle($title), 0, 31, 'UTF-8');

      if (!$this->spreadsheet->getSheetByName($base)) {
         return $base;
      }

      $index = 2;

      do {
         $suffix = ' (' . $index . ')';
         $availableLength = 31 - strlen($suffix);
         $candidate = mb_substr($base, 0, $availableLength, 'UTF-8') . $suffix;
         $index++;
      } while ($this->spreadsheet->getSheetByName($candidate));

      return $candidate;
   }
}
