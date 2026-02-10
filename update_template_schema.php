<?php
include 'config.php';

// Update template_type enum to include 'conditions'
$show_sql = "SHOW COLUMNS FROM quotation_templates LIKE 'template_type'";
$show_res = mysqli_query($conn, $show_sql);
$row = mysqli_fetch_assoc($show_res);

if ($row) {
    $current_type = $row['Type'];
    echo "Current type: $current_type\n";
    
    // Check if 'conditions' is already in the enum
    if (strpos($current_type, "'conditions'") === false) {
        $alter_sql = "ALTER TABLE quotation_templates MODIFY COLUMN template_type ENUM('payment_terms', 'notes', 'conditions') NOT NULL";
        if (mysqli_query($conn, $alter_sql)) {
            echo "Successfully updated template_type to include 'conditions'.\n";
        } else {
            echo "Error updating template_type: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "The 'conditions' type already exists in the enum.\n";
    }
} else {
    // If the table doesn't exist, create it (just in case)
    echo "Table quotation_templates not found. Creating it...\n";
    $create_sql = "CREATE TABLE IF NOT EXISTS quotation_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        template_type ENUM('payment_terms', 'notes', 'conditions') NOT NULL,
        template_name VARCHAR(255) NOT NULL,
        template_content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_company_type (company_id, template_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (mysqli_query($conn, $create_sql)) {
        echo "Table 'quotation_templates' created successfully.\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
?>
