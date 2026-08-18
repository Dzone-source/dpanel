<?php

declare(strict_types=1);

namespace App\Services;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Exception;
use function error_log;

final class Cloudflare
{
    public static function initR2(): S3Client
    {
        $credentials = new Credentials($_ENV['r2_access_key_id'], $_ENV['r2_access_key_secret']);

        $options = [
            'region' => 'auto',
            'endpoint' => 'https://' . $_ENV['r2_account_id'] . '.r2.cloudflarestorage.com',
            'version' => 'latest',
            'credentials' => $credentials,
        ];

        return new S3Client($options);
    }

    public static function uploadR2($name, $file): void
    {
        $r2 = self::initR2();

        try {
            $r2->upload(
                $_ENV['r2_bucket_name'],
                $name,
                $file,
            );
        } catch (Exception $e) {
            // Echoing here would corrupt whatever response is being built.
            error_log('R2 upload failed: ' . $e->getMessage());
        }
    }

    public static function genR2PresignedUrl($fileName): string
    {
        $r2 = self::initR2();

        $cmd = $r2->getCommand('GetObject', [
            'Bucket' => $_ENV['r2_bucket_name'],
            'Key' => $fileName,
        ]);

        return (string) $r2->createPresignedRequest(
            $cmd,
            '+' . $_ENV['r2_client_download_timeout'] . ' minutes'
        )->getUri();
    }
}
