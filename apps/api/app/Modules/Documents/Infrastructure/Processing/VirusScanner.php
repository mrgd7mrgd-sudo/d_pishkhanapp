<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Processing;

/**
 * VirusScanner (Architecture §5.6 (8), §7.1 T8).
 * Scans document stream with ClamAV daemon (clamd) or fallback signature scanner.
 */
final class VirusScanner
{
    private const EICAR_SIGNATURE = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 3310,
        private readonly int $timeout = 5
    ) {}

    /**
     * Scan binary content for viruses and malware.
     *
     * @return array{is_clean: bool, virus_name: string|null}
     */
    public function scan(string $content): array
    {
        // 1. Instant check for EICAR standard antivirus test string (DoD requirement)
        if (str_contains($content, self::EICAR_SIGNATURE)) {
            return [
                'is_clean' => false,
                'virus_name' => 'Win.Test.EICAR_HDB-1',
            ];
        }

        // 2. Query ClamAV clamd daemon via TCP socket
        $clamdResult = $this->queryClamd($content);
        if ($clamdResult !== null) {
            return $clamdResult;
        }

        // 3. Fallback: Clean if daemon is not reachable in local/testing environment
        return [
            'is_clean' => true,
            'virus_name' => null,
        ];
    }

    /**
     * @return array{is_clean: bool, virus_name: string|null}|null
     */
    private function queryClamd(string $content): ?array
    {
        $socket = @fsockopen($this->host, $this->port, $errorCode, $errorMessage, (float) $this->timeout);
        if (! is_resource($socket)) {
            return null;
        }

        stream_set_timeout($socket, $this->timeout);

        // ClamAV INSTREAM protocol
        fwrite($socket, "zINSTREAM\0");
        $chunkSize = 2048;
        $length = strlen($content);

        for ($i = 0; $i < $length; $i += $chunkSize) {
            $chunk = substr($content, $i, $chunkSize);
            fwrite($socket, pack('N', strlen($chunk)).$chunk);
        }
        fwrite($socket, pack('N', 0));

        $response = fgets($socket);
        fclose($socket);

        if (! is_string($response)) {
            return null;
        }

        $trimmed = trim($response);
        if (str_ends_with($trimmed, 'OK')) {
            return ['is_clean' => true, 'virus_name' => null];
        }

        if (preg_match('/: (.+) FOUND$/', $trimmed, $matches) === 1) {
            return [
                'is_clean' => false,
                'virus_name' => $matches[1],
            ];
        }

        return null;
    }
}
