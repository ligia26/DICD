<?php
// Set the default timezone to Spain
date_default_timezone_set('Europe/Madrid');

include "includes/db.php";
include "includes/functions.php";
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
$log_file = 'debug.log';

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'eu-west-3',
    
]);

$bucket = 'your-bucket-name';
$prefix = 'your-folder-prefix/'; // Specify the folder prefix or leave it empty to check all objects

/**
 * Function to restore old versions of objects in S3.
 * It will download the previous version of each object and re-upload it as the latest version.
 */
function restoreOldVersions($s3, $bucket, $prefix) {
    global $log_file;
    
    // Get object versions
    try {
        $result = $s3->listObjectVersions([
            'Bucket' => $bucket,
            'Prefix' => $prefix,  // List only objects under this folder
        ]);

        if (isset($result['Versions'])) {
            foreach ($result['Versions'] as $version) {
                $objectKey = $version['Key'];
                $versionId = $version['VersionId'];

                // Skip the current version
                if ($version['IsLatest']) {
                    continue;
                }

                // Log version being restored
                file_put_contents($log_file, "Restoring version $versionId of $objectKey\n", FILE_APPEND);

                // Download old version of the object
                $tempFile = '/tmp/' . basename($objectKey);
                $s3->getObject([
                    'Bucket' => $bucket,
                    'Key' => $objectKey,
                    'VersionId' => $versionId,
                    'SaveAs' => $tempFile,
                ]);

                // Re-upload the downloaded file as the latest version
                $s3->putObject([
                    'Bucket' => $bucket,
                    'Key' => $objectKey,
                    'SourceFile' => $tempFile,
                    'ACL' => 'private',  // Set the desired ACL (access control list)
                ]);

                // Log success
                file_put_contents($log_file, "Restored $objectKey to version $versionId successfully\n", FILE_APPEND);

                // Clean up temporary file
                unlink($tempFile);
            }
        } else {
            file_put_contents($log_file, "No object versions found in the bucket/prefix.\n", FILE_APPEND);
        }

    } catch (AwsException $e) {
        // Output error message if failed
        file_put_contents($log_file, "AWS Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Call the function to restore versions
restoreOldVersions($s3, $bucket, $prefix);
?>
