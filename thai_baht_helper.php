<?php
function thai_baht($number) {
    if (!is_numeric($number)) return "ศูนย์บาทถ้วน";
    
    $number = number_format($number, 2, '.', '');
    $numbers = explode('.', $number);
    $baht = $numbers[0];
    $satang = $numbers[1];
    
    $baht_text = convert_to_thai_words($baht);
    $satang_text = convert_to_thai_words($satang);
    
    $full_text = "";
    if ($baht_text != "") {
        $full_text .= $baht_text . "บาท";
    }
    
    if ($satang_text != "" && $satang != "00") {
        $full_text .= $satang_text . "สตางค์";
    } else {
        $full_text .= "ถ้วน";
    }
    
    return $full_text == "ถ้วน" ? "ศูนย์บาทถ้วน" : $full_text;
}

function convert_to_thai_words($number) {
    $txtnum1 = array('ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า', 'สิบ');
    $txtnum2 = array('', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน');
    $number = strval(intval($number));
    $len = strlen($number);
    $res = "";
    
    for ($i = 0; $i < $len; $i++) {
        $digit = substr($number, $i, 1);
        if ($digit != '0') {
            if ($i == ($len - 1) && $digit == '1' && $len > 1) {
                $res .= 'เอ็ด';
            } elseif ($i == ($len - 2) && $digit == '2') {
                $res .= 'ยี่สิบ';
            } elseif ($i == ($len - 2) && $digit == '1') {
                $res .= 'สิบ';
            } else {
                $res .= $txtnum1[$digit] . $txtnum2[$len - $i - 1];
            }
        }
    }
    return $res;
}
?>
