<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../connection.php';

$targetDirectory = realpath(__DIR__ . "/../../Frontend/admin dashboard/templates/") . "/";
$allowedTypes = ['png', 'pdf'];

if (!is_dir($targetDirectory)) {
    mkdir($targetDirectory, 0777, true);
}

if (isset($_FILES['templateFile']) && $_FILES['templateFile']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['templateFile']['tmp_name'];
    $fileName = basename($_FILES['templateFile']['name']);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($fileExtension, $allowedTypes)) {
        $targetFilePath = $targetDirectory . $fileName;

        if (!is_writable($targetDirectory)) {
            $_SESSION['uploadMessage'] = "Target directory not writable: " . $targetDirectory;
        } elseif (move_uploaded_file($fileTmpPath, $targetFilePath)) {
            $_SESSION['uploadMessage'] = "Template uploaded successfully.";
        } else {
            $_SESSION['uploadMessage'] = "move_uploaded_file() failed. Temp: $fileTmpPath | Target: $targetFilePath";
        }
    } else {
        $_SESSION['uploadMessage'] = "Invalid file type. Only PNG and PDF allowed.";
    }
} else {
    $_SESSION['uploadMessage'] = "No file uploaded or error code: " . $_FILES['templateFile']['error'];
}

header("Location: ../../Frontend/admin dashboard/admin_certificate.php");
exit();

?>
