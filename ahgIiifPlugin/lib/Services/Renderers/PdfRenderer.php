<?php
declare(strict_types=1);

namespace AhgIiif\Services\Renderers;

/**
 * PDF renderer using browser native PDF viewer (iframe).
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class PdfRenderer implements RendererInterface
{
    public function supports(string $mimeType, array $context = []): bool
    {
        return stripos($mimeType, 'pdf') !== false;
    }

    public function render(array $config): string
    {
        $vid = $config['viewerId'];
        $height = $config['options']['height'] ?? '600px';
        $pdfUrl = htmlspecialchars($config['options']['pdfUrl'] ?? '');

        $html = '<div id="pdf-wrapper-' . $vid . '" class="pdf-wrapper">';

        // Toolbar
        $html .= '<div class="pdf-toolbar mb-2 d-flex justify-content-between align-items-center">';
        $html .= '<span class="badge bg-danger"><i class="fas fa-file-pdf me-1"></i>PDF Document</span>';
        $html .= '<div class="btn-group btn-group-sm">';
        $html .= '<a href="' . $pdfUrl . '" target="_blank" class="btn btn-outline-secondary" title="Open in new tab">';
        $html .= '<i class="fas fa-external-link-alt"></i></a>';
        $html .= '<a href="' . $pdfUrl . '" download class="btn btn-outline-secondary" title="Download PDF">';
        $html .= '<i class="fas fa-download"></i></a>';
        $html .= '</div></div>';

        // Iframe
        $html .= '<iframe id="pdf-frame-' . $vid . '" ';
        $html .= 'src="' . $pdfUrl . '" ';
        $html .= 'style="width:100%;height:' . $height . ';border:none;border-radius:8px;background:#525659;" ';
        $html .= 'title="PDF Viewer"></iframe>';

        $html .= '</div>';

        return $html;
    }

    public function getName(): string
    {
        return 'pdfjs';
    }

    public function getPriority(): int
    {
        return 50;
    }
}
