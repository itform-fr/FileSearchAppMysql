<?php
// Start the session
session_start();

// Check if the user is authenticated by validating the token from the cookie
if (!isset($_COOKIE['auth_token'])) {
    // Redirect to login page if the token is not set
    header("Location: index.php");
    exit();
}
$authToken = $_COOKIE['auth_token'];

// Connect to the MySQL database (update with your database credentials)
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
// Query the database to check if the token is valid
$sql = "SELECT ID, Niv FROM Utilisateurs WHERE token = ? AND login = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $authToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // If no valid token found, redirect to login page
    header("Location: index.php");
    exit();
}

// Handle search form submission
$searchQuery = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
	$searchQuery = trim($_POST['search']);
    if (!empty($searchQuery)) {

    // Ensure the search query is safe and valid (using a regex pattern)
    if (preg_match("/^[a-zA-Z0-9\s\-_]*$/", $searchQuery)) {
        // Prepare a SQL query using the REGEXP operator for filenames
        $sql = "SELECT Doc_ID, Nom FROM Documents WHERE Nom REGEXP ?";
        
        // Prepare statement
        if ($stmt = $conn->prepare($sql)) {
            // Bind the search query parameter
            $stmt->bind_param("s", $searchQuery);
            $stmt->execute();
            
            // Bind result variables
            $stmt->bind_result($id, $filename);

            // Fetch results
            while ($stmt->fetch()) {
                $results[] = ['ID' => $id, 'Nom' => $filename];
            }
            
            // Close statement
            $stmt->close();
        } else {
            $error_message = "Database error: " . $conn->error;
        $error_message = "Invalid search query.";
    	}
    }
    }

} else {
    // If no search is performed, fetch all files
    $sql = "SELECT Doc_ID, Nom FROM Documents";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $results[] = ['ID' => $row['Doc_ID'], 'Nom' => $row['Nom']];
    }
}


// Handle level assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' ) {
	//Level assignment
    if (isset($_POST['assign_level'])) {
        $levels = $_POST['level'] ?? [];
        $selectedFiles = $_POST['selected_files'] ?? [];

        // Validate levels
        $validLevels = ['AIS', 'TSSR', 'MASTERE'];
        foreach ($levels as $level) {
            if (!in_array($level, $validLevels)) {
                $error_message = "Invalid level.";
                break;
            }
        }

        if (!isset($error_message)) {
            // Prepare the SQL queries
            $sql_delete = "DELETE FROM Docs_Niv WHERE Doc_ID = ? AND Nom = ?"; // Delete existing levels assignment
            $sql_insert = "INSERT INTO Docs_Niv (Doc_ID, Nom) VALUES (?, ?)"; // Insert new levels assignment

            // Prepare insert statement
            $stmt_insert = $conn->prepare($sql_insert);
            if (!$stmt_insert) {
                die("Error preparing insert statement: " . $conn->error);
            }

            // Prepare delete statement
            $stmt_delete = $conn->prepare($sql_delete);
            if (!$stmt_delete) {
                die("Error preparing delete statement: " . $conn->error);
            }

            foreach ($selectedFiles as $fileId) {
                // Fetch existing levels assigned to the file
                $existingLevels = [];
                $stmt_check = $conn->prepare("SELECT Nom FROM Docs_Niv WHERE Doc_ID = ?");
                $stmt_check->bind_param("i", $fileId);
                $stmt_check->execute();
                $stmt_check->bind_result($existingLevel);
                while ($stmt_check->fetch()) {
                    $existingLevels[] = $existingLevel;
                }
                $stmt_check->close();

                // Log the existing Levels for debugging
                error_log("Existing levels for file $fileId: " . implode(', ', $existingLevels));

                // Delete levels that were selected before but are no longer selected
                foreach ($existingLevels as $existingLevel) {
                    if (!in_array($existingLevel, $levels)) {
                        // Debugging the deletion
                        error_log("Deleting level $existingLevel for file $fileId");
                        $stmt_delete->bind_param("is", $fileId, $existingLevel);
                        $stmt_delete->execute();
                        if ($stmt_delete->error) {
                            error_log("Error executing DELETE: " . $stmt_delete->error);
                        }
                    }
                }

                // Now, insert the new level assignments
                foreach ($levels as $level) {
                    if (!in_array($level, $existingLevels)) {
                        $stmt_insert->bind_param("is", $fileId, $level);
                        $stmt_insert->execute();
                    }
                }
            }

            // Close statements
            $stmt_insert->close();
            $stmt_delete->close();

            $success_message = "Levels updated successfully!";
        }
    }	
	// Tag insertion
    	if (isset($_POST['add_tags'])) {
	        $tags = $_POST['tags'] ?? '';
	        $selectedFiles = $_POST['selected_files'] ?? [];
	
	        // Insert tags into Docs_Tags
	        if (!empty($tags)) {
	            $tagsArray = explode(',', $tags);
	            $sql_tag_insert = "INSERT INTO Docs_Tags (Doc_ID, Nom) VALUES (?, ?)";
	
	            $stmt_tag_insert = $conn->prepare($sql_tag_insert);
	            foreach ($selectedFiles as $fileId) {
	                foreach ($tagsArray as $tag) {
	                    $tag = trim($tag); // Clean up spaces around the tag
	                    if (!empty($tag)) {
	                        $stmt_tag_insert->bind_param("is", $fileId, $tag);
	                        $stmt_tag_insert->execute();
	                    }
	                }
	            }
	
	            // Close the tags insert statement
	            $stmt_tag_insert->close();
	
	            $success_message = "Tags added successfully!";
	        } else {
	            $error_message = "Please enter at least one tag.";
	        }
	    }
	}
// Close the connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - File Search and Level Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f7f8fa;
        }
        .container {
            max-width: 1200px;
            margin-top: 50px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .card-header {
            background-color: #007bff;
            color: white;
            font-size: 1.5rem;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .card-body {
            padding: 30px;
            background-color: white;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .form-label {
            font-weight: 500;
        }
        .search-input {
            max-width: 400px;
        }
        .file-item {
            cursor: pointer;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
        }
        .file-item.selected-AIS {
            background-color: #007bff;
            color: white;
        }
        .file-item.selected-TSSR {
            background-color: #28a745;
            color: white;
        }
        .file-item.selected-MASTERE {
            background-color: #ffc107;
            color: black;
        }
        .level-dropdown {
            width: 250px;
        }
        .level-section, .tag-section {
            display: none;
        }
        .level-section.show, .tag-section.show {
            display: block;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            Admin Panel - File Search and Level Assignment
        </div>
        <div class="card-body">
            <!-- Search Form -->
            <form method="POST" action="admin.php">
                <div class="mb-4">
                    <label for="search" class="form-label">Search Filenames (RegEx):</label>
                    <input type="text" class="form-control search-input" id="search" name="search" value="<?= htmlspecialchars($searchQuery) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <!-- Display search results and allow file level assignment -->
            <?php if (!empty($results)): ?>
                <h3 class="mt-4">Search Results:</h3>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery) ?>">

                    <div class="mb-4">
                        <button type="button" class="btn btn-secondary" onclick="toggleSection('level')">Assign Levels</button>
                        <button type="button" class="btn btn-secondary ms-2" onclick="toggleSection('tag')">Assign Tags</button>
                    </div>

                    <!-- Level Section (Initially hidden) -->
                    <div class="level-section" id="level-section">
                        <div class="mb-4">
                            <label for="level" class="form-label">Assign to Levels:</label>
                            <select name="level[]" id="level" class="form-select level-dropdown" multiple>
                                <option value="AIS">AIS</option>
                                <option value="TSSR">TSSR</option>
                                <option value="MASTERE">MASTERE</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tag Section (Initially hidden) -->
                    <div class="tag-section" id="tag-section">
                        <div class="mb-4">
                            <label for="tags" class="form-label">Add Tags (Comma separated):</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="Enter tags for selected files">
                        </div>
                    </div>

                    <div class="list-group mt-3">
                        <?php foreach ($results as $file): ?>
                            <div class="file-item form-check" data-file-id="<?= $file['ID'] ?>" id="file-<?= $file['ID'] ?>" onclick="toggleFileSelection(this)">
                                <input type="checkbox" class="form-check-input me-3" name="selected_files[]" value="<?= $file['ID'] ?>" id="file-<?= $file['ID'] ?>-checkbox">
                                <label for="file-<?= $file['ID'] ?>-checkbox"><?= htmlspecialchars($file['Nom']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="assign_level" class="btn btn-primary mt-3">Assign Levels</button>
                    <button type="submit" name="add_tags" class="btn btn-primary mt-3">Add Tags</button>
                </form>
            <?php elseif (isset($error_message)): ?>
                <div class="alert alert-danger mt-3"><?= $error_message ?></div>
            <?php elseif (isset($success_message)): ?>
                <div class="alert alert-success mt-3"><?= $success_message ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Toggle visibility of the levels and tag sections
    function toggleSection(section) {
        const levelSection = document.getElementById('level-section');
        const tagSection = document.getElementById('tag-section');

        if (section === 'level') {
            levelSection.classList.toggle('show');
            tagSection.classList.remove('show');
        } else if (section === 'tag') {
            tagSection.classList.toggle('show');
            levelSection.classList.remove('show');
        }
    }

    // Toggle file selection for levels and tags
    function toggleFileSelection(fileElement) {
        const fileId = fileElement.getAttribute('data-file-id');
        const checkbox = document.getElementById('file-' + fileId + '-checkbox');
        checkbox.checked = !checkbox.checked;

        updateFileLevels(fileElement, checkbox.checked);
    }

    // Update file levels and tag assignments
    function updateFileLevels(fileElement, isSelected) {
        const levels = document.getElementById('level').selectedOptions;
        const fileId = fileElement.getAttribute('data-file-id');

        // Reset previous classes
        fileElement.classList.remove('selected-AIS', 'selected-TSSR', 'selected-MASTERE');

        // Loop through selected levels and add corresponding classes
        for (let option of levels) {
            const level = option.value;
            if (isSelected) {
                fileElement.classList.add(`selected-${level}`);
            } else {
                fileElement.classList.remove(`selected-${level}`);
            }
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>
</html>

