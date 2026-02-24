/**
 * ahgMarketplacePlugin - JavaScript
 * Auction timers, bid polling, favourites, image gallery, category filtering
 */

document.addEventListener('DOMContentLoaded', function () {

    // =========================================================================
    // Auction Countdown Timer
    // =========================================================================
    window.initAuctionTimer = function (elementId, endTimeUTC) {
        var el = document.getElementById(elementId);
        if (!el) return;

        var endMs = new Date(endTimeUTC).getTime();

        function update() {
            var now = Date.now();
            var diff = endMs - now;

            if (diff <= 0) {
                el.innerHTML = '<span class="mkt-timer-ended text-danger fw-bold">ENDED</span>';
                return;
            }

            var d = Math.floor(diff / 86400000);
            var h = Math.floor((diff % 86400000) / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            var s = Math.floor((diff % 60000) / 1000);

            var html = '';
            if (d > 0) {
                html += '<span class="mkt-timer-box"><span class="mkt-timer-value">' + d + '</span><span class="mkt-timer-label">d</span></span>';
            }
            html += '<span class="mkt-timer-box"><span class="mkt-timer-value">' + String(h).padStart(2, '0') + '</span><span class="mkt-timer-label">h</span></span>';
            html += '<span class="mkt-timer-box"><span class="mkt-timer-value">' + String(m).padStart(2, '0') + '</span><span class="mkt-timer-label">m</span></span>';
            html += '<span class="mkt-timer-box"><span class="mkt-timer-value">' + String(s).padStart(2, '0') + '</span><span class="mkt-timer-label">s</span></span>';

            el.innerHTML = html;
            requestAnimationFrame(function () { setTimeout(update, 1000); });
        }

        update();
    };

    // Auto-init all timers on page
    document.querySelectorAll('[data-auction-timer]').forEach(function (el) {
        var endTime = el.getAttribute('data-end-time');
        if (endTime) {
            initAuctionTimer(el.id, endTime);
        }
    });

    // =========================================================================
    // Auction Status Polling
    // =========================================================================
    window.pollAuctionStatus = function (auctionId, elementId, interval) {
        interval = interval || 5000;

        function poll() {
            fetch('/index.php/marketplace/api/auction/' + auctionId + '/status')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) return;

                    var bidEl = document.getElementById(elementId + '-bid');
                    var countEl = document.getElementById(elementId + '-count');

                    if (bidEl && data.current_bid) {
                        bidEl.textContent = parseFloat(data.current_bid).toFixed(2);
                    }
                    if (countEl) {
                        countEl.textContent = data.bid_count;
                    }

                    if (data.status === 'active') {
                        setTimeout(poll, interval);
                    }
                })
                .catch(function () {
                    setTimeout(poll, interval * 2);
                });
        }

        poll();
    };

    // =========================================================================
    // Favourite Toggle
    // =========================================================================
    window.toggleFavourite = function (listingId, btn) {
        fetch('/index.php/marketplace/api/listing/' + listingId + '/favourite', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;

                var icon = btn.querySelector('i');
                if (data.favourited) {
                    icon.classList.remove('far');
                    icon.classList.add('fas', 'text-danger');
                } else {
                    icon.classList.remove('fas', 'text-danger');
                    icon.classList.add('far');
                }

                var countEl = btn.querySelector('.mkt-fav-count');
                if (countEl) {
                    countEl.textContent = data.count;
                }
            });
    };

    // =========================================================================
    // Grid / List View Toggle
    // =========================================================================
    document.querySelectorAll('[data-view-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-view-toggle'));
            var mode = btn.getAttribute('data-view-mode');
            if (!target) return;

            if (mode === 'list') {
                target.classList.remove('mkt-grid');
                target.classList.add('mkt-list');
            } else {
                target.classList.remove('mkt-list');
                target.classList.add('mkt-grid');
            }

            btn.closest('.btn-group').querySelectorAll('.btn').forEach(function (b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');
        });
    });

    // =========================================================================
    // Category Filter (sector → category)
    // =========================================================================
    document.querySelectorAll('[data-sector-select]').forEach(function (sectorSelect) {
        var catId = sectorSelect.getAttribute('data-sector-select');
        var catSelect = document.getElementById(catId);
        if (!catSelect) return;

        sectorSelect.addEventListener('change', function () {
            var sector = this.value;
            if (!sector) {
                catSelect.innerHTML = '<option value="">All categories</option>';
                return;
            }

            fetch('/index.php/marketplace/api/categories/' + sector)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var html = '<option value="">All categories</option>';
                    if (data.data) {
                        data.data.forEach(function (c) {
                            html += '<option value="' + c.id + '">' + c.name + '</option>';
                        });
                    }
                    catSelect.innerHTML = html;
                });
        });
    });

    // =========================================================================
    // Image Gallery (thumbnail click → main swap)
    // =========================================================================
    document.querySelectorAll('.mkt-gallery-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var mainImg = document.querySelector('.mkt-gallery-main img');
            if (mainImg) {
                mainImg.src = this.getAttribute('data-full-src') || this.src;
            }
            document.querySelectorAll('.mkt-gallery-thumb').forEach(function (t) {
                t.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // =========================================================================
    // Bid Form AJAX
    // =========================================================================
    var bidForm = document.getElementById('mkt-bid-form');
    if (bidForm) {
        bidForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var listingId = this.getAttribute('data-listing-id');
            var amount = this.querySelector('[name="bid_amount"]').value;
            var maxBid = this.querySelector('[name="max_bid"]');

            var body = new FormData();
            body.append('bid_amount', amount);
            if (maxBid && maxBid.value) {
                body.append('max_bid', maxBid.value);
            }

            var resultEl = document.getElementById('mkt-bid-result');

            fetch('/index.php/marketplace/api/listing/' + listingId + '/bid', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (resultEl) {
                        if (data.success) {
                            resultEl.innerHTML = '<div class="alert alert-success">Bid placed successfully!</div>';
                        } else {
                            resultEl.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Bid failed') + '</div>';
                        }
                    }
                })
                .catch(function () {
                    if (resultEl) {
                        resultEl.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
                    }
                });
        });
    }

});
