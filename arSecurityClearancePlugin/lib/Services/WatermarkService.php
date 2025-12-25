<?php

/**
 * Watermark Service for Security Clearance Plugin.
 *
 * Applies dynamic watermarks to downloaded documents based on classification.
 * Supports PDF, image, and document watermarking with tracking codes.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

class WatermarkService
{
    /** @var string Watermark text template */
    private string $textTemplate;

    /** @var float Watermark opacity (0-1) */
    private float $opacity;

    /** @var string Font size for text watermarks */
    private string $fontSize = '24';

    /** @var string Watermark color */
    private string $color;

    public function __construct()
    {
        // Load from plugin config
        $this->textTemplate = sfConfig::get(
            'app_security_clearance_watermark_text_template',
            'CONFIDENTIAL - {username} - {date} - {code}'
        );
        $this->opacity = (float) sfConfig::get(
            'app_security_clearance_watermark_opacity',
            0.3
        );
        $this->color = sfConfig::get(
            'app_security_clearance_watermark_color',
            '#FF0000'
        );
    }

    /**
     * Apply watermark to file and return path to watermarked file.
     */
    public function applyWatermark(
        string $filePath,
        int $userId,
        int $objectId,
        ?int $digitalObjectId = null
    ): ?string {
        $watermarkData = $this->generateWatermarkData($userId, $objectId, $digitalObjectId);

        $mimeType = mime_content_type($filePath);

        switch ($mimeType) {
            case 'application/pdf':
                return $this->watermarkPdf($filePath, $watermarkData);

            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
                return $this->watermarkImage($filePath, $watermarkData);

            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return $this->watermarkDocx($filePath, $watermarkData);

            default:
                // For unsupported types, log and return original
                $this->logWatermark($watermarkData, null);

                return $filePath;
        }
    }

    /**
     * Generate watermark data.
     */
    private function generateWatermarkData(int $userId, int $objectId, ?int $digitalObjectId): array
    {
        $user = DB::table('user')->where('id', $userId)->first();
        $code = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 12));

        $text = str_replace(
            ['{username}', '{date}', '{code}', '{email}'],
            [
                $user ? $user->username : 'Unknown',
                date('Y-m-d H:i'),
                $code,
                $user ? $user->email : '',
            ],
            $this->textTemplate
        );

        return [
            'user_id' => $userId,
            'object_id' => $objectId,
            'digital_object_id' => $digitalObjectId,
            'code' => $code,
            'text' => $text,
            'username' => $user ? $user->username : 'Unknown',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Watermark PDF using pdftk or similar.
     */
    private function watermarkPdf(string $filePath, array $watermarkData): ?string
    {
        $outputPath = sys_get_temp_dir().'/'.uniqid('wm_').'.pdf';

        // Create watermark stamp PDF
        $stampPath = $this->createPdfStamp($watermarkData);

        if (!$stampPath) {
            return null;
        }

        // Try pdftk first
        $command = sprintf(
            'pdftk %s stamp %s output %s 2>/dev/null',
            escapeshellarg($filePath),
            escapeshellarg($stampPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        // Cleanup stamp
        @unlink($stampPath);

        if (0 === $returnCode && file_exists($outputPath)) {
            $this->logWatermark($watermarkData, $outputPath);

            return $outputPath;
        }

        // Fallback: Try using Ghostscript
        return $this->watermarkPdfWithGhostscript($filePath, $watermarkData);
    }

    /**
     * Create PDF stamp for watermarking.
     */
    private function createPdfStamp(array $watermarkData): ?string
    {
        $stampPath = sys_get_temp_dir().'/'.uniqid('stamp_').'.pdf';

        // Create simple text stamp using ImageMagick
        $command = sprintf(
            'convert -size 800x100 xc:transparent -font Helvetica -pointsize 24 '.
            '-fill "rgba(255,0,0,0.3)" -gravity Center '.
            '-annotate 0 %s %s 2>/dev/null',
            escapeshellarg($watermarkData['text']),
            escapeshellarg($stampPath)
        );

        exec($command, $output, $returnCode);

        if (0 === $returnCode && file_exists($stampPath)) {
            return $stampPath;
        }

        return null;
    }

    /**
     * Watermark PDF using Ghostscript as fallback.
     */
    private function watermarkPdfWithGhostscript(string $filePath, array $watermarkData): ?string
    {
        $outputPath = sys_get_temp_dir().'/'.uniqid('wm_').'.pdf';

        // Create PostScript watermark overlay
        $psContent = sprintf(
            'gsave
            0.3 setgray
            /Helvetica findfont 24 scalefont setfont
            300 400 moveto
            45 rotate
            (%s) show
            grestore',
            addslashes($watermarkData['text'])
        );

        $psPath = sys_get_temp_dir().'/'.uniqid('wm_').'.ps';
        file_put_contents($psPath, $psContent);

        $command = sprintf(
            'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s 2>/dev/null',
            escapeshellarg($outputPath),
            escapeshellarg($filePath)
        );

        exec($command, $output, $returnCode);

        @unlink($psPath);

        if (0 === $returnCode && file_exists($outputPath)) {
            $this->logWatermark($watermarkData, $outputPath);

            return $outputPath;
        }

        // If all else fails, log but return original
        $this->logWatermark($watermarkData, null);

        return $filePath;
    }

    /**
     * Watermark image using ImageMagick.
     */
    private function watermarkImage(string $filePath, array $watermarkData): ?string
    {
        $outputPath = sys_get_temp_dir().'/'.uniqid('wm_').'.'.pathinfo($filePath, PATHINFO_EXTENSION);

        $command = sprintf(
            'convert %s -gravity SouthEast -font Helvetica -pointsize 16 '.
            '-fill "rgba(255,0,0,0.4)" -annotate +10+10 %s '.
            '-gravity Center -font Helvetica -pointsize 48 -fill "rgba(255,0,0,0.15)" '.
            '-annotate 45x45+0+0 "CONFIDENTIAL" '.
            '%s 2>/dev/null',
            escapeshellarg($filePath),
            escapeshellarg($watermarkData['text']),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        if (0 === $returnCode && file_exists($outputPath)) {
            $this->logWatermark($watermarkData, $outputPath);

            return $outputPath;
        }

        // Fallback using GD if ImageMagick not available
        return $this->watermarkImageWithGD($filePath, $watermarkData);
    }

    /**
     * Watermark image using PHP GD as fallback.
     */
    private function watermarkImageWithGD(string $filePath, array $watermarkData): ?string
    {
        $info = getimagesize($filePath);
        if (!$info) {
            return null;
        }

        $mimeType = $info['mime'];
        $width = $info[0];
        $height = $info[1];

        // Load image
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filePath);
                break;
            default:
                return null;
        }

        if (!$image) {
            return null;
        }

        // Create watermark color (semi-transparent red)
        $watermarkColor = imagecolorallocatealpha($image, 255, 0, 0, 80);

        // Add diagonal watermark text
        $fontSize = 4; // Built-in font
        $text = 'CONFIDENTIAL';

        // Add multiple watermarks diagonally
        for ($y = 50; $y < $height; $y += 150) {
            for ($x = -100; $x < $width; $x += 250) {
                imagettftext($image, 24, 45, $x, $y, $watermarkColor, '', $text);
            }
        }

        // Add tracking info at bottom
        imagestring($image, 2, 10, $height - 20, $watermarkData['text'], $watermarkColor);

        // Save watermarked image
        $outputPath = sys_get_temp_dir().'/'.uniqid('wm_').'.'.pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $outputPath, 90);
                break;
            case 'image/png':
                imagepng($image, $outputPath);
                break;
            case 'image/gif':
                imagegif($image, $outputPath);
                break;
        }

        imagedestroy($image);

        if (file_exists($outputPath)) {
            $this->logWatermark($watermarkData, $outputPath);

            return $outputPath;
        }

        return null;
    }

    /**
     * Watermark DOCX document.
     */
    private function watermarkDocx(string $filePath, array $watermarkData): ?string
    {
        // DOCX watermarking requires unzipping, modifying XML, and rezipping
        $outputPath = sys_get_temp_dir().'/'.uniqid('wm_').'.docx';

        // Copy original
        if (!copy($filePath, $outputPath)) {
            return null;
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($outputPath)) {
            return null;
        }

        // Read existing header or create new one
        $headerXml = $this->createDocxHeaderWithWatermark($watermarkData['text']);

        // Add or update header
        $zip->addFromString('word/header1.xml', $headerXml);

        // Update document.xml.rels to include header
        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if (!str_contains($relsXml, 'header1.xml')) {
            $relsXml = str_replace(
                '</Relationships>',
                '<Relationship Id="rIdHeader1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/></Relationships>',
                $relsXml
            );
            $zip->addFromString('word/_rels/document.xml.rels', $relsXml);
        }

        $zip->close();

        $this->logWatermark($watermarkData, $outputPath);

        return $outputPath;
    }

    /**
     * Create DOCX header XML with watermark.
     */
    private function createDocxHeaderWithWatermark(string $text): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
       xmlns:v="urn:schemas-microsoft-com:vml"
       xmlns:o="urn:schemas-microsoft-com:office:office">
    <w:p>
        <w:pPr>
            <w:pStyle w:val="Header"/>
        </w:pPr>
        <w:r>
            <w:rPr>
                <w:color w:val="FF0000"/>
                <w:sz w:val="20"/>
            </w:rPr>
            <w:t>'.htmlspecialchars($text).'</w:t>
        </w:r>
    </w:p>
</w:hdr>';
    }

    /**
     * Log watermark application.
     */
    private function logWatermark(array $watermarkData, ?string $outputPath): void
    {
        $fileHash = $outputPath && file_exists($outputPath) ? md5_file($outputPath) : null;

        DB::table('security_watermark_log')->insert([
            'user_id' => $watermarkData['user_id'],
            'object_id' => $watermarkData['object_id'],
            'digital_object_id' => $watermarkData['digital_object_id'],
            'watermark_type' => 'visible',
            'watermark_text' => $watermarkData['text'],
            'watermark_code' => $watermarkData['code'],
            'file_hash' => $fileHash,
            'file_name' => $outputPath ? basename($outputPath) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Trace watermark code to find source.
     */
    public function traceWatermark(string $code): ?array
    {
        $log = DB::table('security_watermark_log as swl')
            ->join('user as u', 'swl.user_id', '=', 'u.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('swl.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('swl.watermark_code', $code)
            ->select([
                'swl.*',
                'u.username',
                'u.email',
                'ioi.title as object_title',
            ])
            ->first();

        return $log ? (array) $log : null;
    }

    /**
     * Get all watermarks for an object.
     */
    public function getObjectWatermarks(int $objectId): array
    {
        return DB::table('security_watermark_log as swl')
            ->join('user as u', 'swl.user_id', '=', 'u.id')
            ->where('swl.object_id', $objectId)
            ->select(['swl.*', 'u.username'])
            ->orderBy('swl.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get all watermarks by user.
     */
    public function getUserWatermarks(int $userId, int $limit = 100): array
    {
        return DB::table('security_watermark_log as swl')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('swl.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('swl.user_id', $userId)
            ->select(['swl.*', 'ioi.title as object_title'])
            ->orderBy('swl.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Set watermark template.
     */
    public function setTemplate(string $template): self
    {
        $this->textTemplate = $template;

        return $this;
    }

    /**
     * Set watermark opacity.
     */
    public function setOpacity(float $opacity): self
    {
        $this->opacity = max(0, min(1, $opacity));

        return $this;
    }

    /**
     * Set watermark color.
     */
    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }
}