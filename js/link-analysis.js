/**
 * link-analysis.js - Frontend display logic for link analysis
 * File path: js/link-analysis.js
 */

const LinkAnalysis = {
    /**
     * Initialize the link analysis module
     */
    initialize: function() {
        console.log('Link Analysis module initialized');
        
        // Set up event listeners
        this.setupEventListeners();
    },
    
    /**
     * Set up event listeners
     */
    setupEventListeners: function() {
        // Listen for when link analysis is requested
        $(document).on('requestLinkAnalysis', (event, url) => {
            this.performLinkAnalysis(url);
        });
        
        // Listen for re-analysis button clicks
        $(document).on('click', '#rerunLinkAnalysisBtn', function() {
            const url = $('#websiteUrl').val();
            if (url) {
                LinkAnalysis.performLinkAnalysis(url);
            }
        });
        
        // Listen for max pages change
        $(document).on('change', '#linkAnalysisMaxPages', function() {
            const url = $('#websiteUrl').val();
            if (url) {
                LinkAnalysis.performLinkAnalysis(url);
            }
        });
    },
    
    /**
     * Perform link analysis
     * 
     * @param {string} url URL to analyze
     */
    performLinkAnalysis: function(url) {
        // Show loading indicator
        $('#linkAnalysisContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Analyzing links... This may take a moment.</p>
            </div>
        `);
        
        // Get max pages setting
        const maxPages = $('#linkAnalysisMaxPages').val() || 10;
        
        // Call API
        $.ajax({
            url: 'api/link-analysis-api.php',
            type: 'POST',
            data: { 
                url: url,
                max_pages: maxPages
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.displayResults(response.data);
                } else {
                    this.displayError(response.message || 'Unknown error occurred during link analysis.');
                }
            },
            error: (xhr, status, error) => {
                this.displayError('Server error: ' + error);
            }
        });
    },
    
    /**
     * Display link analysis results
     * 
     * @param {Object} data Analysis results
     */
    displayResults: function(data) {
    // Handle null or undefined data
    if (!data) {
        $('#linkAnalysisContent').html(`
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No link analysis data available.
            </div>
        `);
        return;
    }
    
    try {		
		
		
        // Calculate scores for visual representation
        const internalLinkScore = data.internal_link_score || 0;
        const externalLinkScore = data.external_link_score || 0;
        const overallScore = data.overall_score || 0;
        
        // Create HTML content
        let content = `
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Link Analysis Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="score-circle ${this.getScoreClass(overallScore)}">
                                        <div class="score-number">${overallScore}</div>
                                    </div>
                                    <div class="mt-2">Overall Score</div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Internal Link Structure</label>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar ${this.getProgressBarClass(internalLinkScore)}" 
                                                 style="width: ${internalLinkScore}%;" 
                                                 aria-valuenow="${internalLinkScore}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                ${internalLinkScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">External Link Quality</label>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar ${this.getProgressBarClass(externalLinkScore)}" 
                                                 style="width: ${externalLinkScore}%;" 
                                                 aria-valuenow="${externalLinkScore}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                ${externalLinkScore}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2">Key Link Metrics</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Internal Links:</span>
                                        <span class="badge bg-primary">${data.total_internal_links || 0}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>External Links:</span>
                                        <span class="badge bg-secondary">${data.total_external_links || 0}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Broken Links:</span>
                                        <span class="badge bg-${data.total_broken_links > 0 ? 'danger' : 'success'}">${data.total_broken_links || 0}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Orphaned Pages:</span>
                                        <span class="badge bg-${data.orphaned_pages > 0 ? 'warning' : 'success'}">${data.orphaned_pages || 0}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2">Analysis Settings</h6>
                                    <div class="mb-3">
                                        <label for="linkAnalysisMaxPages" class="form-label">Pages Analyzed:</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="linkAnalysisMaxPages" 
                                                 min="5" max="20" value="${data.analyzed_pages || 10}">
                                            <button class="btn btn-outline-secondary" type="button" id="rerunLinkAnalysisBtn">
                                                <i class="fas fa-sync-alt"></i> Rerun
                                            </button>
                                        </div>
                                        <div class="form-text">Adjust the number of pages to analyze (5-20)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Link Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="linkDistributionChart" height="240"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Link visualization section
        content += `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Link Structure Visualization</h5>
                </div>
                <div class="card-body p-0">
                    <div id="linkVisualization" style="height: 400px; width: 100%;"></div>
                </div>
            </div>
        `;
        
        // Issues and recommendations
        content += `
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Identified Issues</h5>
                        </div>
                        <div class="card-body">
                            ${this.renderIssuesList(data.issues)}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Recommendations</h5>
                        </div>
                        <div class="card-body">
                            ${this.renderRecommendationsList(data.recommendations)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Broken links section (if any)
        if (data.broken_links && data.broken_links.length > 0) {
            content += `
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Broken Links (${data.broken_links.length})</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Broken URL</th>
                                        <th>Source Page</th>
                                        <th>Status</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.broken_links.map(link => `
                                        <tr>
                                            <td><a href="${link.url}" target="_blank" class="text-danger">${this.truncateUrl(link.url, 50)}</a></td>
                                            <td><a href="${link.source}" target="_blank">${this.truncateUrl(link.source, 40)}</a></td>
                                            <td><span class="badge bg-danger">${link.status || 'Error'}</span></td>
                                            <td>${link.type === 'external' ? 'External' : 'Internal'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Update the content
        $('#linkAnalysisContent').html(content);
		UIManager.updateScoreBadge('linkAnalysis', data.overall_score);
        
        // Store the data for later use
        $('#linkAnalysisContent').data('results', data);
        
        // Initialize charts and visualizations
        this.initLinkDistributionChart(data);
        this.initLinkVisualization(data.visualization_data);
    } catch (error) {
        console.error("Error displaying link analysis results:", error);
        $('#linkAnalysisContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Error displaying link analysis: ${error.message}
            </div>
        `);
    }
},
 renderLinkDetails: function(linkDetails) {
    if (!linkDetails || linkDetails.length === 0) {
        return '<p class="text-muted">No link details available.</p>';
    }
    
    let html = '<div class="link-details-container">';
    
    linkDetails.forEach((pageData, pageIndex) => {
        const pageUrl = pageData.url;
        const links = pageData.links;
        
        if (!links || links.length === 0) {
            return;
        }
        
        const shortUrl = this.truncateUrl(pageUrl);
        
        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i> ${shortUrl} (${links.length} links)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>URL</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Text</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${this.renderLinkRows(links)}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}, 
    /**
     * Display error message
     * 
     * @param {string} message Error message
     */
    displayError: function(message) {
        $('#linkAnalysisContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i> ${message}
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Link Analysis Troubleshooting</h5>
                </div>
                <div class="card-body">
                    <p>The link analysis could not be completed. Here are some possible reasons:</p>
                    <ul>
                        <li>The website is blocking automated crawling</li>
                        <li>The website requires JavaScript to display content</li>
                        <li>The website has robots.txt restrictions</li>
                        <li>The server is responding slowly or is unreachable</li>
                    </ul>
                    <div class="mt-3">
                        <button id="rerunLinkAnalysisBtn" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-1"></i> Try Again
                        </button>
                    </div>
                </div>
            </div>
        `);
    },
    
    /**
     * Initialize link distribution chart
     * 
     * @param {Object} data Analysis data
     */
    initLinkDistributionChart: function(data) {
        // Prepare data for chart
        const linkDistribution = data.link_distribution || {};
        
        // Calculate totals
        let totalInternal = 0;
        let totalExternal = 0;
        let totalNofollow = 0;
        
        Object.values(linkDistribution).forEach(page => {
            totalInternal += page.internal || 0;
            totalExternal += page.external || 0;
            totalNofollow += page.nofollow || 0;
        });
        
        // Create chart
        const ctx = document.getElementById('linkDistributionChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Internal Links', 'External Links', 'Nofollow Links'],
                datasets: [{
                    data: [totalInternal, totalExternal, totalNofollow],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    },
    
    /**
     * Initialize link visualization
     * 
     * @param {Object} data Visualization data
     */
    initLinkVisualization: function(data) {
        if (!data || !data.nodes || !data.links) {
            $('#linkVisualization').html('<div class="alert alert-info m-3">No visualization data available</div>');
            return;
        }
        
        // Initialize network visualization
        const container = document.getElementById('linkVisualization');
        
        // Prepare visualization data
        const nodes = new vis.DataSet(data.nodes.map(node => ({
            id: node.id,
            label: node.label,
            title: node.url || node.label,
            group: node.group,
            value: node.size
        })));
        
        const edges = new vis.DataSet(data.links.map(link => ({
            from: link.source,
            to: link.target,
            arrows: link.type === 'external' ? 'to' : null,
            dashes: link.type === 'external',
            color: link.type === 'external' ? '#ff9f40' : '#36a2eb'
        })));
        
        // Create network
        const networkData = { nodes, edges };
        const options = {
            nodes: {
                shape: 'dot',
                size: 16,
                font: {
                    size: 12,
                    face: 'Tahoma'
                },
                borderWidth: 2,
                shadow: true
            },
            edges: {
                width: 2,
                shadow: true
            },
            groups: {
                main: {
                    color: { background: '#36a2eb', border: '#2980b9' },
                    shape: 'star'
                },
                internal: {
                    color: { background: '#3498db', border: '#2980b9' }
                },
                external: {
                    color: { background: '#ff9f40', border: '#e67e22' }
                }
            },
            physics: {
                stabilization: false,
                barnesHut: {
                    gravitationalConstant: -2000,
                    centralGravity: 0.3,
                    springLength: 95,
                    springConstant: 0.04,
                    damping: 0.09,
                    avoidOverlap: 0.1
                }
            }
        };
        
        // Create network
        new vis.Network(container, networkData, options);
    },
    
    /**
     * Render issues list
     * 
     * @param {Array} issues List of issues
     * @return {string} HTML content
     */
    renderIssuesList: function(issues) {
        if (!issues || issues.length === 0) {
            return `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i> No significant link issues detected
                </div>
            `;
        }
        
        let html = '<div class="list-group">';
        
        issues.forEach(issue => {
            const severityClass = this.getSeverityClass(issue.severity);
            const severityIcon = this.getSeverityIcon(issue.severity);
            
            html += `
                <div class="list-group-item list-group-item-${severityClass}">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">
                            <i class="${severityIcon} me-2"></i> ${issue.description}
                        </h6>
                        <small>${this.capitalizeFirstLetter(issue.severity)}</small>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    },
    
 /**
 * Render recommendations list
 * 
 * @param {Array} recommendations List of recommendations
 * @return {string} HTML content
 */
renderRecommendationsList: function(recommendations) {
    if (!recommendations || recommendations.length === 0) {
        return `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> No specific recommendations available
            </div>
        `;
    }
    
    let html = '<div class="link-recommendations-container">';
    
    recommendations.forEach((recommendation, index) => {
        const priorityClass = this.getPriorityClass(recommendation.priority);
        const priorityTextClass = priorityClass === 'danger' ? 'text-danger' : 
                                 priorityClass === 'warning' ? 'text-warning' : 'text-info';
        
        html += `
            <div class="card mb-3 border-${priorityClass}">
                <div class="card-header bg-white d-flex align-items-center">
                    <span class="badge bg-${priorityClass} me-2">${this.capitalizeFirstLetter(recommendation.priority)}</span>
                    <h5 class="mb-0 flex-grow-1">${recommendation.description}</h5>
                </div>
                ${recommendation.details ? `
                <div class="card-body bg-light border-top">
                    <p class="mb-0">${recommendation.details}</p>
                </div>` : ''}
            </div>
        `;
    });
    
    html += '</div>';
    return html;
},  
    
    /**
     * Get CSS class for score
     * 
     * @param {number} score The score value
     * @return {string} CSS class
     */
    getScoreClass: function(score) {
        if (score >= 90) return 'score-excellent';
        if (score >= 70) return 'score-good';
        if (score >= 50) return 'score-average';
        return 'score-poor';
    },
    
    /**
     * Get CSS class for progress bar
     * 
     * @param {number} score The score value
     * @return {string} CSS class
     */
    getProgressBarClass: function(score) {
        if (score >= 90) return 'bg-success';
        if (score >= 70) return 'bg-primary';
        if (score >= 50) return 'bg-warning';
        return 'bg-danger';
    },
    
    /**
     * Get CSS class for severity
     * 
     * @param {string} severity The severity level
     * @return {string} CSS class
     */
    getSeverityClass: function(severity) {
        switch (severity.toLowerCase()) {
            case 'high': return 'danger';
            case 'medium': return 'warning';
            case 'low': return 'info';
            default: return 'secondary';
        }
    },
    
    /**
     * Get icon for severity
     * 
     * @param {string} severity The severity level
     * @return {string} Icon class
     */
    getSeverityIcon: function(severity) {
        switch (severity.toLowerCase()) {
            case 'high': return 'fas fa-exclamation-circle';
            case 'medium': return 'fas fa-exclamation-triangle';
            case 'low': return 'fas fa-info-circle';
            default: return 'fas fa-info-circle';
        }
    },
    
    /**
     * Get CSS class for priority
     * 
     * @param {string} priority The priority level
     * @return {string} CSS class
     */
    getPriorityClass: function(priority) {
        switch (priority.toLowerCase()) {
            case 'high': return 'danger';
            case 'medium': return 'warning';
            case 'low': return 'info';
            default: return 'secondary';
        }
    },
	
	
/**
 * Render link table rows
 * @param {Array} links List of links
 * @return {string} HTML for table rows
 */
renderLinkRows: function(links) {
    if (!links || links.length === 0) {
        return '<tr><td colspan="4" class="text-center">No link data available</td></tr>';
    }
    
    let html = '';
    
    links.forEach(link => {
        // Default values for when link data structure might vary
        const url = link.url || '';
        const type = link.type || (link.external ? 'External' : 'Internal');
        const status = link.status || 'Unknown';
        const text = link.text || '';
        
        // Determine status class
        let statusClass = 'bg-secondary';
        if (typeof status === 'number') {
            if (status >= 200 && status < 300) statusClass = 'bg-success';
            else if (status >= 300 && status < 400) statusClass = 'bg-info';
            else if (status >= 400) statusClass = 'bg-danger';
        }
        
        html += `
            <tr>
                <td class="text-truncate" style="max-width: 200px;">
                    <a href="${url}" target="_blank" title="${url}">${this.truncateUrl(url, 30)}</a>
                </td>
                <td>${type}</td>
                <td><span class="badge ${statusClass}">${status}</span></td>
                <td class="text-truncate" style="max-width: 150px;" title="${text}">${text || '—'}</td>
            </tr>
        `;
    });
    
    return html;
},	
    
    /**
     * Truncate URL for display
     * 
     * @param {string} url The URL to truncate
     * @param {number} maxLength Maximum length
     * @return {string} Truncated URL
     */
    truncateUrl: function(url, maxLength = 50) {
        if (!url) return '';
        
        // Remove protocol
        let displayUrl = url.replace(/^https?:\/\//, '');
        
        // Truncate if too long
        if (displayUrl.length > maxLength) {
            displayUrl = displayUrl.substring(0, maxLength - 3) + '...';
        }
        
        return displayUrl;
    },
    
    /**
     * Capitalize first letter of a string
     * 
     * @param {string} string The string to capitalize
     * @return {string} Capitalized string
     */
    capitalizeFirstLetter: function(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
};

// Initialize when document is ready
$(document).ready(function() {
    LinkAnalysis.initialize();
});


$(document).on('requestLinkAnalysis', function(event, url) {
    // Check if we have data already loaded
    const existingData = $('#linkAnalysisContent').data('results');
    
    if (existingData) {
        // Display the data directly
        LinkAnalysis.displayResults(existingData);
    } else {
        // Request new data
        ApiService.runLinkAnalysis(url)
            .done(function(response) {
                if (response.success) {
                    // Display the results
                    LinkAnalysis.displayResults(response.data);
                    
                    // Store for later reference
                    $('#linkAnalysisContent').data('results', response.data);
                } else {
                    // Display error
                    $('#linkAnalysisContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Link analysis failed: ${response.message}
                        </div>
                    `);
                }
            })
            .fail(function() {
                // Display API error
                $('#linkAnalysisContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Link analysis failed due to a server error
                    </div>
                `);
            });
    }
});