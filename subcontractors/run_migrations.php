<?php
require_once 'db.php'; // Make sure db.php path is correct for your setup

echo "<h2>Running Database Migrations...</h2>";

// 1. Create project_assigned_subcontractors table
$sql1 = "CREATE TABLE IF NOT EXISTS project_assigned_subcontractors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT DEFAULT 1,
    project_id INT,
    job_type VARCHAR(255),
    subcontractor_id INT,
    contract_amount DECIMAL(15,2) DEFAULT 0,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if(mysqli_query($conn, $sql1)) {
    echo "<p style='color:green;'>✅ Table <b>project_assigned_subcontractors</b> created or already exists.</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating <b>project_assigned_subcontractors</b>: " . mysqli_error($conn) . "</p>";
}

// 2. Create subcontractor_settings table
$sql2 = "CREATE TABLE IF NOT EXISTS subcontractor_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT DEFAULT 1,
    category VARCHAR(50) NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if(mysqli_query($conn, $sql2)) {
    echo "<p style='color:green;'>✅ Table <b>subcontractor_settings</b> created or already exists.</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating <b>subcontractor_settings</b>: " . mysqli_error($conn) . "</p>";
}

echo "<h3>Migrations Complete. You can now use the new features.</h3>";
echo "<a href='index.php'>Go back to the application</a>";
?>
