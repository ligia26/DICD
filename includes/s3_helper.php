<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider;

/**
 * S3 Upload Helper Functions
 */
class S3UploadHelper {
    private $s3Client;
    private $bucket;
    
    public function __construct() {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => 'eu-west-3',
            'credentials' => CredentialProvider::ini('default', '/home/www-data/.aws/credentials')
        ]);
        
        // Set your S3 bucket name here
        $this->bucket = 'datainnovation.inbound'; // Update this to your actual bucket name
    }
    
    /**
     * Upload file to S3 with proper directory structure
     * 
     * @param string $localFilePath Full path to the local file
     * @param int $companyId Company ID to get s3_dir from
     * @param string $groupType Group type (e.g., 'subscribers', 'blacklist', etc.)
     * @param string $fileName Original filename
     * @param mysqli $conn Database connection
     * @return array ['success' => bool, 'key' => string|null, 'error' => string|null]
     */
    public function uploadFileToS3($localFilePath, $companyId, $groupType, $fileName, $conn = null) {
        if ($conn === null) {
            global $conn;
        }
        
        try {
            // Get company S3 directory from database
            error_log("S3 Helper: Looking for company identifier: " . $companyId);
            
            // Try to find company by ID first, then by name if it's a string
            $stmt = $conn->prepare("SELECT s3_dir, name FROM companies WHERE id = ? OR name = ?");
            $stmt->bind_param("ss", $companyId, $companyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $company = $result->fetch_assoc();
            $stmt->close();
            
            error_log("S3 Helper: Company data: " . print_r($company, true));
            
            if (!$company || empty($company['s3_dir'])) {
                error_log("S3 Helper: Company S3 directory not found for ID: " . $companyId);
                return [
                    'success' => false,
                    'key' => null,
                    'error' => 'Company S3 directory not found. Company ID: ' . $companyId
                ];
            }
            
            $s3Dir = rtrim($company['s3_dir'], '/'); // Remove trailing slash if exists
            error_log("S3 Helper: Using S3 directory: " . $s3Dir);
            
            // Generate S3 key with proper structure
            // Format: COMPANY_S3_DIR/sources/dashboard/GROUP_TYPE/FILENAME
            $s3Key = $s3Dir . '/sources/dashboard/' . $groupType . '/' . $fileName;
            
            // Check if local file exists
            if (!file_exists($localFilePath)) {
                return [
                    'success' => false,
                    'key' => null,
                    'error' => 'Local file does not exist: ' . $localFilePath
                ];
            }
            
            // Upload to S3
            error_log("S3 Helper: Attempting upload - Bucket: " . $this->bucket . ", Key: " . $s3Key . ", File: " . $localFilePath);
            
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $s3Key,
                'SourceFile' => $localFilePath,
                'ContentType' => $this->getMimeType($fileName)
            ]);
            
            error_log("S3 Helper: Upload successful - Result: " . print_r($result, true));
            
            return [
                'success' => true,
                'key' => $s3Key,
                'error' => null,
                'url' => $result['ObjectURL'] ?? null
            ];
            
        } catch (AwsException $e) {
            error_log('S3 Upload Error: ' . $e->getMessage());
            return [
                'success' => false,
                'key' => null,
                'error' => 'S3 Upload failed: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log('General Upload Error: ' . $e->getMessage());
            return [
                'success' => false,
                'key' => null,
                'error' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate unique filename with timestamp
     * 
     * @param string $originalName Original filename
     * @param string $jobId Unique job ID
     * @return string Generated filename
     */
    public function generateUniqueFileName($originalName, $jobId) {
        $pathInfo = pathinfo($originalName);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $baseName = isset($pathInfo['filename']) ? $pathInfo['filename'] : 'file';
        
        // Format: original_name_jobid_timestamp.extension
        return $baseName . '_' . $jobId . '_' . date('Y-m-d_H-i-s') . $extension;
    }
    
    /**
     * Get MIME type for file
     * 
     * @param string $fileName Filename
     * @return string MIME type
     */
    private function getMimeType($fileName) {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
        ];
        
        return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    }
    
    /**
     * Clean up temporary file
     * 
     * @param string $filePath Path to file to delete
     * @return bool Success status
     */
    public function cleanupTempFile($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true;
    }
    
    /**
     * Delete file from S3
     * 
     * @param string $s3Key S3 key/path to the file
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function deleteFileFromS3($s3Key) {
        try {
            error_log("S3 Helper: Attempting to delete file - Bucket: " . $this->bucket . ", Key: " . $s3Key);
            
            $result = $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $s3Key
            ]);
            
            error_log("S3 Helper: File deletion successful - Result: " . print_r($result, true));
            
            return [
                'success' => true,
                'error' => null
            ];
            
        } catch (AwsException $e) {
            error_log('S3 Delete Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'S3 Delete failed: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log('General Delete Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get S3 bucket name
     * 
     * @return string Bucket name
     */
    public function getBucket() {
        return $this->bucket;
    }
}
?>