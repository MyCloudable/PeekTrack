<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the selected option value from the POST data
    $selectedCrew = $_POST["selectedOption"];

    // Set the session variable with the selected option value
    $_SESSION["selectedCrew"] = $selectedCrew;

    // Send a response back to the client (if needed)
    echo "Crew set to: " . $_SESSION["selectedCrew"];
}
?>