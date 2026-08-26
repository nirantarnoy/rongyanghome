<?php
require '../auth_check.php';
require '../config.php';
require '../vendor/autoload.php';
require '../thai_baht_helper.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$company_id = $_SESSION['company_id'];
$type = $_GET['type'] ?? 'quotation';
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    die("กรุณาระบุ ID เอกสาร");
}

function imageToBase64($path) {
    if (empty($path)) return '';
    if (strpos($path, 'data:image') === 0) return $path;
    
    if (strpos($path, '../') === 0) {
        $path = substr($path, 3);
    }
    
    $fullPath = __DIR__ . '/../' . $path;
    if (file_exists($fullPath) && is_file($fullPath)) {
        $mime = mime_content_type($fullPath) ?: 'image/png';
        $data = file_get_contents($fullPath);
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
    return '';
}

if ($type === 'quotation') {
    $sql = "SELECT * FROM quotations WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $q = mysqli_fetch_assoc($res);
    
    if (!$q) {
        die("ไม่พบข้อมูลใบเสนอราคา");
    }
    
    $items = json_decode($q['items'], true);
    if (!is_array($items)) $items = [];
    
    // Resolve images to base64
    $header_logo = imageToBase64($q['header_logo']) ?: imageToBase64('assets/logo/logo.png');
    $sig1 = imageToBase64($q['signature1']);
    $sig2 = imageToBase64($q['signature2']);
    $sig3 = imageToBase64($q['signature3']);
    $qr_code = imageToBase64($q['qr_code_image']);
    
    $fontRegular = str_replace('\\', '/', realpath(__DIR__ . '/../assets/fonts/Sarabun-Regular.ttf'));
    $fontBold = str_replace('\\', '/', realpath(__DIR__ . '/../assets/fonts/Sarabun-Bold.ttf'));
    
    $thaiAmount = thai_baht($q['grand_total']);
    $docDate = !empty($q['doc_date']) ? date('d/m/Y', strtotime($q['doc_date'])) : '-';
    
    $itemsRows = '';
    $rowNum = 1;
    foreach ($items as $item) {
        $name = htmlspecialchars($item['name'] ?? '');
        $qty = (float)($item['qty'] ?? 0);
        $unit = htmlspecialchars($item['unit'] ?? '');
        $price = (float)($item['price'] ?? 0);
        $total = $qty * $price;
        $imgBase64 = imageToBase64($item['image'] ?? '');
        $imgHtml = $imgBase64 ? '<img src="' . $imgBase64 . '" style="max-height: 35px; max-width: 45px;">' : '';
        
        $itemsRows .= '
            <tr>
                <td style="text-align: center;">' . $rowNum . '</td>
                <td style="text-align: center;">' . $imgHtml . '</td>
                <td>' . nl2br($name) . '</td>
                <td style="text-align: center;">' . number_format($qty, 2) . '</td>
                <td style="text-align: center;">' . $unit . '</td>
                <td style="text-align: right;">' . number_format($price, 2) . '</td>
                <td style="text-align: right;">' . number_format($total, 2) . '</td>
            </tr>
        ';
        $rowNum++;
    }
    
    $html = '
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <style>
            @font-face {
                font-family: "Sarabun";
                src: url("' . $fontRegular . '") format("truetype");
                font-weight: normal;
                font-style: normal;
            }
            @font-face {
                font-family: "Sarabun";
                src: url("' . $fontBold . '") format("truetype");
                font-weight: bold;
                font-style: normal;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            body {
                font-family: "Sarabun", sans-serif;
                font-size: 11px;
                line-height: 1.3;
                color: #000;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            .header-table td {
                vertical-align: top;
            }
            .items-table th, .items-table td {
                border: 1px solid #000;
                padding: 4px;
            }
            .items-table th {
                background-color: #92d050;
                font-weight: bold;
                text-align: center;
            }
            .summary-table td {
                border: 1px solid #000;
                padding: 4px;
            }
            .bg-green {
                background-color: #92d050;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .bold { font-weight: bold; }
        </style>
    </head>
    <body>
        <table class="header-table" style="margin-bottom: 10px;">
            <tr>
                <td style="width: 20%;">
                    ' . ($header_logo ? '<img src="' . $header_logo . '" style="width: 100px; height: auto;">' : '') . '
                </td>
                <td style="width: 55%; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold;">' . htmlspecialchars($q['header_name']) . '</div>
                    <div>' . htmlspecialchars($q['header_address']) . '</div>
                    <div>โทร. ' . htmlspecialchars($q['header_phone']) . '</div>
                    <div>เลขที่ประจำตัวผู้เสียภาษี ' . htmlspecialchars($q['header_tax_id']) . '</div>
                </td>
                <td style="width: 25%; text-align: right;">
                    <div style="font-size: 16px; font-weight: bold;">ใบเสนอราคา</div>
                </td>
            </tr>
        </table>

        <table style="margin-bottom: 10px; font-size: 11px;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div><strong>ชื่อ :</strong> ' . htmlspecialchars($q['customer_name'] ?: '-') . '</div>
                    <div><strong>ที่อยู่ :</strong> ' . htmlspecialchars($q['customer_address'] ?: '-') . '</div>
                    <div><strong>โทรศัพท์ :</strong> ' . htmlspecialchars($q['customer_phone'] ?: '-') . ' &nbsp;&nbsp; <strong>รหัสผู้เสียภาษี :</strong> ' . htmlspecialchars($q['customer_tax_id'] ?: '-') . '</div>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right;">
                    <div><strong>เลขที่ :</strong> ' . htmlspecialchars($q['doc_number']) . '</div>
                    <div><strong>วันที่ยื่น :</strong> ' . $docDate . '</div>
                    <div><strong>ระยะเวลา :</strong> ' . htmlspecialchars($q['delivery_time'] ?: '-') . '</div>
                    <div><strong>เงื่อนไขการชำระ :</strong> ' . htmlspecialchars($q['payment_terms'] ?: '-') . '</div>
                </td>
            </tr>
        </table>

        <table class="items-table" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 30px;">ลำดับ</th>
                    <th style="width: 50px;">รูปภาพ</th>
                    <th>รายการ</th>
                    <th style="width: 50px;">จำนวน</th>
                    <th style="width: 50px;">หน่วยนับ</th>
                    <th style="width: 70px;">ราคา/หน่วย</th>
                    <th style="width: 80px;">รวมเป็นเงิน</th>
                </tr>
            </thead>
            <tbody>
                ' . $itemsRows . '
            </tbody>
        </table>

        <table class="summary-table" style="margin-top: -1px;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div class="bold">หมายเหตุ:</div>
                    <div>' . nl2br(htmlspecialchars($q['notes'] ?: '-')) . '</div>
                    <div class="text-center bold" style="margin-top: 10px; font-size: 12px;">( ' . $thaiAmount . ' )</div>
                </td>
                <td style="width: 40%; padding: 0; vertical-align: top;">
                    <table style="width: 100%;">
                        <tr class="bg-green">
                            <td style="border: none; border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 4px;">มูลค่ารวมก่อนเสียภาษี</td>
                            <td style="border: none; border-bottom: 1px solid #000; text-align: right; padding: 4px;">' . number_format($q['grand_total'] - $q['vat_amount'], 2) . '</td>
                        </tr>
                        <tr class="bg-green">
                            <td style="border: none; border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 4px;">ภาษีมูลค่าเพิ่ม(VAT)</td>
                            <td style="border: none; border-bottom: 1px solid #000; text-align: right; padding: 4px;">' . number_format($q['vat_amount'], 2) . '</td>
                        </tr>
                        <tr class="bg-green bold">
                            <td style="border: none; border-right: 1px solid #000; padding: 4px;">ยอดเงินสุทธิ</td>
                            <td style="border: none; text-align: right; padding: 4px;">' . number_format($q['grand_total'], 2) . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="margin-top: 5px; font-size: 10px;">
            <strong>เงื่อนไข:</strong> ' . nl2br(htmlspecialchars($q['conditions'] ?: '-')) . '
        </div>

        <table style="margin-top: 20px; width: 100%;">
            <tr>
                <td style="width: 30%; vertical-align: bottom;">
                    ' . ($qr_code ? '<img src="' . $qr_code . '" style="width: 100px;">' : '
                    <div style="border: 1px solid #ccc; padding: 5px; text-align: center; font-size: 9px; background: #fafafa;">
                        <div class="bold" style="color: #059669;">ธนาคารกสิกรไทย</div>
                        <div class="bold">197-1-29205-5</div>
                        <div>บจก. บ้านสักทอง โรงยาง</div>
                    </div>
                    ') . '
                </td>
                <td style="width: 35%; text-align: center; vertical-align: bottom;">
                    <div>ผู้เสนอราคา</div>
                    <div style="height: 50px;">
                        ' . ($sig1 ? '<img src="' . $sig1 . '" style="height: 45px;">' : '') . '
                    </div>
                    <div style="border-bottom: 1px dotted #000; width: 130px; margin: 0 auto;"></div>
                    <div style="margin-top: 3px;">' . htmlspecialchars($q['signer_name1']) . '</div>
                </td>
                <td style="width: 35%; text-align: center; vertical-align: bottom;">
                    <div>ผู้รับ</div>
                    <div style="height: 50px;">
                        ' . ($sig3 ? '<img src="' . $sig3 . '" style="height: 45px;">' : '') . '
                    </div>
                    <div style="border-bottom: 1px dotted #000; width: 130px; margin: 0 auto;"></div>
                    <div style="margin-top: 3px;">' . htmlspecialchars($q['signer_name3']) . '</div>
                </td>
            </tr>
        </table>

        <div class="bg-green text-center bold" style="padding: 4px; margin-top: 15px; font-size: 11px;">
            BANSAKTHONG RONGYANG CO., LTD.
        </div>
    </body>
    </html>
    ';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('chroot', __DIR__ . '/..');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $fileName = 'quotation_' . ($q['doc_number'] ?: 'document') . '.pdf';
    $dompdf->stream($fileName, ['Attachment' => false]);
    exit;
}
?>
