/**
 * Provenance Plugin JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
  // Agent autocomplete
  initAgentAutocomplete();
});

function initAgentAutocomplete() {
  var inputs = document.querySelectorAll('.agent-autocomplete');
  
  inputs.forEach(function(input) {
    var timeout = null;
    var resultsDiv = null;
    
    input.addEventListener('input', function() {
      var term = this.value.trim();
      
      if (timeout) clearTimeout(timeout);
      
      if (term.length < 2) {
        if (resultsDiv) resultsDiv.remove();
        return;
      }
      
      timeout = setTimeout(function() {
        fetch('/provenance/searchAgents?term=' + encodeURIComponent(term))
          .then(function(r) { return r.json(); })
          .then(function(agents) {
            showAutocompleteResults(input, agents);
          });
      }, 300);
    });
    
    input.addEventListener('blur', function() {
      setTimeout(function() {
        if (resultsDiv) resultsDiv.remove();
      }, 200);
    });
  });
}

function showAutocompleteResults(input, agents) {
  // Remove existing
  var existing = input.parentNode.querySelector('.agent-autocomplete-results');
  if (existing) existing.remove();
  
  if (!agents || agents.length === 0) return;
  
  var div = document.createElement('div');
  div.className = 'agent-autocomplete-results';
  div.style.width = input.offsetWidth + 'px';
  
  agents.forEach(function(agent) {
    var item = document.createElement('div');
    item.className = 'item';
    item.innerHTML = '<strong>' + escapeHtml(agent.name) + '</strong> <small class="text-muted">(' + agent.agent_type + ')</small>';
    item.addEventListener('mousedown', function(e) {
      e.preventDefault();
      input.value = agent.name;
      div.remove();
    });
    div.appendChild(item);
  });
  
  input.parentNode.style.position = 'relative';
  input.parentNode.appendChild(div);
}

function escapeHtml(text) {
  var div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
