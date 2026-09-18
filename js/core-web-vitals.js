/**
 * core-web-vitals.js - Frontend display logic for Core Web Vitals and performance analysis
 * File path: js/core-web-vitals.js
 */

const CoreWebVitals = {
    /**
     * Initialize the Core Web Vitals module
     */
    initialize: function() {
        console.log('Core Web Vitals module initialized');
        
        // Set up event listeners
        this.setupEventListeners();
    },
    
    /**
     * Set up event listeners
     */
    setupEventListeners: function() {
        // Listen for when Core Web Vitals analysis is requested
        $(document).on('requestCoreWebVitalsAnalysis', (event, url) => {
            this.performCoreWebVitalsAnalysis(url);
        });
        
        // Listen for re-analysis button clicks
        $(document).on('click', '#rerunCoreWebVitalsBtn', function() {
            const url = $('#websiteUrl').val();
            if (url) {
                CoreWebVitals.performCoreWebVitalsAnalysis(url);
            }
        });
    },
    
    /**
     * Perform Core Web Vitals analysis
     * 
     * @param {string} url URL to analyze
     */
    performCoreWebVitalsAnalysis: function(url) {
        // Show loading indicator
        $('#coreWebVitalsContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Analyzing Core Web Vitals... This may take a moment.</p>
            </div>
        `);
        
        // Call API
        $.ajax({
            url: 'api/performance-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.displayResults(response.data);
                } else {
                    this.displayError(response.message || 'Unknown error occurred during Core Web Vitals analysis.');
                }
            },
            error: (xhr, status, error) => {
                this.displayError('Server error: ' + error);
            }
        });
    },
    
    /**
     * Display Core Web Vitals analysis results
     * 
     * @param {Object} data Analysis results
     */
    displayResults: function(data) {
        // Handle null or undefined data
        if (!data) {
            $('#coreWebVitalsContent').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No Core Web Vitals data available.
                </div>
            `);
            return;
        }
        
        try {
            // Extract metrics
            const lcp = data.metrics.lcp || {};
            const fid = data.metrics.fid || {};
            const cls = data.metrics.cls || {};
            const ttfb = data.metrics.ttfb || {};
            const tbt = data.metrics.tbt || {};
            const fcp = data.metrics.fcp || {};
            
            // Format metrics for display
            const lcpValue = lcp.value ? `${(lcp.value / 1000).toFixed(2)}s` : 'N/A';
            const fidValue = fid.value ? `${fid.value}ms` : 'N/A';
            const clsValue = cls.value || 'N/A';
            const ttfbValue = ttfb.value ? `${ttfb.value}ms` : 'N/A';
            const tbtValue = tbt.value ? `${tbt.value}ms` : 'N/A';
            const fcpValue = fcp.value ? `${(fcp.value / 1000).toFixed(2)}s` : 'N/A';
            
            // Get scores
            const performanceScore = data.overall_score || 0;
            
            // Create gauges div IDs
            const lcpGaugeId = 'lcpGauge';
            const fidGaugeId = 'fidGauge';
            const clsGaugeId = 'clsGauge';
            
            // Create HTML content
            let content = `
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Overall Performance</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="performance-gauge mb-3">
                                    <canvas id="performanceGauge" width="200" height="200"></canvas>
                                    <div class="performance-score">${performanceScore}</div>
                                </div>
                                <p class="mb-2">${this.getScoreLabel(performanceScore)}</p>
                                ${data.is_estimated ? `
                                    <div class="alert alert-info mt-3 mb-0">
                                        <i class="fas fa-info-circle"></i> This score is estimated based on available data.
                                    </div>
                                ` : ''}
                                <div class="mt-3">
                                    <button id="rerunCoreWebVitalsBtn" class="btn btn-primary">
                                        <i class="fas fa-sync-alt me-1"></i> Reanalyze
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Core Web Vitals</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i> Core Web Vitals are a set of specific factors that Google considers important in a webpage's overall user experience.
                                </div>
                                <div class="row">
                                    <div class="col-md-4 text-center mb-3">
                                        <div class="vitals-gauge">
                                            <canvas id="${lcpGaugeId}" width="120" height="120"></canvas>
                                        </div>
                                        <h6>LCP: ${lcpValue}</h6>
                                        <span class="badge ${this.getStatusBadgeClass(lcp.status)}">${this.formatStatus(lcp.status)}</span>
                                        <p class="small text-muted mt-1">Largest Contentful Paint</p>
                                    </div>
                                    <div class="col-md-4 text-center mb-3">
                                        <div class="vitals-gauge">
                                            <canvas id="${fidGaugeId}" width="120" height="120"></canvas>
                                        </div>
                                        <h6>FID: ${fidValue}</h6>
                                        <span class="badge ${this.getStatusBadgeClass(fid.status)}">${this.formatStatus(fid.status)}</span>
                                        <p class="small text-muted mt-1">First Input Delay</p>
                                    </div>
                                    <div class="col-md-4 text-center mb-3">
                                        <div class="vitals-gauge">
                                            <canvas id="${clsGaugeId}" width="120" height="120"></canvas>
                                        </div>
                                        <h6>CLS: ${clsValue}</h6>
                                        <span class="badge ${this.getStatusBadgeClass(cls.status)}">${this.formatStatus(cls.status)}</span>
                                        <p class="small text-muted mt-1">Cumulative Layout Shift</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add additional metrics section
            content += `
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Additional Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="metric-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <i class="fas fa-tachometer-alt me-2"></i> 
                                            <strong>Time to First Byte (TTFB)</strong>
                                        </div>
                                        <span class="badge ${this.getStatusBadgeClass(ttfb.status)}">${ttfbValue}</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar ${this.getProgressBarClass(ttfb.status)}" style="width: ${ttfb.score || 0}%"></div>
                                    </div>
                                    <p class="small text-muted mt-1">${ttfb.description || ''}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="metric-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <i class="fas fa-hourglass-half me-2"></i> 
                                            <strong>Total Blocking Time (TBT)</strong>
                                        </div>
                                        <span class="badge ${this.getStatusBadgeClass(tbt.status)}">${tbtValue}</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar ${this.getProgressBarClass(tbt.status)}" style="width: ${tbt.score || 0}%"></div>
                                    </div>
                                    <p class="small text-muted mt-1">${tbt.description || ''}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="metric-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <i class="fas fa-paint-brush me-2"></i> 
                                            <strong>First Contentful Paint (FCP)</strong>
                                        </div>
                                        <span class="badge ${this.getStatusBadgeClass(fcp.status)}">${fcpValue}</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar ${this.getProgressBarClass(fcp.status)}" style="width: ${fcp.score || 0}%"></div>
                                    </div>
                                    <p class="small text-muted mt-1">${fcp.description || ''}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add recommendations section
            if (data.recommendations && data.recommendations.length > 0) {
                content += `
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Performance Recommendations</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                `;
                
                data.recommendations.forEach((recommendation, index) => {
                    const priorityClass = recommendation.priority === 'high' ? 'danger' : 
                                         recommendation.priority === 'medium' ? 'warning' : 'info';
                    
                    content += `
                        <div class="col-md-6 mb-3">
                            <div class="card border-${priorityClass} h-100">
                                <div class="card-header bg-${priorityClass} bg-opacity-10">
                                    <h6 class="mb-0">
                                        <span class="badge bg-${priorityClass} me-2">${this.capitalizeFirstLetter(recommendation.priority)}</span>
                                        ${recommendation.title}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>${recommendation.description}</p>
                                    <div class="mt-3">
                                        <strong>Suggested Actions:</strong>
                                        <ul class="mb-0">
                    `;
                    
                    // FIX: Check if actions exists and is an array before using forEach
                    if (recommendation.actions && Array.isArray(recommendation.actions)) {
                        recommendation.actions.forEach(action => {
                            content += `<li>${action}</li>`;
                        });
                    } else if (recommendation.implementation) {
                        // If there are no actions but there is an implementation, use that
                        content += `<li>${recommendation.implementation}</li>`;
                    }
                    
                    content += `
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                content += `
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Add diagnostics section if available
            if (data.diagnostics && data.diagnostics.length > 0) {
                content += `
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Performance Diagnostics</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Diagnostic</th>
                                            <th>Description</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;
                
                data.diagnostics.forEach(diagnostic => {
                    content += `
                        <tr>
                            <td>${diagnostic.title}</td>
                            <td>${diagnostic.description}</td>
                            <td>${diagnostic.display_value || '-'}</td>
                        </tr>
                    `;
                });
                
                content += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Add learn more section
            content += `
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Learn More</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-book me-2"></i> Core Web Vitals</h6>
                                <p>Core Web Vitals are a set of specific factors that Google considers important in a webpage's overall user experience.</p>
                                <ul>
                                    <li><strong>LCP (Largest Contentful Paint)</strong>: Measures loading performance</li>
                                    <li><strong>FID (First Input Delay)</strong>: Measures interactivity</li>
                                    <li><strong>CLS (Cumulative Layout Shift)</strong>: Measures visual stability</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-lightbulb me-2"></i> Why Performance Matters</h6>
                                <p>Website performance directly impacts:</p>
                                <ul>
                                    <li>User experience and engagement</li>
                                    <li>Conversion rates</li>
                                    <li>Search engine rankings</li>
                                    <li>Mobile usability</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Update the content
            $('#coreWebVitalsContent').html(content);
            
            // Initialize gauge charts
            this.initPerformanceGauge('performanceGauge', performanceScore);
            this.initCoreWebVitalsGauge(lcpGaugeId, lcp.score || 0, lcp.status);
            this.initCoreWebVitalsGauge(fidGaugeId, fid.score || 0, fid.status);
            this.initCoreWebVitalsGauge(clsGaugeId, cls.score || 0, cls.status);
            
            // Update score badge in accordion header
            UIManager.updateScoreBadge('coreWebVitals', performanceScore);
            
            // Store the data for later use
            $('#coreWebVitalsContent').data('results', data);
        } catch (error) {
            console.error("Error displaying Core Web Vitals results:", error);
            $('#coreWebVitalsContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error displaying Core Web Vitals analysis: ${error.message}
                </div>
            `);
        }
    },
    
    /**
     * Display error message
     * 
     * @param {string} message Error message
     */
    displayError: function(message) {
        $('#coreWebVitalsContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i> ${message}
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Core Web Vitals Analysis Troubleshooting</h5>
                </div>
                <div class="card-body">
                    <p>The Core Web Vitals analysis could not be completed. Here are some possible reasons:</p>
                    <ul>
                        <li>The website may be blocking automated performance analysis</li>
                        <li>The PageSpeed Insights API may be unavailable or rate-limited</li>
                        <li>The site may be too large or complex for analysis</li>
                        <li>There may be network connectivity issues</li>
                    </ul>
                    <div class="mt-3">
                        <button id="rerunCoreWebVitalsBtn" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-1"></i> Try Again
                        </button>
                    </div>
                </div>
            </div>
        `);
    },
    
    /**
     * Initialize performance gauge chart
     * 
     * @param {string} canvasId Canvas element ID
     * @param {number} score Score value
     */
    initPerformanceGauge: function(canvasId, score) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 10;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw background circle
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        ctx.fillStyle = '#f0f0f0';
        ctx.fill();
        
        // Calculate angles for score arc (from -135° to +135°)
        const startAngle = -Math.PI * 0.75;
        const endAngle = startAngle + (score / 100) * Math.PI * 1.5;
        
        // Draw score arc
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.lineWidth = 15;
        ctx.strokeStyle = this.getScoreColor(score);
        ctx.stroke();
        
        // Draw gauge markings
        this.drawGaugeMarkings(ctx, centerX, centerY, radius, 5);
    },
    
    /**
     * Initialize Core Web Vitals gauge chart
     * 
     * @param {string} canvasId Canvas element ID
     * @param {number} score Score value
     * @param {string} status Status (good, needs-improvement, poor)
     */
    initCoreWebVitalsGauge: function(canvasId, score, status) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 5;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw background circle
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        ctx.fillStyle = '#f8f9fa';
        ctx.fill();
        
        // Calculate angles for score arc (from -135° to +135°)
        const startAngle = -Math.PI * 0.75;
        const endAngle = startAngle + (score / 100) * Math.PI * 1.5;
        
        // Draw score arc
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.lineWidth = 8;
        ctx.strokeStyle = this.getStatusColor(status);
        ctx.stroke();
        
        // Draw gauge markings
        this.drawGaugeMarkings(ctx, centerX, centerY, radius, 3);
    },
    
    /**
     * Draw gauge markings
     * 
     * @param {CanvasRenderingContext2D} ctx Canvas context
     * @param {number} centerX Center X coordinate
     * @param {number} centerY Center Y coordinate
     * @param {number} radius Gauge radius
     * @param {number} markingWidth Marking width
     */
    drawGaugeMarkings: function(ctx, centerX, centerY, radius, markingWidth) {
        // Draw gauge markings (0, 50, 100)
        const markingPositions = [0, 50, 100];
        
        markingPositions.forEach(pos => {
            // Calculate angle for this position
            const angle = -Math.PI * 0.75 + (pos / 100) * Math.PI * 1.5;
            
            // Calculate coordinates
            const innerX = centerX + (radius - 15) * Math.cos(angle);
            const innerY = centerY + (radius - 15) * Math.sin(angle);
            const outerX = centerX + radius * Math.cos(angle);
            const outerY = centerY + radius * Math.sin(angle);
            
            // Draw marking line
            ctx.beginPath();
            ctx.moveTo(innerX, innerY);
            ctx.lineTo(outerX, outerY);
            ctx.lineWidth = markingWidth;
            ctx.strokeStyle = "#aaa";
            ctx.stroke();
        });
    },
    
    /**
     * Get score label based on score value
     * 
     * @param {number} score Score value
     * @return {string} Score label
     */
    getScoreLabel: function(score) {
        if (score >= 90) {
            return '<span class="text-success">Excellent</span>';
        } else if (score >= 70) {
            return '<span class="text-primary">Good</span>';
        } else if (score >= 50) {
            return '<span class="text-warning">Needs Improvement</span>';
        } else {
            return '<span class="text-danger">Poor</span>';
        }
    },
    
    /**
     * Get score color based on score value
     * 
     * @param {number} score Score value
     * @return {string} CSS color value
     */
    getScoreColor: function(score) {
        if (score >= 90) {
            return '#198754'; // success
        } else if (score >= 70) {
            return '#0d6efd'; // primary
        } else if (score >= 50) {
            return '#ffc107'; // warning
        } else {
            return '#dc3545'; // danger
        }
    },
    
    /**
     * Get status color based on status
     * 
     * @param {string} status Status value
     * @return {string} CSS color value
     */
    getStatusColor: function(status) {
        switch (status) {
            case 'good':
                return '#198754'; // success
            case 'needs-improvement':
                return '#ffc107'; // warning
            case 'poor':
                return '#dc3545'; // danger
            default:
                return '#6c757d'; // secondary
        }
    },
    
    /**
     * Get CSS class for status badge
     * 
     * @param {string} status Status value
     * @return {string} Badge CSS class
     */
    getStatusBadgeClass: function(status) {
        switch (status) {
            case 'good':
                return 'bg-success';
            case 'needs-improvement':
                return 'bg-warning';
            case 'poor':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    },
    
    /**
     * Get CSS class for progress bar
     * 
     * @param {string} status Status value
     * @return {string} Progress bar CSS class
     */
    getProgressBarClass: function(status) {
        switch (status) {
            case 'good':
                return 'bg-success';
            case 'needs-improvement':
                return 'bg-warning';
            case 'poor':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    },
    
    /**
     * Format status for display
     * 
     * @param {string} status Status value
     * @return {string} Formatted status
     */
    formatStatus: function(status) {
        switch (status) {
            case 'good':
                return 'Good';
            case 'needs-improvement':
                return 'Needs Improvement';
            case 'poor':
                return 'Poor';
            default:
                return 'Unknown';
        }
    },
    
    /**
     * Capitalize first letter of a string
     * 
     * @param {string} string String to capitalize
     * @return {string} Capitalized string
     */
    capitalizeFirstLetter: function(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
};

// Initialize when document is ready
$(document).ready(function() {
    CoreWebVitals.initialize();
});

// Event listener for analysis manager integration
$(document).on('requestCoreWebVitalsAnalysis', function(event, url) {
    // Check if we have data already loaded
    const existingData = $('#coreWebVitalsContent').data('results');
    
    if (existingData) {
        // Display the data directly
        CoreWebVitals.displayResults(existingData);
    } else {
        // Request new data
        ApiService.runPerformanceAnalysis(url)
            .done(function(response) {
                if (response.success) {
                    // Display the results
                    CoreWebVitals.displayResults(response.data);
                    
                    // Store for later reference
                    $('#coreWebVitalsContent').data('results', response.data);
                } else {
                    // Display error
                    CoreWebVitals.displayError(response.message || 'Performance analysis failed');
                }
            })
            .fail(function() {
                // Display API error
                CoreWebVitals.displayError('Performance analysis failed due to a server error');
            });
    }
});