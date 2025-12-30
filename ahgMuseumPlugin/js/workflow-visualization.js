/**
 * Workflow Visualization.
 *
 * D3.js-based visualization for Spectrum 5.0 workflow state machines.
 * Displays states, transitions, and current progress.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
(function(global) {
  'use strict';

  var WorkflowVisualization = function(selector, options) {
    this.container = d3.select(selector);
    this.options = Object.assign({}, WorkflowVisualization.defaults, options);
    this.workflow = options.workflow || null;
    this.currentState = options.currentState || null;
    
    if (this.workflow) {
      this.init();
    }
  };

  WorkflowVisualization.defaults = {
    width: 900,
    height: 500,
    nodeRadius: 35,
    colors: {
      initial: '#2196f3',
      normal: '#fff',
      current: '#4caf50',
      final: '#9e9e9e',
      danger: '#f44336',
      warning: '#ff9800',
      success: '#4caf50',
      info: '#2196f3',
      primary: '#3f51b5',
      secondary: '#607d8b',
      default: '#9e9e9e'
    },
    transitionDuration: 300,
    showLabels: true,
    interactive: true
  };

  WorkflowVisualization.prototype = {
    init: function() {
      this.nodes = [];
      this.links = [];
      this.simulation = null;
      
      this.parseWorkflow();
      this.setupSvg();
      this.setupSimulation();
      this.render();
    },

    parseWorkflow: function() {
      var self = this;
      var states = this.workflow.states || {};
      var transitions = this.workflow.transitions || {};
      var initialState = this.workflow.initial_state;
      var finalStates = this.workflow.final_states || [];

      // Create nodes from states
      Object.keys(states).forEach(function(stateId) {
        var state = states[stateId];
        self.nodes.push({
          id: stateId,
          label: state.label || stateId,
          description: state.description || '',
          color: state.color || 'default',
          icon: state.icon || null,
          isInitial: stateId === initialState,
          isFinal: finalStates.indexOf(stateId) >= 0,
          isCurrent: stateId === self.currentState,
          phase: state.phase || null
        });
      });

      // Create links from transitions
      Object.keys(transitions).forEach(function(transitionId) {
        var transition = transitions[transitionId];
        var fromStates = Array.isArray(transition.from) ? transition.from : [transition.from];
        var toState = transition.to;

        fromStates.forEach(function(fromState) {
          if (fromState !== '*' && states[fromState] && states[toState]) {
            self.links.push({
              id: transitionId + '_' + fromState,
              source: fromState,
              target: toState,
              label: transition.label || transitionId,
              color: transition.color || 'default',
              isAvailable: self.isTransitionAvailable(fromState, transitionId)
            });
          }
        });
      });
    },

    isTransitionAvailable: function(fromState, transitionId) {
      if (fromState !== this.currentState) return false;
      
      var availableTransitions = this.options.availableTransitions || [];
      return availableTransitions.indexOf(transitionId) >= 0;
    },

    setupSvg: function() {
      var opts = this.options;
      
      this.container.selectAll('*').remove();
      
      this.svg = this.container
        .append('svg')
        .attr('width', opts.width)
        .attr('height', opts.height)
        .attr('class', 'workflow-visualization');

      // Arrow marker
      var defs = this.svg.append('defs');
      
      defs.append('marker')
        .attr('id', 'workflow-arrow')
        .attr('viewBox', '0 -5 10 10')
        .attr('refX', 25)
        .attr('refY', 0)
        .attr('markerWidth', 8)
        .attr('markerHeight', 8)
        .attr('orient', 'auto')
        .append('path')
        .attr('d', 'M0,-5L10,0L0,5')
        .attr('fill', '#999');

      // Available transition arrow (green)
      defs.append('marker')
        .attr('id', 'workflow-arrow-available')
        .attr('viewBox', '0 -5 10 10')
        .attr('refX', 25)
        .attr('refY', 0)
        .attr('markerWidth', 8)
        .attr('markerHeight', 8)
        .attr('orient', 'auto')
        .append('path')
        .attr('d', 'M0,-5L10,0L0,5')
        .attr('fill', '#4caf50');

      // Glow filter for current state
      var filter = defs.append('filter')
        .attr('id', 'glow')
        .attr('x', '-50%')
        .attr('y', '-50%')
        .attr('width', '200%')
        .attr('height', '200%');
      
      filter.append('feGaussianBlur')
        .attr('stdDeviation', '3')
        .attr('result', 'coloredBlur');
      
      var feMerge = filter.append('feMerge');
      feMerge.append('feMergeNode').attr('in', 'coloredBlur');
      feMerge.append('feMergeNode').attr('in', 'SourceGraphic');

      // Main group
      this.mainGroup = this.svg.append('g')
        .attr('class', 'workflow-main');

      // Layers
      this.linksLayer = this.mainGroup.append('g').attr('class', 'links-layer');
      this.nodesLayer = this.mainGroup.append('g').attr('class', 'nodes-layer');

      // Tooltip
      this.tooltip = d3.select('body')
        .append('div')
        .attr('class', 'workflow-tooltip')
        .style('opacity', 0)
        .style('position', 'absolute')
        .style('background', 'white')
        .style('border', '1px solid #ccc')
        .style('border-radius', '4px')
        .style('padding', '10px')
        .style('pointer-events', 'none')
        .style('max-width', '250px')
        .style('box-shadow', '0 2px 8px rgba(0,0,0,0.15)')
        .style('z-index', 1000);
    },

    setupSimulation: function() {
      var self = this;
      var opts = this.options;

      this.simulation = d3.forceSimulation(this.nodes)
        .force('link', d3.forceLink(this.links)
          .id(function(d) { return d.id; })
          .distance(120))
        .force('charge', d3.forceManyBody().strength(-400))
        .force('center', d3.forceCenter(opts.width / 2, opts.height / 2))
        .force('collision', d3.forceCollide().radius(opts.nodeRadius + 20));

      this.simulation.on('tick', function() {
        self.updatePositions();
      });
    },

    render: function() {
      this.renderLinks();
      this.renderNodes();
    },

    renderLinks: function() {
      var self = this;

      var link = this.linksLayer.selectAll('.workflow-link')
        .data(this.links)
        .enter()
        .append('g')
        .attr('class', 'workflow-link');

      // Link line
      link.append('path')
        .attr('class', 'link-path')
        .attr('fill', 'none')
        .attr('stroke', function(d) {
          return d.isAvailable ? '#4caf50' : '#999';
        })
        .attr('stroke-width', function(d) {
          return d.isAvailable ? 3 : 1.5;
        })
        .attr('stroke-dasharray', function(d) {
          return d.isAvailable ? 'none' : '5,3';
        })
        .attr('marker-end', function(d) {
          return d.isAvailable ? 'url(#workflow-arrow-available)' : 'url(#workflow-arrow)';
        });

      // Link label
      if (this.options.showLabels) {
        link.append('text')
          .attr('class', 'link-label')
          .attr('text-anchor', 'middle')
          .attr('dy', -5)
          .attr('font-size', '9px')
          .attr('fill', function(d) {
            return d.isAvailable ? '#4caf50' : '#666';
          })
          .text(function(d) { return d.label; });
      }
    },

    renderNodes: function() {
      var self = this;
      var opts = this.options;

      var node = this.nodesLayer.selectAll('.workflow-node')
        .data(this.nodes)
        .enter()
        .append('g')
        .attr('class', 'workflow-node')
        .style('cursor', opts.interactive ? 'pointer' : 'default')
        .on('mouseover', function(event, d) { self.showTooltip(event, d); })
        .on('mouseout', function() { self.hideTooltip(); })
        .on('click', function(event, d) { 
          if (opts.onNodeClick) opts.onNodeClick(d); 
        });

      // Enable drag if interactive
      if (opts.interactive) {
        node.call(d3.drag()
          .on('start', function(event, d) { self.dragStart(event, d); })
          .on('drag', function(event, d) { self.dragging(event, d); })
          .on('end', function(event, d) { self.dragEnd(event, d); }));
      }

      // Node circle
      node.append('circle')
        .attr('r', opts.nodeRadius)
        .attr('fill', function(d) {
          if (d.isCurrent) return opts.colors.current;
          if (d.isFinal) return opts.colors.final;
          if (d.isInitial) return opts.colors.initial;
          return opts.colors[d.color] || opts.colors.default;
        })
        .attr('stroke', function(d) {
          return d.isCurrent ? '#2e7d32' : '#ccc';
        })
        .attr('stroke-width', function(d) {
          return d.isCurrent ? 3 : 1;
        })
        .attr('filter', function(d) {
          return d.isCurrent ? 'url(#glow)' : null;
        });

      // Double circle for final states
      node.filter(function(d) { return d.isFinal; })
        .append('circle')
        .attr('r', opts.nodeRadius - 5)
        .attr('fill', 'none')
        .attr('stroke', '#666')
        .attr('stroke-width', 2);

      // Initial state indicator (arrow)
      node.filter(function(d) { return d.isInitial; })
        .append('path')
        .attr('d', 'M-60,-10 L-45,0 L-60,10')
        .attr('fill', opts.colors.initial);

      // Node label
      node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', opts.nodeRadius + 15)
        .attr('font-size', '10px')
        .attr('font-weight', function(d) { return d.isCurrent ? 'bold' : 'normal'; })
        .text(function(d) {
          return d.label.length > 15 ? d.label.substr(0, 13) + '...' : d.label;
        });

      // Current state badge
      node.filter(function(d) { return d.isCurrent; })
        .append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', 4)
        .attr('font-size', '20px')
        .text('✓');
    },

    updatePositions: function() {
      var opts = this.options;

      // Keep nodes within bounds
      this.nodes.forEach(function(d) {
        d.x = Math.max(opts.nodeRadius, Math.min(opts.width - opts.nodeRadius, d.x));
        d.y = Math.max(opts.nodeRadius, Math.min(opts.height - opts.nodeRadius, d.y));
      });

      // Update node positions
      this.nodesLayer.selectAll('.workflow-node')
        .attr('transform', function(d) {
          return 'translate(' + d.x + ',' + d.y + ')';
        });

      // Update link positions
      this.linksLayer.selectAll('.workflow-link .link-path')
        .attr('d', function(d) {
          return 'M' + d.source.x + ',' + d.source.y + 
                 'L' + d.target.x + ',' + d.target.y;
        });

      this.linksLayer.selectAll('.workflow-link .link-label')
        .attr('x', function(d) { return (d.source.x + d.target.x) / 2; })
        .attr('y', function(d) { return (d.source.y + d.target.y) / 2; });
    },

    showTooltip: function(event, d) {
      var html = '<strong>' + d.label + '</strong>';
      if (d.description) {
        html += '<br><small>' + d.description + '</small>';
      }
      if (d.isCurrent) {
        html += '<br><span style="color:#4caf50">● Current State</span>';
      }
      if (d.isInitial) {
        html += '<br><span style="color:#2196f3">→ Initial State</span>';
      }
      if (d.isFinal) {
        html += '<br><span style="color:#9e9e9e">◎ Final State</span>';
      }

      this.tooltip
        .html(html)
        .style('left', (event.pageX + 10) + 'px')
        .style('top', (event.pageY - 10) + 'px')
        .transition()
        .duration(200)
        .style('opacity', 1);
    },

    hideTooltip: function() {
      this.tooltip
        .transition()
        .duration(200)
        .style('opacity', 0);
    },

    dragStart: function(event, d) {
      if (!event.active) this.simulation.alphaTarget(0.3).restart();
      d.fx = d.x;
      d.fy = d.y;
    },

    dragging: function(event, d) {
      d.fx = event.x;
      d.fy = event.y;
    },

    dragEnd: function(event, d) {
      if (!event.active) this.simulation.alphaTarget(0);
      d.fx = null;
      d.fy = null;
    },

    setCurrentState: function(state) {
      this.currentState = state;
      this.nodes.forEach(function(n) {
        n.isCurrent = n.id === state;
      });
      this.updateNodeStyles();
    },

    updateNodeStyles: function() {
      var opts = this.options;

      this.nodesLayer.selectAll('.workflow-node circle')
        .transition()
        .duration(opts.transitionDuration)
        .attr('fill', function(d) {
          if (d.isCurrent) return opts.colors.current;
          if (d.isFinal) return opts.colors.final;
          if (d.isInitial) return opts.colors.initial;
          return opts.colors[d.color] || opts.colors.default;
        })
        .attr('stroke', function(d) {
          return d.isCurrent ? '#2e7d32' : '#ccc';
        })
        .attr('stroke-width', function(d) {
          return d.isCurrent ? 3 : 1;
        });
    },

    destroy: function() {
      if (this.simulation) {
        this.simulation.stop();
      }
      this.container.selectAll('*').remove();
      if (this.tooltip) {
        this.tooltip.remove();
      }
    }
  };

  // Progress bar component
  WorkflowVisualization.ProgressBar = function(selector, options) {
    this.container = d3.select(selector);
    this.options = Object.assign({
      width: 400,
      height: 30,
      progress: 0,
      color: '#4caf50',
      backgroundColor: '#e0e0e0',
      showLabel: true
    }, options);
    
    this.render();
  };

  WorkflowVisualization.ProgressBar.prototype = {
    render: function() {
      var opts = this.options;
      
      this.container.selectAll('*').remove();
      
      var svg = this.container
        .append('svg')
        .attr('width', opts.width)
        .attr('height', opts.height);

      // Background
      svg.append('rect')
        .attr('width', opts.width)
        .attr('height', opts.height)
        .attr('fill', opts.backgroundColor)
        .attr('rx', 4);

      // Progress
      this.progressBar = svg.append('rect')
        .attr('width', (opts.progress / 100) * opts.width)
        .attr('height', opts.height)
        .attr('fill', opts.color)
        .attr('rx', 4);

      // Label
      if (opts.showLabel) {
        this.label = svg.append('text')
          .attr('x', opts.width / 2)
          .attr('y', opts.height / 2 + 5)
          .attr('text-anchor', 'middle')
          .attr('font-size', '12px')
          .attr('fill', opts.progress > 50 ? 'white' : '#333')
          .text(opts.progress + '%');
      }
    },

    update: function(progress) {
      var opts = this.options;
      opts.progress = progress;

      this.progressBar
        .transition()
        .duration(300)
        .attr('width', (progress / 100) * opts.width);

      if (this.label) {
        this.label
          .attr('fill', progress > 50 ? 'white' : '#333')
          .text(progress + '%');
      }
    }
  };

  // Export
  global.WorkflowVisualization = WorkflowVisualization;

})(typeof window !== 'undefined' ? window : this);
