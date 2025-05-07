<?php
require 'db.php';  

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $method = $_POST['method'];
    
    try {
        $db = getDB();
        
        if ($method === 'dynamic') {
            $points = [];
            foreach ($_POST['points'] as $point) {
                if (!empty($point['lat']) && !empty($point['lng'])) {
                    $points[] = "{$point['lng']} {$point['lat']}";
                }
            }
            
            if (count($points) < 3) {
                throw new Exception("A polygon needs at least 3 points");
            }
            
            // Close the polygon
            $points[] = $points[0];
            $wkt = "POLYGON((" . implode(", ", $points) . "))";
        } 
        elseif ($method === 'wkt') {
            $wkt = trim($_POST['wkt']);
            if (!preg_match('/^POLYGON\(\(.*\)\)$/i', $wkt)) {
                throw new Exception("Invalid WKT format. Must start with POLYGON(( and end with ))");
            }
        } 
        elseif ($method === 'draw') {
            $coordinates = json_decode($_POST['coordinates'], true);
            if (!$coordinates || count($coordinates) < 3) {
                throw new Exception("Invalid drawn polygon");
            }
            
            // Format coordinates into WKT
            $points = [];
            foreach ($coordinates as $coord) {
                $points[] = "{$coord['lng']} {$coord['lat']}";
            }
            
            // Close the polygon
            $points[] = $points[0];
            $wkt = "POLYGON((" . implode(", ", $points) . "))";
        } 
        else {
            throw new Exception("Invalid method");
        }
        
        $stmt = $db->prepare("INSERT INTO areas (name, geom) VALUES (?, ST_GeomFromText(?))");
        $stmt->execute([$name, $wkt]);
        
        $message = [
            'type' => 'success',
            'text' => 'Polygon successfully saved! <a href="index.php">View on map</a>'
        ];
    } catch (Exception $e) {
        $message = [
            'type' => 'error',
            'text' => 'Error: ' . $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Polygon</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.svg">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <link rel="stylesheet" href="assets/Style.css" />
    <link rel="stylesheet" href="assets/insert.css" />
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Add New Polygon</h1>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Map
            </a>
        </div>
    </header>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?= $message['type'] ?>">
                <?= $message['text'] ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="polygonForm">
            <div class="card">
                <div class="form-group">
                    <label for="name">Area Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Creation Method *</label>
                    <div class="tabs">
                        <div class="tab active" data-tab="draw">Draw on Map</div>
                        <div class="tab" data-tab="dynamic">Enter Coordinates</div>
                        <div class="tab" data-tab="wkt">WKT Input</div>
                    </div>
                    
                    <input type="hidden" name="method" id="method" value="draw">
                    
                    <!-- Draw on Map Tab -->
                    <div id="draw-tab" class="tab-content active">
                        <p>Draw a polygon directly on the map:</p>
                        <div id="drawing-map" class="map-preview"></div>
                        <input type="hidden" name="coordinates" id="coordinates">
                    </div>
                    
                    <!-- Dynamic Entry Tab -->
                    <div id="dynamic-tab" class="tab-content">
                        <p>Enter coordinates manually (latitude, longitude):</p>
                        
                        <div class="point-container">
                            <div class="point-list" id="pointList">
                                <!-- Points will be added here -->
                            </div>
                            
                            <div class="form-group">
                                <button type="button" class="btn btn-primary" id="addPointBtn">
                                    <i class="fas fa-plus"></i> Add Point
                                </button>
                            </div>
                        </div>
                        
                        <div class="preview-title">Polygon Preview:</div>
                        <div id="dynamic-preview" class="map-preview"></div>
                    </div>
                    
                    <!-- WKT Input Tab -->
                    <div id="wkt-tab" class="tab-content">
                        <p>Enter Well-Known Text (WKT) format:</p>
                        <div class="form-group">
                            <textarea name="wkt" id="wkt" rows="5" placeholder="POLYGON((long1 lat1, long2 lat2, long3 lat3, long1 lat1))"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="shape-template">Predefined Shape:</label>
                            <select id="shape-template" class="form-control">
                                <option value="">-- Select a shape --</option>
                                <option value="triangle">Triangle</option>
                                <option value="square">Square</option>
                                <option value="pentagon">Pentagon</option>
                                <option value="hexagon">Hexagon</option>
                                <option value="octagon">Octagon</option>
                            </select>
                        </div>
                        
                        <div class="preview-title">Polygon Preview:</div>
                        <div id="wkt-preview" class="map-preview"></div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Polygon
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script>
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update active content
            const tabId = this.getAttribute('data-tab');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(`${tabId}-tab`).classList.add('active');
            
            // Update method value
            document.getElementById('method').value = tabId;
            
            // Initialize map if needed
            if (tabId === 'draw' && !drawingMap) {
                initDrawingMap();
            }
            else if (tabId === 'dynamic') {
                initDynamicPreview();
                updateDynamicPreview();
                
                // Penting! Render ulang peta setelah tab menjadi visible
                setTimeout(() => {
                    if (dynamicPreviewMap) {
                        dynamicPreviewMap.invalidateSize();
                        
                        // Jika polygon sudah ada, sesuaikan bounds
                        if (dynamicPolygon) {
                            dynamicPreviewMap.fitBounds(dynamicPolygon.getBounds(), {
                                padding: [50, 50],
                                maxZoom: 15
                            });
                        }
                    }
                }, 100);
            }
            else if (tabId === 'wkt') {
                initWktPreview();
                previewWKT();
                
                // Penting! Render ulang peta setelah tab menjadi visible
                setTimeout(() => {
                    if (wktPreviewMap) {
                        wktPreviewMap.invalidateSize();
                        
                        // Jika polygon sudah ada, sesuaikan bounds
                        if (wktPolygon) {
                            wktPreviewMap.fitBounds(wktPolygon.getBounds(), {
                                padding: [50, 50],
                                maxZoom: 15
                            });
                        }
                    }
                }, 100);
            }
        });
    });
    
    // Initialize drawing map
    let drawingMap, drawnItems, drawControl;
    
    function initDrawingMap() {
        drawingMap = L.map('drawing-map').setView([-2.23409, 113.89649], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(drawingMap);
        
        // Initialize the draw control
        drawnItems = new L.FeatureGroup();
        drawingMap.addLayer(drawnItems);
        
        drawControl = new L.Control.Draw({
            position: 'topright',
            draw: {
                polygon: {
                    allowIntersection: false,
                    showArea: true,
                    shapeOptions: {
                        color: '#3498db',
                        weight: 3,  // Thicker lines
                        fillOpacity: 0.6  // More opaque fill
                    }
                },
                polyline: false,
                rectangle: false,
                circle: false,
                marker: false,
                circlemarker: false
            },
            edit: {
                featureGroup: drawnItems
            }
        });
        
        drawingMap.addControl(drawControl);
        
        // Handle drawing events
        drawingMap.on(L.Draw.Event.CREATED, function(e) {
            const layer = e.layer;
            const coords = layer.getLatLngs()[0];
            
            if (coords.length < 3) {
                alert("A polygon needs at least 3 points");
                drawingMap.removeLayer(layer);
                return;
            }
            
            drawnItems.clearLayers();
            drawnItems.addLayer(layer);
            
            // Store coordinates
            const coordData = coords.map(latlng => ({
                lat: latlng.lat,
                lng: latlng.lng
            }));
            
            document.getElementById('coordinates').value = JSON.stringify(coordData);
        });
        
        drawingMap.on(L.Draw.Event.EDITED, function(e) {
            const layers = e.layers;
            layers.eachLayer(function(layer) {
                const coords = layer.getLatLngs()[0];
                const coordData = coords.map(latlng => ({
                    lat: latlng.lat,
                    lng: latlng.lng
                }));
                document.getElementById('coordinates').value = JSON.stringify(coordData);
            });
        });
        
        // Pastikan peta dirender dengan ukuran penuh
        setTimeout(() => {
            drawingMap.invalidateSize();
        }, 100);
    }
    
    // Initialize the drawing map
    initDrawingMap();
    
    // Dynamic point entry functionality
    let pointCounter = 0;
    const pointList = document.getElementById('pointList');
    let dynamicPreviewMap, dynamicPolygon;
    
    function initDynamicPreview() {
        if (!dynamicPreviewMap) {
            dynamicPreviewMap = L.map('dynamic-preview').setView([-2.23409, 113.89649], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(dynamicPreviewMap);
            
            // Tambahkan kontrol zoom
            dynamicPreviewMap.addControl(new L.Control.Zoom({
                position: 'topright'
            }));
            
            // Pastikan peta dirender ulang setelah tab diaktifkan
            setTimeout(() => {
                dynamicPreviewMap.invalidateSize();
            }, 100);
        } else {
            // Pastikan peta dirender ulang setiap kali tab diaktifkan
            dynamicPreviewMap.invalidateSize();
        }
    }
    
    function updateDynamicPreview() {
        // Inisialisasi map dahulu
        initDynamicPreview();
        
        const points = [];
        const inputs = pointList.querySelectorAll('input[type="number"]');
        
        // Loop melalui input untuk mendapatkan koordinat
        for (let i = 0; i < inputs.length; i += 2) {
            const lat = parseFloat(inputs[i].value);
            const lng = parseFloat(inputs[i+1].value);
            
            if (!isNaN(lat) && !isNaN(lng)) {
                points.push([lat, lng]);
            }
        }
        
        // Hapus polygon dan marker yang sudah ada
        if (dynamicPolygon) {
            dynamicPreviewMap.removeLayer(dynamicPolygon);
            dynamicPolygon = null;
        }
        
        dynamicPreviewMap.eachLayer(layer => {
            if (layer instanceof L.CircleMarker) {
                dynamicPreviewMap.removeLayer(layer);
            }
        });
        
        // Hanya buat polygon jika ada minimal 3 titik
        if (points.length >= 3) {
            // Buat polygon baru
            dynamicPolygon = L.polygon(points, {
                color: '#e74c3c',
                weight: 3,
                fillColor: '#e74c3c',
                fillOpacity: 0.6
            }).addTo(dynamicPreviewMap);
            
            // Tambahkan marker untuk setiap titik
            points.forEach((point, index) => {
                L.circleMarker(point, {
                    radius: 6,
                    color: '#fff',
                    weight: 1,
                    fillColor: '#e74c3c',
                    fillOpacity: 1
                }).addTo(dynamicPreviewMap)
                .bindTooltip(`Point ${index + 1}`, {permanent: false, direction: 'top'});
            });
            
            // Atur batas peta sesuai dengan polygon
            dynamicPreviewMap.fitBounds(dynamicPolygon.getBounds(), {
                padding: [50, 50], // Tambahkan padding agar polygon terlihat jelas
                maxZoom: 15        // Batasi zoom maksimal
            });
        }
    }
    
    function updateRemoveButtons() {
        document.querySelectorAll('.remove-point-btn').forEach(btn => {
            btn.disabled = pointList.children.length <= 3;
        });
    }
    
    document.getElementById('addPointBtn').addEventListener('click', function() {
        pointCounter++;
        const pointItem = document.createElement('div');
        pointItem.className = 'point-item';
        pointItem.innerHTML = `
            <input type="number" step="0.000001" name="points[${pointCounter}][lat]" placeholder="Latitude" required>
            <input type="number" step="0.000001" name="points[${pointCounter}][lng]" placeholder="Longitude" required>
            <button type="button" class="remove-point-btn" ${pointCounter < 3 ? 'disabled' : ''}>
                <i class="fas fa-trash"></i>
            </button>
        `;
        pointList.appendChild(pointItem);
        
        // Add event listeners
        pointItem.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', updateDynamicPreview);
        });
        
        pointItem.querySelector('.remove-point-btn').addEventListener('click', function() {
            if (pointList.children.length > 3) {
                pointItem.remove();
                updateRemoveButtons();
                updateDynamicPreview();
            }
        });
        
        updateRemoveButtons();
    });
    
    // Add 3 initial points with default values
    for (let i = 0; i < 3; i++) {
        document.getElementById('addPointBtn').click();
    }
    
    // Set default values for initial points
    setTimeout(() => {
        const inputs = pointList.querySelectorAll('input');
        const defaultCoords = [
            [-2.214733, 113.920188],  // Point 1
            [-2.224508, 113.903381],  // Point 2
            [-2.235292, 113.919936]   // Point 3
        ];
        
        for (let i = 0; i < 6; i += 2) {
            const pointIndex = i / 2;
            inputs[i].value = defaultCoords[pointIndex][0];   // Lat
            inputs[i+1].value = defaultCoords[pointIndex][1]; // Lng
        }
        
        // Initialize dan update preview dengan penundaan
        setTimeout(() => {
            initDynamicPreview();
            updateDynamicPreview();
            
            // Pastikan peta dirender dengan ukuran penuh
            if (dynamicPreviewMap) {
                dynamicPreviewMap.invalidateSize();
                if (dynamicPolygon) {
                    dynamicPreviewMap.fitBounds(dynamicPolygon.getBounds(), {
                        padding: [50, 50], 
                        maxZoom: 15
                    });
                }
            }
        }, 200);
    }, 100);
    
    // WKT Preview functionality
    let wktPreviewMap, wktPolygon;
    
    function initWktPreview() {
        if (!wktPreviewMap) {
            wktPreviewMap = L.map('wkt-preview').setView([-2.23409, 113.89649], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(wktPreviewMap);
            
            // Tambahkan kontrol zoom
            wktPreviewMap.addControl(new L.Control.Zoom({
                position: 'topright'
            }));
            
            setTimeout(() => {
                wktPreviewMap.invalidateSize();
            }, 100);
        } else {
            wktPreviewMap.invalidateSize();
        }
    }
    
    function previewWKT() {
        const wktText = document.getElementById('wkt').value.trim();
        
        initWktPreview();
        
        // Remove existing polygon if any
        if (wktPolygon) {
            wktPreviewMap.removeLayer(wktPolygon);
            wktPolygon = null;
            wktPreviewMap.eachLayer(layer => {
                if (layer instanceof L.CircleMarker) {
                    wktPreviewMap.removeLayer(layer);
                }
            });
        }
        
        if (!wktText.startsWith('POLYGON((') || !wktText.endsWith('))')) {
            return;
        }
        
        try {
            // Extract coordinates
            const coordsText = wktText.substring(9, wktText.length - 2);
            const coordPairs = coordsText.split(',');
            const latLngs = [];
            
            for (const pair of coordPairs) {
                const [lng, lat] = pair.trim().split(' ').map(Number);
                if (isNaN(lat) || isNaN(lng)) continue;
                latLngs.push([lat, lng]);
            }
            
            // Create and add polygon
            if (latLngs.length >= 3) {
                // Create polygon
                wktPolygon = L.polygon(latLngs, {
                    color: '#2ecc71',
                    weight: 3,
                    fillColor: '#2ecc71',
                    fillOpacity: 0.6
                }).addTo(wktPreviewMap);
                
                // Add markers for each point
                latLngs.forEach((point, index) => {
                    L.circleMarker(point, {
                        radius: 6,
                        color: '#fff',
                        weight: 1,
                        fillColor: '#2ecc71',
                        fillOpacity: 1
                    }).addTo(wktPreviewMap)
                    .bindTooltip(`Point ${index + 1}`, {permanent: false, direction: 'top'});
                });
                
                // Adjust map view
                wktPreviewMap.fitBounds(wktPolygon.getBounds(), {
                    padding: [50, 50],
                    maxZoom: 15
                });
            }
        } catch (e) {
            console.error("Error parsing WKT:", e);
        }
    }
    
    // Auto-preview WKT when typing
    document.getElementById('wkt').addEventListener('input', previewWKT);
    
    // Shape templates for WKT input
    const shapeTemplates = {
            "triangle": "POLYGON((113.9130 -2.2100, 113.9160 -2.2130, 113.9100 -2.2130, 113.9130 -2.2100))",
            "square": "POLYGON((113.9120 -2.2090, 113.9160 -2.2090, 113.9160 -2.2130, 113.9120 -2.2130, 113.9120 -2.2090))",
            "pentagon": "POLYGON((113.9140 -2.2080, 113.9170 -2.2100, 113.9160 -2.2140, 113.9120 -2.2140, 113.9110 -2.2100, 113.9140 -2.2080))",
            "hexagon": "POLYGON((113.9140 -2.2090, 113.9170 -2.2090, 113.9188 -2.2120, 113.9170 -2.2150, 113.9140 -2.2149, 113.9120 -2.21180, 113.9140 -2.2090))",
            "octagon": "POLYGON((113.9131 -2.2080, 113.916 -2.2080, 113.918 -2.2100, 113.9180 -2.2130, 113.9160 -2.2150, 113.9130 -2.2150, 113.9110 -2.2130, 113.9110 -2.2100, 113.9131 -2.2080))"
    };
    
    // Handle shape selection
    document.getElementById('shape-template').addEventListener('change', function() {
        const shape = this.value;
        if (shape && shapeTemplates[shape]) {
            document.getElementById('wkt').value = shapeTemplates[shape];
            previewWKT();
        }
    });
    
    // Form validation before submission
    document.getElementById('polygonForm').addEventListener('submit', function(e) {
        const method = document.getElementById('method').value;
        const name = document.getElementById('name').value.trim();
        
        if (!name) {
            e.preventDefault();
            alert('Please enter an area name');
            return;
        }
        
        if (method === 'dynamic') {
            const pointInputs = document.querySelectorAll('[name^="points["]');
            const validPoints = Array.from(pointInputs).filter(input => input.value.trim() !== '');
            
            if (validPoints.length < 6) { // 3 points (each has lat and lng inputs)
                e.preventDefault();
                alert('You need at least 3 points to create a polygon');
                return;
            }
        }
        
        if (method === 'draw') {
            const coords = document.getElementById('coordinates').value;
            if (!coords || JSON.parse(coords).length < 3) {
                e.preventDefault();
                alert('You need to draw a polygon with at least 3 points');
                return;
            }
        }
        
        if (method === 'wkt') {
            const wktText = document.getElementById('wkt').value.trim();
            if (!wktText.startsWith('POLYGON((') || !wktText.endsWith('))')) {
                e.preventDefault();
                alert('Invalid WKT format. Must start with POLYGON(( and end with ))');
                return;
            }
            
            const coordsText = wktText.substring(9, wktText.length - 2);
            if (coordsText.split(',').length < 3) {
                e.preventDefault();
                alert('WKT polygon must have at least 3 points');
                return;
            }
        }
    });
    </script>
</body>
</html>
