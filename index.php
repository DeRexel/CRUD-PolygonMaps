<?php
require 'db.php';

// Get all areas from database
$areas = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, name, ST_AsText(geom) as wkt FROM areas");
    $areas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Polygon Map Viewer</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.svg">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/Style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Polygon Map Viewer</h1>
            <div>
                <a href="export_excel_all.php" class="btn btn-success" style="margin-right: 10px;">
                    <i class="fas fa-file-export"></i> Export All CSV
                </a>
                <a href="insert_area.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Polygon
                </a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <div class="sidebar">
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search areas..." id="searchInput">
                </div>
                
                <h3>Saved Areas</h3>
                
                <div class="area-list" id="areaList">
                    <?php if (empty($areas)): ?>
                        <div class="empty-state">
                            <i class="fas fa-map-marked-alt"></i>
                            <p>No areas found. Add your first polygon!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($areas as $area): ?>
                            <div class="area-item" 
                                data-id="<?= htmlspecialchars($area['id']) ?>" 
                                data-wkt="<?= htmlspecialchars($area['wkt']) ?>">
                                <span class="area-name"><?= htmlspecialchars($area['name']) ?></span>
                                <div class="area-actions">
                                    <a href="edit_area.php?id=<?= $area['id'] ?>" 
                                    class="btn btn-primary btn-sm btn-icon" 
                                    title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="export_excel.php?id=<?= $area['id'] ?>" 
                                    class="btn btn-success btn-sm btn-icon" 
                                    title="Export to CSV">
                                        <i class="fas fa-file-export"></i>
                                    </a>
                                    <button class="btn btn-danger btn-sm btn-icon delete-btn" 
                                            title="Delete" 
                                            data-id="<?= $area['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="map-container">
                <div id="map"></div>
                
                <div class="map-controls">
                    <div class="legend">
                        <h4>Legend</h4>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #3498db;"></div>
                            <span>Polygon Areas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Initialize map
        // const map = L.map('map').setView([-7.15, 110.25], 13);
        //tampilan set area palangka 
        const map = L.map('map').setView([-2.23409, 113.89649], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Store polygons and layers
        const polygons = {};
        const areaItems = document.querySelectorAll('.area-item');
        
        // Function to parse WKT and create polygon
        function createPolygonFromWKT(wkt, color = '#3498db') {
            const coordsText = wkt.replace('POLYGON((', '').replace('))', '');
            const coordPairs = coordsText.split(',');
            const latLngs = [];
            
            for (const pair of coordPairs) {
                const [lng, lat] = pair.trim().split(' ');
                latLngs.push([parseFloat(lat), parseFloat(lng)]);
            }
            
            return L.polygon(latLngs, {
                color: color,
                fillOpacity: 0.4,
                weight: 2
            });
        }
        
        // Add polygons to map from PHP data
        <?php foreach ($areas as $area): ?>
            const polygon_<?= $area['id'] ?> = createPolygonFromWKT('<?= $area['wkt'] ?>');
            polygon_<?= $area['id'] ?>.addTo(map)
                .bindPopup('<b><?= addslashes($area['name']) ?></b>');
            
            polygons[<?= $area['id'] ?>] = polygon_<?= $area['id'] ?>;
        <?php endforeach; ?>
        
        // Fit map to show all polygons if there are any
        <?php if (!empty($areas)): ?>
            const bounds = new L.LatLngBounds();
            <?php foreach ($areas as $area): ?>
                bounds.extend(polygons[<?= $area['id'] ?>].getBounds());
            <?php endforeach; ?>
            map.fitBounds(bounds);
        <?php endif; ?>
        
        // Highlight polygon when area item is clicked
        areaItems.forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all items
                areaItems.forEach(i => i.classList.remove('active'));
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Get the polygon ID
                const id = this.getAttribute('data-id');
                const polygon = polygons[id];
                
                // Highlight the polygon
                polygon.setStyle({color: '#e74c3c', weight: 3});
                
                // Center map on this polygon
                map.fitBounds(polygon.getBounds());
                
                // Reset style after 3 seconds
                setTimeout(() => {
                    polygon.setStyle({color: '#3498db', weight: 2});
                }, 3000);
                
                // Open popup
                polygon.openPopup();
            });
        });
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.area-item');
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Delete functionality
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('id', id);
                        
                        fetch('delete_area.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (polygons[id]) {
                                    map.removeLayer(polygons[id]);
                                    delete polygons[id];
                                }
                                
                                const item = document.querySelector(`.area-item[data-id="${id}"]`);
                                if (item) item.remove();
                                
                                Swal.fire(
                                    'Deleted!',
                                    'The area has been deleted.',
                                    'success'
                                );
                                
                                if (document.querySelectorAll('.area-item').length === 0) {
                                    document.getElementById('areaList').innerHTML = `
                                        <div class="empty-state">
                                            <i class="fas fa-map-marked-alt"></i>
                                            <p>No areas found. Add your first polygon!</p>
                                        </div>
                                    `;
                                }
                            } else {
                                Swal.fire(
                                    'Error!',
                                    data.message || 'Failed to delete the area.',
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error!',
                                error.message || 'An error occurred while deleting the area.',
                                'error'
                            );
                        });
                    }
                });
            });
        });

    </script>
</body>
</html>