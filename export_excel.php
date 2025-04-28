<?php
// Ensure no output before headers
ob_start();
require 'db.php';
ob_end_clean();

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
            
            // Clear any previous output
            if (ob_get_level()) ob_end_clean();
            
            // Set headers for CSV download
            header("Content-Type: text/csv");
            header("Content-Disposition: attachment; filename=" . str_replace(' ', '_', $name) . "_coordinates.csv");
            header("Cache-Control: max-age=0");
            
            // Output CSV data
            $output = fopen("php://output", "w");
            
            // Write headers
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
    // Clear any previous output
    if (ob_get_level()) ob_end_clean();
    
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
    <!-- Rest of your error HTML -->';
    exit;
}
?>