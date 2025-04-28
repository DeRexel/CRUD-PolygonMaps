<?php
require 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT name, ST_AsText(geom) as wkt FROM areas WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $name = $row['name'];
            $wkt = $row['wkt'];
            
            // Set headers for CSV download
            header("Content-Type: text/csv");
            header("Content-Disposition: attachment; filename=" . str_replace(' ', '_', $name) . "_coordinates.csv");
            header("Cache-Control: max-age=0");
            
            // Output CSV data
            $output = fopen("php://output", "w");
            
            // Write headers with escape character explicitly set
            fputcsv($output, ["Area Name", "WKT Format", "Latitude", "Longitude"], ',', '"', '\\');
            
            // Write the complete WKT in first row
            fputcsv($output, [$name, $wkt, "", ""], ',', '"', '\\');
            
            // Then write individual coordinates
            $coordsString = str_replace(['POLYGON((', '))'], '', $wkt);
            $points = explode(',', $coordsString);
            
            foreach ($points as $point) {
                list($lng, $lat) = explode(' ', trim($point));
                fputcsv($output, ["", "", $lat, $lng], ',', '"', '\\');
            }
            
            fclose($output);
            exit;
        } else {
            showError("Polygon not found");
        }
    } catch (PDOException $e) {
        showError("Database error: " . $e->getMessage());
    }
} else {
    showError("Missing polygon ID");
}

function showError($message) {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/x-icon" href="assets/favicon.svg">
        <title>Error</title>
        <link rel="icon" type="image/x-icon" href="favicon.svg">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
        <style>
            body {
                font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f5f7fa;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .error-container {
                text-align: center;
                padding: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 400px;
            }
            .error-icon {
                font-size: 50px;
                color: #e74c3c;
                margin-bottom: 15px;
            }
            .error-message {
                color: #333;
                margin-bottom: 20px;
            }
            .btn {
                display: inline-block;
                padding: 10px 15px;
                background-color: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                transition: background-color 0.2s;
            }
            .btn:hover {
                background-color: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="error-message">Error</h2>
            <p>'.htmlspecialchars($message).'</p>
            <a href="index.php" class="btn">
                <i class="fas fa-arrow-left"></i> Back to Map
            </a>
        </div>
    </body>
    </html>';
    exit;
}
?>