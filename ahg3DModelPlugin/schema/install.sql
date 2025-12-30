-- =====================================================
-- ar3DModelPlugin Database Schema
-- 3D Model Support with IIIF 3D Extension for AtoM
-- =====================================================

-- 3D Model main table
CREATE TABLE IF NOT EXISTS object_3d_model (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT,
    mime_type VARCHAR(100),
    format ENUM('glb', 'gltf', 'obj', 'fbx', 'stl', 'ply', 'usdz') DEFAULT 'glb',
    
    -- Model metadata
    vertex_count INT,
    face_count INT,
    texture_count INT,
    animation_count INT DEFAULT 0,
    has_materials TINYINT(1) DEFAULT 1,
    
    -- Viewer settings
    auto_rotate TINYINT(1) DEFAULT 1,
    rotation_speed DECIMAL(3,2) DEFAULT 1.00,
    camera_orbit VARCHAR(100) DEFAULT '0deg 75deg 105%',
    min_camera_orbit VARCHAR(100),
    max_camera_orbit VARCHAR(100),
    field_of_view VARCHAR(20) DEFAULT '30deg',
    exposure DECIMAL(3,2) DEFAULT 1.00,
    shadow_intensity DECIMAL(3,2) DEFAULT 1.00,
    shadow_softness DECIMAL(3,2) DEFAULT 1.00,
    environment_image VARCHAR(255),
    skybox_image VARCHAR(255),
    background_color VARCHAR(20) DEFAULT '#f5f5f5',
    
    -- AR settings
    ar_enabled TINYINT(1) DEFAULT 1,
    ar_scale VARCHAR(20) DEFAULT 'auto',
    ar_placement ENUM('floor', 'wall') DEFAULT 'floor',
    
    -- Poster/thumbnail
    poster_image VARCHAR(500),
    thumbnail VARCHAR(500),
    
    -- Status
    is_primary TINYINT(1) DEFAULT 0,
    is_public TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    
    -- Audit
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object_id (object_id),
    INDEX idx_format (format),
    INDEX idx_is_public (is_public),
    FOREIGN KEY (object_id) REFERENCES information_object(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3D Model translations
CREATE TABLE IF NOT EXISTS object_3d_model_i18n (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    title VARCHAR(255),
    description TEXT,
    alt_text VARCHAR(500),
    
    UNIQUE KEY unique_model_culture (model_id, culture),
    FOREIGN KEY (model_id) REFERENCES object_3d_model(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3D Model hotspots/annotations
CREATE TABLE IF NOT EXISTS object_3d_hotspot (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id INT NOT NULL,
    hotspot_type ENUM('annotation', 'info', 'link', 'damage', 'detail') DEFAULT 'annotation',
    
    -- Position in 3D space
    position_x DECIMAL(10,6) NOT NULL,
    position_y DECIMAL(10,6) NOT NULL,
    position_z DECIMAL(10,6) NOT NULL,
    
    -- Normal vector for surface alignment
    normal_x DECIMAL(10,6) DEFAULT 0,
    normal_y DECIMAL(10,6) DEFAULT 1,
    normal_z DECIMAL(10,6) DEFAULT 0,
    
    -- Styling
    icon VARCHAR(50) DEFAULT 'info',
    color VARCHAR(20) DEFAULT '#1a73e8',
    
    -- Link target (optional)
    link_url VARCHAR(500),
    link_target ENUM('_self', '_blank') DEFAULT '_blank',
    
    display_order INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_model_id (model_id),
    FOREIGN KEY (model_id) REFERENCES object_3d_model(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3D Hotspot translations
CREATE TABLE IF NOT EXISTS object_3d_hotspot_i18n (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotspot_id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    title VARCHAR(255),
    description TEXT,
    
    UNIQUE KEY unique_hotspot_culture (hotspot_id, culture),
    FOREIGN KEY (hotspot_id) REFERENCES object_3d_hotspot(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3D Model materials/textures
CREATE TABLE IF NOT EXISTS object_3d_texture (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id INT NOT NULL,
    texture_type ENUM('diffuse', 'normal', 'roughness', 'metallic', 'ao', 'emissive', 'environment') DEFAULT 'diffuse',
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    width INT,
    height INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_model_id (model_id),
    FOREIGN KEY (model_id) REFERENCES object_3d_model(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Global 3D viewer settings
CREATE TABLE IF NOT EXISTS viewer_3d_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(500),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default viewer settings
INSERT INTO viewer_3d_settings (setting_key, setting_value, setting_type, description) VALUES
('default_viewer', 'model-viewer', 'string', 'Default 3D viewer component (model-viewer or threejs)'),
('enable_ar', '1', 'boolean', 'Enable AR viewing on supported devices'),
('enable_fullscreen', '1', 'boolean', 'Enable fullscreen button'),
('enable_download', '0', 'boolean', 'Allow model download'),
('default_background', '#f5f5f5', 'string', 'Default background color'),
('default_exposure', '1.0', 'string', 'Default lighting exposure'),
('default_shadow_intensity', '1.0', 'string', 'Default shadow intensity'),
('max_file_size_mb', '100', 'integer', 'Maximum upload file size in MB'),
('allowed_formats', '["glb","gltf","usdz"]', 'json', 'Allowed 3D model formats'),
('poster_auto_generate', '1', 'boolean', 'Auto-generate poster images'),
('enable_annotations', '1', 'boolean', 'Enable 3D hotspot annotations'),
('watermark_enabled', '0', 'boolean', 'Enable watermark on models'),
('watermark_text', 'The Archive and Heritage Group', 'string', 'Watermark text'),
('enable_auto_rotate', '1', 'boolean', 'Enable auto-rotation by default'),
('rotation_speed', '30', 'integer', 'Rotation speed in degrees per second')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- IIIF 3D manifest cache
CREATE TABLE IF NOT EXISTS iiif_3d_manifest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id INT NOT NULL UNIQUE,
    manifest_json LONGTEXT,
    manifest_hash VARCHAR(64),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_model_id (model_id),
    FOREIGN KEY (model_id) REFERENCES object_3d_model(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit log for 3D model operations
CREATE TABLE IF NOT EXISTS object_3d_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id INT,
    object_id INT,
    user_id INT,
    user_name VARCHAR(255),
    action ENUM('upload', 'update', 'delete', 'view', 'ar_view', 'download', 'hotspot_add', 'hotspot_delete') NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_model_id (model_id),
    INDEX idx_object_id (object_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
