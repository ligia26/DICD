<?php
require '/var/www/clients.datainnovation.io/html/vendor/autoload.php';
include 'includes/db.php';
include 'includes/functions.php';
global $conn;

use Aws\Athena\AthenaClient;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider; 

class AthenaSql {
    public function sql($query, $output) {
        echo $query . PHP_EOL;
        $retry = 2;

        $client = new AthenaClient([
            'version' => 'latest',
            'region'  => 'eu-west-3',
            'credentials' => CredentialProvider::ini('default', '/home/www-data/.aws/credentials')
        ]);

        while ($retry > 0) {
            try {
                $queryId = $client->startQueryExecution([
                    'QueryString' => $query,
                    'ResultConfiguration' => [
                        'OutputLocation' => $output
                    ]
                ])->get('QueryExecutionId');

                echo "Query ID: " . $queryId . PHP_EOL;

                $queryStatus = null;
                while ($queryStatus == 'QUEUED' || $queryStatus == 'RUNNING' || $queryStatus == null) {
                    $res = $client->getQueryExecution([
                        'QueryExecutionId' => $queryId
                    ])->get('QueryExecution');

                    $queryStatus = $res['Status']['State'];

                    if ($queryStatus == 'FAILED' || $queryStatus == 'CANCELLED') {
                        $reason = $res['Status']['StateChangeReason'];
                        throw new Exception("Athena query failed or was cancelled. Error: {$reason}");
                    }

                    sleep(2);
                    echo "Query Status: " . $queryStatus . PHP_EOL;
                }

                if ($queryStatus == 'SUCCEEDED') {
                    $parsedUrl = parse_url($output);
                    $bucket = $parsedUrl['host'];
                    $prefix = ltrim($parsedUrl['path'], '/');
                    $outputS3Key = $prefix . $queryId . '.csv';

                    return [$bucket, $outputS3Key];
                }

            } catch (AwsException $e) {
                echo "Unexpected error: " . $e->getMessage() . PHP_EOL;
                error_log("Athena query failed with error: " . $e->getAwsErrorMessage());
                error_log("Request ID: " . $e->getAwsRequestId());
                sleep(2);
                $retry--;

                if ($retry == 0) {
                    throw new Exception("Athena query failed after multiple attempts.");
                }
            }
        }
    }
}

$query = "
SELECT
    ROUND(100 * SUM(open) / CAST(SUM(sent) AS DOUBLE), 2)                         AS open_rate,
    ROUND(100 * SUM(click) / CAST(SUM(sent) AS DOUBLE), 2)                        AS click_rate,
    ROUND(100 * SUM(softbounces + hardbounces + block_bounce) 
             / CAST(SUM(sent) AS DOUBLE), 2)                                      AS bounce_rate,
    SUM(sent)                                                                     AS sent_amount,
    class                                                                         AS class_old,
    sending_domain,
    user_domain_mx,
    country                                                                       AS country_code
FROM (
    SELECT * FROM plnv.events_grouped_dimension     WHERE date = CURRENT_DATE - interval '1' day
    UNION ALL
    SELECT * FROM feebbo.events_grouped_dimension   WHERE date = CURRENT_DATE - interval '1' day
    UNION ALL
    SELECT * FROM adviceme.events_grouped_dimension WHERE date = CURRENT_DATE - interval '1' day
    UNION ALL
    SELECT * FROM leadgenios.events_grouped_dimension WHERE date = CURRENT_DATE - interval '1' day
    UNION ALL
    SELECT * FROM cpcseguro.events_grouped_dimension WHERE date = CURRENT_DATE - interval '1' day
    UNION ALL
    SELECT * FROM kummedia.events_grouped_dimension WHERE date = CURRENT_DATE - interval '1' day
    UNION ALL
    SELECT * FROM tden.events_grouped_dimension     WHERE date = CURRENT_DATE - interval '1' day
    UNION ALL
    SELECT * FROM mnst.events_grouped_dimension     WHERE date = CURRENT_DATE - interval '1' day
) e
GROUP BY
    class,
    sending_domain,
    user_domain_mx,
    country;
";

$output = 's3://datainnovation.athenaqueryresults2022b/hello/';
$athenaSql = new AthenaSql();

try {
    list($bucket, $outputS3Key) = $athenaSql->sql($query, $output);
    echo "The result is stored at S3 Bucket: $bucket with Key: $outputS3Key" . PHP_EOL;
} catch (Exception $e) {
    echo "Failed to execute Athena query: " . $e->getMessage();
    exit();
}

function updateRatesAndClass($s3Client, $bucket, $key, $conn) {
    try {
        $result = $s3Client->getObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        $data = (string) $result['Body'];

        $rows = explode("\n", trim($data));
        $header = str_getcsv(array_shift($rows));

        foreach ($rows as $row) {
            if (empty(trim($row))) continue;
            $rowData = str_getcsv($row);
            $dataAssoc = array_combine($header, $rowData);

            $sendingDomain = $dataAssoc['sending_domain'];
            $userDomainMx = $dataAssoc['user_domain_mx'];
            $countryCode = strtolower($dataAssoc['country_code']);
            $classOld = $dataAssoc['class_old'];
            $openRate = $dataAssoc['open_rate'];
            $clickRate = $dataAssoc['click_rate'];
            $bounceRate = $dataAssoc['bounce_rate'];
            $sent_amount = $dataAssoc['sent_amount'];

            $stmt = $conn->prepare("UPDATE config_changes_s3_data SET current_auto_rule = ?, open_rate = ?, click_rate = ?, bounce_rate = ?,sent_amount = ?, last_update = NOW() WHERE sending_domain = ? AND LOWER(user_domain) = LOWER(?) AND LOWER(country) = LOWER(?)");
            if ($stmt) {
                $stmt->bind_param("ssssssss", $classOld, $openRate, $clickRate, $bounceRate,  $sent_amount, $sendingDomain, $userDomainMx, $countryCode);
                $stmt->execute();
                echo "Updated $sendingDomain | $userDomainMx | $countryCode" . PHP_EOL;
                $stmt->close();
            } else {
                echo "MySQL statement failed to prepare for $sendingDomain | $userDomainMx" . PHP_EOL;
            }
        }

    } catch (AwsException $e) {
        echo "Error fetching file from S3: " . $e->getMessage() . PHP_EOL;
        error_log($e->getMessage());
    }
}

$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'eu-west-3',
    'credentials' => CredentialProvider::ini('default', '/home/www-data/.aws/credentials')
]);

updateRatesAndClass($s3Client, $bucket, $outputS3Key, $conn);
?>