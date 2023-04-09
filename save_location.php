<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $location = "Latitude: $latitude, Longitude: $longitude";
    
    // Append location data to log file
    $file = 'loggps.txt';
    $current = file_get_contents($file);
    $current .= "$location\n";
    file_put_contents($file, $current);
  }
}

?>
