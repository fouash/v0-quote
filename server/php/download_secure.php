<?php
/**
 * Secure file download handler
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

class SecureDownloadHandler
{
    private $db;
    private $allowedModels = ['ProjectDocument', 'JobApply', 'Portfolio'];
    
    public function __construct()
    {
        // Use PDO for secure database connections
        $dsn = 'pgsql:host=' . R_DB_HOST . ';port=' . R_DB_PORT . ';dbname=' . R_DB_NAME;
        $this->db = new PDO($dsn, R_DB_USER, R_DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    
    /**
     * Process download request securely
     */
    public function processRequest()
    {
        try {
            // Validate and sanitize input parameters
            $model = $this->validateModel($_GET['model'] ?? '');
            $filename = $this->validateFilename($_GET['filename'] ?? '');
            $id = $this->validateId($_GET['id'] ?? '');
            
            if (!$model || !$filename || !$id) {
                $this->sendError(400, 'Invalid parameters');
                return;
            }
            
            // Parse filename to get hash and extension
            $parts = explode('.', $filename);
            if (count($parts) < 2) {
                $this->sendError(400, 'Invalid filename format');
                return;
            }
            
            $hash = $parts[0];
            $ext = end($parts);
            
            // Handle special case for ProjectDocument
            $attachmentId = null;
            if ($model === 'ProjectDocument' && count($parts) >= 3) {
                $attachmentId = $parts[1];
                $ext = $parts[2];
            }
            
            // Validate security hash
            if (!$this->validateHash($hash, $model, $id)) {
                $this->sendError(403, 'Invalid security hash');
                return;
            }
            
            // Get attachment from database
            $attachment = $this->getAttachment($id, $model, $attachmentId);
            if (!$attachment) {
                $this->sendError(404, 'File not found');
                return;
            }
            
            // Validate file path and existence
            $filePath = FileUploadSecurity::sanitizeFilePath(
                'media/' . $attachment['dir'] . '/' . $attachment['filename'],
                APP_PATH
            );
            
            if (!$filePath || !file_exists($filePath)) {
                $this->sendError(404, 'File not found on disk');
                return;
            }
            
            // Check user permissions (implement based on your auth system)
            if (!$this->checkDownloadPermissions($model, $id, $attachment)) {
                $this->sendError(403, 'Access denied');
                return;
            }
            
            // Serve the file securely
            $this->serveFile($filePath, $attachment['filename']);
            
        } catch (Exception $e) {
            error_log('Download error: ' . $e->getMessage());
            $this->sendError(500, 'Internal server error');
        }
    }
    
    /**
     * Validate model parameter
     */
    private function validateModel($model)
    {
        if (empty($model) || !in_array($model, $this->allowedModels)) {
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
     * Validate ID parameter
     */
    private function validateId($id)
    {
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            return false;
        }
        return (int)$id;
    }
    
    /**
     * Validate security hash
     */
    private function validateHash($hash, $model, $id)
    {
        $expectedHash = md5($model . $id . 'docdownload');
        return hash_equals($expectedHash, $hash);
    }
    
    /**
     * Get attachment from database using prepared statement
     */
    private function getAttachment($id, $model, $attachmentId = null)
    {
        if ($model === 'ProjectDocument' && $attachmentId) {
            $stmt = $this->db->prepare(
                "SELECT filename, dir FROM attachments WHERE foreign_id = ? AND id = ? AND class = ? LIMIT 1"
            );
            $stmt->execute([$id, $attachmentId, $model]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT filename, dir FROM attachments WHERE foreign_id = ? AND class = ? LIMIT 1"
            );
            $stmt->execute([$id, $model]);
        }
        
        return $stmt->fetch();
    }
    
    /**
     * Check download permissions based on user authentication and model
     */
    private function checkDownloadPermissions($model, $id, $attachment)
    {
        // Basic implementation - extend based on your authentication system
        // For now, we'll allow downloads but you should implement proper auth checks
        
        // Example permission checks:
        switch ($model) {
            case 'ProjectDocument':
                // Check if user is project owner or has access to project
                return $this->checkProjectAccess($id);
                
            case 'JobApply':
                // Check if user is job poster or applicant
                return $this->checkJobApplyAccess($id);
                
            case 'Portfolio':
                // Portfolio items might be public or require specific permissions
                return $this->checkPortfolioAccess($id);
                
            default:
                return false;
        }
    }
    
    /**
     * Check project access permissions
     */
    private function checkProjectAccess($projectId)
    {
        // Implement based on your authentication system
        // This is a placeholder - you should check if the current user
        // has permission to access this project's documents
        return true; // Placeholder
    }
    
    /**
     * Check job application access permissions
     */
    private function checkJobApplyAccess($jobApplyId)
    {
        // Implement based on your authentication system
        return true; // Placeholder
    }
    
    /**
     * Check portfolio access permissions
     */
    private function checkPortfolioAccess($portfolioId)
    {
        // Portfolio items might be public
        return true; // Placeholder
    }
    
    /**
     * Serve file securely with appropriate headers
     */
    private function serveFile($filePath, $originalFilename)
    {
        // Get file info
        $fileSize = filesize($filePath);
        $pathInfo = pathinfo($filePath);
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        // Set security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // Set content type based on extension
        $contentType = $this->getContentType($extension);
        header('Content-Type: ' . $contentType);
        
        // Set download headers
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $this->sanitizeFilename($originalFilename) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');
        
        // Clear output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Read and output file in chunks to handle large files
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            $this->sendError(500, 'Failed to open file');
            return;
        }
        
        while (!feof($handle)) {
            $chunk = fread($handle, 8192); // 8KB chunks
            echo $chunk;
            flush();
        }
        
        fclose($handle);
        exit;
    }
    
    /**
     * Get appropriate content type for file extension
     */
    private function getContentType($extension)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Sanitize filename for download
     */
    private function sanitizeFilename($filename)
    {
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit filename length
        if (strlen($filename) > 255) {
            $pathInfo = pathinfo($filename);
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            $basename = substr($pathInfo['filename'], 0, 255 - strlen($extension));
            $filename = $basename . $extension;
        }
        
        return $filename;
    }
    
    /**
     * Send error response
     */
    private function sendError($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}

// Process the download request
$handler = new SecureDownloadHandler();
$handler->processRequest();