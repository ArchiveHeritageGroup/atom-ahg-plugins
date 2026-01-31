/**
 * ahgNMMZPlugin JavaScript
 * National Museums and Monuments of Zimbabwe
 */

(function() {
    'use strict';

    function init() {
        initAntiquityAgeCheck();
        initGPSValidation();
        initExportPermitValidation();
    }

    /**
     * Check if object qualifies as antiquity (>100 years old)
     */
    function initAntiquityAgeCheck() {
        var ageInput = document.getElementById('estimated_age_years');
        var ageWarning = document.getElementById('age_warning');
        var threshold = 100; // Default threshold

        if (!ageInput) return;

        ageInput.addEventListener('input', function() {
            var age = parseInt(ageInput.value) || 0;
            if (ageWarning) {
                if (age < threshold) {
                    ageWarning.innerHTML = '<span class="text-warning">Object may not qualify as antiquity (' + threshold + ' years required)</span>';
                    ageWarning.style.display = 'block';
                } else {
                    ageWarning.innerHTML = '<span class="text-success">Qualifies as antiquity</span>';
                    ageWarning.style.display = 'block';
                }
            }
        });
    }

    /**
     * Validate GPS coordinates
     */
    function initGPSValidation() {
        var latInput = document.getElementById('gps_latitude');
        var lonInput = document.getElementById('gps_longitude');

        function validateCoords() {
            if (!latInput || !lonInput) return;

            var lat = parseFloat(latInput.value);
            var lon = parseFloat(lonInput.value);

            // Zimbabwe bounds approximately: -22.4 to -15.6 lat, 25.2 to 33.1 lon
            if (lat && lon) {
                if (lat < -22.5 || lat > -15.5 || lon < 25 || lon > 33.5) {
                    latInput.classList.add('is-invalid');
                    lonInput.classList.add('is-invalid');
                } else {
                    latInput.classList.remove('is-invalid');
                    lonInput.classList.remove('is-invalid');
                    latInput.classList.add('is-valid');
                    lonInput.classList.add('is-valid');
                }
            }
        }

        if (latInput) latInput.addEventListener('change', validateCoords);
        if (lonInput) lonInput.addEventListener('change', validateCoords);
    }

    /**
     * Export permit validation
     */
    function initExportPermitValidation() {
        var purposeSelect = document.getElementById('export_purpose');
        var returnDateGroup = document.getElementById('return_date_group');

        if (!purposeSelect) return;

        purposeSelect.addEventListener('change', function() {
            var purpose = purposeSelect.value;
            // Show return date for temporary exports
            if (returnDateGroup) {
                if (['exhibition', 'research', 'conservation'].includes(purpose)) {
                    returnDateGroup.style.display = 'block';
                } else {
                    returnDateGroup.style.display = 'none';
                }
            }
        });
    }

    /**
     * Calculate monument age
     */
    window.calculateMonumentAge = function(gazetteDate) {
        if (!gazetteDate) return null;
        var gazette = new Date(gazetteDate);
        var now = new Date();
        var years = now.getFullYear() - gazette.getFullYear();
        return years;
    };

    /**
     * Format coordinates for display
     */
    window.formatCoordinates = function(lat, lon) {
        if (!lat || !lon) return 'Not recorded';
        var latDir = lat >= 0 ? 'N' : 'S';
        var lonDir = lon >= 0 ? 'E' : 'W';
        return Math.abs(lat).toFixed(6) + '° ' + latDir + ', ' + Math.abs(lon).toFixed(6) + '° ' + lonDir;
    };

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
