/**
 * ahgAuthorityPlugin JavaScript
 */
(function() {
  'use strict';

  // Recalculate completeness score for a single actor
  window.ahgAuthorityRecalc = function(actorId, callback) {
    fetch('/api/authority/completeness/' + actorId + '/recalc', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (callback) callback(d);
      })
      .catch(function(err) {
        console.error('Completeness recalc failed:', err);
      });
  };

  // Batch assign completeness records
  window.ahgAuthorityBatchAssign = function(actorIds, assigneeId, callback) {
    var data = new FormData();
    data.append('actor_ids', actorIds.join(','));
    data.append('assignee_id', assigneeId);

    fetch('/api/authority/completeness/batch-assign', { method: 'POST', body: data })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (callback) callback(d);
      });
  };

})();
