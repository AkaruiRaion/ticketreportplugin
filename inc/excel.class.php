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
    * Строит лист Excel по переданным данным.
    * Структура:  | Дата | Наименование заявки | ID заявки |
    *
    * @param array  $rows       Данные, полученные из PluginTicketreportReport
    * @param string $sheetTitle Название листа (например "07-2026")
    *
    * @return self Для fluent-вызова build()->download()
    */
   public function build(array $rows, string $sheetTitle = 'Report'): self
   {
      $sheet = $this->spreadsheet->getActiveSheet();

      $sheet->setTitle(
         substr($this->sanitizeSheetTitle($sheetTitle), 0, 31)
      );

      // Заголовки таблицы
      $headers = [
         '№',
         'Дата закрытия',
         'Описание заявки',
         'ID заявки'
      ];

      $sheet->fromArray($headers, null, 'A1');

      // ОФОРМЛЕНИЕ ЗАГОЛОВКА

      $headerStyle = $sheet->getStyle('A1:D1');

      // Шрифт
      $headerStyle->getFont()->setBold(true)->setSize(12);

      // Цвет фона
      $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('333333');

      // Цвет текста
      $headerStyle->getFont()->getColor()->setRGB('FFFFFF');

      // Выравнивание
      $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

      // Высота строки заголовка
      $sheet->getRowDimension(1)->setRowHeight(30);

      //ДАННЫЕ

      $line = 2;
      $num = 1;

      foreach ($rows as $row) {

         $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
            new \DateTime($row['closedate'])
         );

         $sheet->setCellValueExplicit('A' . $line, $num, DataType::TYPE_NUMERIC);
         $sheet->setCellValue('B' . $line, $excelDate);
         $sheet->getStyle('B' . $line)->getNumberFormat()->setFormatCode('dd.mm.yyyy');
         $sheet->setCellValueExplicit('C' . $line, $row['name'], DataType::TYPE_STRING);
         $sheet->setCellValueExplicit('D' . $line, $row['id'], DataType::TYPE_NUMERIC);

         // Чередование цветов строк

         if ($num % 2 === 0) {
            $sheet->getStyle('A' . $line . ':D' . $line)
               ->getFill()
               ->setFillType(
                  Fill::FILL_SOLID
               )
               ->getStartColor()
               ->setRGB('F2F2F2');
         }

         $line++;
         $num++;
      }

      // ОФОРМЛЕНИЕ ТЕЛА ТАБЛИЦЫ

      if ($line > 2) {

         $lastRow = $line - 1;

         $bodyStyle = $sheet->getStyle('A2:D' . $lastRow);

         // Вертикальное выравнивание
         $bodyStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

         // Перенос текста для описания заявки
         $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setWrapText(true);

         // №, дата и ID по центру
         $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
         $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
         $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

         // Границы всей таблицы
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

         // Высота строк с данными
         for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
            $sheet->getRowDimension($rowNumber)->setRowHeight(30);
         }
      }

      // Ширина колонок

      $sheet->getColumnDimension('A')->setWidth(10);
      $sheet->getColumnDimension('B')->setWidth(25);
      $sheet->getColumnDimension('C')->setWidth(90);
      $sheet->getColumnDimension('D')->setWidth(18);

      // Доп. настройки

      // Закрепляем строку заголовка
      $sheet->freezePane('A2');

      // Включаем автофильтр
      if ($line > 2) {
         $sheet->setAutoFilter('A1:D' . ($line - 1));
      }

      // Печать заголовка на каждой странице
      $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

      // Альбомная ориентация
      $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

      // Подгонка таблицы по ширине страницы
      $sheet->getPageSetup()->setFitToWidth(1);
      $sheet->getPageSetup()->setFitToHeight(0);

      return $this;
   }

   /**
    * Отдаёт сформированный файл пользователю на скачивание и завершает
    * выполнение скрипта.
    *
    * @param string $filename Имя файла, например "report_2026_07.xlsx"
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
    * Excel запрещает в названии листа символы \ / ? * [ ] и длину > 31.
    */
   private function sanitizeSheetTitle(string $title): string
   {
      return preg_replace('/[\\\\\/\?\*\[\]]/', '_', $title);
   }
}
