<?php
session_start();
if (!isset($_SESSION['user_login'])) {
    header("Location: ../login.php");
    exit();
}

require '../config.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$module_type = isset($_GET['module_type']) ? (int)$_GET['module_type'] : 1;
$company_id = $_SESSION['company_id'];

if ($project_id == 0) {
    die("Invalid project ID");
}

// Fetch project details
$sql = "SELECT * FROM projects_list WHERE id = $project_id AND company_id = $company_id";
$result = mysqli_query($conn, $sql);
$project = mysqli_fetch_assoc($result);

if (!$project) {
    die("ไม่พบข้อมูลโครงการ");
}

// Fetch all transactions
$sql_t = "SELECT t.*, c.name as category_name, c.direction 
          FROM transactions t
          LEFT JOIN categories c ON t.category_id = c.id
          WHERE t.project_id = $project_id AND t.module_type = $module_type AND t.company_id = $company_id
          ORDER BY t.transaction_date ASC, t.id ASC";
$result_t = mysqli_query($conn, $sql_t);

$transactions = [];
$total_income = 0;
$total_expense = 0;

$income_by_category = [];
$expense_by_category = [];

$min_date = null;
$max_date = null;

while ($row = mysqli_fetch_assoc($result_t)) {
    $transactions[] = $row;
    
    $t_date = strtotime($row['transaction_date']);
    if ($min_date === null || $t_date < $min_date) $min_date = $t_date;
    if ($max_date === null || $t_date > $max_date) $max_date = $t_date;

    $amount = (float)$row['amount'];
    $cat_name = $row['category_name'];

    if ($row['direction'] == 'income') {
        $total_income += $amount;
        if (!isset($income_by_category[$cat_name])) $income_by_category[$cat_name] = 0;
        $income_by_category[$cat_name] += $amount;
    } else {
        $total_expense += $amount;
        if (!isset($expense_by_category[$cat_name])) $expense_by_category[$cat_name] = 0;
        $expense_by_category[$cat_name] += $amount;
    }
}
$profit = $total_income - $total_expense;

$start_date_str = $min_date ? date('d/m/Y', $min_date) : '-';
$end_date_str = $max_date ? date('d/m/Y', $max_date) : '-';
$report_date = date('d/m/Y');
$project_year = date('Y', strtotime($project['created_at'])) + 543;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('สรุปโครงการ');

$fontPrompt = ['name' => 'Prompt']; // Or Arial if Prompt doesn't work well in Excel
$defaultStyle = [
    'font' => ['name' => 'Tahoma', 'size' => 10],
];
$spreadsheet->getDefaultStyle()->applyFromArray($defaultStyle);

// --- Row 1 & 2: Title ---
$sheet->setCellValue('A1', 'รายงานสรุปโครงการ');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$sheet->setCellValue('A2', $project['project_name']);
$sheet->mergeCells('A2:G2');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// --- Row 3 to 5: Meta ---
$sheet->setCellValue('A3', 'รหัสโครงการ');
$sheet->setCellValue('B3', ': โครงการ' . $project_year);
$sheet->setCellValue('A4', 'ชื่อโครงการ');
$sheet->setCellValue('B4', ': ' . $project['project_name']);
$sheet->setCellValue('A5', 'ช่วงวันที่รายงาน');
$sheet->setCellValue('B5', ': ' . $start_date_str . ' - ' . $end_date_str);

$sheet->setCellValue('F4', 'วันที่พิมพ์รายงาน');
$sheet->setCellValue('G4', ': ' . $report_date);
$sheet->getStyle('A3:A5')->getFont()->setBold(true);
$sheet->getStyle('F4')->getFont()->setBold(true);

// --- KPI Blocks (Row 7-8) ---
// Income
$sheet->setCellValue('A7', 'รายรับรวม');
$sheet->mergeCells('A7:B7');
$sheet->setCellValue('A8', $total_income);
$sheet->mergeCells('A8:B8');
$sheet->getStyle('A7:B8')->applyFromArray([
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF10B981']]],
]);
$sheet->getStyle('A7:B7')->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFD1FAE5']], // Light green
    'font' => ['bold' => true, 'color' => ['argb' => 'FF047857']],
]);
$sheet->getStyle('A8')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF059669']],
]);

// Expense
$sheet->setCellValue('C7', 'รายจ่ายรวม');
$sheet->mergeCells('C7:D7');
$sheet->setCellValue('C8', $total_expense);
$sheet->mergeCells('C8:D8');
$sheet->getStyle('C7:D8')->applyFromArray([
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFEF4444']]],
]);
$sheet->getStyle('C7:D7')->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFEE2E2']], // Light red
    'font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']],
]);
$sheet->getStyle('C8')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFDC2626']],
]);

// Profit
$sheet->setCellValue('E7', 'กำไร / ขาดทุน');
$sheet->mergeCells('E7:E8'); // Or span?
$sheet->setCellValue('E8', $profit);
$sheet->unmergeCells('E7:E8');
$sheet->mergeCells('E7:F7');
$sheet->setCellValue('E8', $profit);
$sheet->mergeCells('E8:F8');
$sheet->getStyle('E7:F8')->applyFromArray([
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFF59E0B']]],
]);
$sheet->getStyle('E7:F7')->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFEF3C7']], // Light orange
    'font' => ['bold' => true, 'color' => ['argb' => 'FFB45309']],
]);
$sheet->getStyle('E8')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFD97706']],
]);

// Dates
$sheet->setCellValue('G7', 'วันที่เริ่มโครงการ');
$sheet->setCellValue('G8', $start_date_str);
$sheet->getStyle('G7:G8')->applyFromArray([
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF94A3B8']]],
]);
$sheet->getStyle('G7')->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFF1F5F9']],
    'font' => ['bold' => true, 'color' => ['argb' => 'FF334155']],
]);
$sheet->getStyle('G8')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF0F172A']],
]);

$sheet->setCellValue('H7', 'วันที่สิ้นสุดโครงการ');
$sheet->setCellValue('H8', $end_date_str);
$sheet->getStyle('H7:H8')->applyFromArray([
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF94A3B8']]],
]);
$sheet->getStyle('H7')->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFF1F5F9']],
    'font' => ['bold' => true, 'color' => ['argb' => 'FF334155']],
]);
$sheet->getStyle('H8')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF0F172A']],
]);

$sheet->getStyle('A8:F8')->getNumberFormat()->setFormatCode('#,##0.00');


$row = 10;

// --- 1. Income Summary ---
$sheet->setCellValue('A'.$row, '1. รายงานรายรับแยกหมวดหมู่');
$sheet->mergeCells('A'.$row.':E'.$row);
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FF047857']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFD1FAE5']],
    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
]);
$row++;

$sheet->setCellValue('A'.$row, 'ลำดับ');
$sheet->setCellValue('B'.$row, 'หมวดหมู่รายรับ');
$sheet->mergeCells('B'.$row.':D'.$row);
$sheet->setCellValue('E'.$row, 'จำนวนเงิน (บาท)');
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFF8FAFC']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$row++;

$i = 1;
foreach ($income_by_category as $cat => $amt) {
    $sheet->setCellValue('A'.$row, $i++);
    $sheet->setCellValue('B'.$row, $cat);
    $sheet->mergeCells('B'.$row.':D'.$row);
    $sheet->setCellValue('E'.$row, $amt);
    
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

// Total Income row
$sheet->setCellValue('A'.$row, 'รวมรายรับทั้งหมด');
$sheet->mergeCells('A'.$row.':D'.$row);
$sheet->setCellValue('E'.$row, $total_income);
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FF047857']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFF0FDF4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$row += 2;

// --- 2. Expense Summary ---
$sheet->setCellValue('A'.$row, '2. รายงานรายจ่ายแยกหมวดหมู่');
$sheet->mergeCells('A'.$row.':E'.$row);
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFEE2E2']],
    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
]);
$row++;

$sheet->setCellValue('A'.$row, 'ลำดับ');
$sheet->setCellValue('B'.$row, 'หมวดหมู่รายจ่าย');
$sheet->mergeCells('B'.$row.':D'.$row);
$sheet->setCellValue('E'.$row, 'จำนวนเงิน (บาท)');
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFF8FAFC']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$row++;

$i = 1;
foreach ($expense_by_category as $cat => $amt) {
    $sheet->setCellValue('A'.$row, $i++);
    $sheet->setCellValue('B'.$row, $cat);
    $sheet->mergeCells('B'.$row.':D'.$row);
    $sheet->setCellValue('E'.$row, $amt);
    
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

// Total Expense row
$sheet->setCellValue('A'.$row, 'รวมรายจ่ายทั้งหมด');
$sheet->mergeCells('A'.$row.':D'.$row);
$sheet->setCellValue('E'.$row, $total_expense);
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFEF2F2']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$row += 2;

// --- 3. All Transactions ---
$sheet->setCellValue('A'.$row, '3. รายละเอียดรายการทั้งหมด');
$sheet->mergeCells('A'.$row.':G'.$row);
$sheet->getStyle('A'.$row.':G'.$row)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FF1D4ED8']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFDBEAFE']],
    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]],
]);
$row++;

$sheet->setCellValue('A'.$row, 'ลำดับ');
$sheet->setCellValue('B'.$row, 'ประเภท');
$sheet->setCellValue('C'.$row, 'หมวดหมู่');
$sheet->setCellValue('D'.$row, 'รายการ');
$sheet->setCellValue('E'.$row, 'รายละเอียด');
$sheet->setCellValue('F'.$row, 'วันที่');
$sheet->setCellValue('G'.$row, 'จำนวนเงิน (บาท)');
$sheet->getStyle('A'.$row.':G'.$row)->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFF8FAFC']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$row++;

$i = 1;
foreach ($transactions as $t) {
    $sheet->setCellValue('A'.$row, $i++);
    $type_str = $t['direction'] == 'income' ? 'รายรับ' : 'รายจ่าย';
    $sheet->setCellValue('B'.$row, $type_str);
    $sheet->setCellValue('C'.$row, $t['category_name']);
    $sheet->setCellValue('D'.$row, $t['note'] ?: '-'); // Or item name if distinct
    $sheet->setCellValue('E'.$row, '-'); // Detail col
    $sheet->setCellValue('F'.$row, date('d/m/Y', strtotime($t['transaction_date'])));
    $sheet->setCellValue('G'.$row, (float)$t['amount']);
    
    $sheet->getStyle('A'.$row.':B'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('A'.$row.':G'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

// Auto size columns
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(30);
$sheet->getColumnDimension('E')->setWidth(30);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(20);
$sheet->getColumnDimension('H')->setWidth(20);

// Export
ob_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="project_report_' . $project_id . '_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
