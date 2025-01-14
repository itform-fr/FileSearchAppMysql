<?php
// Initialize session
session_start();

// Database connection parameters
$servername = "ADDRESSIP";
$username = "root";
$password = "poseidon";
$dbname = "app";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle the form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve user input from the form
    $usernameInput = $_POST['username'];
    $passwordInput = $_POST['password'];

    // Query to check the user credentials
    $sql = "SELECT ID, Niv FROM Utilisateurs WHERE login = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usernameInput, $passwordInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch user details
        $row = $result->fetch_assoc();
        $userId = $row['ID'];
        $niv = $row['Niv'];

        // Generate a unique token (use a strong random value)
        $token = bin2hex(random_bytes(32)); // 64 characters token

        // Store the token in the database associated with the user
        $sql = "UPDATE Utilisateurs SET token = ? WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $token, $userId);
        $stmt->execute();

        // Set a cookie with the token (expires in 1 hour)
        setcookie('auth_token', $token, time() + 3600, "/", "", false, true);

        // Set session variable for niv
        $_SESSION['Niv'] = $niv;

        // Redirect based on the user's role (niv)
        header("Location: main.php?niv=" . urlencode($niv));
        exit();
    } else {
        echo "Invalid credentials.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>

    <h2>Login</h2>
    <form method="POST">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>
        
        <input type="submit" value="Login">
    </form>

</body>
</html>

