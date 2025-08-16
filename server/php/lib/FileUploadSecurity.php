<?php
/**
 * Secure File Upload Handler
 *
 * PHP version 7+
 *
 * @category   Security
 * @package    GetlancerV3
 * @subpackage Core
 * @author     Security Review Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

class FileUploadSecurity
{
    private static $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    private static $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx'
    ];

    private static $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phps',
        'js', 'html', 'htm', 'asp', 'aspx', 'jsp',
        'exe', 'bat', 'cmd', 'com', 'scr', 'vbs',
        'sh', 'pl', 'py', 'rb', 'jar'
    ];

    /**
     * Validate uploaded file for security
     *
     * @param array $file $_FILES array element
     * @param string $class File class for specific validation
     * @return array Validation result
     */
    public static function validateFile($file, $class = '')
    {
        $result = [
            'valid' => false,
            'error' => '',
            'sanitized_name' => ''
        ];

        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $result['error'] = 'Invalid file upload';
            return $result;
        }

        // Check file size
        if ($file['size'] > self::getMaxFileSize($class)) {
            $result['error'] = 'File size exceeds maximum allowed size';
            return $result;
        }

        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check for dangerous extensions
        if (in_array($extension, self::$dangerousExtensions)) {
            $result['error'] = 'File type not allowed for security reasons';
            return $result;
        }

        // Check allowed extensions
        if (!in_array($extension, self::$allowedExtensions)) {
            $result['error'] = 'File extension not allowed';
            return $result;
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::$allowedMimeTypes)) {
            $result['error'] = 'File MIME type not allowed';
            return $result;
        }

        // Additional content validation for images
        if (strpos($mimeType, 'image/') === 0) {
            if (!self::validateImageContent($file['tmp_name'])) {
                $result['error'] = 'Invalid image file content';
                return $result;
            }
        }

        // Generate secure filename
        $result['sanitized_name'] = self::generateSecureFilename($file['name']);
        $result['valid'] = true;

        return $result;
    }

    /**
     * Validate image content to prevent malicious files
     *
     * @param string $filePath Path to uploaded file
     * @return bool True if valid image
     */
    private static function validateImageContent($filePath)
    {
        // Try to get image info
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return false;
        }

        // Check if it's a valid image type
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($imageInfo[2], $allowedImageTypes)) {
            return false;
        }

        // Try to create image resource to validate content
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($filePath);
                break;
            case IMAGETYPE_WEBP:
                $image = @imagecreatefromwebp($filePath);
                break;
            default:
                return false;
        }

        if ($image === false) {
            return false;
        }

        imagedestroy($image);
        return true;
    }

    /**
     * Generate secure filename
     *
     * @param string $originalName Original filename
     * @return string Secure filename
     */
    private static function generateSecureFilename($originalName)
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize basename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $basename = substr($basename, 0, 50); // Limit length
        
        // Generate unique identifier
        $uniqueId = bin2hex(random_bytes(16));
        
        return $basename . '_' . $uniqueId . '.' . $extension;
    }

    /**
     * Get maximum file size for class
     *
     * @param string $class File class
     * @return int Maximum file size in bytes
     */
    private static function getMaxFileSize($class)
    {
        $defaultSize = 5 * 1024 * 1024; // 5MB default
        
        $classSizes = [
            'UserAvatar' => 2 * 1024 * 1024,    // 2MB
            'Portfolio' => 10 * 1024 * 1024,    // 10MB
            'ProjectDocument' => 20 * 1024 * 1024, // 20MB
            'QuoteServicePhoto' => 5 * 1024 * 1024  // 5MB
        ];

        return isset($classSizes[$class]) ? $classSizes[$class] : $defaultSize;
    }

    /**
     * Create secure upload directory
     *
     * @param string $path Directory path
     * @return bool Success status
     */
    public static function createSecureDirectory($path)
    {
        if (!file_exists($path)) {
            if (!mkdir($path, 0755, true)) {
                return false;
            }
        }

        // Create .htaccess to prevent direct access to PHP files
        $htaccessPath = $path . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Deny direct access to uploaded files\n";
            $htaccessContent .= "<Files \"*.php\">\n";
            $htaccessContent .= "    Require all denied\n";
            $htaccessContent .= "</Files>\n";
            $htaccessContent .= "<Files \"*.phtml\">\n";
            $htaccessContent .= "    Require all denied\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
        }

        return true;
    }

    /**
     * Sanitize file path to prevent directory traversal
     *
     * @param string $path File path
     * @param string $basePath Base directory path
     * @return string|false Sanitized path or false if invalid
     */
    public static function sanitizeFilePath($path, $basePath)
    {
        // Remove any null bytes
        $path = str_replace("\0", '', $path);
        
        // Resolve the real path
        $realPath = realpath($basePath . '/' . $path);
        $realBasePath = realpath($basePath);
        
        // Check if the resolved path is within the base directory
        if ($realPath === false || $realBasePath === false) {
            return false;
        }
        
        if (strpos($realPath, $realBasePath) !== 0) {
            return false;
        }
        
        return $realPath;
    }
}