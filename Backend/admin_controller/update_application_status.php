<?php
require_once '../../Backend/connection.php';
require_once '../../Frontend/vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = $_POST['application_id'];
    $new_status = htmlspecialchars($_POST['status'], ENT_QUOTES, 'UTF-8'); // Prevent XSS

    // Update application status
    $update_sql = "UPDATE applications SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $application_id);

    if ($stmt->execute()) {
        // Fetch user_id from applications
        $query = "SELECT user_id FROM applications WHERE id = ?";
        $stmt2 = $conn->prepare($query);
        $stmt2->bind_param("i", $application_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $application = $result->fetch_assoc();

        if (!$application) {
            die("Application not found.");
        }

        $user_id = $application['user_id'];

        // Fetch user email and name
        $query = "SELECT email, first_name FROM user WHERE id = ?";
        $stmt3 = $conn->prepare($query);
        $stmt3->bind_param("i", $user_id);
        $stmt3->execute();
        $result = $stmt3->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            die("User not found.");
        }

        $user_email = $user['email'];
        $user_name = $user['first_name'];

        // Send email notification
        $mail = new PHPMailer(true);
        try {
            // Enable debugging (For Testing Only - Remove When Live)
            $mail->SMTPDebug = 2; 

            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'BarangayMSP@gmail.com';
            $mail->Password = 'xnbt fsfu bvai kpfu'; // Replace with a new App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('BarangayMSP@gmail.com', 'KALUPPA');
            $mail->addAddress($user_email, $user_name);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Application Status Update';
            $mail->Body = "Dear $user_name,<br><br>Your application status has been updated to: <strong>$new_status</strong>.<br><br>Best regards,<br>Your Team";

            // Send email
            if (!$mail->send()) {
                die("Mailer Error: " . $mail->ErrorInfo);
            }

        } catch (Exception $e) {
            die("Mailer Error: " . $mail->ErrorInfo);
        }

        // Store notification in the database
        $notification_sql = "INSERT INTO notifications (user_id, email, message, category, status) VALUES (?, ?, ?, 'application', 'unread')";
        $stmt4 = $conn->prepare($notification_sql);
        $message = "Your application status has been updated to: $new_status.";
        $stmt4->bind_param("iss", $user_id, $user_email, $message);
        $stmt4->execute();

        header("Location: /Frontend/admin dashboard/admin_scholarship.php");
        exit();
    } else {
        die("SQL error during execution: " . $stmt->error);
    }
} else {
    die("Invalid request method.");
}
?>
