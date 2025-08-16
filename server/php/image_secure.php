<?php
/**
 * Secure image processing and thumbnail generation
 *
 * PHP version 7+
 *
 * @category   Security
 * @package    GetlancerV3
 * @subpackage Core
 * @author     Security Review Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

require_once 'config.inc.php';
require_once __DIR__ . '/Slim/vendor/autoload.php';
require_once './Slim/lib/database.php';
require_once './Slim/lib/settings.php';
require_once './lib/FileUploadSecurity.php';

class SecureImageProcessor
{
    private $db;
    private $thumbsizes;
    private $cacheDir;
    
    public function __construct()
    {
        // Use PDO for secure database connections
        $dsn = 'pgsql:host=' . R_DB_HOST . ';port=' . R_DB_PORT . ';dbname=' . R_DB_NAME;
        $this->db = new PDO($dsn, R_DB_USER, R_DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        $this->thumbsizes = [
            'UserAvatar' => [
                'micro_thumb' => '16x16',
                'small_thumb' => '42x42',
                'medium_thumb' => '59x59',
                'normal_thumb' => '64x64',
                'big_thumb' => '225x225',
                'large_thumb' => '152x152'
            ],
            'Post' => [
                'micro_thumb' => '16x16',
                'small_thumb' => '32x32',
                'normal_thumb' => '64x64'
            ],
            'QuoteService' => [
                'micro_thumb' => '16x16',
                'small_thumb' => '64x64',
                'entry_big_thumb' => '120x120',
                'normal_thumb' => '230x115',
                'medium_thumb' => '201x182',
                'large_thumb' => '284x200',
            ],
            'Portfolio' => [
                'micro_thumb' => '16x16',
                'small_thumb' => '32x32',
                'normal_thumb' => '190x160',
                'medium_thumb' => '385x400',
                'large_thumb' => '831x701'
            ]
        ];
        
        $this->cacheDir = APP_PATH . '/cache/images/';
        $this->ensureCacheDirectory();
    }
    
    /**
     * Process image request securely
     */
    public function processRequest()
    {
        try {
            // Validate and sanitize input parameters
            $size = $this->validateSize($_GET['size'] ?? '');
            $model = $this->validateModel($_GET['model'] ?? '');
            $filename = $this->validateFilename($_GET['filename'] ?? '');
            
            if (!$size || !$model || !$filename) {
                $this->sendError(400, 'Invalid parameters');
                return;
            }
            
            // Parse filename components
            $parts = explode('.', $filename);
            if (count($parts) !== 3) {
                $this->sendError(400, 'Invalid filename format');
                return;
            }
            
            list($id, $hash, $ext) = $parts;
            
            // Validate hash for security
            if (!$this->validateHash($hash, $model, $id, $ext, $size)) {
                $this->sendError(403, 'Invalid hash');
                return;
            }
            
            // Check cache first
            $cachedImage = $this->getCachedImage($model, $id, $size, $hash, $ext);
            if ($cachedImage) {
                $this->serveCachedImage($cachedImage, $ext);
                return;
            }
            
            // Get attachment info from database
            $attachment = $this->getAttachment($id, $model);
            if (!$attachment) {
                $this->sendError(404, 'Attachment not found');
                return;
            }
            
            // Validate file path
            $fullPath = FileUploadSecurity::sanitizeFilePath(
                'media/' . $attachment['dir'] . '/' . $attachment['filename'],
                APP_PATH
            );
            
            if (!$fullPath || !file_exists($fullPath)) {
                $this->sendError(404, 'File not found');
                return;
            }
            
            // Generate and serve image
            $this->generateAndServeImage($fullPath, $model, $size, $id, $hash, $ext);
            
        } catch (Exception $e) {
            error_log('Image processing error: ' . $e->getMessage());
            $this->sendError(500, 'Internal server error');
        }
    }
    
    /**
     * Validate size parameter
     */
    private function validateSize($size)
    {
        if (empty($size) || !preg_match('/^[a-z_]+$/', $size)) {
            return false;
        }
        return $size;
    }
    
    /**
     * Validate model parameter
     */
    private function validateModel($model)
    {
        $allowedModels = array_keys($this->thumbsizes);
        if (!in_array($model, $allowedModels)) {
            return false;
        }
        return $model;
    }
    
    /**
     * Validate filename parameter
     */
    private function validateFilename($filename)
    {
        if (empty($filename) || !preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            return false;
        }
        return $filename;
    }
    
    /**
     * Validate security hash
     */
    private function validateHash($hash, $model, $id, $ext, $size)
    {
        $expectedHash = md5($model . $id . $ext . $size);
        return hash_equals($expectedHash, $hash);
    }
    
    /**
     * Get attachment from database using prepared statement
     */
    private function getAttachment($id, $model)
    {
        $stmt = $this->db->prepare(
            "SELECT filename, dir FROM attachments WHERE foreign_id = ? AND class = ? LIMIT 1"
        );
        $stmt->execute([$id, $model]);
        return $stmt->fetch();
    }
    
    /**
     * Check if cached image exists
     */
    private function getCachedImage($model, $id, $size, $hash, $ext)
    {
        $cacheFile = $this->cacheDir . $model . '/' . $size . '/' . $id . '.' . $hash . '.' . $ext;
        
        if (file_exists($cacheFile)) {
            // Check if cache is still valid (24 hours)
            if (time() - filemtime($cacheFile) < 86400) {
                return $cacheFile;
            }
        }
        
        return false;
    }
    
    /**
     * Serve cached image
     */
    private function serveCachedImage($cacheFile, $ext)
    {
        $this->setImageHeaders($ext);
        readfile($cacheFile);
    }
    
    /**
     * Generate and serve image
     */
    private function generateAndServeImage($fullPath, $model, $size, $id, $hash, $ext)
    {
        if (!isset($this->thumbsizes[$model][$size])) {
            $this->sendError(400, 'Invalid size for model');
            return;
        }
        
        $dimensions = $this->thumbsizes[$model][$size];
        list($width, $height) = explode('x', $dimensions);
        
        if ($size === 'original') {
            $this->serveOriginalImage($fullPath, $ext);
            return;
        }
        
        // Generate thumbnail
        $thumbnail = $this->generateThumbnail($fullPath, $width, $height);
        if (!$thumbnail) {
            $this->sendError(500, 'Failed to generate thumbnail');
            return;
        }
        
        // Cache the thumbnail
        $this->cacheThumbnail($thumbnail, $model, $size, $id, $hash, $ext);
        
        // Serve the thumbnail
        $this->setImageHeaders($ext);
        $this->outputImage($thumbnail, $ext);
        
        imagedestroy($thumbnail);
    }
    
    /**
     * Generate thumbnail securely
     */
    private function generateThumbnail($sourcePath, $width, $height)
    {
        // Validate image
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        list($currentWidth, $currentHeight, $imageType) = $imageInfo;
        
        // Create source image
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $source = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = @imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = @imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG and GIF
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled(
            $thumbnail, $source,
            0, 0, 0, 0,
            $width, $height,
            $currentWidth, $currentHeight
        );
        
        imagedestroy($source);
        return $thumbnail;
    }
    
    /**
     * Cache thumbnail
     */
    private function cacheThumbnail($thumbnail, $model, $size, $id, $hash, $ext)
    {
        $cacheDir = $this->cacheDir . $model . '/' . $size . '/';
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . $id . '.' . $hash . '.' . $ext;
        
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumbnail, $cacheFile, 90);
                break;
            case 'png':
                imagepng($thumbnail, $cacheFile, 8);
                break;
            case 'gif':
                imagegif($thumbnail, $cacheFile);
                break;
            case 'webp':
                imagewebp($thumbnail, $cacheFile, 90);
                break;
        }
    }
    
    /**
     * Serve original image
     */
    private function serveOriginalImage($fullPath, $ext)
    {
        $this->setImageHeaders($ext);
        readfile($fullPath);
    }
    
    /**
     * Output image to browser
     */
    private function outputImage($image, $ext)
    {
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, null, 90);
                break;
            case 'png':
                imagepng($image, null, 8);
                break;
            case 'gif':
                imagegif($image);
                break;
            case 'webp':
                imagewebp($image, null, 90);
                break;
        }
    }
    
    /**
     * Set appropriate headers for image
     */
    private function setImageHeaders($ext)
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        
        $mimeType = $mimeTypes[strtolower($ext)] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Cache-Control: public, max-age=31536000'); // 1 year cache
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }
    
    /**
     * Send error response
     */
    private function sendError($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory()
    {
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Create .htaccess for cache directory
        $htaccessPath = $this->cacheDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Cache directory protection\n";
            $htaccessContent .= "Options -Indexes\n";
            $htaccessContent .= "<Files \"*.php\">\n";
            $htaccessContent .= "    Require all denied\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }
}

// Process the request
$processor = new SecureImageProcessor();
$processor->processRequest();