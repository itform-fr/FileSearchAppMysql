<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Documents</title>
    <link rel="stylesheet" href="styles.css"> <!-- Optional CSS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery -->
    <style>
        /* Add the styles inline or use your existing styles.css */

        /* Body styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        /* Wrapper for the whole page content */
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Header for page */
        h1 {
            color: #2c3e50;
            font-size: 32px;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Search bar styling */
        #tag-search, #regex-search {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            font-size: 18px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        #tag-search:focus, #regex-search:focus {
            border-color: #3498db;
            outline: none;
        }

        /* Autocomplete dropdown */
        #tag-suggestions {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ccc;
            border-top: none;
            background-color: #fff;
            position: absolute;
            width: calc(100% - 24px);
            z-index: 10;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0 0 8px 8px;
            display: none;
        }

        #tag-suggestions li {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.3s ease;
        }

        #tag-suggestions li:hover {
            background-color: #3498db;
            color: white;
        }

        /* Category dropdown */
        #category-dropdown {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            font-size: 18px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        /* Document list section */
        .documents-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .document-item {
            background-color: #ecf0f1;
            margin-bottom: 15px;
            border-radius: 8px;
            padding: 15px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .document-link {
            text-decoration: none;
            color: #3498db;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
        }

        .document-name {
            font-weight: bold;
        }

        .document-folder {
            font-style: italic;
            color: #7f8c8d;
        }

        .document-item:hover {
            background-color: #d0e1f9;
            transform: translateY(-5px);
        }

        .no-documents {
            text-align: center;
            color: #e74c3c;
            font-size: 18px;
            padding: 20px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Search Documents</h1>
        
        <!-- Tag search bar -->
        <label for="tag-search">Search by Tag:</label>
        <input type="text" id="tag-search" name="tag-search" placeholder="Start typing a tag...">
        <ul id="tag-suggestions"></ul> <!-- Autocomplete suggestions -->
        
        <!-- Regex search bar -->
        <label for="regex-search">Search by Regex (File Name):</label>
        <input type="text" id="regex-search" name="regex-search" placeholder="Enter regex to match file names">

        <!-- Category dropdown -->
        <label for="category-dropdown">Filter by Category:</label>
        <select id="category-dropdown">
            <option value="">Select a category</option>
            <!-- Categories will be populated via AJAX -->
        </select>

        <div id="documents-list">
            <!-- List of documents will be populated here -->
        </div>
    </div>

    <script>
        $(document).ready(function(){
            // Populate category dropdown
            $.ajax({
                url: "get_categories.php", // PHP file that queries database for categories
                type: "GET",
                success: function(response) {
                    var categories = JSON.parse(response);
                    categories.forEach(function(category) {
                        $("#category-dropdown").append('<option value="' + category + '">' + category + '</option>');
                    });
                }
            });

            // Search by tag as user types in the tag search box
            $("#tag-search").on("input", function(){
                var tag = $(this).val();
                if (tag.length >= 1) {
                    $.ajax({
                        url: "search_tags.php", // PHP file that queries database for tags
                        type: "GET",
                        data: {tag: tag}, // Send user input to PHP
                        success: function(response) {
                            var suggestions = JSON.parse(response);
                            $("#tag-suggestions").empty().show();
                            suggestions.forEach(function(suggestion) {
                                $("#tag-suggestions").append('<li>' + suggestion + '</li>');
                            });
                        }
                    });
                } else {
                    $("#tag-suggestions").hide(); // Hide suggestions when no input
                }
            });

            // Add selected tag to the search bar
            $(document).on("click", "#tag-suggestions li", function(){
                $("#tag-search").val($(this).text());
                $("#tag-suggestions").hide();
                searchDocuments(); // Trigger document search by selected tag
            });
            // Search by category dropdown change
            $("#category-dropdown").change(function(){
                searchDocuments(); // Trigger document search by selected category
            });

            // Search by regex (file name) change
            $("#regex-search").on("input", function(){
                searchDocuments(); // Trigger document search by regex
            });
        });

        // Function to search documents based on tag, regex, and category
        function searchDocuments() {
            var tag = $("#tag-search").val();
            var regex = $("#regex-search").val();
            var category = $("#category-dropdown").val();

            $.ajax({
                url: "search_documents.php", // PHP file that handles searching documents
                type: "GET",
                data: {tag: tag, regex: regex, category: category}, // Send the filter data to PHP
                success: function(response) {
                    $("#documents-list").html(response); // Display search results
                }
            });
        }
    </script>

</body>
</html>

