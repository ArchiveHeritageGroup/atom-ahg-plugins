/**
 * Extended Rights Plugin JavaScript
 */
(function() {
  'use strict';

  // Initialize tooltips for rights icons
  document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap 5 tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
      new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle perpetual checkbox
    var perpetualCheckbox = document.getElementById('is_perpetual');
    var endDateInput = document.getElementById('end_date');
    
    if (perpetualCheckbox && endDateInput) {
      perpetualCheckbox.addEventListener('change', function() {
        endDateInput.disabled = this.checked;
        if (this.checked) {
          endDateInput.value = '';
        }
      });
    }

    // Handle exception type change
    var exceptionTypeSelect = document.getElementById('exception_type');
    if (exceptionTypeSelect) {
      exceptionTypeSelect.addEventListener('change', function() {
        var isIpRange = this.value === 'ip_range';
        var idField = document.getElementById('exception_id_field');
        var ipFields = document.getElementById('ip_range_fields');
        
        if (idField) idField.style.display = isIpRange ? 'none' : 'block';
        if (ipFields) ipFields.style.display = isIpRange ? 'block' : 'none';
      });
    }

    // AJAX delete for rights
    document.querySelectorAll('.delete-rights-btn').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to remove this rights assignment?')) {
          return;
        }
        
        var url = this.getAttribute('href');
        fetch(url, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            btn.closest('.rights-item').remove();
          }
        });
      });
    });
  });

  // Rights display helper
  window.ExtendedRights = {
    formatDate: function(dateStr) {
      if (!dateStr) return '-';
      var date = new Date(dateStr);
      return date.toLocaleDateString();
    },
    
    showRightsInfo: function(element) {
      var content = element.getAttribute('data-rights-info');
      if (content) {
        alert(content);
      }
    }
  };
})();
