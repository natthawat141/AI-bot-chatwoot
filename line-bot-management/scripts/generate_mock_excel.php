<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$headings = [
    'code', 'name_th', 'description_th', 'price', 'sale_price',
    'effective_from', 'effective_until', 'terms', 'keywords',
];

$rows = [
    ['MOCK-PROMO-001', '[ข้อมูลจำลอง] โปรโมชันดูแลผิว', '[ข้อมูลจำลอง] ใช้ทดสอบระบบเท่านั้น ไม่ใช่บริการหรือราคาจริง', 1500, 1290, '2026-07-01', '2026-12-31', '[ข้อมูลจำลอง] กรุณายืนยันเงื่อนไขจริงกับผู้ให้บริการ', 'ข้อมูลจำลอง,โปรโมชัน,ดูแลผิว'],
    ['MOCK-PACKAGE-001', '[ข้อมูลจำลอง] แพ็กเกจทดลองครั้งแรก', '[ข้อมูลจำลอง] แพ็กเกจตัวอย่างสำหรับทดสอบการตอบของ AI', 900, 690, '2026-07-01', '2026-12-31', '[ข้อมูลจำลอง] ใช้ทดสอบระบบเท่านั้น', 'ข้อมูลจำลอง,ลูกค้าใหม่,แพ็กเกจ'],
    ['MOCK-LASER-001', '[ข้อมูลจำลอง] แพ็กเกจเลเซอร์ผิว', '[ข้อมูลจำลอง] รายการสมมติสำหรับทดสอบการค้นหาแพ็กเกจ', 2500, 1990, '', '', '[ข้อมูลจำลอง] ราคาไม่มีผลใช้จริง', 'ข้อมูลจำลอง,เลเซอร์,laser'],
];

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('Bill Natthawat × Aion3')
    ->setTitle('AI Knowledge package and promotion mock data')
    ->setDescription('ข้อมูลจำลองสำหรับทดสอบการนำเข้าแพ็กเกจและโปรโมชัน');

$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('packages-promotions');
$sheet->fromArray($headings, null, 'A1');
$sheet->fromArray($rows, null, 'A2');

$lastColumn = Coordinate::stringFromColumnIndex(count($headings));
$lastRow = count($rows) + 1;
$sheet->freezePane('A2');
$sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
$sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '15803D']],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

foreach ($headings as $index => $heading) {
    $column = Coordinate::stringFromColumnIndex($index + 1);
    $sheet->getColumnDimension($column)->setWidth(in_array($heading, ['description_th', 'terms'], true) ? 44 : 20);
}

$outputDirectory = dirname(__DIR__).'/public/mock-data';
if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0777, true) && ! is_dir($outputDirectory)) {
    throw new RuntimeException("Cannot create output directory: {$outputDirectory}");
}

$path = $outputDirectory.'/mock-packages.xlsx';
(new Xlsx($spreadsheet))->save($path);
$spreadsheet->disconnectWorksheets();

$verification = IOFactory::load($path)->getActiveSheet();
if ($verification->rangeToArray("A1:{$lastColumn}1")[0] !== $headings || $verification->getHighestDataRow() !== $lastRow) {
    throw new RuntimeException('Generated workbook failed validation');
}

echo $path." ({$lastRow} rows including header)".PHP_EOL;
