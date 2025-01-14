<?php
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

// Query to fetch categories from the Categorie table
$sql = "SELECT DISTINCT Nom FROM Categorie";
$result = $conn->query($sql);

// Prepare an array to store categories
$categories = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Add the category name to the array
        $categories[] = $row['Nom'];
    }
}

// Return categories as a JSON response
echo json_encode($categories);

// Close the connection
$conn->close();
?>

