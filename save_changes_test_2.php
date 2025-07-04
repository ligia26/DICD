<?php
// Set the default timezone to Spain
date_default_timezone_set('Europe/Madrid');

include "includes/db.php";
include "includes/functions.php";
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider; 

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$log_file = '/var/www/clients.datainnovation.io/html/test.log';
file_put_contents($log_file, "Request received\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['data']) && isset($_POST['user_id'])) {

    $data    = $_POST['data'];
    $user_id = $_POST['user_id'];
    $currentTimestamp = date('Y-m-d H:i:s');

    file_put_contents($log_file, "Posted rows:\n" . print_r($data, true) . "\n", FILE_APPEND);

    //-----------------------------------------------
    // 1) Group rows by sending_domain
    //-----------------------------------------------
    $domainsGrouped = [];
    foreach ($data as $row) {
        $thisDomain = $row['sending_domain'] ?? '';

        // If domain is empty, label it "all-domains"
        if ($thisDomain === '') {
            $thisDomain = 'all-domains';
        }

        if (!isset($domainsGrouped[$thisDomain])) {
            $domainsGrouped[$thisDomain] = [];
        }
        $domainsGrouped[$thisDomain][] = $row;
    }

    //-----------------------------------------------
    // Helper: Safely escape or NULL out SQL values
    //-----------------------------------------------
    function escapeValue($value, $conn) {
        if (is_null($value) || $value === '' || trim($value) === '') {
            return 'NULL';
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return "'" . $conn->real_escape_string($value) . "'";
        }
    }

    //-----------------------------------------------
    // Helper: Upload local file to S3
    //-----------------------------------------------
    function uploadToS3($filePath, $bucket, $key, $log_file) {
        // Use AWS credentials from .aws/credentials
        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => 'eu-west-3',
            'credentials' => CredentialProvider::ini('default', '/home/www-data/.aws/credentials'),
        ]);

        try {
            $result = $s3->putObject([
                'Bucket'     => $bucket,
                'Key'        => $key,
                'SourceFile' => $filePath,
                'ACL'        => 'private',
            ]);
            file_put_contents($log_file, "S3 Upload Result: " . json_encode($result) . "\n", FILE_APPEND);
            return $result['ObjectURL'] ?? false;
        } catch (AwsException $e) {
            file_put_contents($log_file, "S3 Upload Exception: " . $e->getAwsErrorCode() . " - " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($log_file, "S3 Debug: " . json_encode($e->toArray()) . "\n", FILE_APPEND);
            return false;
        }
    }

    //-----------------------------------------------
    // 2) For each domain, create a local CSV
    //-----------------------------------------------
    foreach ($domainsGrouped as $thisDomain => $rowsForThisDomain) {

        // Build the CSV header
        $csvHeader = [
            'sending_domain','user_domain','country','manual_category','expected_volume_increment',
            'date_start','date_end','quien','current_auto_rule','percent_lwor','percent_ldor',
            'current_sent','sendable_user','clickers','openers','reactivated','preactivated',
            'halfslept','awaken','whitelist','precached','zeroclicks','new','aos','slept',
            'keepalive','stranger','new_inactive','total_inactive'
        ];

        // Build CSV data rows
        $csvData = [];
        foreach ($rowsForThisDomain as $row) {
            // If "Auto", store blank in CSV
            $csv_manual_category = ($row['manual_category'] === 'Auto') ? '' : ($row['manual_category'] ?? '');

            $csvData[] = [
                $row['sending_domain']           ?? '',
                $row['user_domain']              ?? '',
                $row['country']                  ?? '',
                $csv_manual_category,
                $row['expected_volume_increment'] ?? '',
                $row['date_start']               ?? '',
                $row['date_end']                 ?? '',
                $row['quien']                    ?? '',
                $row['current_auto_rule']        ?? '',
                $row['percent_lwor']             ?? '',
                $row['percent_ldor']             ?? '',
                $row['current_sent']             ?? '',
                $row['sendable_user']            ?? '',
                $row['clickers']                 ?? '',
                $row['openers']                  ?? '',
                $row['reactivated']              ?? '',
                $row['preactivated']             ?? '',
                $row['halfslept']                ?? '',
                $row['awaken']                   ?? '',
                $row['whitelist']                ?? '',
                $row['precached']                ?? '',
                $row['zeroclicks']               ?? '',
                $row['new']                      ?? '',
                $row['aos']                      ?? '',
                $row['slept']                    ?? '',
                $row['keepalive']                ?? '',
                $row['stranger']                 ?? '',
                $row['new_inactive']             ?? '',
                $row['total_inactive']           ?? ''
            ];
        }

        // Convert to CSV string
        $csvContent = implode(',', $csvHeader) . "\n";
        foreach ($csvData as $fields) {
            $csvContent .= implode(',', $fields) . "\n";
        }

        // Write CSV to local temp file
        $tempFilePath = sys_get_temp_dir() . '/test_' . $thisDomain . '.csv';
        file_put_contents($tempFilePath, $csvContent);

        file_put_contents(
            $log_file,
            "Created CSV for domain: $thisDomain at $tempFilePath\n",
            FILE_APPEND
        );

        // If this domain is "all-domains" (i.e. user picked "all"), we skip S3/DB
        if ($thisDomain === 'all-domains') {
            file_put_contents($log_file, "Skipping S3 + DB for 'all-domains' placeholder.\n", FILE_APPEND);
            continue;
        }

        //-----------------------------------------------
        // 3) Look up this domain's s3_dir from DB
        //-----------------------------------------------
        $safeDomain = $conn->real_escape_string($thisDomain);
        $company_query = "
            SELECT c.s3_dir
            FROM companies c
            JOIN sending_domains sd ON c.id = sd.company
            WHERE sd.domain = '$safeDomain'
        ";
        file_put_contents($log_file, "company_query: $company_query\n", FILE_APPEND);

        $company_result = $conn->query($company_query);
        if (!$company_result || $company_result->num_rows === 0) {
            file_put_contents($log_file, "No s3_dir found for domain: $thisDomain\n", FILE_APPEND);
            // If no s3_dir, you might skip DB or handle differently
            continue;
        }

        $companyRow = $company_result->fetch_assoc();
        $s3_dir     = $companyRow['s3_dir'] ?? '';
        if (empty($s3_dir)) {
            file_put_contents($log_file, "Empty s3_dir for domain: $thisDomain\n", FILE_APPEND);
            continue;
        }

        //-----------------------------------------------
        // 4) Upload CSV to S3
        //-----------------------------------------------
        $bucket = 'datainnovation.inbound';
        $key    = $s3_dir . '/sources/dashboard/manual-rules/' . $thisDomain . '.csv';

        file_put_contents(
            $log_file,
            "Uploading to S3: Bucket=$bucket Key=$key\n",
            FILE_APPEND
        );

        $uploadResult = uploadToS3($tempFilePath, $bucket, $key, $log_file);
        if ($uploadResult === false) {
            file_put_contents($log_file, "S3 upload failed for $thisDomain\n", FILE_APPEND);
            // Optionally continue DB or skip it
            continue;
        } else {
            file_put_contents($log_file, "S3 upload OK for $thisDomain => $uploadResult\n", FILE_APPEND);
        }

        //-----------------------------------------------
        // 5) Insert/Update DB for each row
        //-----------------------------------------------
        foreach ($rowsForThisDomain as $row) {
            $sending_domain = $row['sending_domain'] ?? '';
            $user_domain    = $row['user_domain']    ?? '';
            $country        = $row['country']        ?? '';
            $manual_category = ($row['manual_category'] === 'Auto')
                ? null
                : $row['manual_category'];

            $expected_volume_increment = (isset($row['expected_volume_increment']) && is_numeric($row['expected_volume_increment']))
                ? $row['expected_volume_increment'] : '';
            $date_start = empty($row['date_start']) ? null : $row['date_start'];
            $date_end   = empty($row['date_end'])   ? null : $row['date_end'];
            $quien      = $row['quien']            ?? '';
            $current_auto_rule = $row['current_auto_rule'] ?? '';
            $percent_lwor      = (isset($row['percent_lwor']) && is_numeric($row['percent_lwor']))
                ? $row['percent_lwor'] : '';
            $percent_ldor      = (isset($row['percent_ldor']) && is_numeric($row['percent_ldor']))
                ? $row['percent_ldor'] : '';
            $current_sent  = (isset($row['current_sent'])  && is_numeric($row['current_sent']))
                ? $row['current_sent']  : '';
            $sendable_user = (isset($row['sendable_user']) && is_numeric($row['sendable_user']))
                ? $row['sendable_user'] : '';
            $clickers      = (isset($row['clickers'])      && is_numeric($row['clickers']))
                ? $row['clickers']      : '';
            $openers       = (isset($row['openers'])       && is_numeric($row['openers']))
                ? $row['openers']       : '';
            $reactivated   = (isset($row['reactivated'])   && is_numeric($row['reactivated']))
                ? $row['reactivated']   : '';
            $preactivated  = (isset($row['preactivated'])  && is_numeric($row['preactivated']))
                ? $row['preactivated']  : '';
            $halfslept     = (isset($row['halfslept'])     && is_numeric($row['halfslept']))
                ? $row['halfslept']     : '';
            $awaken        = (isset($row['awaken'])        && is_numeric($row['awaken']))
                ? $row['awaken']        : '';
            $whitelist     = (isset($row['whitelist'])     && is_numeric($row['whitelist']))
                ? $row['whitelist']     : '';
            $precached     = (isset($row['precached'])     && is_numeric($row['precached']))
                ? $row['precached']     : '';
            $zeroclicks    = (isset($row['zeroclicks'])    && is_numeric($row['zeroclicks']))
                ? $row['zeroclicks']    : '';
            $new           = (isset($row['new'])           && is_numeric($row['new']))
                ? $row['new']           : '';
            $aos           = (isset($row['aos'])           && is_numeric($row['aos']))
                ? $row['aos']           : '';
            $slept         = (isset($row['slept'])         && is_numeric($row['slept']))
                ? $row['slept']         : '';
            $keepalive     = (isset($row['keepalive'])     && is_numeric($row['keepalive']))
                ? $row['keepalive']     : '';
            $stranger      = (isset($row['stranger'])      && is_numeric($row['stranger']))
                ? $row['stranger']      : '';
            $new_inactive  = (isset($row['new_inactive'])  && is_numeric($row['new_inactive']))
                ? $row['new_inactive']  : '';
            $total_inactive= (isset($row['total_inactive'])&& is_numeric($row['total_inactive']))
                ? $row['total_inactive']: '';

            // Check if record exists in config_changes
            $check_query = "
                SELECT COUNT(*) 
                FROM config_changes 
                WHERE sending_domain = '" . $conn->real_escape_string($sending_domain) . "'
                  AND user_domain    = '" . $conn->real_escape_string($user_domain)   . "'
                  AND country        = '" . $conn->real_escape_string($country)       . "'
            ";
            $check_result = $conn->query($check_query);
            if ($check_result === false) {
                $error = "Check error: " . htmlspecialchars($conn->error);
                file_put_contents($log_file, $error . "\n", FILE_APPEND);
                echo $error;
                continue;
            }

            $record_count = $check_result->fetch_row()[0];
            // If found => UPDATE, else => INSERT
            if ($record_count > 0) {
                // Update
                $update_query = "UPDATE config_changes SET 
                    manual_category  = " . escapeValue($manual_category, $conn)       . ",
                    updated_at       = " . escapeValue($currentTimestamp, $conn)     . ",
                    expected_volume_increment = " . escapeValue($expected_volume_increment, $conn) . ",
                    date_start       = " . escapeValue($date_start, $conn)           . ",
                    date_end         = " . escapeValue($date_end, $conn)             . ",
                    quien            = " . escapeValue($quien, $conn)                . ",
                    current_auto_rule= " . escapeValue($current_auto_rule, $conn)    . ",
                    percent_lwor     = " . escapeValue($percent_lwor, $conn)         . ",
                    percent_ldor     = " . escapeValue($percent_ldor, $conn)         . ",
                    current_sent     = " . escapeValue($current_sent, $conn)         . ",
                    sendable_user    = " . escapeValue($sendable_user, $conn)        . ",
                    clickers         = " . escapeValue($clickers, $conn)            . ",
                    openers          = " . escapeValue($openers, $conn)             . ",
                    reactivated      = " . escapeValue($reactivated, $conn)         . ",
                    preactivated     = " . escapeValue($preactivated, $conn)        . ",
                    halfslept        = " . escapeValue($halfslept, $conn)           . ",
                    awaken           = " . escapeValue($awaken, $conn)              . ",
                    whitelist        = " . escapeValue($whitelist, $conn)           . ",
                    precached        = " . escapeValue($precached, $conn)           . ",
                    zeroclicks       = " . escapeValue($zeroclicks, $conn)          . ",
                    new              = " . escapeValue($new, $conn)                 . ",
                    aos              = " . escapeValue($aos, $conn)                 . ",
                    slept            = " . escapeValue($slept, $conn)               . ",
                    keepalive        = " . escapeValue($keepalive, $conn)           . ",
                    stranger         = " . escapeValue($stranger, $conn)            . ",
                    new_inactive     = " . escapeValue($new_inactive, $conn)        . ",
                    total_inactive   = " . escapeValue($total_inactive, $conn)      . "
                WHERE sending_domain = " . escapeValue($sending_domain, $conn) . "
                  AND user_domain    = " . escapeValue($user_domain, $conn)   . "
                  AND country        = " . escapeValue($country, $conn);

                if ($conn->query($update_query) === false) {
                    $error = "Update error: " . htmlspecialchars($conn->error);
                    file_put_contents($log_file, $error . "\n", FILE_APPEND);
                    file_put_contents($log_file, $update_query . "\n", FILE_APPEND);
                    echo $error;
                } else {
                    file_put_contents($log_file, "Update successful for $sending_domain/$user_domain/$country\n", FILE_APPEND);
                }
            } else {
                // Insert
                $insert_query = "INSERT INTO config_changes (
                    sending_domain, user_domain, country, manual_category, user_id, updated_at, 
                    expected_volume_increment, date_start, date_end, quien, current_auto_rule, 
                    percent_lwor, percent_ldor, current_sent, sendable_user, clickers, openers, 
                    reactivated, preactivated, halfslept, awaken, whitelist, precached, 
                    zeroclicks, new, aos, slept, keepalive, stranger, new_inactive, total_inactive
                ) VALUES (
                    " . escapeValue($sending_domain, $conn)          . ",
                    " . escapeValue($user_domain, $conn)             . ",
                    " . escapeValue($country, $conn)                 . ",
                    " . escapeValue($manual_category, $conn)         . ",
                    " . escapeValue($user_id, $conn)                 . ",
                    " . escapeValue($currentTimestamp, $conn)        . ",
                    " . escapeValue($expected_volume_increment, $conn). ",
                    " . escapeValue($date_start, $conn)              . ",
                    " . escapeValue($date_end, $conn)                . ",
                    " . escapeValue($quien, $conn)                   . ",
                    " . escapeValue($current_auto_rule, $conn)       . ",
                    " . escapeValue($percent_lwor, $conn)            . ",
                    " . escapeValue($percent_ldor, $conn)            . ",
                    " . escapeValue($current_sent, $conn)            . ",
                    " . escapeValue($sendable_user, $conn)           . ",
                    " . escapeValue($clickers, $conn)                . ",
                    " . escapeValue($openers, $conn)                 . ",
                    " . escapeValue($reactivated, $conn)             . ",
                    " . escapeValue($preactivated, $conn)            . ",
                    " . escapeValue($halfslept, $conn)               . ",
                    " . escapeValue($awaken, $conn)                  . ",
                    " . escapeValue($whitelist, $conn)               . ",
                    " . escapeValue($precached, $conn)               . ",
                    " . escapeValue($zeroclicks, $conn)              . ",
                    " . escapeValue($new, $conn)                     . ",
                    " . escapeValue($aos, $conn)                     . ",
                    " . escapeValue($slept, $conn)                   . ",
                    " . escapeValue($keepalive, $conn)               . ",
                    " . escapeValue($stranger, $conn)                . ",
                    " . escapeValue($new_inactive, $conn)            . ",
                    " . escapeValue($total_inactive, $conn)          . "
                )";

                if ($conn->query($insert_query) === false) {
                    $error = "Insert error: " . htmlspecialchars($conn->error);
                    file_put_contents($log_file, $error . "\n", FILE_APPEND);
                    file_put_contents($log_file, $insert_query . "\n", FILE_APPEND);
                    echo $error;
                } else {
                    file_put_contents($log_file, "Insert successful for $sending_domain/$user_domain/$country\n", FILE_APPEND);
                }
            }

            // Always insert into conf_changes_log
            $insert_log_query = "INSERT INTO conf_changes_log (
                sending_domain, user_domain, country, manual_category, user_id, updated_at, 
                expected_volume_increment, date_start, date_end, quien, current_auto_rule, 
                percent_lwor, percent_ldor, current_sent, sendable_user, clickers, openers, 
                reactivated, preactivated, halfslept, awaken, whitelist, precached, 
                zeroclicks, new, aos, slept, keepalive, stranger, new_inactive, total_inactive
            ) VALUES (
                " . escapeValue($sending_domain, $conn)          . ",
                " . escapeValue($user_domain, $conn)             . ",
                " . escapeValue($country, $conn)                 . ",
                " . escapeValue($manual_category, $conn)         . ",
                " . escapeValue($user_id, $conn)                 . ",
                " . escapeValue($currentTimestamp, $conn)        . ",
                " . escapeValue($expected_volume_increment, $conn). ",
                " . escapeValue($date_start, $conn)              . ",
                " . escapeValue($date_end, $conn)                . ",
                " . escapeValue($quien, $conn)                   . ",
                " . escapeValue($current_auto_rule, $conn)       . ",
                " . escapeValue($percent_lwor, $conn)            . ",
                " . escapeValue($percent_ldor, $conn)            . ",
                " . escapeValue($current_sent, $conn)            . ",
                " . escapeValue($sendable_user, $conn)           . ",
                " . escapeValue($clickers, $conn)                . ",
                " . escapeValue($openers, $conn)                 . ",
                " . escapeValue($reactivated, $conn)             . ",
                " . escapeValue($preactivated, $conn)            . ",
                " . escapeValue($halfslept, $conn)               . ",
                " . escapeValue($awaken, $conn)                  . ",
                " . escapeValue($whitelist, $conn)               . ",
                " . escapeValue($precached, $conn)               . ",
                " . escapeValue($zeroclicks, $conn)              . ",
                " . escapeValue($new, $conn)                     . ",
                " . escapeValue($aos, $conn)                     . ",
                " . escapeValue($slept, $conn)                   . ",
                " . escapeValue($keepalive, $conn)               . ",
                " . escapeValue($stranger, $conn)                . ",
                " . escapeValue($new_inactive, $conn)            . ",
                " . escapeValue($total_inactive, $conn)          . "
            )";

            if ($conn->query($insert_log_query) === false) {
                $error = "Insert error (conf_changes_log): " . htmlspecialchars($conn->error);
                file_put_contents($log_file, $error . "\n", FILE_APPEND);
                echo $error;
            } else {
                file_put_contents($log_file, "Insert successful in conf_changes_log for $sending_domain/$user_domain/$country\n", FILE_APPEND);
            }
        } // End foreach domain row
    } // End foreach domain

    // Close DB connection
    $conn->close();
    echo "Changes saved successfully! Check /tmp for local CSVs and test.log for details.";
}
?>
