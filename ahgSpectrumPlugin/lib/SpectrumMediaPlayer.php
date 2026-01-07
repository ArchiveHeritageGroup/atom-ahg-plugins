<?php

/*
 * Enhanced Media Player Component
 * 
 * Provides advanced video/audio playback with custom controls
 * 
 * @package    ahgSpectrumPlugin
 * @subpackage lib/components
 */

class SpectrumMediaPlayer
{
    protected $settings = [];
    protected $mediaFile;
    protected $mediaType;
    protected $options = [];
    
    /**
     * Constructor
     */
    public function __construct($mediaFile = null, $options = [])
    {
        $this->loadSettings();
        
        if ($mediaFile) {
            $this->setMedia($mediaFile);
        }
        
        $this->options = array_merge([
            'autoplay' => $this->getSetting('media_autoplay', false),
            'controls' => $this->getSetting('media_show_controls', true),
            'loop' => $this->getSetting('media_loop', false),
            'volume' => $this->getSetting('media_volume_default', 0.8),
            'width' => '100%',
            'height' => 'auto',
            'poster' => null,
            'preload' => 'metadata',
            'playbackRates' => [0.5, 0.75, 1, 1.25, 1.5, 2],
            'showPlaybackRate' => true,
            'showVolumeControl' => true,
            'showProgressBar' => true,
            'showTimeDisplay' => true,
            'showFullscreen' => true,
            'showPictureInPicture' => true,
            'showDownload' => false,
            'theme' => 'dark',
            'playerType' => $this->getSetting('media_player_default', 'enhanced')
        ], $options);
    }
    
    /**
     * Load settings
     */
    protected function loadSettings()
    {
        try {
            $conn = Propel::getConnection();
            $sql = "SELECT setting_key, setting_value, setting_type FROM spectrum_media_settings WHERE setting_group = 'media'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $value = $row['setting_value'];
                
                if ($row['setting_type'] === 'boolean') {
                    $value = $value === 'true' || $value === '1';
                } elseif ($row['setting_type'] === 'integer') {
                    $value = (int) $value;
                }
                
                $this->settings[$row['setting_key']] = $value;
            }
        } catch (Exception $e) {
            // Use defaults if database not available
        }
    }
    
    /**
     * Get setting
     */
    public function getSetting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Set media file
     */
    public function setMedia($mediaFile)
    {
        $this->mediaFile = $mediaFile;
        $this->mediaType = $this->detectMediaType($mediaFile);
        
        return $this;
    }
    
    /**
     * Detect media type from file
     */
    protected function detectMediaType($file)
    {
        $mimeType = null;
        
        if (is_array($file) && isset($file['mime_type'])) {
            $mimeType = $file['mime_type'];
        } elseif (is_object($file) && method_exists($file, 'getMimeType')) {
            $mimeType = $file->getMimeType();
        } elseif (is_string($file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mimeTypes = [
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'ogg' => 'video/ogg',
                'ogv' => 'video/ogg',
                'mov' => 'video/quicktime',
                'avi' => 'video/x-msvideo',
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'flac' => 'audio/flac',
                'm4a' => 'audio/mp4',
                'aac' => 'audio/aac'
            ];
            $mimeType = $mimeTypes[$ext] ?? null;
        }
        
        if (strpos($mimeType, 'video/') === 0) {
            return 'video';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            return 'audio';
        }
        
        return null;
    }
    
    /**
     * Set option
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }
    
    /**
     * Get options
     */
    public function getOptions()
    {
        return $this->options;
    }
    
    /**
     * Render the media player
     */
    public function render($id = null)
    {
        if (!$this->mediaFile) {
            return '<div class="alert alert-warning">No media file specified</div>';
        }
        
        $id = $id ?: 'spectrum-player-' . uniqid();
        
        if ($this->options['playerType'] === 'basic') {
            return $this->renderBasicPlayer($id);
        }
        
        return $this->renderEnhancedPlayer($id);
    }
    
    /**
     * Render basic HTML5 player
     */
    protected function renderBasicPlayer($id)
    {
        $url = $this->getMediaUrl();
        $attrs = $this->buildAttributes();
        
        if ($this->mediaType === 'video') {
            return sprintf(
                '<video id="%s" src="%s" %s>Your browser does not support video playback.</video>',
                htmlspecialchars($id),
                htmlspecialchars($url),
                $attrs
            );
        } else {
            return sprintf(
                '<audio id="%s" src="%s" %s>Your browser does not support audio playback.</audio>',
                htmlspecialchars($id),
                htmlspecialchars($url),
                $attrs
            );
        }
    }
    
    /**
     * Render enhanced player with custom controls
     */
    protected function renderEnhancedPlayer($id)
    {
        $url = $this->getMediaUrl();
        $theme = $this->options['theme'];
        $isVideo = $this->mediaType === 'video';
        
        $html = '<div class="spectrum-media-player theme-' . htmlspecialchars($theme) . '" id="' . htmlspecialchars($id) . '-container">';
        
        // Media element
        if ($isVideo) {
            $html .= '<div class="media-wrapper">';
            $html .= '<video id="' . htmlspecialchars($id) . '" preload="' . htmlspecialchars($this->options['preload']) . '"';
            if ($this->options['poster']) {
                $html .= ' poster="' . htmlspecialchars($this->options['poster']) . '"';
            }
            $html .= '><source src="' . htmlspecialchars($url) . '"></video>';
            
            // Loading overlay
            $html .= '<div class="loading-overlay"><div class="spinner"></div></div>';
            
            // Play button overlay
            $html .= '<div class="play-overlay"><button class="play-btn-large"><i class="fas fa-play"></i></button></div>';
            
            $html .= '</div>';
        } else {
            $html .= '<div class="audio-wrapper">';
            $html .= '<div class="audio-visualizer" id="' . htmlspecialchars($id) . '-visualizer"></div>';
            $html .= '<audio id="' . htmlspecialchars($id) . '" preload="' . htmlspecialchars($this->options['preload']) . '">';
            $html .= '<source src="' . htmlspecialchars($url) . '">';
            $html .= '</audio>';
            $html .= '</div>';
        }
        
        // Controls
        if ($this->options['controls']) {
            $html .= $this->renderControls($id, $isVideo);
        }
        
        $html .= '</div>';
        
        // Include JavaScript initialization
        $html .= $this->renderScript($id);
        
        return $html;
    }
    
    /**
     * Render player controls
     */
    protected function renderControls($id, $isVideo)
    {
        $html = '<div class="media-controls">';
        
        // Progress bar
        if ($this->options['showProgressBar']) {
            $html .= '<div class="progress-container">';
            $html .= '<div class="progress-bar">';
            $html .= '<div class="progress-buffered"></div>';
            $html .= '<div class="progress-played"></div>';
            $html .= '<div class="progress-handle"></div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '<div class="controls-row">';
        
        // Left controls
        $html .= '<div class="controls-left">';
        
        // Play/Pause
        $html .= '<button class="ctrl-btn play-pause" title="Play/Pause"><i class="fas fa-play"></i></button>';
        
        // Skip buttons
        $html .= '<button class="ctrl-btn skip-back" title="Back 10s"><i class="fas fa-undo"></i></button>';
        $html .= '<button class="ctrl-btn skip-forward" title="Forward 10s"><i class="fas fa-redo"></i></button>';
        
        // Volume
        if ($this->options['showVolumeControl']) {
            $html .= '<div class="volume-control">';
            $html .= '<button class="ctrl-btn volume-btn" title="Volume"><i class="fas fa-volume-up"></i></button>';
            $html .= '<div class="volume-slider"><input type="range" min="0" max="1" step="0.05" value="' . $this->options['volume'] . '"></div>';
            $html .= '</div>';
        }
        
        // Time display
        if ($this->options['showTimeDisplay']) {
            $html .= '<div class="time-display"><span class="current-time">0:00</span> / <span class="duration">0:00</span></div>';
        }
        
        $html .= '</div>';
        
        // Right controls
        $html .= '<div class="controls-right">';
        
        // Playback rate
        if ($this->options['showPlaybackRate']) {
            $html .= '<div class="playback-rate">';
            $html .= '<button class="ctrl-btn rate-btn" title="Playback Speed">1x</button>';
            $html .= '<div class="rate-menu">';
            foreach ($this->options['playbackRates'] as $rate) {
                $html .= '<button data-rate="' . $rate . '">' . $rate . 'x</button>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Picture in Picture (video only)
        if ($isVideo && $this->options['showPictureInPicture']) {
            $html .= '<button class="ctrl-btn pip-btn" title="Picture in Picture"><i class="fas fa-clone"></i></button>';
        }
        
        // Download
        if ($this->options['showDownload']) {
            $html .= '<a href="' . htmlspecialchars($this->getMediaUrl()) . '" download class="ctrl-btn download-btn" title="Download"><i class="fas fa-download"></i></a>';
        }
        
        // Fullscreen (video only)
        if ($isVideo && $this->options['showFullscreen']) {
            $html .= '<button class="ctrl-btn fullscreen-btn" title="Fullscreen"><i class="fas fa-expand"></i></button>';
        }
        
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render JavaScript initialization
     */
    protected function renderScript($id)
    {
        $options = json_encode([
            'autoplay' => $this->options['autoplay'],
            'loop' => $this->options['loop'],
            'volume' => $this->options['volume']
        ]);
        
        return <<<SCRIPT
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    var options = {$options};
    var container = document.getElementById('{$id}-container');
    var media = document.getElementById('{$id}');
    
    if (!container || !media) return;
    
    // Initialize SpectrumPlayer
    if (typeof SpectrumPlayer !== 'undefined') {
        new SpectrumPlayer('{$id}', options);
    } else {
        // Fallback basic initialization
        initBasicPlayer(container, media, options);
    }
    
    function initBasicPlayer(container, media, options) {
        var playPauseBtn = container.querySelector('.play-pause');
        var playOverlay = container.querySelector('.play-overlay');
        var progressPlayed = container.querySelector('.progress-played');
        var progressContainer = container.querySelector('.progress-container');
        var currentTimeEl = container.querySelector('.current-time');
        var durationEl = container.querySelector('.duration');
        var volumeSlider = container.querySelector('.volume-slider input');
        var volumeBtn = container.querySelector('.volume-btn');
        var fullscreenBtn = container.querySelector('.fullscreen-btn');
        var skipBackBtn = container.querySelector('.skip-back');
        var skipForwardBtn = container.querySelector('.skip-forward');
        var rateBtn = container.querySelector('.rate-btn');
        var rateMenu = container.querySelector('.rate-menu');
        var pipBtn = container.querySelector('.pip-btn');
        var loadingOverlay = container.querySelector('.loading-overlay');
        
        // Set initial volume
        media.volume = options.volume;
        
        // Play/Pause
        function togglePlay() {
            if (media.paused) {
                media.play();
            } else {
                media.pause();
            }
        }
        
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', togglePlay);
        }
        
        if (playOverlay) {
            playOverlay.addEventListener('click', function() {
                togglePlay();
                this.style.display = 'none';
            });
        }
        
        // Update button icon
        media.addEventListener('play', function() {
            if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
            if (playOverlay) playOverlay.style.display = 'none';
        });
        
        media.addEventListener('pause', function() {
            if (playPauseBtn) playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
        });
        
        // Loading state
        media.addEventListener('waiting', function() {
            if (loadingOverlay) loadingOverlay.style.display = 'flex';
        });
        
        media.addEventListener('canplay', function() {
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        });
        
        // Progress
        media.addEventListener('timeupdate', function() {
            if (progressPlayed && media.duration) {
                var percent = (media.currentTime / media.duration) * 100;
                progressPlayed.style.width = percent + '%';
            }
            if (currentTimeEl) {
                currentTimeEl.textContent = formatTime(media.currentTime);
            }
        });
        
        media.addEventListener('loadedmetadata', function() {
            if (durationEl) {
                durationEl.textContent = formatTime(media.duration);
            }
        });
        
        // Seek
        if (progressContainer) {
            progressContainer.addEventListener('click', function(e) {
                var rect = this.getBoundingClientRect();
                var percent = (e.clientX - rect.left) / rect.width;
                media.currentTime = percent * media.duration;
            });
        }
        
        // Volume
        if (volumeSlider) {
            volumeSlider.addEventListener('input', function() {
                media.volume = this.value;
                updateVolumeIcon();
            });
        }
        
        if (volumeBtn) {
            volumeBtn.addEventListener('click', function() {
                media.muted = !media.muted;
                updateVolumeIcon();
            });
        }
        
        function updateVolumeIcon() {
            if (!volumeBtn) return;
            var icon = 'fa-volume-up';
            if (media.muted || media.volume === 0) {
                icon = 'fa-volume-mute';
            } else if (media.volume < 0.5) {
                icon = 'fa-volume-down';
            }
            volumeBtn.innerHTML = '<i class="fas ' + icon + '"></i>';
        }
        
        // Skip
        if (skipBackBtn) {
            skipBackBtn.addEventListener('click', function() {
                media.currentTime = Math.max(0, media.currentTime - 10);
            });
        }
        
        if (skipForwardBtn) {
            skipForwardBtn.addEventListener('click', function() {
                media.currentTime = Math.min(media.duration, media.currentTime + 10);
            });
        }
        
        // Playback rate
        if (rateBtn && rateMenu) {
            rateBtn.addEventListener('click', function() {
                rateMenu.classList.toggle('active');
            });
            
            rateMenu.querySelectorAll('button').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    media.playbackRate = parseFloat(this.dataset.rate);
                    rateBtn.textContent = this.dataset.rate + 'x';
                    rateMenu.classList.remove('active');
                });
            });
        }
        
        // Fullscreen
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', function() {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else {
                    container.requestFullscreen();
                }
            });
        }
        
        // Picture in Picture
        if (pipBtn && document.pictureInPictureEnabled) {
            pipBtn.addEventListener('click', function() {
                if (document.pictureInPictureElement) {
                    document.exitPictureInPicture();
                } else {
                    media.requestPictureInPicture();
                }
            });
        }
        
        // Keyboard shortcuts
        container.addEventListener('keydown', function(e) {
            switch (e.key) {
                case ' ':
                case 'k':
                    e.preventDefault();
                    togglePlay();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    media.currentTime -= 5;
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    media.currentTime += 5;
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    media.volume = Math.min(1, media.volume + 0.1);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    media.volume = Math.max(0, media.volume - 0.1);
                    break;
                case 'f':
                    e.preventDefault();
                    if (fullscreenBtn) fullscreenBtn.click();
                    break;
                case 'm':
                    e.preventDefault();
                    media.muted = !media.muted;
                    updateVolumeIcon();
                    break;
            }
        });
        
        // Auto-play if enabled
        if (options.autoplay) {
            media.play().catch(function() {});
        }
        
        // Loop
        media.loop = options.loop;
    }
    
    function formatTime(seconds) {
        var mins = Math.floor(seconds / 60);
        var secs = Math.floor(seconds % 60);
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }
})();
</script>
SCRIPT;
    }
    
    /**
     * Get media URL
     */
    protected function getMediaUrl()
    {
        if (is_string($this->mediaFile)) {
            return $this->mediaFile;
        }
        
        if (is_array($this->mediaFile) && isset($this->mediaFile['path'])) {
            return '/uploads/' . $this->mediaFile['path'];
        }
        
        if (is_object($this->mediaFile) && method_exists($this->mediaFile, 'getPath')) {
            return $this->mediaFile->getPath();
        }
        
        return '';
    }
    
    /**
     * Build HTML attributes
     */
    protected function buildAttributes()
    {
        $attrs = [];
        
        if ($this->options['controls']) {
            $attrs[] = 'controls';
        }
        if ($this->options['autoplay']) {
            $attrs[] = 'autoplay';
        }
        if ($this->options['loop']) {
            $attrs[] = 'loop';
        }
        if ($this->options['preload']) {
            $attrs[] = 'preload="' . htmlspecialchars($this->options['preload']) . '"';
        }
        if ($this->options['width']) {
            $attrs[] = 'width="' . htmlspecialchars($this->options['width']) . '"';
        }
        if ($this->options['height'] && $this->options['height'] !== 'auto') {
            $attrs[] = 'height="' . htmlspecialchars($this->options['height']) . '"';
        }
        if ($this->options['poster']) {
            $attrs[] = 'poster="' . htmlspecialchars($this->options['poster']) . '"';
        }
        
        return implode(' ', $attrs);
    }
    
    /**
     * Get CSS for the player
     */
    public static function getCSS()
    {
        return file_get_contents(__DIR__ . '/../web/css/media-player.css');
    }
    
    /**
     * Get JavaScript for the player
     */
    public static function getJS()
    {
        return file_get_contents(__DIR__ . '/../web/js/media-player.js');
    }
}
