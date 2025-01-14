<?php
// Start the session
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

// Check if the user is authenticated by validating the token from the cookie
if (!isset($_COOKIE['auth_token'])) {
    header("Location: index.php");
    exit();
}

$authToken = $_COOKIE['auth_token'];

// Query the database to check if the token is valid
$sql = "SELECT ID, Niv FROM Utilisateurs WHERE token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $authToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

// Fetch the user role (niv) and store it in session
$row = $result->fetch_assoc();
$niv = $row['Niv'];
$_SESSION['Niv'] = $niv;

// Retrieve filters (tag, regex, category)
$tag = isset($_GET['tag']) ? $_GET['tag'] : '';
$regex = isset($_GET['regex']) ? $_GET['regex'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Start the SQL query
$sql = "SELECT Documents.Nom AS doc_name, Categorie.Nom AS folder_name 
        FROM Documents
        INNER JOIN Docs_Niv ON Documents.Doc_ID = Docs_Niv.Doc_ID
        INNER JOIN Categorie ON Documents.Doc_ID = Categorie.Doc_ID
        LEFT JOIN Docs_Tags ON Documents.Doc_ID = Docs_Tags.Doc_ID
        WHERE Docs_Niv.Nom = ? 
        AND (Docs_Tags.Nom LIKE ? OR ? = '') 
        AND (Documents.Nom REGEXP ? OR ? = '')";

// Add category filter only if a category is selected
if ($category != '') {
    $sql .= " AND Categorie.Nom = ?";
}

// Prepare the statement
$stmt = $conn->prepare($sql);

// Prepare parameters for binding
$tag_search = '%' . $tag . '%'; // Wildcard for partial tag matching
$param_count = 5; // At least 5 parameters are always there: Niv, tag, tag, regex, regex

// Check if category is selected and adjust parameter count
if ($category != '') {
    $param_count = 6; // Add one more for category
}

// Bind parameters dynamically based on the count
if ($param_count == 6) {
    $stmt->bind_param("ssssss", $niv, $tag_search, $tag, $regex, $regex, $category);
} else {
    $stmt->bind_param("sssss", $niv, $tag_search, $tag, $regex, $regex);
}

$stmt->execute();
$result = $stmt->get_result();

// Start the document list
echo '<div class="documents-container">';

// Display the documents based on tag, regex, and Niv
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $docName = $row['doc_name'];
        $folderName = $row['folder_name'];

        $fileUrl = "/" . $folderName . "/" . $docName;

        echo '<div class="document-item">
                <a href="' . $fileUrl . '" download class="document-link">
                    <span class="document-name">' . htmlspecialchars($docName) . '</span>
                    <span class="document-folder">(' . htmlspecialchars($folderName) . ')</span>
                </a>
              </div>';
    }
} else {
    echo '<p class="no-documents">No documents found for the selected filters.</p>';
}

echo '</div>'; // End of document list
$conn->close();
?>

