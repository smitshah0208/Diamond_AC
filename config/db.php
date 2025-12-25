<?php
$servername = "localhost";  // MySQL server
$username = "root";         // MySQL user
$password = "";             // MySQL password
$database = "ac_test";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    // echo "Connected successfully";
}
?>
