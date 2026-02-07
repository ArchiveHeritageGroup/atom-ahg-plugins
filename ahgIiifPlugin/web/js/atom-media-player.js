/**
 * AHG Enhanced Media Player
 * Full-featured player with Bootstrap 5 UI, transcription, snippets
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */

(function(window) {
    'use strict';

    class AhgMediaPlayer {
        constructor(containerId, options = {}) {
            this.container = typeof containerId === 'string'
                ? document.querySelector(containerId)
                : containerId;

            if (!this.container) {
                console.error('AhgMediaPlayer: Container not found');
                return;
            }

            this.options = Object.assign({
                mediaUrl: '',
                streamUrl: '',
                mediaType: 'video',
                digitalObjectId: 0,
                objectId: 0,
                mimeType: '',
                autoplay: false,
                poster: '',
                waveformUrl: '',
                transcriptUrl: '',
                snippetsUrl: '/media/snippets',
                theme: 'dark',
                allowSnippets: true,
                skipSeconds: 10
            }, options);

            this.player = null;
            this.isStreaming = false;
            this.speeds = [0.5, 0.75, 1, 1.25, 1.5, 2];
            this.currentSpeedIndex = 2;
            this.hasTranscription = false;
            this.transcriptData = null;
            this.snippetsData = [];
            this.snippetMarker = { startTime: 0, endTime: 0 };

            this.init();
        }

        init() {
            this.container.innerHTML = '';
            this.container.style.height = 'auto';
            this.createPlayer();
            this.attachEvents();
            this.checkPlaybackSupport();
            this.buildUI();
            this.container._ahgPlayer = this;
        }

        // ===== Media Element =====

        createPlayer() {
            const isVideo = this.options.mediaType === 'video';

            this.player = document.createElement(isVideo ? 'video' : 'audio');
            this.player.controls = true;
            this.player.preload = 'metadata';
            this.player.className = isVideo ? 'w-100' : 'w-100';
            if (isVideo) {
                this.player.style.cssText = 'max-height:500px; background:#000; display:block;';
            }
            if (this.options.poster) this.player.poster = this.options.poster;

            const source = document.createElement('source');
            source.src = this.options.streamUrl || this.options.mediaUrl;
            source.type = this.getPlayerMimeType();
            this.player.appendChild(source);

            this.container.appendChild(this.player);
        }

        // ===== Build Bootstrap UI =====

        buildUI() {
            const id = this.options.digitalObjectId;

            // Speed / skip bar
            this.container.appendChild(this.buildControlBar(id));

            // Action buttons row
            this.container.appendChild(this.buildActionButtons(id));

            // Transcript panel (collapsible)
            this.container.appendChild(this.buildTranscriptPanel(id));

            // Snippets panel
            if (this.options.allowSnippets) {
                this.container.appendChild(this.buildSnippetsPanel(id));
            }

            // Snippet creator modal
            if (this.options.allowSnippets) {
                this.container.appendChild(this.buildSnippetModal(id));
            }

            // Load data
            this.loadTranscript();
            this.loadSnippets();
        }

        buildControlBar(id) {
            const bar = document.createElement('div');
            bar.className = 'd-flex align-items-center gap-2 py-2';

            // Play/Pause
            const playBtn = document.createElement('button');
            playBtn.className = 'btn btn-sm btn-outline-secondary';
            playBtn.innerHTML = '<i class="fas fa-play"></i>';
            playBtn.title = 'Play / Pause';
            playBtn.onclick = () => this.toggle();
            bar.appendChild(playBtn);
            this._playBtn = playBtn;

            // Skip back
            const skipBack = document.createElement('button');
            skipBack.className = 'btn btn-sm btn-outline-secondary';
            skipBack.innerHTML = '<i class="fas fa-backward"></i> ' + this.options.skipSeconds + 's';
            skipBack.onclick = () => this.skip(-this.options.skipSeconds);
            bar.appendChild(skipBack);

            // Skip forward
            const skipFwd = document.createElement('button');
            skipFwd.className = 'btn btn-sm btn-outline-secondary';
            skipFwd.innerHTML = this.options.skipSeconds + 's <i class="fas fa-forward"></i>';
            skipFwd.onclick = () => this.skip(this.options.skipSeconds);
            bar.appendChild(skipFwd);

            // Speed
            const speedBtn = document.createElement('button');
            speedBtn.className = 'btn btn-sm btn-outline-dark';
            speedBtn.id = 'speed-display-' + id;
            speedBtn.textContent = '1x';
            speedBtn.title = 'Cycle playback speed';
            speedBtn.style.minWidth = '45px';
            speedBtn.style.fontWeight = 'bold';
            speedBtn.onclick = () => this.cycleSpeed();
            bar.appendChild(speedBtn);

            // Spacer
            const spacer = document.createElement('div');
            spacer.style.flex = '1';
            bar.appendChild(spacer);

            // Time
            const time = document.createElement('small');
            time.className = 'text-muted font-monospace';
            time.textContent = '0:00 / 0:00';
            bar.appendChild(time);
            this._timeDisplay = time;

            // Fullscreen
            if (this.options.mediaType === 'video') {
                const fsBtn = document.createElement('button');
                fsBtn.className = 'btn btn-sm btn-outline-secondary';
                fsBtn.innerHTML = '<i class="fas fa-expand"></i>';
                fsBtn.title = 'Fullscreen';
                fsBtn.onclick = () => this.fullscreen();
                bar.appendChild(fsBtn);
            }

            return bar;
        }

        buildActionButtons(id) {
            const wrap = document.createElement('div');
            wrap.className = 'ahg-media-actions mt-2';

            const row = document.createElement('div');
            row.className = 'd-flex flex-wrap gap-2 align-items-center';
            row.id = 'ahg-actions-' + id;

            // Extract Metadata button
            const extractBtn = document.createElement('button');
            extractBtn.className = 'btn btn-sm btn-primary';
            extractBtn.id = 'extract-btn-' + id;
            extractBtn.innerHTML = '<i class="fas fa-cogs"></i> Extract Metadata';
            extractBtn.onclick = function() { window._ahgExtract(id); };
            row.appendChild(extractBtn);

            // Transcribe placeholder — will be replaced by loadTranscript()
            const transcribeSlot = document.createElement('span');
            transcribeSlot.id = 'ahg-transcribe-slot-' + id;
            row.appendChild(transcribeSlot);

            // Snippet button
            if (this.options.allowSnippets) {
                const snippetBtn = document.createElement('button');
                snippetBtn.className = 'btn btn-sm btn-outline-primary';
                snippetBtn.innerHTML = '<i class="fas fa-cut"></i> Create Snippet';
                snippetBtn.onclick = function() { window._ahgShowSnippetModal(id); };
                row.appendChild(snippetBtn);
            }

            wrap.appendChild(row);
            return wrap;
        }

        buildTranscriptPanel(id) {
            const wrap = document.createElement('div');
            wrap.className = 'collapse mt-3';
            wrap.id = 'transcript-panel-' + id;

            const card = document.createElement('div');
            card.className = 'card';

            // Header
            const header = document.createElement('div');
            header.className = 'card-header bg-light d-flex justify-content-between align-items-center';
            header.innerHTML = '<strong><i class="fas fa-closed-captioning me-1"></i> Transcript</strong>';

            const headerRight = document.createElement('div');
            headerRight.className = 'd-flex gap-1 align-items-center';
            headerRight.id = 'ahg-transcript-header-right-' + id;
            header.appendChild(headerRight);
            card.appendChild(header);

            // Body
            const body = document.createElement('div');
            body.className = 'card-body';
            body.id = 'ahg-transcript-body-' + id;
            body.style.maxHeight = '300px';
            body.style.overflowY = 'auto';
            body.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            card.appendChild(body);

            wrap.appendChild(card);
            return wrap;
        }

        buildSnippetsPanel(id) {
            const section = document.createElement('div');
            section.className = 'snippets-section mt-3';

            const card = document.createElement('div');
            card.className = 'card';

            const header = document.createElement('div');
            header.className = 'card-header py-2';
            header.innerHTML = '<h6 class="mb-0"><i class="fas fa-cut me-2"></i>Snippets</h6>';
            card.appendChild(header);

            const body = document.createElement('div');
            body.className = 'card-body py-2';
            body.id = 'snippets-list-' + id;
            body.innerHTML = '<div class="text-muted small">Loading...</div>';
            card.appendChild(body);

            section.appendChild(card);
            return section;
        }

        buildSnippetModal(id) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'snippetCreatorModal-' + id;
            modal.tabIndex = -1;
            modal.innerHTML =
                '<div class="modal-dialog">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title"><i class="fas fa-cut me-2"></i>Create Snippet</h5>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                '</div>' +
                '<div class="modal-body">' +
                '<div class="mb-3">' +
                '<label class="form-label">Snippet Title</label>' +
                '<input type="text" class="form-control" id="snippetTitle-' + id + '" placeholder="Enter a title for this snippet">' +
                '</div>' +
                '<div class="row mb-3">' +
                '<div class="col-6">' +
                '<label class="form-label">Start Time</label>' +
                '<div class="input-group">' +
                '<input type="text" class="form-control" id="snippetStart-' + id + '" value="0:00" readonly>' +
                '<button class="btn btn-outline-secondary" type="button" onclick="window._ahgSetSnipTime(\'start\',' + id + ')">' +
                '<i class="fas fa-map-marker-alt"></i> Set</button>' +
                '</div></div>' +
                '<div class="col-6">' +
                '<label class="form-label">End Time</label>' +
                '<div class="input-group">' +
                '<input type="text" class="form-control" id="snippetEnd-' + id + '" value="0:00" readonly>' +
                '<button class="btn btn-outline-secondary" type="button" onclick="window._ahgSetSnipTime(\'end\',' + id + ')">' +
                '<i class="fas fa-flag-checkered"></i> Set</button>' +
                '</div></div></div>' +
                '<div class="mb-3">' +
                '<label class="form-label">Notes</label>' +
                '<textarea class="form-control" id="snippetNotes-' + id + '" rows="2" placeholder="Optional notes"></textarea>' +
                '</div>' +
                '<div class="alert alert-info py-2 small mb-0">' +
                '<i class="fas fa-info-circle me-1"></i>' +
                'Play the media to the desired position, then click "Set" to mark start/end times.' +
                '</div></div>' +
                '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>' +
                '<button type="button" class="btn btn-primary" onclick="window._ahgSaveSnippet(' + id + ')">' +
                '<i class="fas fa-save me-1"></i>Save Snippet</button>' +
                '</div></div></div>';
            return modal;
        }

        // ===== Transcript =====

        loadTranscript() {
            var self = this;
            var id = this.options.digitalObjectId;
            var slot = document.getElementById('ahg-transcribe-slot-' + id);

            if (!this.options.transcriptUrl) {
                // No transcript — show Generate Transcript button
                this.hasTranscription = false;
                if (slot) {
                    slot.innerHTML =
                        '<button class="btn btn-sm btn-success" id="transcribe-btn-' + id + '" onclick="window._ahgStartTranscription(' + id + ')">' +
                        '<i class="fas fa-microphone"></i> Generate Transcript</button>';
                }
                var body = document.getElementById('ahg-transcript-body-' + id);
                if (body) body.innerHTML = '<div class="text-muted text-center py-3">No transcription yet. Click "Generate Transcript" above.</div>';
                return;
            }

            fetch(this.options.transcriptUrl, { headers: { 'Accept': 'application/json' } })
                .then(function(r) {
                    if (!r.ok) throw new Error('No transcript');
                    return r.json();
                })
                .then(function(data) {
                    self.hasTranscription = true;
                    self.transcriptData = data;
                    self.renderTranscriptButtons(id, slot, data);
                    self.renderTranscriptContent(id, data);
                })
                .catch(function() {
                    self.hasTranscription = false;
                    if (slot) {
                        slot.innerHTML =
                            '<button class="btn btn-sm btn-success" id="transcribe-btn-' + id + '" onclick="window._ahgStartTranscription(' + id + ')">' +
                            '<i class="fas fa-microphone"></i> Generate Transcript</button>';
                    }
                    var body = document.getElementById('ahg-transcript-body-' + id);
                    if (body) body.innerHTML = '<div class="text-muted text-center py-3">No transcription yet. Click "Generate Transcript" above.</div>';
                });
        }

        renderTranscriptButtons(id, slot, data) {
            if (!slot) return;

            var html = '';
            // View Transcript toggle
            html += '<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#transcript-panel-' + id + '">' +
                '<i class="fas fa-closed-captioning"></i> View Transcript</button>';

            // Download buttons
            html += '<a href="/media/transcription/' + id + '/vtt" class="btn btn-sm btn-outline-info" title="Download VTT">' +
                '<i class="fas fa-download"></i> VTT</a>';
            html += '<a href="/media/transcription/' + id + '/srt" class="btn btn-sm btn-outline-info" title="Download SRT">' +
                '<i class="fas fa-download"></i> SRT</a>';

            // Re-transcribe
            html += '<button class="btn btn-sm btn-outline-warning" onclick="window._ahgReTranscribe(' + id + ')" title="Re-transcribe">' +
                '<i class="fas fa-redo"></i> Re-transcribe</button>';

            // Delete
            html += '<button class="btn btn-sm btn-outline-danger" onclick="window._ahgDeleteTranscription(' + id + ')" title="Delete transcription">' +
                '<i class="fas fa-trash"></i></button>';

            slot.innerHTML = html;

            // Language badge in header
            var headerRight = document.getElementById('ahg-transcript-header-right-' + id);
            if (headerRight && data.language) {
                headerRight.innerHTML = '<span class="badge bg-info">' + (data.language || '').toUpperCase() + '</span>';
            }
        }

        renderTranscriptContent(id, data) {
            var body = document.getElementById('ahg-transcript-body-' + id);
            if (!body) return;

            var segments = data.segments || [];
            var fullText = data.full_text || data.text || '';

            var html = '';

            // Search
            html += '<div class="input-group input-group-sm mb-2">';
            html += '<input type="text" class="form-control" id="ahg-tsearch-' + id + '" placeholder="Search transcript...">';
            html += '<button class="btn btn-outline-secondary" onclick="window._ahgTSearch(' + id + ')"><i class="fas fa-search"></i></button>';
            html += '</div>';

            if (segments.length > 0) {
                html += '<div class="transcript-segments">';
                for (var i = 0; i < segments.length; i++) {
                    var seg = segments[i];
                    html += '<p class="transcript-segment mb-2" data-start="' + (seg.start || 0) + '" data-end="' + (seg.end || 0) + '" ' +
                        'onclick="window._ahgTSeek(' + id + ',' + (seg.start || 0) + ',this)" ' +
                        'style="cursor:pointer; padding:4px 8px; border-radius:4px; transition:background 0.2s;" ' +
                        'onmouseover="this.style.background=\'#f0f0f0\'" onmouseout="this.style.background=\'transparent\'">' +
                        '<small class="text-muted">[' + AhgMediaPlayer.formatTime(seg.start || 0) + ']</small> ' +
                        AhgMediaPlayer.escapeHtml((seg.text || '').trim()) +
                        '</p>';
                }
                html += '</div>';
            } else if (fullText) {
                html += '<p class="mb-0" style="white-space:pre-wrap;">' + AhgMediaPlayer.escapeHtml(fullText) + '</p>';
            }

            body.innerHTML = html;
        }

        // ===== Snippets =====

        loadSnippets() {
            if (!this.options.allowSnippets || !this.options.snippetsUrl) return;
            var self = this;
            var id = this.options.digitalObjectId;

            fetch(this.options.snippetsUrl + '?digital_object_id=' + id, {
                headers: { 'Accept': 'application/json' }
            })
                .then(function(r) {
                    if (!r.ok) throw new Error('No snippets');
                    return r.json();
                })
                .then(function(data) {
                    self.snippetsData = data.snippets || data || [];
                    self.renderSnippetsList(id);
                })
                .catch(function() {
                    self.snippetsData = [];
                    self.renderSnippetsList(id);
                });
        }

        renderSnippetsList(id) {
            var el = document.getElementById('snippets-list-' + id);
            if (!el) return;
            var snippets = this.snippetsData;

            if (!snippets || snippets.length === 0) {
                el.innerHTML = '<div class="text-muted small">No saved snippets</div>';
                return;
            }

            var html = '';
            for (var i = 0; i < snippets.length; i++) {
                var s = snippets[i];
                html += '<div class="snippet-item d-flex justify-content-between align-items-center py-2 border-bottom">';
                html += '<div>';
                html += '<strong>' + AhgMediaPlayer.escapeHtml(s.title || 'Snippet') + '</strong>';
                html += '<div class="small text-muted">';
                html += AhgMediaPlayer.formatTime(s.start_time || 0) + ' &rarr; ' + AhgMediaPlayer.formatTime(s.end_time || 0);
                if (s.duration) html += ' (' + AhgMediaPlayer.formatTime(s.duration) + ')';
                html += '</div></div>';
                html += '<div class="btn-group btn-group-sm">';
                html += '<button class="btn btn-outline-primary" onclick="window._ahgSnipPlay(' + id + ',' + (s.start_time || 0) + ',' + (s.end_time || 0) + ')"><i class="fas fa-play"></i></button>';
                html += '<button class="btn btn-outline-danger" onclick="window._ahgDeleteSnippet(' + (s.id || 0) + ',' + id + ')"><i class="fas fa-trash"></i></button>';
                if (s.export_path) {
                    html += '<a class="btn btn-outline-secondary" href="' + s.export_path + '" download><i class="fas fa-download"></i></a>';
                }
                html += '</div></div>';
            }
            el.innerHTML = html;
        }

        // ===== Player Core =====

        getPlayerMimeType() {
            var mime = this.options.mimeType;
            if (this.options.streamUrl && this.needsTranscoding(mime)) {
                return this.options.mediaType === 'video' ? 'video/mp4' : 'audio/mpeg';
            }
            return mime;
        }

        needsTranscoding(mimeType) {
            var list = [
                'video/x-ms-asf', 'video/x-msvideo', 'video/quicktime',
                'video/x-ms-wmv', 'video/x-flv', 'video/x-matroska',
                'video/mp2t', 'video/x-ms-wtv', 'video/hevc',
                'application/mxf', 'video/3gpp',
                'audio/aiff', 'audio/x-aiff', 'audio/basic', 'audio/x-au',
                'audio/ac3', 'audio/8svx', 'audio/AMB', 'audio/x-ms-wma',
                'audio/x-pn-realaudio', 'audio/flac', 'audio/x-flac',
                'audio/x-wav'
            ];
            return list.indexOf(mimeType) >= 0;
        }

        checkPlaybackSupport() {
            var source = this.player.querySelector('source');
            if (!source) return;
            var canPlay = this.player.canPlayType(source.type);
            if (!canPlay || canPlay === '') {
                if (this.options.digitalObjectId && !this.isStreaming) {
                    this.switchToStreaming();
                }
            }
        }

        switchToStreaming() {
            if (this.isStreaming) return;
            var streamUrl = this.options.streamUrl || (window.location.origin + '/media/stream/' + this.options.digitalObjectId);
            var newMime = this.options.mediaType === 'video' ? 'video/mp4' : 'audio/mpeg';
            var source = this.player.querySelector('source');
            if (source) { source.src = streamUrl; source.type = newMime; }
            this.player.load();
            this.isStreaming = true;
        }

        attachEvents() {
            if (!this.player) return;
            var self = this;

            this.player.addEventListener('error', function() { self.handleError(); });
            this.player.addEventListener('play', function() {
                if (self._playBtn) self._playBtn.innerHTML = '<i class="fas fa-pause"></i>';
            });
            this.player.addEventListener('pause', function() {
                if (self._playBtn) self._playBtn.innerHTML = '<i class="fas fa-play"></i>';
            });
            this.player.addEventListener('ended', function() {
                if (self._playBtn) self._playBtn.innerHTML = '<i class="fas fa-play"></i>';
            });
            this.player.addEventListener('timeupdate', function() { self.updateTimeDisplay(); });
        }

        handleError() {
            var error = this.player.error;
            if (error && (error.code === MediaError.MEDIA_ERR_DECODE || error.code === MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED)) {
                if (!this.isStreaming && this.options.digitalObjectId) {
                    this.switchToStreaming();
                }
            }
        }

        updateTimeDisplay() {
            if (!this._timeDisplay || !this.player) return;
            this._timeDisplay.textContent = AhgMediaPlayer.formatTime(this.player.currentTime) +
                ' / ' + AhgMediaPlayer.formatTime(this.player.duration || 0);
            this.highlightActiveSegment();
        }

        highlightActiveSegment() {
            var id = this.options.digitalObjectId;
            var panel = document.getElementById('transcript-panel-' + id);
            if (!panel || !panel.classList.contains('show')) return;

            var t = this.player.currentTime;
            panel.querySelectorAll('.transcript-segment').forEach(function(seg) {
                var start = parseFloat(seg.dataset.start);
                var end = parseFloat(seg.dataset.end);
                seg.style.background = (t >= start && t < end) ? '#fff3cd' : 'transparent';
            });
        }

        toggle() {
            if (this.player) { this.player.paused ? this.play() : this.pause(); }
        }
        play() { if (this.player) this.player.play().catch(function() {}); }
        pause() { if (this.player) this.player.pause(); }
        skip(s) { if (this.player) this.player.currentTime = Math.max(0, Math.min(this.player.duration || 0, this.player.currentTime + s)); }
        seek(s) { if (this.player) this.player.currentTime = s; }
        getCurrentTime() { return this.player ? this.player.currentTime : 0; }
        getDuration() { return this.player ? this.player.duration : 0; }

        cycleSpeed() {
            this.currentSpeedIndex = (this.currentSpeedIndex + 1) % this.speeds.length;
            var speed = this.speeds[this.currentSpeedIndex];
            if (this.player) this.player.playbackRate = speed;
            var el = document.getElementById('speed-display-' + this.options.digitalObjectId);
            if (el) el.textContent = speed + 'x';
            return speed;
        }

        fullscreen() {
            if (this.player && this.player.requestFullscreen) this.player.requestFullscreen();
            else if (this.player && this.player.webkitRequestFullscreen) this.player.webkitRequestFullscreen();
        }

        destroy() {
            if (this.player) { this.player.pause(); this.player.src = ''; this.player.load(); }
        }

        static formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            seconds = Math.floor(seconds);
            var h = Math.floor(seconds / 3600);
            var m = Math.floor((seconds % 3600) / 60);
            var s = seconds % 60;
            if (h > 0) return h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            return m + ':' + (s < 10 ? '0' : '') + s;
        }

        static escapeHtml(text) {
            var d = document.createElement('div');
            d.textContent = text || '';
            return d.innerHTML;
        }
    }

    // ===== Global Functions =====

    window._ahgExtract = function(id) {
        var btn = document.getElementById('extract-btn-' + id);
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Extracting...';
        fetch('/media/extract/' + id, { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) location.reload();
                else { alert('Error: ' + (d.error || 'Failed')); btn.disabled = false; btn.innerHTML = '<i class="fas fa-cogs"></i> Extract Metadata'; }
            })
            .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="fas fa-cogs"></i> Extract Metadata'; });
    };

    window._ahgStartTranscription = function(id) {
        var btn = document.getElementById('transcribe-btn-' + id);
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting transcription...';
        fetch('/media/transcribe/' + id, { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.error) {
                    alert('Error: ' + d.error);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-microphone"></i> Generate Transcript';
                    return;
                }
                // Background job started — poll for completion
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Transcribing (background)...';
                var body = document.getElementById('ahg-transcript-body-' + id);
                if (body) body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Transcription running in background...<br><small class="text-muted">This may take a few minutes. The page will update automatically.</small></div>';
                window._ahgPollTranscription(id, btn, 0);
            })
            .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="fas fa-microphone"></i> Generate Transcript'; });
    };

    window._ahgPollTranscription = function(id, btn, attempts) {
        if (attempts > 120) { // Max 10 minutes (5s intervals)
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-microphone"></i> Generate Transcript';
            alert('Transcription timed out. Please try again.');
            return;
        }
        setTimeout(function() {
            fetch('/media/transcription/' + id, { headers: { 'Accept': 'application/json' } })
                .then(function(r) {
                    if (r.ok) return r.json();
                    throw new Error('not ready');
                })
                .then(function(data) {
                    if (data && data.full_text) {
                        location.reload(); // Transcription complete
                    } else {
                        window._ahgPollTranscription(id, btn, attempts + 1);
                    }
                })
                .catch(function() {
                    window._ahgPollTranscription(id, btn, attempts + 1);
                });
        }, 5000);
    };

    window._ahgReTranscribe = function(id) {
        if (!confirm('Re-transcribe? This will replace the existing transcription.')) return;
        window._ahgStartTranscription(id);
    };

    window._ahgDeleteTranscription = function(id) {
        if (!confirm('Delete this transcription?')) return;
        fetch('/media/transcription/' + id, { method: 'DELETE' })
            .then(function(r) { return r.json(); })
            .then(function(d) { if (d.success) location.reload(); });
    };

    window._ahgTSearch = function(id) {
        var input = document.getElementById('ahg-tsearch-' + id);
        var panel = document.getElementById('transcript-panel-' + id);
        if (!input || !panel) return;
        var query = input.value.toLowerCase().trim();
        panel.querySelectorAll('.transcript-segment').forEach(function(seg) {
            if (!query) { seg.style.display = 'block'; seg.style.background = 'transparent'; return; }
            if (seg.textContent.toLowerCase().indexOf(query) >= 0) {
                seg.style.display = 'block';
                seg.style.background = '#d4edda';
            } else {
                seg.style.display = 'none';
            }
        });
    };

    window._ahgTSeek = function(id, time, el) {
        var container = document.getElementById('media-player-' + id);
        if (container && container._ahgPlayer) {
            container._ahgPlayer.seek(time);
            container._ahgPlayer.play();
        }
        if (el && el.parentNode) {
            el.parentNode.querySelectorAll('.transcript-segment').forEach(function(s) { s.style.background = 'transparent'; });
            el.style.background = '#fff3cd';
        }
    };

    window._ahgShowSnippetModal = function(id) {
        var container = document.getElementById('media-player-' + id);
        if (container && container._ahgPlayer) {
            container._ahgPlayer.snippetMarker = { startTime: 0, endTime: 0 };
        }
        var titleEl = document.getElementById('snippetTitle-' + id);
        var startEl = document.getElementById('snippetStart-' + id);
        var endEl = document.getElementById('snippetEnd-' + id);
        var notesEl = document.getElementById('snippetNotes-' + id);
        if (titleEl) titleEl.value = '';
        if (startEl) startEl.value = '0:00';
        if (endEl) endEl.value = '0:00';
        if (notesEl) notesEl.value = '';
        new bootstrap.Modal(document.getElementById('snippetCreatorModal-' + id)).show();
    };

    window._ahgSetSnipTime = function(type, id) {
        var container = document.getElementById('media-player-' + id);
        if (!container || !container._ahgPlayer) return;
        var player = container._ahgPlayer;
        var time = player.getCurrentTime();
        if (type === 'start') {
            player.snippetMarker.startTime = time;
            var el = document.getElementById('snippetStart-' + id);
            if (el) el.value = AhgMediaPlayer.formatTime(time);
        } else {
            player.snippetMarker.endTime = time;
            var el2 = document.getElementById('snippetEnd-' + id);
            if (el2) el2.value = AhgMediaPlayer.formatTime(time);
        }
    };

    window._ahgSaveSnippet = function(id) {
        var container = document.getElementById('media-player-' + id);
        if (!container || !container._ahgPlayer) return;
        var player = container._ahgPlayer;
        var title = (document.getElementById('snippetTitle-' + id) || {}).value || '';
        var notes = (document.getElementById('snippetNotes-' + id) || {}).value || '';

        if (!title.trim()) { alert('Please enter a title'); return; }
        if (player.snippetMarker.startTime >= player.snippetMarker.endTime) { alert('End time must be after start time'); return; }

        fetch(player.options.snippetsUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                digital_object_id: id,
                title: title.trim(),
                start_time: player.snippetMarker.startTime,
                end_time: player.snippetMarker.endTime,
                notes: notes.trim()
            })
        })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success || d.id) {
                    bootstrap.Modal.getInstance(document.getElementById('snippetCreatorModal-' + id)).hide();
                    location.reload();
                } else {
                    alert('Failed: ' + (d.error || 'Unknown error'));
                }
            })
            .catch(function(err) { alert('Error: ' + err.message); });
    };

    window._ahgSnipPlay = function(id, start, end) {
        var container = document.getElementById('media-player-' + id);
        if (!container || !container._ahgPlayer) return;
        var player = container._ahgPlayer;
        player.seek(start);
        player.play();
        var check = setInterval(function() {
            if (player.player && player.player.currentTime >= end) {
                player.pause();
                clearInterval(check);
            }
        }, 100);
    };

    window._ahgDeleteSnippet = function(snippetId, doId) {
        if (!confirm('Delete this snippet?')) return;
        fetch('/media/snippets/' + snippetId + '/delete', { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(d) { if (d.success) location.reload(); });
    };

    window.togglePlaybackSpeed = function(id) {
        var container = document.getElementById('media-player-' + id);
        if (container && container._ahgPlayer) container._ahgPlayer.cycleSpeed();
    };

    // Export
    window.AhgMediaPlayer = AhgMediaPlayer;

})(window);
