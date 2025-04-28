# Polygon Mapping Application

![Application Screenshot](![image](https://github.com/user-attachments/assets/773e19cd-0e18-4d71-9e28-c8a4363c7361)
)

deployment : https://elder.my.id/maps/

A web-based application for creating, managing, and exporting geographic polygons with various input methods. Built with PHP, MySQL, and Leaflet.js.

## Features

- **Multiple Polygon Creation Methods**:
  - Draw directly on the map
  - Enter coordinates manually
  - Input Well-Known Text (WKT) format
  - Select from predefined shapes

- **Interactive Map Interface**:
  - Leaflet.js with OpenStreetMap base layer
  - Drawing tools with polygon validation
  - Real-time previews for all creation methods

- **Data Management**:
  - Store polygons in MySQL database with spatial extension
  - View all saved polygons on a map
  - Edit existing polygons

- **Export Functionality**:
  - Export single polygon or all polygons
  - CSV format with WKT representation
  - Includes both complete polygon and individual coordinates

## Technology Stack

- **Frontend**:
  - Leaflet.js 1.7.1
  - Leaflet.draw plugin
  - Font Awesome 6.0.0
  - Custom CSS

- **Backend**:
  - PHP 7.4+
  - MySQL 5.7+ with spatial extensions
  - PDO for database access

- **Spatial Data**:
  - Well-Known Text (WKT) format
  - MySQL spatial functions (ST_GeomFromText, ST_AsText)

## Installation

1. **Prerequisites**:
   - Web server (Apache/Nginx)
   - PHP 7.4+
   - MySQL 5.7+ with spatial support

2. **Database Setup**:
   ```sql
   CREATE TABLE areas (
     id INT AUTO_INCREMENT PRIMARY KEY,
     name VARCHAR(255) NOT NULL,
     geom GEOMETRY NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
