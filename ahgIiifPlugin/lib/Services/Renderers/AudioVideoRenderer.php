<?php
declare(strict_types=1);

namespace AhgIiif\Services\Renderers;

/**
 * Audio/Video renderer using HTML5 media elements.
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class AudioVideoRenderer implements RendererInterface
{
    public function supports(string $mimeType, array $context = []): bool
    {
        return stripos($mimeType, 'audio') !== false
            || stripos($mimeType, 'video') !== false;
    }

    public function render(array $config): string
    {
        $vid = $config['viewerId'];
        $height = $config['options']['height'] ?? '600px';
        $mediaUrl = htmlspecialchars($config['options']['mediaUrl'] ?? '');
        $mimeType = $config['mimeType'] ?? 'video/mp4';
        $isAudio = stripos($mimeType, 'audio') !== false;

        $html = '<div id="av-wrapper-' . $vid . '" class="av-wrapper">';

        if ($isAudio) {
            $html .= '<audio id="audio-' . $vid . '" controls style="width:100%;">';
            $html .= '<source src="' . $mediaUrl . '" type="' . htmlspecialchars($mimeType) . '">';
            $html .= 'Your browser does not support the audio element.</audio>';
        } else {
            $html .= '<video id="video-' . $vid . '" controls ';
            $html .= 'style="width:100%;height:' . $height . ';background:#000;border-radius:8px;">';
            $html .= '<source src="' . $mediaUrl . '" type="' . htmlspecialchars($mimeType) . '">';
            $html .= 'Your browser does not support the video element.</video>';
        }

        $html .= '</div>';

        return $html;
    }

    public function getName(): string
    {
        return 'av';
    }

    public function getPriority(): int
    {
        return 40;
    }
}
