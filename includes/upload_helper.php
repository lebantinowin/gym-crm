<?php
// includes/upload_helper.php - Secure File Upload Logic

function upload_profile_picture($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return 'default.png';
    
    // Ensure upload directory exists
    if (!file_exists(UPLOAD_DIR_PATH)) {
        mkdir(UPLOAD_DIR_PATH, 0777, true);
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) return 'default.png';
    
    // Validate size (5MB)
    if ($file['size'] > 5 * 1024 * 1024) return 'default.png';
    
    // Map MIME to extension
    $ext_map = [
        'image/jpeg' => 'jpg', 
        'image/png'  => 'png', 
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    ];
    $ext = $ext_map[$mime] ?? 'png';
    
    // Generate secure unique filename
    $filename = 'profile_' . bin2hex(random_bytes(16)) . '.' . $ext;
    $filepath = UPLOAD_DIR_PATH . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        chmod($filepath, 0644);
        return $filename;
    }
    
    return 'default.png';
}
