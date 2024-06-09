<?php
header('Content-Type: application/json');


// Database configuration
$servername = "localhost";
$username = "evank078_newsletter";
$password = "QAnewsletter12"; // Replace with your actual password
$dbname = "evank078_newsletter";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

$response = [];

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        
        // Server-side validation
        $emailPattern = "/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.(com|ca|net|org|edu|gov|mil|biz|info|mobi|name|aero|jobs|museum)$/";
        $specialCharPattern = "/[!#$%^&*(),?\":{}|<>]/";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($emailPattern, $email)) {
            $response = ["status" => "error", "message" => "Invalid email address."];
        } elseif (preg_match($specialCharPattern, $email)) {
            $response = ["status" => "error", "message" => "Email contains special characters."];
        } else {
            // Check if the email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM subscribers WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $response = ["status" => "error", "message" => "Email is already subscribed."];
            } else {
                // Insert the new subscriber
                $stmt = $conn->prepare("INSERT INTO subscribers (email, subscribed_at) VALUES (?, NOW())");
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                $stmt->bind_param("s", $email);

                if ($stmt->execute()) {
                    $response = ["status" => "success", "message" => "Success! Your email has been subscribed."];
                } else {
                    throw new Exception("Execute statement failed: " . $stmt->error);
                }

                $stmt->close();
            }
        }
    } else {
        $response = ["status" => "error", "message" => "Invalid request method."];
    }
} catch (Exception $e) {
    $response = ["status" => "error", "message" => "An error occurred: " . $e->getMessage()];
}

echo json_encode($response);
$conn->close();
?>