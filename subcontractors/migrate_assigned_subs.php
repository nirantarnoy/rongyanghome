<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS project_assigned_subcontractors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT DEFAULT 1,
    project_id INT,
    job_type VARCHAR(255),
    subcontractor_id INT,
    contract_amount DECIMAL(15,2) DEFAULT 0,
    attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)) {
    echo "Table created successfully.";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
