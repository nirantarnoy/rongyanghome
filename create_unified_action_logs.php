<?php
require 'config.php';

// Create unified action_logs table for all modules
$sql = "CREATE TABLE IF NOT EXISTS action_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    module VARCHAR(50) NOT NULL COMMENT 'stock, quotation, project, etc.',
    activity TEXT NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'view') NOT NULL,
    reference_id INT DEFAULT NULL COMMENT 'ID of the affected record',
    year INT NOT NULL DEFAULT 2026,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_module (module),
    INDEX idx_year (year),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'action_logs' created successfully\n";
    
    // Migrate existing stock_action_logs if exists
    $migrate_sql = "INSERT INTO action_logs (company_id, user_id, module, activity, action_type, created_at)
                    SELECT company_id, user_id, 'stock' as module, activity, action_type, created_at 
                    FROM stock_action_logs 
                    WHERE NOT EXISTS (
                        SELECT 1 FROM action_logs 
                        WHERE action_logs.company_id = stock_action_logs.company_id 
                        AND action_logs.created_at = stock_action_logs.created_at
                    )";
    
    if (mysqli_query($conn, $migrate_sql)) {
        echo "✅ Migrated existing stock logs to unified action_logs\n";
    }
} else {
    echo "❌ Error creating table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
