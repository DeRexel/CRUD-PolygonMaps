<?php
// Ensure no output before headers
ob_start();
require 'db.php';
ob_end_clean();

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, name, ST_AsText(geom) as wkt FROM areas");
    $areas = $stmt->fetchAll();
    
    if (empty($areas)) {
        showEmptyState();
    }
    
    // Clear any previous output
    if (ob_get_level()) ob_end_clean();
    
    // Set headers for CSV download
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=all_areas_coordinates.csv");
    header("Cache-Control: max-age=0");
    
    // Output CSV data
    $output = fopen("php://output", "w");
    
    // Write headers
    fputcsv($output, ["Area ID", "Area Name", "WKT Format", "Latitude", "Longitude"], ',', '"', '\\');
    
    foreach ($areas as $area) {
        // Write the complete WKT in first row for each area
        fputcsv($output, [
            $area['id'],
            $area['name'],
            $area['wkt'],
            "",
            ""
        ], ',', '"', '\\');
        
        // Then write individual coordinates
        $coordsString = str_replace(['POLYGON((', '))'], '', $area['wkt']);
        $points = explode(',', $coordsString);
        
        foreach ($points as $point) {
            list($lng, $lat) = explode(' ', trim($point));
            fputcsv($output, [
                "",
                "",
                "",
                $lat,
                $lng
            ], ',', '"', '\\');
        }
        
        // Add empty row between areas
        fputcsv($output, [], ',', '"', '\\');
    }
    
    fclose($output);
    exit;
} catch (PDOException $e) {
    showError("Database error: " . $e->getMessage());
}

function showEmptyState() {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>No Areas Found</title>
        <link rel="icon" type="image/x-icon" href="assets/favicon.svg">
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
            .empty-container {
                text-align: center;
                padding: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 400px;
            }
            .empty-icon {
                font-size: 50px;
                color: #ddd;
                margin-bottom: 15px;
            }
            .empty-message {
                color: #777;
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
        <div class="empty-container">
            <div class="empty-icon">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <h2 class="empty-message">No areas found</h2>
            <p>There are no polygon areas to export.</p>
            <a href="index.php" class="btn">
                <i class="fas fa-arrow-left"></i> Back to Map
            </a>
        </div>
    </body>
    </html>';
    exit;
}

function showError($message) {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <link rel="icon" type="image/x-icon" href="assets/favicon.svg">
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