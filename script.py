#!/usr/bin/python3
import os
import pymysql
from datetime import datetime

# Database connection setup
def connect_to_db():
    try:
        return pymysql.connect(
            host="172.21.53.227",        # MySQL host (e.g., 'localhost')
            user="root",             # MySQL username
            password="poseidon",     # MySQL password
            database="app",          # Your database name
            #cursorclass=pymysql.cursors.DictCursor  # Return dict cursor for easy access
        )
    except pymysql.MySQLError as e:
        print(f"Error connecting to MySQL: {e}")
        raise

# Get the modification time of a file
def get_file_modification_time(file_path):
    return datetime.fromtimestamp(os.path.getmtime(file_path))

# Scan directory and import/update files
def scan_directory_and_update_db(directory_path):
    db = connect_to_db()
    cursor = db.cursor()

    # Loop through the directory to analyze one level of subfolders
    for folder_name in os.listdir(directory_path):
        folder_path = os.path.join(directory_path, folder_name)

        if os.path.isdir(folder_path):  # Check if it is a directory (not a file)
            # Loop through the files in the folder
            for file_name in os.listdir(folder_path):
                file_path = os.path.join(folder_path, file_name)

                if os.path.isfile(file_path) and file_name != "index.php":
                    # Get the last modification time of the file
                    file_mod_time = get_file_modification_time(file_path)

                    # Check if the file is already in the database
                    cursor.execute("SELECT Doc_ID,Date FROM Documents WHERE Nom = %s", (file_name))
                    result = cursor.fetchone()

                    if result:
                        # File exists in the database, check if it has been modified
                        db_id, last_modified = result
                        if file_mod_time > last_modified :
                            # File has been modified or folder name is different, update the record
                            cursor.execute("""
                                UPDATE Documents
                                SET Date = CURRENT_TIMESTAMP()
                                WHERE Doc_ID = %s
                            """, (db_id))
                            print(f"Updated {file_name} in the database with folder '{folder_name}'.")
                    else:
                        # File does not exist in the database, insert it
                        cursor.execute("""
                            INSERT INTO Documents (Nom)
                            VALUES (%s)
                        """, (file_name))
                        cursor.execute("SELECT Doc_ID FROM Documents WHERE Nom = %s", (file_name))
                        result = cursor.fetchone()
                        db_id, = result 
                        cursor.execute("""
                            INSERT INTO Categorie (Doc_ID,Nom)
                            VALUES (%s,%s)
                        """, (db_id,folder_name))
                        print(f"Added {file_name} to the database with folder '{folder_name}'.")

    # Commit changes and close the connection
    db.commit()
    cursor.close()
    db.close()

# Example usage
directory_to_scan = "/var/www/"
scan_directory_and_update_db(directory_to_scan)

