<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Infrastructure\Pdf;

use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use TCPDF;
use TCPDF_FONTS;

/**
 * Generates official PDF waybills with Persian RTL and Code128 barcodes (Architecture §6.8, TASK-097).
 */
final class WaybillGenerator
{
    /**
     * Generate raw binary PDF content for the given delivery request.
     */
    public function generate(DeliveryRequest $delivery): string
    {
        $this->ensureTcpdfLoaded();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('سامانه ملی پیشخوان هوشمند');
        $pdf->SetAuthor($delivery->office->name ?? 'پیشخوان');
        $pdf->SetTitle("بارنامه مرسوله {$delivery->tracking_barcode}");

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->setRTL(true);

        $pdf->AddPage();

        $font = $this->loadVazirmatnFont();
        $pdf->SetFont($font, '', 9);

        // Render Code128 barcode at top left
        $barcodeParams = [
            'position' => 'S',
            'border' => false,
            'padding' => 2,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4,
        ];
        $pdf->write1DBarcode($delivery->tracking_barcode, 'C128', 15, 12, 60, 16, 0.4, $barcodeParams, 'N');
        $pdf->Ln(20);

        // Render HTML content from Blade template
        $html = View::make('pdf.waybill', ['delivery' => $delivery])->render();
        $pdf->writeHTML($html, true, false, true, false, '');

        /** @var string $output */
        $output = $pdf->Output('', 'S');

        return $output;
    }

    /**
     * Generate waybill PDF and store it encrypted in MinIO under delivery-waybills/ (Architecture §6.8).
     *
     * @return array{
     *     storage_key: string,
     *     encrypted_data_key: string,
     *     content_sha256: string,
     *     size_bytes: int,
     *     encrypted_size_bytes: int
     * }
     */
    public function generateAndStore(DeliveryRequest $delivery, EncryptedObjectStore $store): array
    {
        $pdfContent = $this->generate($delivery);

        $province = strtoupper(trim($delivery->office->province_code ?? 'THR'));
        $year = $delivery->created_at->format('Y');
        $month = $delivery->created_at->format('m');
        $storageKey = "delivery-waybills/{$province}/{$year}/{$month}/{$delivery->id}.pdf.enc";

        return $store->store($storageKey, $pdfContent);
    }

    private function ensureTcpdfLoaded(): void
    {
        if (! class_exists('TCPDF')) {
            $tcpdfPath = App::basePath('vendor/tecnickcom/tcpdf/tcpdf.php');
            if (file_exists($tcpdfPath)) {
                require_once $tcpdfPath;
            }
        }
    }

    private function loadVazirmatnFont(): string
    {
        $fontPath = App::resourcePath('fonts/Vazirmatn-Regular.ttf');

        if (file_exists($fontPath)) {
            /** @var string $font */
            $font = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
            if ($font !== '') {
                return $font;
            }
        }

        return 'dejavusans';
    }
}
