<?php
// Database connection
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

// Check if the user provided a tag input
if (isset($_GET['tag'])) {
    $tag = $_GET['tag'];
    
    // Query to fetch tags from the Docs_Tags table that match the input
    $sql = "SELECT DISTINCT Nom FROM Docs_Tags WHERE Nom LIKE ? LIMIT 10";
    $stmt = $conn->prepare($sql);
    $likeTag = "%" . $tag . "%"; // Prepare the LIKE pattern
    $stmt->bind_param("s", $likeTag);
    $stmt->execute();
    $result = $stmt->get_result();

    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['Nom']; // Add matching tags to the array
    }

    // Return the tags as a JSON response
    echo json_encode($tags);
}

// Close the connection
$conn->close();
?>

