<?php
// Ensure a valid file_id is passed
if (isset($_GET['file_id'])) {
    $fileId = $_GET['file_id'];
    
    // Connect to the database again (as we're working with a different script)
    $servername = "ADDRESSIP";
    $username = "root";
    $password = "poseidon";
    $dbname = "app";

    // Create a MySQLi connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Fetch the file information
    $sql = "SELECT Nom, Path FROM Documents WHERE Doc_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->bind_result($filename, $filePath);
    $stmt->fetch();
    $stmt->close();

    if ($filePath && file_exists($filePath)) {
        // Send the appropriate headers to force download
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=" . basename($filename));
        header("Content-Length: " . filesize($filePath));

        // Read the file and output it to the browser
        readfile($filePath);
        exit;
    } else {
        echo "File not found!";
    }

    // Close the connection
    $conn->close();
}
?>

