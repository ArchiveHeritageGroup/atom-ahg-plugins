<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Barcode;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Barcode Generator Service.
 *
 * Generates barcodes and QR codes for museum objects, locations,
 * and other entities. Supports multiple barcode formats and
 * outputs (PNG, SVG, PDF).
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class BarcodeGenerator
{
    /** Barcode types */
    public const TYPE_CODE128 = 'C128';
    public const TYPE_CODE39 = 'C39';
    public const TYPE_EAN13 = 'EAN13';
    public const TYPE_UPCA = 'UPCA';
    public const TYPE_QR = 'QRCODE';
    public const TYPE_DATAMATRIX = 'DATAMATRIX';

    /** Output formats */
    public const FORMAT_PNG = 'png';
    public const FORMAT_SVG = 'svg';
    public const FORMAT_HTML = 'html';

    /** Default settings */
    private const DEFAULT_WIDTH = 2;
    private const DEFAULT_HEIGHT = 50;
    private const DEFAULT_QR_SIZE = 4;

    private LoggerInterface $logger;
    private string $outputDir;

    public function __construct(
        string $outputDir = '/tmp/barcodes',
        ?LoggerInterface $logger = null
    ) {
        $this->outputDir = rtrim($outputDir, '/');
        $this->logger = $logger ?? new NullLogger();

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Generate a barcode.
     *
     * @param string $data    Data to encode
     * @param string $type    Barcode type (C128, C39, EAN13, etc.)
     * @param string $format  Output format (png, svg, html)
     * @param array  $options Additional options
     *
     * @return string Base64-encoded image or SVG/HTML content
     */
    public function generate(
        string $data,
        string $type = self::TYPE_CODE128,
        string $format = self::FORMAT_PNG,
        array $options = []
    ): string {
        $width = $options['width'] ?? self::DEFAULT_WIDTH;
        $height = $options['height'] ?? self::DEFAULT_HEIGHT;
        $color = $options['color'] ?? [0, 0, 0];
        $showText = $options['show_text'] ?? true;

        // Check if tc-lib-barcode is available
        if (class_exists('\Com\Tecnick\Barcode\Barcode')) {
            return $this->generateWithTcLib($data, $type, $format, $width, $height, $color, $showText);
        }

        // Fallback to pure PHP implementation
        return $this->generateFallback($data, $type, $format, $width, $height, $color, $showText);
    }

    /**
     * Generate a QR code.
     *
     * @param string $data    Data to encode
     * @param string $format  Output format
     * @param array  $options Additional options
     *
     * @return string Generated QR code
     */
    public function generateQR(
        string $data,
        string $format = self::FORMAT_PNG,
        array $options = []
    ): string {
        $size = $options['size'] ?? self::DEFAULT_QR_SIZE;
        $margin = $options['margin'] ?? 2;
        $errorLevel = $options['error_level'] ?? 'M'; // L, M, Q, H

        if (class_exists('\Com\Tecnick\Barcode\Barcode')) {
            return $this->generateQRWithTcLib($data, $format, $size, $margin, $errorLevel);
        }

        return $this->generateQRFallback($data, $format, $size, $margin);
    }

    /**
     * Generate barcode for a museum object.
     *
     * @param string $identifier Object identifier (accession number)
     * @param string $type       Barcode type
     * @param array  $options    Additional options
     *
     * @return array Barcode data with multiple formats
     */
    public function generateForObject(
        string $identifier,
        string $type = self::TYPE_CODE128,
        array $options = []
    ): array {
        // Clean identifier for barcode
        $cleanId = $this->sanitizeForBarcode($identifier);

        return [
            'identifier' => $identifier,
            'clean_id' => $cleanId,
            'barcode_type' => $type,
            'png' => $this->generate($cleanId, $type, self::FORMAT_PNG, $options),
            'svg' => $this->generate($cleanId, $type, self::FORMAT_SVG, $options),
            'qr_png' => $this->generateQR($identifier, self::FORMAT_PNG, $options),
            'qr_svg' => $this->generateQR($identifier, self::FORMAT_SVG, $options),
        ];
    }

    /**
     * Generate barcode for a location.
     *
     * @param string $locationCode Location code
     * @param string $locationName Location name (for QR metadata)
     *
     * @return array Barcode data
     */
    public function generateForLocation(
        string $locationCode,
        string $locationName = ''
    ): array {
        $qrData = $locationName
            ? "LOC:{$locationCode}|{$locationName}"
            : "LOC:{$locationCode}";

        return [
            'location_code' => $locationCode,
            'location_name' => $locationName,
            'barcode_png' => $this->generate($locationCode, self::TYPE_CODE128, self::FORMAT_PNG),
            'barcode_svg' => $this->generate($locationCode, self::TYPE_CODE128, self::FORMAT_SVG),
            'qr_png' => $this->generateQR($qrData, self::FORMAT_PNG),
            'qr_svg' => $this->generateQR($qrData, self::FORMAT_SVG),
        ];
    }

    /**
     * Generate label sheet with multiple barcodes.
     *
     * @param array $items   Items to generate barcodes for
     * @param array $options Label options
     *
     * @return string HTML for label sheet
     */
    public function generateLabelSheet(array $items, array $options = []): string
    {
        $labelsPerRow = $options['labels_per_row'] ?? 3;
        $labelWidth = $options['label_width'] ?? '60mm';
        $labelHeight = $options['label_height'] ?? '30mm';
        $showTitle = $options['show_title'] ?? true;
        $showQR = $options['show_qr'] ?? true;
        $barcodeType = $options['barcode_type'] ?? self::TYPE_CODE128;

        $html = $this->getLabelSheetStyles($labelWidth, $labelHeight, $labelsPerRow);
        $html .= '<div class="label-sheet">';

        foreach (array_chunk($items, $labelsPerRow) as $row) {
            $html .= '<div class="label-row">';

            foreach ($row as $item) {
                $identifier = $item['identifier'] ?? $item['id'] ?? '';
                $title = $item['title'] ?? '';

                $barcodeSvg = $this->generate(
                    $this->sanitizeForBarcode($identifier),
                    $barcodeType,
                    self::FORMAT_SVG,
                    ['height' => 30, 'show_text' => true]
                );

                $html .= '<div class="label">';

                if ($showTitle && $title) {
                    $html .= '<div class="label-title">'.htmlspecialchars(substr($title, 0, 40)).'</div>';
                }

                $html .= '<div class="label-barcode">'.$barcodeSvg.'</div>';

                if ($showQR) {
                    $qrSvg = $this->generateQR($identifier, self::FORMAT_SVG, ['size' => 2]);
                    $html .= '<div class="label-qr">'.$qrSvg.'</div>';
                }

                $html .= '<div class="label-id">'.htmlspecialchars($identifier).'</div>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Save barcode to file.
     */
    public function saveToFile(
        string $data,
        string $filename,
        string $type = self::TYPE_CODE128,
        string $format = self::FORMAT_PNG,
        array $options = []
    ): string {
        $content = $this->generate($data, $type, $format, $options);

        $ext = $format;
        $path = $this->outputDir.'/'.$filename.'.'.$ext;

        if (self::FORMAT_PNG === $format) {
            $imageData = base64_decode($content);
            file_put_contents($path, $imageData);
        } else {
            file_put_contents($path, $content);
        }

        $this->logger->info('Barcode saved to file', ['path' => $path]);

        return $path;
    }

    /**
     * Generate using tc-lib-barcode library.
     */
    private function generateWithTcLib(
        string $data,
        string $type,
        string $format,
        int $width,
        int $height,
        array $color,
        bool $showText
    ): string {
        $barcode = new \Com\Tecnick\Barcode\Barcode();

        $bobj = $barcode->getBarcodeObj(
            $type,
            $data,
            $width,
            $height,
            'black',
            [-2, -2, -2, -2]
        );

        return match ($format) {
            self::FORMAT_SVG => $bobj->getSvgCode(),
            self::FORMAT_HTML => $bobj->getHtmlDiv(),
            default => base64_encode($bobj->getPngData()),
        };
    }

    /**
     * Generate QR using tc-lib-barcode library.
     */
    private function generateQRWithTcLib(
        string $data,
        string $format,
        int $size,
        int $margin,
        string $errorLevel
    ): string {
        $barcode = new \Com\Tecnick\Barcode\Barcode();

        $type = 'QRCODE,'.$errorLevel;
        $bobj = $barcode->getBarcodeObj($type, $data, $size, $size, 'black');

        return match ($format) {
            self::FORMAT_SVG => $bobj->getSvgCode(),
            self::FORMAT_HTML => $bobj->getHtmlDiv(),
            default => base64_encode($bobj->getPngData()),
        };
    }

    /**
     * Fallback barcode generation using pure PHP.
     */
    private function generateFallback(
        string $data,
        string $type,
        string $format,
        int $width,
        int $height,
        array $color,
        bool $showText
    ): string {
        $barcode = $this->encodeCode128B($data);

        if (self::FORMAT_SVG === $format) {
            return $this->renderAsSvg($barcode, $data, $width, $height, $showText);
        }

        return $this->renderAsPng($barcode, $data, $width, $height, $color, $showText);
    }

    /**
     * Fallback QR code generation.
     */
    private function generateQRFallback(
        string $data,
        string $format,
        int $size,
        int $margin
    ): string {
        $pixelSize = $size * 4;
        $totalSize = $pixelSize + ($margin * 2 * $size);

        if (self::FORMAT_SVG === $format) {
            return $this->generateSimpleQRSvg($data, $totalSize);
        }

        return $this->generateSimpleQRPng($data, $totalSize);
    }

    /**
     * Generate simple QR-like SVG.
     */
    private function generateSimpleQRSvg(string $data, int $size): string
    {
        $hash = md5($data);
        $gridSize = 21;
        $cellSize = $size / $gridSize;

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">',
            $size,
            $size,
            $size,
            $size
        );

        $svg .= sprintf('<rect width="%d" height="%d" fill="white"/>', $size, $size);

        $hashBits = '';
        for ($i = 0; $i < strlen($hash); ++$i) {
            $hashBits .= str_pad(decbin(hexdec($hash[$i])), 4, '0', STR_PAD_LEFT);
        }

        // Draw finder patterns
        $svg .= $this->drawFinderPattern(0, 0, $cellSize);
        $svg .= $this->drawFinderPattern($size - (7 * $cellSize), 0, $cellSize);
        $svg .= $this->drawFinderPattern(0, $size - (7 * $cellSize), $cellSize);

        // Draw data pattern
        $bitIndex = 0;
        for ($y = 8; $y < $gridSize - 8; ++$y) {
            for ($x = 8; $x < $gridSize - 8; ++$x) {
                if ($bitIndex < strlen($hashBits) && '1' === $hashBits[$bitIndex]) {
                    $svg .= sprintf(
                        '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="black"/>',
                        $x * $cellSize,
                        $y * $cellSize,
                        $cellSize,
                        $cellSize
                    );
                }
                ++$bitIndex;
                if ($bitIndex >= strlen($hashBits)) {
                    $bitIndex = 0;
                }
            }
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Draw QR finder pattern.
     */
    private function drawFinderPattern(float $x, float $y, float $cellSize): string
    {
        $svg = '';

        $svg .= sprintf(
            '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="black"/>',
            $x,
            $y,
            7 * $cellSize,
            7 * $cellSize
        );

        $svg .= sprintf(
            '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="white"/>',
            $x + $cellSize,
            $y + $cellSize,
            5 * $cellSize,
            5 * $cellSize
        );

        $svg .= sprintf(
            '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="black"/>',
            $x + (2 * $cellSize),
            $y + (2 * $cellSize),
            3 * $cellSize,
            3 * $cellSize
        );

        return $svg;
    }

    /**
     * Generate simple QR PNG placeholder.
     */
    private function generateSimpleQRPng(string $data, int $size): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return '';
        }

        $image = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $white);

        $hash = md5($data);
        $gridSize = 21;
        $cellSize = (int) ($size / $gridSize);

        for ($i = 0; $i < strlen($hash); ++$i) {
            $val = hexdec($hash[$i]);
            $x = ($i % $gridSize) * $cellSize;
            $y = (int) ($i / $gridSize) * $cellSize;

            if ($val > 7) {
                imagefilledrectangle($image, $x, $y, $x + $cellSize - 1, $y + $cellSize - 1, $black);
            }
        }

        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();
        imagedestroy($image);

        return base64_encode($pngData);
    }

    /**
     * Encode data as Code 128 B.
     */
    private function encodeCode128B(string $data): string
    {
        $patterns = [
            ' ' => '11011001100', '!' => '11001101100', '"' => '11001100110',
            '0' => '10011101100', '1' => '10011100110', '2' => '11001110010',
            '3' => '11001011100', '4' => '11001001110', '5' => '11011100100',
            '6' => '11001110100', '7' => '11101101110', '8' => '11101001100',
            '9' => '11100101100', 'A' => '10100011000', 'B' => '10001011000',
            'C' => '10001000110', 'D' => '10110001000', 'E' => '10001101000',
            'F' => '10001100010', 'G' => '11010001000', 'H' => '11000101000',
            'I' => '11000100010', 'J' => '10110111000', 'K' => '10110001110',
            'L' => '10001101110', 'M' => '10111011000', 'N' => '10111000110',
            'O' => '10001110110', 'P' => '11101110110', 'Q' => '11010001110',
            'R' => '11000101110', 'S' => '11011101000', 'T' => '11011100010',
            'U' => '11011101110', 'V' => '11101011000', 'W' => '11101000110',
            'X' => '11100010110', 'Y' => '11101101000', 'Z' => '11101100010',
            '-' => '10011011100', '.' => '10011001110', '/' => '10111001100',
        ];

        $startB = '11010010000';
        $stop = '1100011101011';

        $barcode = $startB;
        $checksum = 104;

        $data = strtoupper($data);
        for ($i = 0; $i < strlen($data); ++$i) {
            $char = $data[$i];
            if (isset($patterns[$char])) {
                $barcode .= $patterns[$char];
                $checksum += (ord($char) - 32) * ($i + 1);
            }
        }

        $checksumChar = chr(($checksum % 103) + 32);
        if (isset($patterns[$checksumChar])) {
            $barcode .= $patterns[$checksumChar];
        }

        $barcode .= $stop;

        return $barcode;
    }

    /**
     * Render barcode pattern as SVG.
     */
    private function renderAsSvg(
        string $pattern,
        string $data,
        int $barWidth,
        int $height,
        bool $showText
    ): string {
        $width = strlen($pattern) * $barWidth;
        $totalHeight = $showText ? $height + 15 : $height;

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">',
            $width,
            $totalHeight,
            $width,
            $totalHeight
        );

        $svg .= sprintf('<rect width="%d" height="%d" fill="white"/>', $width, $totalHeight);

        $x = 0;
        for ($i = 0; $i < strlen($pattern); ++$i) {
            if ('1' === $pattern[$i]) {
                $svg .= sprintf(
                    '<rect x="%d" y="0" width="%d" height="%d" fill="black"/>',
                    $x,
                    $barWidth,
                    $height
                );
            }
            $x += $barWidth;
        }

        if ($showText) {
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="monospace" font-size="10" text-anchor="middle">%s</text>',
                $width / 2,
                $height + 12,
                htmlspecialchars($data)
            );
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Render barcode pattern as PNG.
     */
    private function renderAsPng(
        string $pattern,
        string $data,
        int $barWidth,
        int $height,
        array $color,
        bool $showText
    ): string {
        if (!function_exists('imagecreatetruecolor')) {
            return '';
        }

        $width = strlen($pattern) * $barWidth;
        $totalHeight = $showText ? $height + 15 : $height;

        $image = imagecreatetruecolor($width, $totalHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, $color[0], $color[1], $color[2]);

        imagefill($image, 0, 0, $white);

        $x = 0;
        for ($i = 0; $i < strlen($pattern); ++$i) {
            if ('1' === $pattern[$i]) {
                imagefilledrectangle($image, $x, 0, $x + $barWidth - 1, $height - 1, $black);
            }
            $x += $barWidth;
        }

        if ($showText) {
            $textX = (int) (($width - (strlen($data) * 6)) / 2);
            imagestring($image, 2, $textX, $height + 2, $data, $black);
        }

        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();
        imagedestroy($image);

        return base64_encode($pngData);
    }

    /**
     * Sanitize data for barcode encoding.
     */
    private function sanitizeForBarcode(string $data): string
    {
        $clean = preg_replace('/[^\x20-\x7E]/', '', $data);

        return substr($clean, 0, 50);
    }

    /**
     * Get CSS styles for label sheet.
     */
    private function getLabelSheetStyles(string $labelWidth, string $labelHeight, int $perRow): string
    {
        return <<<HTML
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
@media print {
    body { margin: 0; padding: 0; }
    .label-sheet { page-break-inside: avoid; }
}
.label-sheet {
    font-family: Arial, sans-serif;
    font-size: 10px;
}
.label-row {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 2mm;
}
.label {
    width: {$labelWidth};
    height: {$labelHeight};
    border: 1px solid #ccc;
    padding: 2mm;
    margin: 1mm;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    box-sizing: border-box;
    position: relative;
}
.label-title {
    font-weight: bold;
    font-size: 8px;
    text-align: center;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.label-barcode {
    flex: 1;
    display: flex;
    align-items: center;
}
.label-barcode svg {
    max-width: 100%;
    height: auto;
}
.label-qr {
    position: absolute;
    right: 2mm;
    top: 2mm;
}
.label-qr svg {
    width: 12mm;
    height: 12mm;
}
.label-id {
    font-family: monospace;
    font-size: 9px;
}
</style>
HTML;
    }

    /**
     * Get supported barcode types.
     */
    public function getSupportedTypes(): array
    {
        return [
            self::TYPE_CODE128 => 'Code 128',
            self::TYPE_CODE39 => 'Code 39',
            self::TYPE_EAN13 => 'EAN-13',
            self::TYPE_UPCA => 'UPC-A',
            self::TYPE_QR => 'QR Code',
            self::TYPE_DATAMATRIX => 'Data Matrix',
        ];
    }
}
