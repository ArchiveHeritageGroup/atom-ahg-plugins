/**
 * Provenance Timeline Visualization.
 *
 * D3.js-based timeline visualization for museum object provenance chains.
 * Displays ownership history with transfer events and date ranges.
 *
 * Usage:
 *   var timeline = new ProvenanceTimeline('#timeline-container', {
 *     data: timelineData,
 *     width: 800,
 *     height: 400
 *   });
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
(function(global) {
  'use strict';

  var ProvenanceTimeline = function(selector, options) {
    this.container = d3.select(selector);
    this.options = Object.assign({}, ProvenanceTimeline.defaults, options);
    this.data = options.data || { nodes: [], links: [], events: [] };
    
    this.init();
  };

  ProvenanceTimeline.defaults = {
    width: 900,
    height: 400,
    margin: { top: 40, right: 30, bottom: 60, left: 30 },
    nodeRadius: 20,
    nodeHeight: 60,
    colors: {
      person: '#2196f3',
      family: '#9c27b0',
      dealer: '#ff9800',
      auction_house: '#f44336',
      museum: '#4caf50',
      corporate: '#607d8b',
      government: '#795548',
      religious: '#673ab7',
      artist: '#e91e63',
      unknown: '#9e9e9e',
      gap: '#ffeb3b'
    },
    transferIcons: {
      sale: '💰',
      auction: '🔨',
      gift: '🎁',
      bequest: '📜',
      inheritance: '👨‍👩‍👧',
      commission: '✏️',
      exchange: '🔄',
      seizure: '⚠️',
      restitution: '↩️',
      transfer: '➡️',
      loan: '⏱️',
      found: '🔍',
      created: '🎨',
      unknown: '❓'
    },
    showLabels: true,
    showDates: true,
    showTransferDetails: true,
    animationDuration: 500
  };

  ProvenanceTimeline.prototype = {
    init: function() {
      this.svg = null;
      this.xScale = null;
      this.yScale = null;
      this.tooltip = null;
      
      this.setupSvg();
      this.setupScales();
      this.setupTooltip();
      this.render();
    },

    setupSvg: function() {
      var opts = this.options;
      
      // Clear existing
      this.container.selectAll('*').remove();
      
      // Create SVG
      this.svg = this.container
        .append('svg')
        .attr('width', opts.width)
        .attr('height', opts.height)
        .attr('class', 'provenance-timeline');

      // Add defs for markers
      var defs = this.svg.append('defs');
      
      // Arrow marker
      defs.append('marker')
        .attr('id', 'arrow')
        .attr('viewBox', '0 -5 10 10')
        .attr('refX', 8)
        .attr('refY', 0)
        .attr('markerWidth', 6)
        .attr('markerHeight', 6)
        .attr('orient', 'auto')
        .append('path')
        .attr('d', 'M0,-5L10,0L0,5')
        .attr('fill', '#666');

      // Main group with margins
      this.mainGroup = this.svg.append('g')
        .attr('transform', 'translate(' + opts.margin.left + ',' + opts.margin.top + ')');
      
      // Layers
      this.linksLayer = this.mainGroup.append('g').attr('class', 'links-layer');
      this.nodesLayer = this.mainGroup.append('g').attr('class', 'nodes-layer');
      this.eventsLayer = this.mainGroup.append('g').attr('class', 'events-layer');
      this.labelsLayer = this.mainGroup.append('g').attr('class', 'labels-layer');
    },

    setupScales: function() {
      var opts = this.options;
      var data = this.data;
      var innerWidth = opts.width - opts.margin.left - opts.margin.right;
      var innerHeight = opts.height - opts.margin.top - opts.margin.bottom;

      // X scale: time
      var minYear = data.dateRange ? data.dateRange.min : 1900;
      var maxYear = data.dateRange ? data.dateRange.max : new Date().getFullYear();
      
      // Add padding
      var yearPadding = Math.max(10, (maxYear - minYear) * 0.05);
      
      this.xScale = d3.scaleLinear()
        .domain([minYear - yearPadding, maxYear + yearPadding])
        .range([0, innerWidth]);

      // Y scale: just center vertically
      this.yCenter = innerHeight / 2;
    },

    setupTooltip: function() {
      this.tooltip = d3.select('body')
        .append('div')
        .attr('class', 'provenance-tooltip')
        .style('opacity', 0)
        .style('position', 'absolute')
        .style('background', 'white')
        .style('border', '1px solid #ccc')
        .style('border-radius', '4px')
        .style('padding', '10px')
        .style('pointer-events', 'none')
        .style('max-width', '300px')
        .style('box-shadow', '0 2px 8px rgba(0,0,0,0.15)')
        .style('z-index', 1000);
    },

    render: function() {
      this.renderAxis();
      this.renderLinks();
      this.renderNodes();
      this.renderEvents();
      if (this.options.showLabels) {
        this.renderLabels();
      }
    },

    renderAxis: function() {
      var opts = this.options;
      var innerHeight = opts.height - opts.margin.top - opts.margin.bottom;

      // Time axis
      var xAxis = d3.axisBottom(this.xScale)
        .tickFormat(d3.format('d'))
        .ticks(10);

      this.mainGroup.append('g')
        .attr('class', 'x-axis')
        .attr('transform', 'translate(0,' + innerHeight + ')')
        .call(xAxis);

      // Axis label
      this.mainGroup.append('text')
        .attr('class', 'axis-label')
        .attr('x', (opts.width - opts.margin.left - opts.margin.right) / 2)
        .attr('y', innerHeight + 40)
        .attr('text-anchor', 'middle')
        .text('Year');
    },

    renderLinks: function() {
      var self = this;
      var nodes = this.data.nodes;
      
      // Create node position map
      var nodePositions = {};
      nodes.forEach(function(node, i) {
        var x = self.xScale(node.startYear || self.data.dateRange.min);
        nodePositions[node.id] = { x: x, y: self.yCenter };
      });

      // Draw links
      var links = this.linksLayer.selectAll('.provenance-link')
        .data(this.data.links)
        .enter()
        .append('line')
        .attr('class', 'provenance-link')
        .attr('x1', function(d) { 
          return nodePositions[d.source] ? nodePositions[d.source].x + self.options.nodeRadius : 0; 
        })
        .attr('y1', this.yCenter)
        .attr('x2', function(d) { 
          return nodePositions[d.target] ? nodePositions[d.target].x - self.options.nodeRadius : 0; 
        })
        .attr('y2', this.yCenter)
        .attr('stroke', '#999')
        .attr('stroke-width', 2)
        .attr('marker-end', 'url(#arrow)');
    },

    renderNodes: function() {
      var self = this;
      var opts = this.options;

      var nodeGroups = this.nodesLayer.selectAll('.provenance-node')
        .data(this.data.nodes)
        .enter()
        .append('g')
        .attr('class', 'provenance-node')
        .attr('transform', function(d, i) {
          var x = self.xScale(d.startYear || self.data.dateRange.min + (i * 10));
          return 'translate(' + x + ',' + self.yCenter + ')';
        })
        .style('cursor', 'pointer')
        .on('mouseover', function(event, d) { self.showTooltip(event, d, 'node'); })
        .on('mouseout', function() { self.hideTooltip(); })
        .on('click', function(event, d) { self.onNodeClick(d); });

      // Node circles
      nodeGroups.append('circle')
        .attr('r', opts.nodeRadius)
        .attr('fill', function(d) {
          if (d.isGap) return opts.colors.gap;
          return d.color || opts.colors[d.ownerType] || opts.colors.unknown;
        })
        .attr('stroke', function(d) {
          return d.certaintyValue < 50 ? '#999' : 'none';
        })
        .attr('stroke-width', 2)
        .attr('stroke-dasharray', function(d) {
          return d.certaintyValue < 50 ? '4,2' : 'none';
        })
        .attr('opacity', function(d) {
          return 0.5 + (d.certaintyValue / 200);
        });

      // Owner type icon
      nodeGroups.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '0.35em')
        .attr('font-size', '14px')
        .text(function(d) {
          return self.getOwnerTypeIcon(d.ownerType);
        });

      // Date range indicator
      if (opts.showDates) {
        nodeGroups.each(function(d) {
          if (d.startYear && d.endYear && d.endYear !== d.startYear) {
            var width = self.xScale(d.endYear) - self.xScale(d.startYear);
            d3.select(this).insert('rect', 'circle')
              .attr('x', 0)
              .attr('y', -5)
              .attr('width', Math.max(0, width))
              .attr('height', 10)
              .attr('fill', opts.colors[d.ownerType] || opts.colors.unknown)
              .attr('opacity', 0.2);
          }
        });
      }
    },

    renderEvents: function() {
      var self = this;
      var opts = this.options;

      var eventGroups = this.eventsLayer.selectAll('.provenance-event')
        .data(this.data.events)
        .enter()
        .append('g')
        .attr('class', 'provenance-event')
        .attr('transform', function(d) {
          var x = self.xScale(d.year || self.data.dateRange.min);
          return 'translate(' + x + ',' + (self.yCenter - 40) + ')';
        })
        .style('cursor', 'pointer')
        .on('mouseover', function(event, d) { self.showTooltip(event, d, 'event'); })
        .on('mouseout', function() { self.hideTooltip(); });

      // Event icon
      eventGroups.append('text')
        .attr('text-anchor', 'middle')
        .attr('font-size', '16px')
        .text(function(d) {
          return opts.transferIcons[d.transferType] || opts.transferIcons.unknown;
        });

      // Year label
      if (opts.showDates) {
        eventGroups.append('text')
          .attr('text-anchor', 'middle')
          .attr('y', -15)
          .attr('font-size', '10px')
          .attr('fill', '#666')
          .text(function(d) { return d.year || ''; });
      }
    },

    renderLabels: function() {
      var self = this;
      var opts = this.options;

      this.labelsLayer.selectAll('.provenance-label')
        .data(this.data.nodes)
        .enter()
        .append('text')
        .attr('class', 'provenance-label')
        .attr('x', function(d) {
          return self.xScale(d.startYear || self.data.dateRange.min);
        })
        .attr('y', this.yCenter + opts.nodeRadius + 15)
        .attr('text-anchor', 'middle')
        .attr('font-size', '11px')
        .attr('fill', '#333')
        .text(function(d) {
          var label = d.label || '';
          return label.length > 20 ? label.substr(0, 18) + '...' : label;
        });
    },

    showTooltip: function(event, d, type) {
      var html = '';
      
      if (type === 'node') {
        html = '<strong>' + (d.label || 'Unknown') + '</strong>';
        if (d.location) html += '<br><em>' + d.location + '</em>';
        if (d.startYear) {
          html += '<br>Period: ' + d.startYear;
          if (d.endYear && d.endYear !== d.startYear) {
            html += ' - ' + d.endYear;
          }
        }
        html += '<br>Certainty: ' + (d.certainty || 'unknown');
        if (d.isGap) html += '<br><span style="color:#f44336">⚠ Gap in provenance</span>';
      } else if (type === 'event') {
        html = '<strong>' + (d.label || 'Transfer') + '</strong>';
        if (d.year) html += '<br>Year: ' + d.year;
        if (d.details) html += '<br>' + d.details;
        if (d.salePrice) {
          html += '<br>Price: ' + d.saleCurrency + ' ' + d.salePrice.toLocaleString();
        }
        if (d.auctionHouse) {
          html += '<br>Auction: ' + d.auctionHouse;
          if (d.auctionLot) html += ' (Lot ' + d.auctionLot + ')';
        }
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

    getOwnerTypeIcon: function(ownerType) {
      var icons = {
        person: '👤',
        family: '👨‍👩‍👧',
        dealer: '🏪',
        auction_house: '🔨',
        museum: '🏛️',
        corporate: '🏢',
        government: '🏛️',
        religious: '⛪',
        artist: '🎨',
        unknown: '❓'
      };
      return icons[ownerType] || icons.unknown;
    },

    onNodeClick: function(d) {
      if (this.options.onNodeClick) {
        this.options.onNodeClick(d);
      }
    },

    update: function(newData) {
      this.data = newData;
      this.setupScales();
      this.mainGroup.selectAll('*').remove();
      this.linksLayer = this.mainGroup.append('g').attr('class', 'links-layer');
      this.nodesLayer = this.mainGroup.append('g').attr('class', 'nodes-layer');
      this.eventsLayer = this.mainGroup.append('g').attr('class', 'events-layer');
      this.labelsLayer = this.mainGroup.append('g').attr('class', 'labels-layer');
      this.render();
    },

    resize: function(width, height) {
      this.options.width = width;
      this.options.height = height;
      this.init();
    },

    destroy: function() {
      this.container.selectAll('*').remove();
      if (this.tooltip) {
        this.tooltip.remove();
      }
    }
  };

  // Export
  global.ProvenanceTimeline = ProvenanceTimeline;

})(typeof window !== 'undefined' ? window : this);
