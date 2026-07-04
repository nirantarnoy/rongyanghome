<?php
$_GET['action'] = 'project_list';
session_start();
$_SESSION['company_id'] = 1;
include 'action.php';
