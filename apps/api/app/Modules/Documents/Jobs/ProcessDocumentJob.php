<?php

declare(strict_types=1);

namespace App\Modules\Documents\Jobs;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\Documents\Infrastructure\Processing\ExifStripper;
use App\Modules\Documents\Infrastructure\Processing\ImageNormalizer;
use App\Modules\Documents\Infrastructure\Processing\MimeSniffer;
use App\Modules\Documents\Infrastructure\Processing\QualityAnalyzer;
use App\Modules\Documents\Infrastructure\Processing\VirusScanner;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ProcessDocumentJob (Architecture §5.6 (8), §7.1 T8).
 * 8-step pipeline for document processing, malware scanning, EXIF stripping,
 * quality analysis, and envelope encrypted storage.
 */
final class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $documentId
    ) {}

    public function handle(
        MimeSniffer $mimeSniffer,
        VirusScanner $virusScanner,
        ExifStripper $exifStripper,
        ImageNormalizer $imageNormalizer,
        QualityAnalyzer $qualityAnalyzer,
        EncryptedObjectStore $store
    ): void {
        $caseDoc = CaseDocument::query()->find($this->documentId);
        if ($caseDoc === null) {
            return;
        }

        $rawPlaintext = $store->retrieve($caseDoc->storage_key, $caseDoc->encrypted_data_key);

        // Step 1: MIME sniffing from content
        $sniffedMime = $mimeSniffer->sniff($rawPlaintext);
        if (! $mimeSniffer->isAllowed($sniffedMime)) {
            $caseDoc->update([
                'status' => CaseDocumentStatus::REJECTED,
                'reason_code' => 'DOC_WRONG_TYPE',
            ]);

            return;
        }

        // Step 2: Virus and malware scanning
        $scan = $virusScanner->scan($rawPlaintext);
        if (! $scan['is_clean']) {
            $caseDoc->update([
                'status' => CaseDocumentStatus::REJECTED,
                'reason_code' => 'VIRUS_DETECTED',
            ]);

            return;
        }

        // Step 3: Complete EXIF stripping (including GPS coordinates)
        $strippedContent = $exifStripper->strip($rawPlaintext, $sniffedMime);

        // Step 4: JPEG normalization (quality 85, max dimension 2400)
        $normalized = $imageNormalizer->normalize($strippedContent, $sniffedMime);
        $processedContent = $normalized['content'];
        $finalMime = $normalized['mime_type'];

        // Step 5: Local quality analysis (blur, crop, rotation)
        $warnings = $qualityAnalyzer->analyze($processedContent, $finalMime);

        // Step 6 & 7: Envelope encryption and storage to MinIO
        $storeResult = $store->store($caseDoc->storage_key, $processedContent);

        // Step 8: Update CaseDocument with verified status and quality warnings
        $caseDoc->update([
            'encrypted_data_key' => $storeResult['encrypted_data_key'],
            'content_sha256' => $storeResult['content_sha256'],
            'size_bytes' => $storeResult['size_bytes'],
            'mime_type' => $finalMime,
            'quality_warnings' => $warnings,
            'status' => CaseDocumentStatus::VERIFIED,
        ]);
    }
}
