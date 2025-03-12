<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once 'connection.php';
require_once 'functions.php'; // Include the functions.php file for sendOTPByEmail
require_once '../vendor/autoload.php'; // if using composer
session_start();

$errors = [];

// Function to resend confirmation link
function resendConfirmationLink($email, $first_name, $otp) {
    $subject = "Resend: Verify Your Email";
    $message = "Click the link to verify your account: ";
    $message .= "http:/Kaluppa.online/Backend/otpverification.html?email=$email&otp=$otp"; 
    return sendOTPByEmail($email, $first_name, $otp, $subject);
}

// Login Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Debugging statement
    error_log("Login attempt for email: $email");

    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 1) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role'];

                // Debugging statement
                error_log("User role: " . $user['role']);

                if ($user['role'] === 'superadmin' || $user['role'] === 'admin') {
                    error_log("Redirecting to admin dashboard");
                    header("Location: ../Frontend/admin dashboard/admin_dashboard.php");
                    exit();
                } else if ($user['role'] === 'user' || $user['role'] === 'alumni') {
                    error_log("Redirecting to user dashboard");
                    header("Location: ../Frontend/user dashboard/user_dashboard.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Account not verified. Please check your email for the verification link.";
            }
        } else {
            $_SESSION['error'] = "Invalid password.";
        }
    } else {
        $_SESSION['error'] = "User not found.";
    }
    // Redirect to login page to show error message
    header("Location: ../Frontend/index.php");
    exit();
}

// Registration Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_first_name'], $_POST['reg_password'], $_POST['gender'], $_POST['birthday'], $_POST['google_auth_code'])) {
    $first_name = $_POST['reg_first_name'];
    $middle_name = $_POST['reg_middle_name'];
    $last_name = $_POST['reg_last_name'];
    $email = $_POST['email'];
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['confirm_password'];
    $gender = $_POST['gender'];
    $birthday = $_POST['birthday']; // Capture birthday
    $house_number = $_POST['house_number'];
    $street = $_POST['street'];
    $barangay = $_POST['barangay'];
    $district = $_POST['district'];
    $city = $_POST['city'];
    $region = $_POST['region'];
    $postal_code = $_POST['postal_code'];
    $phone = $_POST['phone'];
    $otp = generateOTP();

    // Validate password
    if (strlen($password) < 10 || strlen($password) > 16 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[@$!%*?&#^+=._-]/', $password)) {
        $_SESSION['error'] = "Password must be 10-16 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.";
        header("Location: ../Frontend/index.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: ../Frontend/index.php");
        exit();
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, password, role, gender, birthday, house_number, street, barangay, district, city, region, postal_code, phone, otp, google_auth_secret, is_verified) VALUES (?, ?, ?, ?, ?, 'user', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");

    $stmt->bind_param("ssssssssssssssssss", $first_name, $middle_name, $last_name, $email, $password_hashed, $gender, $birthday, $house_number, $street, $barangay, $district, $city, $region, $postal_code, $phone, $otp, $secret);

    if ($stmt->execute()) {
        $subject = "Verify Your Email";
        $message = "Click the link to verify your account: ";
<<<<<<< HEAD
<<<<<<< HEAD
        $message .= "http://localhost/Kaluppa/Backend/otpverification.html?email=$email&otp=$otp"; 
=======
        $message .= "http://54.151.138.43/Backend/otpverification.html?email=$email&otp=$otp"; 
>>>>>>> ff71895629d112a5d66f3a4004a43f9bd6565c92
=======
        $message .= "http:/Kaluppa.online/Backend/otpverification.html?email=$email&otp=$otp"; 
>>>>>>> cd65b962d36b99c5da6a68b74986201d10e05a5a
        $mailResult = sendOTPByEmail($email, $first_name, $otp, $subject);

        if ($mailResult === true) {
            $_SESSION['registration_success'] = 'Registration successful! Please check your email to verify your account.';
            $_SESSION['resend_email'] = $email;
            $_SESSION['resend_first_name'] = $first_name;
            $_SESSION['resend_otp'] = $otp;
            header("Location: ../Frontend/index.php"); 
            exit();
        } else {
            $_SESSION['error'] = "Error sending verification email: " . $mailResult;
            header("Location: ../Frontend/index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Error in registration. Please try again.";
        header("Location: ../Frontend/index.php");
        exit();
    }
}

// Optional: Display any errors in the session
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<div class='alert alert-danger'>$error</div>";
    }
}

// Resend confirmation link handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_confirmation'])) {
    $email = $_SESSION['resend_email'];
    $first_name = $_SESSION['resend_first_name'];
    $otp = $_SESSION['resend_otp'];

    $mailResult = resendConfirmationLink($email, $first_name, $otp);

    if ($mailResult === true) {
        $_SESSION['info'] = 'Verification email resent successfully! Please check your email.';
    } else {
        $_SESSION['error'] = "Error resending verification email: " . $mailResult;
    }
    header("Location: ../Frontend/index.php");
    exit();
}
?>
