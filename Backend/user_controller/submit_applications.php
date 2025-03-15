<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file
ini_set("log_errors", 1);
ini_set("error_log", "../../Backend/logs/application_form_errors.log");

require_once '../../Backend/connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    error_log("User not logged in.");
    header("Location: /Frontend/index.php");
    exit();
}

// Fetch user info
$userQuery = "SELECT id, first_name, middle_name, last_name, email FROM user WHERE email = ?";
$stmt = $conn->prepare($userQuery);
if (!$stmt) {
    error_log("User fetch prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit();
}
$stmt->bind_param('s', $_SESSION['email']);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    error_log("User not found with email: " . $_SESSION['email']);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

// Assign user data
$user_id = $user['id'];
$first_name = $user['first_name'];
$middle_name = $user['middle_name'];
$last_name = $user['last_name'];
$email = $user['email'];

// Get POST data
$phone = $_POST['phone'] ?? '';
$house_number = $_POST['house_number'] ?? '';
$street = $_POST['street'] ?? '';
$barangay = $_POST['barangay'] ?? '';
$district = $_POST['district'] ?? '';
$city = $_POST['city'] ?? '';
$region = $_POST['region'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$work_id = $_POST['work_id'] ?? '';

// File upload
$uploadDir = realpath(__DIR__ . '/../../Backend/Documents/Volunteer/');
if ($uploadDir === false) {
    error_log("Invalid upload path.");
    echo json_encode(['success' => false, 'message' => 'Invalid upload path.']);
    exit();
}

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$resumeName = basename($_FILES['resume']['name']);
$uploadFile = $uploadDir . DIRECTORY_SEPARATOR . $resumeName;

// Move file
if (!move_uploaded_file($_FILES['resume']['tmp_name'], $uploadFile)) {
    error_log("File upload failed. Error Code: " . $_FILES['resume']['error']);
    echo json_encode(['success' => false, 'message' => 'File upload failed.']);
    exit();
}

// Generate custom ID: VOL-00001
$idQuery = "SELECT id FROM volunteer_application ORDER BY id DESC LIMIT 1";
$idResult = $conn->query($idQuery);

if ($idResult && $idResult->num_rows > 0) {
    $lastIdRow = $idResult->fetch_assoc();
    $lastId = $lastIdRow['id'];
    $lastNum = (int)substr($lastId, 4);
    $newId = 'VOL-' . str_pad($lastNum + 1, 5, '0', STR_PAD_LEFT);
} else {
    $newId = 'VOL-00001';
}
error_log("Generated new application ID: $newId");

// Insert query
$insertQuery = "INSERT INTO volunteer_application (
    id, work_id, user_id, first_name, middle_name, last_name, email,
    phone, house_number, street, barangay, district, city, region, postal_code, resume_path
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insertQuery);
if (!$stmt) {
    error_log("Insert prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Insert prepare failed.']);
    exit();
}

// Corrected parameter types: All strings now
$stmt->bind_param(
    'ssssssssssssssss',
    $newId,
    $work_id,
    $user_id,
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $phone,
    $house_number,
    $street,
    $barangay,
    $district,
    $city,
    $region,
    $postal_code,
    $uploadFile
);

// Execute insert
if ($stmt->execute()) {
    error_log("Application successfully inserted with ID: $newId");
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully.']);
} else {
    error_log("Application insert failed: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Insert failed.']);
}

$stmt->close();
$conn->close();
?>
