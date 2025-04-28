<?php
require 'db.php';

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $coordinates = $_POST['coordinates'] ?? '';
        
        if (!$id || empty($coordinates)) {
            throw new Exception('Missing required fields');
        }
        
        // Validate coordinates format
        $coordPairs = explode(',', $coordinates);
        foreach ($coordPairs as $pair) {
            $parts = array_values(array_filter(explode(' ', trim($pair))));
            if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
                throw new Exception('Invalid coordinates format');
            }
        }
        
        $db = getDB();
        $wkt = "POLYGON(($coordinates))";
        
        // For MySQL, we'll skip ST_IsValid check since it's not available
        // Instead, we'll try to create the geometry directly
        try {
            $testStmt = $db->prepare("SELECT ST_GeomFromText(?)");
            $testStmt->execute([$wkt]);
        } catch (PDOException $e) {
            throw new Exception('Invalid polygon geometry: ' . $e->getMessage());
        }
        
        // Update the area
        $stmt = $db->prepare("UPDATE areas SET name = ?, geom = ST_GeomFromText(?) WHERE id = ?");
        $stmt->execute([$name, $wkt, $id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('No rows were updated - polygon may not exist');
        }
        
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Polygon updated successfully',
            'redirect' => 'index.php'
        ]);
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Handle GET request
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, ST_AsText(geom) as wkt FROM areas WHERE id = ?");
        $stmt->execute([$id]);
        $area = $stmt->fetch();
        
        if (!$area) {
            die("Area not found");
        }
        
        $coordinates = str_replace(['POLYGON((', '))'], '', $area['wkt']);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    die("Missing area ID");
}

ob_end_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Polygon Area</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.svg">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/Style.css"/>
    <link rel="stylesheet" href="assets/edit.css"/>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Edit Polygon Area</h1>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Map
            </a>
        </div>
    </header>
    
    <div class="container">
        <div id="map"></div>
        
        <div id="errorAlert" class="alert alert-danger"></div>
        
        <form id="editForm">
            <input type="hidden" name="id" value="<?= htmlspecialchars($area['id']) ?>">
            
            <div class="form-group">
                <label for="name">Area Name:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($area['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="coordinates">Coordinates (longitude latitude pairs, comma separated):</label>
                <textarea id="coordinates" name="coordinates" required><?= htmlspecialchars($coordinates) ?></textarea>
                <small>Example: 110.123 -7.123, 110.124 -7.122, 110.125 -7.123</small>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </form>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([-7.15, 110.25], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Parse coordinates from textarea
        function parseCoordinates() {
            const coordsText = document.getElementById('coordinates').value;
            const coordPairs = coordsText.split(',');
            const latLngs = [];
            
            for (const pair of coordPairs) {
                const [lng, lat] = pair.trim().split(' ');
                if (lat && lng) {
                    latLngs.push([parseFloat(lat), parseFloat(lng)]);
                }
            }
            
            return latLngs;
        }
        
        // Draw polygon from coordinates
        let polygon = null;
        function drawPolygon() {
            const latLngs = parseCoordinates();
            
            if (polygon) {
                map.removeLayer(polygon);
            }
            
            if (latLngs.length > 2) {
                polygon = L.polygon(latLngs, {
                    color: '#3498db',
                    fillOpacity: 0.4,
                    weight: 2
                }).addTo(map);
                
                map.fitBounds(polygon.getBounds());
                document.getElementById('errorAlert').style.display = 'none';
            } else if (latLngs.length > 0) {
                showError('At least 3 points are required to form a polygon');
            }
        }
        
        // Show error message
        function showError(message) {
            const errorAlert = document.getElementById('errorAlert');
            errorAlert.textContent = message;
            errorAlert.style.display = 'block';
        }
        
        // Initial draw
        drawPolygon();
        
        // Update polygon when coordinates change
        document.getElementById('coordinates').addEventListener('input', function() {
            drawPolygon();
        });
        
        // Handle form submission
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const latLngs = parseCoordinates();
            
            if (latLngs.length < 3) {
                showError('At least 3 points are required to form a polygon');
                return;
            }
            
            try {
                const response = await fetch('edit_area.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Polygon updated successfully',
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                    }).then(() => {
                        window.location.href = result.redirect || 'index.php';
                    });
                } else {
                    showError(result.message || 'Failed to update polygon');
                }
            } catch (error) {
                showError('An error occurred while saving the polygon');
            }
        });
    </script>
</body>
</html>