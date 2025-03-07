<?php
namespace App\Helpers;

class FileUploadHelper {
    /**
     * Upload a file
     *
     * @param array $file The uploaded file ($_FILES['field'])
     * @param string $uploadDir The directory to upload to
     * @param array $allowedTypes Array of allowed MIME types
     * @param int $maxSize Maximum file size in bytes (default: 10MB)
     * @return string|false The filename on success, false on failure
     */
    public static function uploadFile($file, $uploadDir, $allowedTypes = [], $maxSize = 10485760) {
        // Check if the file was uploaded without errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Check MIME type if allowedTypes is provided
        if (!empty($allowedTypes)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $fileType = $finfo->file($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                return false;
            }
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate a unique filename
        $filename = uniqid() . '_' . basename($file['name']);
        $uploadFile = $uploadDir . $filename;
        
        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            return $filename;
        }
        
        return false;
    }
    
    /**
     * Delete a file
     *
     * @param string $filename The filename to delete
     * @param string $uploadDir The directory where the file is located
     * @return bool True on success, false on failure
     */
    public static function deleteFile($filename, $uploadDir) {
        $filePath = $uploadDir . $filename;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
}