<?php
function getProductionData($conn) {
    $sql = "
        SELECT 
            p.MACHINENO,
            p.PRODID,
            p.ITEMID,
            p.QTY_PER_DAY,
            p.SHIFT,
            p.PROD_TRANS_DATE,
            ISNULL(SUM(r.QTY), 0) AS RECEIVED_QTY
        FROM USRN_PTB_PLAN p
        LEFT JOIN USRN_PTB_REC_TEMP r
            ON p.PRODID = r.prod_id
            AND p.MACHINENO = r.machine_no
            AND CAST(r.trans_date AS DATE) = CAST(GETDATE() AS DATE)
            AND p.SHIFT = r.shift
        WHERE CAST(p.PROD_TRANS_DATE AS DATE) = CAST(GETDATE() AS DATE) AND p.PRODID != ''
        GROUP BY p.MACHINENO, p.PRODID,p.ITEMID, p.QTY_PER_DAY,p.SHIFT,p.PROD_TRANS_DATE
        ORDER BY p.MACHINENO,p.PROD_TRANS_DATE
    ";

    $stmt = sqlsrv_query($conn, $sql);
    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}

function getMaxID($conn) {
    $sql = "SELECT MAX(RECID) AS max_id FROM USRN_WO_PTBM_RECEIVE";
    $stmt = sqlsrv_query($conn, $sql);

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row['max_id'] ? ($row['max_id'] + 1) : 1;
    }
    return 1;
}

function actionPosted($conn, $conn_req, $date = null, $machineNo = null, $itemId = null) {
    // ถ้าไม่ส่งวันที่มา ใช้วันที่ปัจจุบัน
    if (empty($date)) {
        $find_date = date('Y-m-d');
    } else {
        // แปลงจาก Y-m-d หรือ d/m/Y
        if (strpos($date, '/') !== false) {
            $a = explode('/', $date);
            $find_date = $a[2] . '-' . $a[1] . '-' . $a[0];
        } else {
            $find_date = $date;
        }
    }

    // สร้าง WHERE clause แบบ dynamic
    $where_conditions = ["CONVERT(DATE, REC_TRANS_DATE) = ?", "POSTED = 0"];
    $params = [$find_date];

    if (!empty($machineNo)) {
        $where_conditions[] = "MACHINE_NO = ?";
        $params[] = $machineNo;
    }

    if (!empty($itemId)) {
        $where_conditions[] = "ITEM_ID = ?";
        $params[] = $itemId;
    }

    $where_sql = implode(' AND ', $where_conditions);

    // Query data from USRN_PTB_REC_NEW
    $sql = "SELECT * FROM USRN_PTB_REC_NEW WHERE " . $where_sql;

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo "<div style='color:red;'>SQL Error: " . print_r(sqlsrv_errors(), true) . "</div>";
        return false;
    }

    $hasRows = false;
    $insertCount = 0;

    while ($value = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $hasRows = true;
        $recid = getMaxID($conn);

        $sql_insert = "INSERT INTO USRN_WO_PTBM_RECEIVE(TRANS_DATE, PRODID, QTY, FAIL_CODE, LINE_STATUS, LINE_NUM, TRANS_SHIFT, ITEMID, QC_STATUS, WORKER, WCID, RECID, DATAAREAID, POSTED)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $worker = $value['EMP_NO'] ?? '';
        $line_num = 0;
        $dataareaid = 'cic';
        $posted = 0;
        $wcid = 'PTBM';
        $trans_date = ($value['REC_TRANS_DATE'] instanceof DateTime)
            ? $value['REC_TRANS_DATE']->format('Y-m-d')
            : date('Y-m-d', strtotime($value['REC_TRANS_DATE']));

        $insert_params = array(
            $trans_date,
            $value['PRODID'],
            $value['QTY'],
            '',
            0,
            $line_num,
            $value['SHIFT'],
            $value['ITEM_ID'],
            0,
            $worker,
            $wcid,
            $recid,
            $dataareaid,
            $posted
        );

        $stmt_insert = sqlsrv_query($conn, $sql_insert, $insert_params);

        if ($stmt_insert) {
            $update_sql = "UPDATE USRN_PTB_REC_NEW SET POSTED = 1, LINE_STATUS = 1 WHERE id = ?";
            $stmt_update = sqlsrv_query($conn, $update_sql, array($value['ID']));
            sqlsrv_free_stmt($stmt_update);
            sqlsrv_free_stmt($stmt_insert);
            $insertCount++;

            // Update Stock (PTBM)
            $stock_dept = 'PTBM';
            $stock_item = $value['ITEM_ID'];
            $stock_qty = $value['QTY'];
            
            // Check Stock
            $sql_check_stock = "SELECT ID FROM USRN_REQ_STOCK WHERE DEPT = ? AND ITEM_ID = ?";
            $stmt_check_stock = sqlsrv_query($conn_req, $sql_check_stock, [$stock_dept, $stock_item]);
            
            if($stmt_check_stock && sqlsrv_has_rows($stmt_check_stock)) {
                $sql_upd_stock = "UPDATE USRN_REQ_STOCK SET QTY = QTY + ?, LAST_UPDATED = GETDATE() WHERE DEPT = ? AND ITEM_ID = ?";
                sqlsrv_query($conn_req, $sql_upd_stock, [$stock_qty, $stock_dept, $stock_item]);
            } else {
                $sql_ins_stock = "INSERT INTO USRN_REQ_STOCK (DEPT, ITEM_ID, QTY, UNIT, LAST_UPDATED) VALUES (?, ?, ?, 'PCS', GETDATE())";
                sqlsrv_query($conn_req, $sql_ins_stock, [$stock_dept, $stock_item, $stock_qty]);
            }
        }
    }

    sqlsrv_free_stmt($stmt);

    if ($hasRows) {
        echo "<div style='color:green;'>✅ บันทึกรายการเรียบร้อย (จำนวน $insertCount รายการ)</div>";
        return true;
    } else {
        echo "<div style='color:red;'>⚠️ ไม่พบข้อมูลที่ต้องการส่ง</div>";
        return false;
    }

}

function getAllmachine($conn, $start_date = null, $end_date = null) {
    // Default to today if no date provided
    if ($start_date === null) $start_date = date('Y-m-d');
    if ($end_date === null) $end_date = date('Y-m-d');

    // Format for SQL (Start of day to End of day)
    $start_ts = $start_date . ' 00:00:00';
    $end_ts = $end_date . ' 23:59:59';

    // 1. Get Production Data & Last Production Time
    $sql = "
        SELECT 
            cs.LINEID,
            SUM(tmp.QTY) AS QTY,
            MAX(tmp.TRANS_DATE) AS LAST_PROD
        FROM CIC_STDLINE cs
        LEFT JOIN USRN_PTB_REC_TEMP tmp 
            ON tmp.MACHINE_NO = cs.LINEID
            AND tmp.TRANS_DATE BETWEEN ? AND ?
        WHERE cs.WCID = 'PTBM'
        GROUP BY 
            cs.LINEID
        ORDER BY cs.LINEID ASC
    ";

    $params = [$start_ts, $end_ts];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $data = [];
    $current_time = time();

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($row["QTY"] === null) {
            $row["QTY"] = 0;
        }

        $machine_no = $row['LINEID'];
        $last_prod_ts = $row['LAST_PROD'] ? $row['LAST_PROD']->getTimestamp() : 0;
        
        // Default status
        $status = "error"; // Stopped

        // Check Pause Status
        // We need to check if the machine is currently in a pause state that covers the current time
        // OR if there was a pause that covers the gap if we are checking history, but here we check 'Current Status'.
        // So we just check if there is a active pause or a pause that ended very recently?
        // Actually, user said: "Check from latest product sent > 10 mins ... WITHOUT overlapping with production pause".
        // This implies we check the GAP.
        
        $is_paused = false;
        
        // Check if there is a pause record that covers the period from LastProdTime to Now
        // Or simply, is the machine currently paused?
        // Let's check if there is a pause record where (START <= NOW AND (END >= NOW OR END IS NULL))
        // But USRN_PTB_PROD_PAUSE_TRANS usually has TRANS_DATE (Start) and PAUSE_DURATION.
        // So End = Start + Duration.
        
        $sql_pause = "SELECT TOP 1 * FROM USRN_PTB_PROD_PAUSE_TRANS 
                      WHERE MACHINE_NO = ? 
                      AND CAST(TRANS_DATE AS DATE) = CAST(GETDATE() AS DATE)
                      ORDER BY TRANS_DATE DESC";
        $stmt_pause = sqlsrv_query($conn, $sql_pause, [$machine_no]);
        
        if ($stmt_pause && $row_pause = sqlsrv_fetch_array($stmt_pause, SQLSRV_FETCH_ASSOC)) {
            $pause_start = $row_pause['TRANS_DATE']->getTimestamp();
            $pause_duration = $row_pause['PAUSE_DURATION']; // Seconds
            $pause_end = $pause_start + $pause_duration;
            
            // If the latest pause covers the current time (or close to it)
            if ($pause_end >= $current_time) {
                $is_paused = true;
            }
            // Or if the pause started AFTER the last production and covers the gap?
            // If LastProd < PauseStart, and Pause covers the rest?
        }

        // Logic:
        // If (Now - LastProd) > 10 mins (600 sec)
        //    If NOT Paused -> STOP
        //    If Paused -> RUNNING (or Paused, but user implies 'Running' as 'Not Stop')
        // Else -> RUNNING
        
        $time_diff = $current_time - $last_prod_ts;
        
        if ($row['QTY'] > 0) {
            if ($time_diff > 600) {
                if ($is_paused) {
                    // Check if reason indicates maintenance
                    $reason_lower = strtolower($row_pause['REASON'] ?? '');
                    if (strpos($reason_lower, 'repair') !== false || strpos($reason_lower, 'maintenance') !== false || strpos($reason_lower, 'ซ่อม') !== false) {
                        $status = "maintenance";
                    } else {
                        $status = "running"; // Active Wait / Planned Stop
                    }
                } else {
                    $status = "error"; // Stop (Idle > 10 mins and not paused)
                }
            } else {
                $status = "running";
            }
        } else {
            // No qty today
             if ($is_paused) {
                $reason_lower = strtolower($row_pause['REASON'] ?? '');
                if (strpos($reason_lower, 'repair') !== false || strpos($reason_lower, 'maintenance') !== false || strpos($reason_lower, 'ซ่อม') !== false) {
                    $status = "maintenance";
                } else {
                    $status = "error"; 
                }
            } else {
                $status = "error";
            }
        }

        $row["status"] = $status;
        $data[] = $row;
    }

    return $data;
}

function getBymachine($conn,$machine_no) {
    $sql = "
        SELECT 
            cs.LINEID,
            tmp.EMP_NO,
            wk.NAME AS EMP_NAME,
            SUM(tmp.QTY) AS QTY
        FROM CIC_STDLINE cs
        LEFT JOIN USRN_PTB_REC_TEMP tmp 
            ON tmp.MACHINE_NO = cs.LINEID
            AND CONVERT(DATE, tmp.TRANS_DATE) = CONVERT(DATE, GETDATE())
        LEFT JOIN USRN_WorkerUser wk 
            ON tmp.EMP_NO = wk.PERSONNELNUMBER
        WHERE cs.LINEID='" . $machine_no . "'
        GROUP BY 
            cs.LINEID, 
            tmp.EMP_NO, 
            wk.NAME
        ORDER BY cs.LINEID ASC
    ";

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // ถ้าไม่มีผลผลิตให้ QTY = 0
        if ($row["QTY"] === null) {
            $row["QTY"] = 0;
        }
        if($row["QTY"] > 0){
            $row["status"] = "running";
        }else{
            $row["status"] = "error";
        }

        $data[] = $row;
    }

    return $data;
}


function getMachineStandard($conn, $machine_no) {
    $sql = "SELECT TOP 1 * FROM USRN_MACHINE_STANDARD 
            WHERE machine_no = ? 
            AND (end_date IS NULL OR end_date >= CAST(GETDATE() AS DATE))
            ORDER BY start_date DESC";
    $params = [$machine_no];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row;
    }
    return null;
}

function getMachineDowntime($conn, $machine_no) {
    $sql = "SELECT SUM(PAUSE_DURATION) AS total_seconds, COUNT(ID) as count_times
            FROM USRN_PTB_PROD_PAUSE_TRANS
            WHERE MACHINE_NO = ? 
            AND CAST(TRANS_DATE AS DATE) = CAST(GETDATE() AS DATE)";
    $params = [$machine_no];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row;
    }
    return ['total_seconds' => 0, 'count_times' => 0];
}

function getMachineDowntimeLog($conn, $machine_no) {
    $sql = "SELECT *
            FROM USRN_PTB_PROD_PAUSE_TRANS
            WHERE MACHINE_NO = ? 
            AND CAST(TRANS_DATE AS DATE) = CAST(GETDATE() AS DATE)
            ORDER BY TRANS_DATE DESC";
    $params = [$machine_no];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}

function getMachineCurrentTasks($conn, $machine_no) {
    $sql = "SELECT 
                t.MACHINE_NO,
                t.PROD_ID,
                (SELECT TOP 1 ITEMID FROM USRN_PTB_PLAN WHERE PRODID = t.PROD_ID) as ITEMID,
                SUM(t.QTY) as QTY,
                t.EMP_NO,
                MAX(u.NAME) as EMP_NAME
            FROM USRN_PTB_REC_TEMP t
            LEFT JOIN USRN_WorkerUser u ON t.EMP_NO = u.PERSONNELNUMBER
            WHERE t.MACHINE_NO = ? 
            AND CAST(t.TRANS_DATE AS DATE) = CAST(GETDATE() AS DATE)
            GROUP BY t.MACHINE_NO, t.PROD_ID, t.EMP_NO
            ORDER BY MAX(t.TRANS_DATE) ASC";
            
    $params = [$machine_no];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $data = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
    }
    return $data;
}

function getMachineHistory($conn, $machine_no, $start_date, $end_date) {
    $sql = "SELECT 
                MAX(r.REC_TRANS_DATE) as REC_TRANS_DATE,
                r.PRODID,
                r.ITEM_ID,
                MAX(r.QTY) as QTY,
                r.SHIFT,
                r.EMP_NO,
                u.NAME as EMP_NAME,
                r.POSTED
            FROM USRN_PTB_REC_NEW r
            LEFT JOIN USRN_WorkerUser u ON r.EMP_NO = u.PERSONNELNUMBER
            WHERE r.MACHINE_NO = ? 
            AND CAST(r.REC_TRANS_DATE AS DATE) BETWEEN ? AND ?
            GROUP BY 
                r.PRODID, 
                r.ITEM_ID, 
                r.SHIFT, 
                r.EMP_NO, 
                u.NAME, 
                r.POSTED, 
                CAST(r.REC_TRANS_DATE AS DATE), 
                DATEPART(HOUR, r.REC_TRANS_DATE), 
                DATEPART(MINUTE, r.REC_TRANS_DATE)
            ORDER BY MAX(r.REC_TRANS_DATE) DESC";
          
    $params = [$machine_no, $start_date, $end_date];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo "SQL Error (getMachineHistory): " . print_r(sqlsrv_errors(), true);
        return [];
    }
    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }
    
    if (empty($data)) {
        // Debug output (only visible if no data found)
        echo "<!-- Debug getMachineHistory: No data found for Machine: $machine_no, Start: $start_date, End: $end_date -->";
    }
    
    return $data;
}

function getEmployeeInfo($conn, $emp_id) {
    $sql = "SELECT * FROM USRN_WorkerUser WHERE PERSONNELNUMBER = ?";
    $params = [$emp_id];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row;
    }
    return null;
}

function getEmployeeCurrentTasks($conn, $emp_id) {
    $sql = "SELECT 
                t.MACHINE_NO,
                t.PROD_ID,
                (SELECT TOP 1 ITEMID FROM USRN_PTB_PLAN WHERE PRODID = t.PROD_ID) as ITEMID,
                SUM(t.QTY) as QTY,
                t.SHIFT
            FROM USRN_PTB_REC_TEMP t
            WHERE t.EMP_NO = ? 
            AND CAST(t.TRANS_DATE AS DATE) = CAST(GETDATE() AS DATE)
            GROUP BY t.MACHINE_NO, t.PROD_ID, t.SHIFT";
            
    $params = [$emp_id];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $data = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
    }
    return $data;
}

function getEmployeeHistory($conn, $emp_id, $start_date, $end_date) {
    $sql = "SELECT 
                r.TRANS_DATE AS REC_TRANS_DATE,
                r.PROD_ID AS PRODID,
                r.ITEM_ID,
                r.QTY,
                r.SHIFT,
                r.MACHINE_NO,
                r.IS_FINISHED AS POSTED
            FROM USRN_PTB_REC_TEMP r
            WHERE r.EMP_NO = ? 
            AND CAST(r.TRANS_DATE AS DATE) >= ? 
            AND CAST(r.TRANS_DATE AS DATE) <= ?
            ORDER BY r.TRANS_DATE DESC";
            
    $params = [$emp_id, $start_date, $end_date];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $data = [];
    if ($stmt === false) {
        echo "SQL Error (getEmployeeHistory): " . print_r(sqlsrv_errors(), true);
        return [];
    }
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}

function getEmployeeFirstLastTime($conn, $emp_id, $start_date, $end_date) {
    // User requested to pull Start Time from USRN_PTB_REC_TEMP
    $first_time = null;
    $last_time = null;

    // 1. Try to get First Time from USRN_PTB_REC_TEMP
    $sql_temp = "SELECT MIN(TRANS_DATE) as first_prod, MAX(TRANS_DATE) as last_prod 
                 FROM USRN_PTB_REC_TEMP 
                 WHERE EMP_NO = ? 
                 AND TRANS_DATE >= ? AND TRANS_DATE <= ?";
    $stmt_temp = sqlsrv_query($conn, $sql_temp, [$emp_id, $start_date, $end_date]);
    if ($stmt_temp && $row_temp = sqlsrv_fetch_array($stmt_temp, SQLSRV_FETCH_ASSOC)) {
        $first_time = $row_temp['first_prod'];
        $last_time = $row_temp['last_prod'];
    }

    // 2. If not found in TEMP (or to complement), check USRN_PTB_REC_NEW
    // We want the absolute earliest and latest across both tables if possible, 
    // but user emphasized TEMP for start. Let's check NEW as well to be comprehensive
    // unless TEMP is guaranteed to have the start log.
    // If TEMP is empty (data moved), we MUST check NEW.
    
    $sql_new = "SELECT MIN(REC_TRANS_DATE) as first_prod, MAX(REC_TRANS_DATE) as last_prod 
                FROM USRN_PTB_REC_NEW 
                WHERE EMP_NO = ? 
                AND REC_TRANS_DATE >= ? AND REC_TRANS_DATE <= ?";
    $stmt_new = sqlsrv_query($conn, $sql_new, [$emp_id, $start_date, $end_date]);
    if ($stmt_new && $row_new = sqlsrv_fetch_array($stmt_new, SQLSRV_FETCH_ASSOC)) {
        $new_first = $row_new['first_prod'];
        $new_last = $row_new['last_prod'];

        // Compare and take the earliest/latest
        if ($first_time === null || ($new_first !== null && $new_first < $first_time)) {
            $first_time = $new_first;
        }
        if ($last_time === null || ($new_last !== null && $new_last > $last_time)) {
            $last_time = $new_last;
        }
    }

    return ['first' => $first_time, 'last' => $last_time];
}

function getMachineQCStats($conn, $machine_no) {
    $sql = "SELECT 
                COUNT(*) as total_checks,
                SUM(CAST(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END AS INT)) as passed_checks,
                SUM(CAST(CASE WHEN is_passed = 0 THEN 1 ELSE 0 END AS INT)) as failed_checks
            FROM USRN_PTB_QC_LOG
            WHERE machine_no = ? 
            AND CAST(trans_date AS DATE) = CAST(GETDATE() AS DATE)";
            
    $params = [$machine_no];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row;
    }
    return ['total_checks' => 0, 'passed_checks' => 0, 'failed_checks' => 0];
}

function getMachineCurrentStatus($conn, $machine_no) {
    // 1. Check Last Production Time
    $sql_last_prod = "SELECT MAX(TRANS_DATE) as last_prod FROM USRN_PTB_REC_TEMP WHERE MACHINE_NO = ?";
    $stmt_last = sqlsrv_query($conn, $sql_last_prod, [$machine_no]);
    $last_prod_ts = 0;
    if ($stmt_last && $row = sqlsrv_fetch_array($stmt_last, SQLSRV_FETCH_ASSOC)) {
        if ($row['last_prod']) {
            $last_prod_ts = $row['last_prod']->getTimestamp();
        }
    }

    // 2. Check Active Pause/Downtime
    $current_time = time();
    $is_paused = false;
    $pause_reason = '';
    
    $sql_pause = "SELECT TOP 1 * FROM USRN_PTB_PROD_PAUSE_TRANS 
                  WHERE MACHINE_NO = ? 
                  AND CAST(TRANS_DATE AS DATE) = CAST(GETDATE() AS DATE)
                  ORDER BY TRANS_DATE DESC";
    $stmt_pause = sqlsrv_query($conn, $sql_pause, [$machine_no]);
    
    if ($stmt_pause && $row_pause = sqlsrv_fetch_array($stmt_pause, SQLSRV_FETCH_ASSOC)) {
        $pause_start = $row_pause['TRANS_DATE']->getTimestamp();
        $pause_duration = $row_pause['PAUSE_DURATION'];
        $pause_end = $pause_start + $pause_duration;
        
        if ($pause_end >= $current_time) {
            $is_paused = true;
            $pause_reason = $row_pause['REASON'];
        }
    }

    // 3. Determine Status
    $time_diff = $current_time - $last_prod_ts;
    $status = 'stopped';

    if ($last_prod_ts > 0 && $time_diff <= 600) {
        $status = 'running';
    } else {
        // Idle > 10 mins or No Prod
        if ($is_paused) {
            $reason_lower = strtolower($pause_reason);
            if (strpos($reason_lower, 'repair') !== false || strpos($reason_lower, 'maintenance') !== false || strpos($reason_lower, 'ซ่อม') !== false) {
                $status = 'maintenance';
            } else {
                $status = 'running'; // Active Wait
            }
        } else {
            $status = 'stopped';
        }
    }
    
    return $status;
}
