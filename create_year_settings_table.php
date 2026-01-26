<?php
require 'config.php';

// Create year_settings table for storing active year per company
$sql = "CREATE TABLE IF NOT EXISTS year_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    active_year INT NOT NULL DEFAULT 2026,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_company (company_id),
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'year_settings' created successfully\n";
    
    // Insert default year for existing companies
    $insert_sql = "INSERT IGNORE INTO year_settings (company_id, active_year) 
                   SELECT id, YEAR(CURDATE()) FROM company";
    if (mysqli_query($conn, $insert_sql)) {
        echo "✅ Default year settings inserted for existing companies\n";
    }
} else {
    echo "❌ Error creating table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
