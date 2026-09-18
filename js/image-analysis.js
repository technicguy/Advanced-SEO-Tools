/**
 * image-analysis.js - Image analysis display and visualization
 */

const ImageAnalysis = {
    /**
     * Initialize the image analysis module
     */
    initialize: function() {
        console.log('Image Analysis module initialized');
        
        // Bind event handlers
        $(document).on('imageAnalysisCompleted', this.handleAnalysisCompleted);
    },
    
    /**
     * Handle image analysis completion event
     * @param {Event} event The event object
     * @param {Object} data The analysis data
     */
    handleAnalysisCompleted: function(event, data) {
        if (data && data.url) {
            ImageAnalysis.displayResults(data);
        }
    },
    
    /**
     * Display image analysis results
     * @param {Object} data Analysis results data
     */
    displayResults: function(data) {
    // Handle null or undefined data
    if (!data) {
        $('#imageAnalysisContent').html(`
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No image analysis data available.
            </div>
        `);
        return;
    }
    
    try {		
		
        // Store results in the content area for later reference
        $('#imageAnalysisContent').data('results', data);
        
        // Update score badge
        UIManager.updateScoreBadge('imageAnalysis', data.overall_score);
        
        // Generate the content HTML
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Image Optimization Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-center mb-4">
                                <div class="score-gauge">
                                    <canvas id="imageScoreGauge" width="150" height="150"></canvas>
                                    <div class="score-value">${data.overall_score}</div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <p><strong>Total Images Analyzed:</strong> ${data.total_images}</p>
                                <p><strong>Pages Analyzed:</strong> ${data.pages_analyzed}</p>
                                
                                ${this.renderSummaryStats(data)}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Score Breakdown</h5>
                        </div>
                        <div class="card-body">
                            <div class="score-breakdown-chart-container">
                                <canvas id="imageScoreBreakdownChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Image Format Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="format-distribution-chart-container">
                                <canvas id="imageFormatChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recommendations</h5>
                        </div>
                        <div class="card-body">
                            ${this.renderRecommendations(data.recommendations)}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Image Details</h5>
                            <button class="btn btn-sm btn-outline-primary" id="toggleImageDetails">Show Details</button>
                        </div>
                        <div class="card-body">
                            <div id="imageDetailsContainer" class="d-none">
                                ${this.renderImageDetails(data.image_details)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Update the content area
        $('#imageAnalysisContent').html(content);
        
        // Initialize the gauge chart
        this.initGaugeChart(data.overall_score);
        
        // Initialize score breakdown chart
        this.initScoreBreakdownChart(data.score_components);
        
        // Initialize format distribution chart
        this.initFormatDistributionChart(data.image_details);
        
        // Setup toggle for image details
        $('#toggleImageDetails').on('click', function() {
            const $container = $('#imageDetailsContainer');
            const $button = $(this);
            
            if ($container.hasClass('d-none')) {
                $container.removeClass('d-none');
                $button.text('Hide Details');
            } else {
                $container.addClass('d-none');
                $button.text('Show Details');
            }
        });
    } catch (error) {
        console.error("Error displaying image analysis results:", error);
        $('#imageAnalysisContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Error displaying image analysis: ${error.message}
            </div>
        `);
    }
},
    
    /**
     * Render summary statistics
     * @param {Object} data Analysis data
     * @return {string} HTML for summary stats
     */
    renderSummaryStats: function(data) {
        return `
            <div class="progress-stats">
                <div class="stat-item">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Images with Alt Text</span>
                        <span>${data.summary.images_with_alt_percent}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar ${this.getProgressBarClass(data.summary.images_with_alt_percent)}" 
                             style="width: ${data.summary.images_with_alt_percent}%" 
                             role="progressbar"></div>
                    </div>
                </div>
                
                <div class="stat-item mt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Images with Dimensions</span>
                        <span>${data.summary.images_with_dimensions_percent}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar ${this.getProgressBarClass(data.summary.images_with_dimensions_percent)}" 
                             style="width: ${data.summary.images_with_dimensions_percent}%" 
                             role="progressbar"></div>
                    </div>
                </div>
                
                <div class="stat-item mt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Lazy Loaded Images</span>
                        <span>${data.summary.lazy_loaded_images_percent}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar ${this.getProgressBarClass(data.summary.lazy_loaded_images_percent)}" 
                             style="width: ${data.summary.lazy_loaded_images_percent}%" 
                             role="progressbar"></div>
                    </div>
                </div>
                
                <div class="stat-item mt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Responsive Images</span>
                        <span>${data.summary.responsive_images_percent}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar ${this.getProgressBarClass(data.summary.responsive_images_percent)}" 
                             style="width: ${data.summary.responsive_images_percent}%" 
                             role="progressbar"></div>
                    </div>
                </div>
                
                <div class="stat-item mt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Optimally Sized Images</span>
                        <span>${100 - data.summary.oversized_images_percent}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar ${this.getProgressBarClass(100 - data.summary.oversized_images_percent)}" 
                             style="width: ${100 - data.summary.oversized_images_percent}%" 
                             role="progressbar"></div>
                    </div>
                </div>
                
                <div class="stat-item mt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Modern Format Images</span>
                        <span>${data.summary.modern_format_images_percent}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar ${this.getProgressBarClass(data.summary.modern_format_images_percent)}" 
                             style="width: ${data.summary.modern_format_images_percent}%" 
                             role="progressbar"></div>
                    </div>
                </div>
            </div>
        `;
    },
    
    /**
     * Get appropriate progress bar class based on value
     * @param {number} value Percentage value
     * @return {string} Bootstrap class
     */
    getProgressBarClass: function(value) {
        if (value >= 80) return 'bg-success';
        if (value >= 50) return 'bg-info';
        if (value >= 30) return 'bg-warning';
        return 'bg-danger';
    },
    
    /**
     * Render recommendations
     * @param {Array} recommendations List of recommendations
     * @return {string} HTML for recommendations
     */
    renderRecommendations: function(recommendations) {
        if (!recommendations || recommendations.length === 0) {
            return '<p class="text-muted">No recommendations available.</p>';
        }
        
        // Sort recommendations by priority
        const sortedRecommendations = [...recommendations].sort((a, b) => {
            const priorityOrder = { 'high': 0, 'medium': 1, 'low': 2 };
            return priorityOrder[a.priority] - priorityOrder[b.priority];
        });
        
        let html = '<div class="recommendations-list">';
        
        sortedRecommendations.forEach(rec => {
            let priorityClass = 'text-info';
            let priorityIcon = 'fa-info-circle';
            
            if (rec.priority === 'high') {
                priorityClass = 'text-danger';
                priorityIcon = 'fa-exclamation-circle';
            } else if (rec.priority === 'medium') {
                priorityClass = 'text-warning';
                priorityIcon = 'fa-exclamation-triangle';
            }
            
            html += `
                <div class="recommendation-item">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas ${priorityIcon} ${priorityClass} fa-lg"></i>
                        </div>
                        <div>
                            <p class="mb-1">${rec.description}</p>
                            <small class="text-muted">Priority: <span class="${priorityClass}">${rec.priority}</span></small>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    },
  /**
 * Render image details
 * @param {Array} imageDetails Image details by page
 * @return {string} HTML for image details
 */
renderImageDetails: function(imageDetails) {
    if (!imageDetails || imageDetails.length === 0) {
        return '<p class="text-muted">No image details available.</p>';
    }
    
    let html = '<div class="image-details-container">';
    
    imageDetails.forEach((pageData, pageIndex) => {
        const pageUrl = pageData.url;
        const images = pageData.images;
        
        if (!images || images.length === 0) {
            return;
        }
        
        const shortUrl = this.truncateUrl(pageUrl);
        
        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i> ${shortUrl} (${images.length} images)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Alt Text</th>
                                    <th>Size</th>
                                    <th>Dimensions</th>
                                    <th>Format</th>
                                    <th>Optimization</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${this.renderImageRows(images)}
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
     * Render image table rows
     * @param {Array} images List of images
     * @return {string} HTML for table rows
     */
    renderImageRows: function(images) {
        let html = '';
        
        images.forEach(image => {
            const altText = image.has_alt ? (image.alt_text || '(empty)') : '<span class="text-danger">Missing</span>';
            const size = this.formatFileSize(image.size);
            const dimensions = image.width && image.height ? `${image.width} × ${image.height}` : 'Unknown';
            const format = image.format ? image.format.toUpperCase() : 'Unknown';
            
            // Generate optimization badges
            let optimizationHtml = '';
            
            if (image.has_alt) {
                optimizationHtml += '<span class="badge bg-success me-1 mb-1">Alt Text</span>';
            }
            
            if (image.has_dimensions) {
                optimizationHtml += '<span class="badge bg-success me-1 mb-1">Dimensions</span>';
            }
            
            if (image.uses_lazy_loading) {
                optimizationHtml += '<span class="badge bg-success me-1 mb-1">Lazy Load</span>';
            }
            
            if (image.is_responsive) {
                optimizationHtml += '<span class="badge bg-success me-1 mb-1">Responsive</span>';
            }
            
            if (!image.is_oversized) {
                optimizationHtml += '<span class="badge bg-success me-1 mb-1">Optimal Size</span>';
            } else {
                optimizationHtml += '<span class="badge bg-danger me-1 mb-1">Oversized</span>';
            }
            
            if (image.is_modern_format) {
                optimizationHtml += '<span class="badge bg-success me-1 mb-1">Modern Format</span>';
            }
            
            html += `
                <tr>
                    <td>
                        <div class="image-thumbnail">
                            <img src="${image.src}" alt="Thumbnail" class="img-thumbnail" style="max-width: 100px; max-height: 60px;">
                            <div class="image-path text-truncate" style="max-width: 100px;" title="${image.src}">
                                ${this.truncateUrl(image.src)}
                            </div>
                        </div>
                    </td>
                    <td>${altText}</td>
                    <td>${size}</td>
                    <td>${dimensions}</td>
                    <td>${format}</td>
                    <td>${optimizationHtml}</td>
                </tr>
            `;
        });
        
        return html;
    },
    
    /**
     * Format file size in a human-readable format
     * @param {number} bytes Size in bytes
     * @return {string} Formatted size
     */
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 B';
        
        const units = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        
        return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + units[i];
    },
    
    /**
     * Truncate URL for display
     * @param {string} url URL to truncate
     * @return {string} Truncated URL
     */
    truncateUrl: function(url) {
        if (!url) return '';
        
        // Remove protocol
        let cleanUrl = url.replace(/^https?:\/\//, '');
        
        // Truncate if too long
        if (cleanUrl.length > 30) {
            return cleanUrl.substring(0, 27) + '...';
        }
        
        return cleanUrl;
    },
    
    /**
     * Initialize the gauge chart
     * @param {number} score Overall score
     */
    initGaugeChart: function(score) {
        const canvas = document.getElementById('imageScoreGauge');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = canvas.width / 2 - 10;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw background circle
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        ctx.fillStyle = '#f0f0f0';
        ctx.fill();
        
        // Draw score arc
        const startAngle = Math.PI;
        const endAngle = startAngle + (score / 100) * Math.PI;
        
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.lineWidth = 20;
        ctx.strokeStyle = this.getScoreGradient(ctx, score, centerX, centerY, radius);
        ctx.stroke();
    },
    
    /**
     * Get gradient for score gauge
     * @param {CanvasRenderingContext2D} ctx Canvas context
     * @param {number} score Score value
     * @param {number} centerX Center X coordinate
     * @param {number} centerY Center Y coordinate
     * @param {number} radius Circle radius
     * @return {CanvasGradient} Gradient
     */
    getScoreGradient: function(ctx, score, centerX, centerY, radius) {
        let gradient = ctx.createLinearGradient(0, 0, 0, centerY * 2);
        
        if (score < 50) {
            gradient.addColorStop(0, '#dc3545'); // Red
            gradient.addColorStop(1, '#ffc107'); // Yellow
        } else {
            gradient.addColorStop(0, '#ffc107'); // Yellow
            gradient.addColorStop(1, '#28a745'); // Green
        }
        
        return gradient;
    },
    
    /**
     * Initialize score breakdown chart
     * @param {Object} scoreComponents Score components
     */
/**
 * Initialize score breakdown chart
 * @param {Object} scoreComponents Score components
 */
initScoreBreakdownChart: function(scoreComponents) {
    const canvas = document.getElementById('imageScoreBreakdownChart');
    if (!canvas || !scoreComponents) return;
    
    // Prepare data
    const labels = [
        'Alt Text',
        'Dimensions',
        'Lazy Loading',
        'Responsive',
        'Size Optimization',
        'Modern Format'
    ];
    
    const data = [
        scoreComponents.alt_text_score || 0,
        scoreComponents.dimensions_score || 0,
        scoreComponents.lazy_loading_score || 0,
        scoreComponents.responsive_image_score || 0,
        scoreComponents.optimization_score || 0,
        scoreComponents.modern_format_score || 0
    ];
    
    // Get colors based on scores
    const colors = data.map(score => {
        if (score >= 80) return '#28a745';
        if (score >= 50) return '#17a2b8';
        if (score >= 30) return '#ffc107';
        return '#dc3545';
    });
    
    // Check if chart instance exists before destroying
    if (window.imageScoreBreakdownChart instanceof Chart) {
        window.imageScoreBreakdownChart.destroy();
    }
    
    // Create chart
    window.imageScoreBreakdownChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Component Score',
                data: data,
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw + '%';
                        }
                    }
                }
            }
        }
    });
},
    
    /**
     * Initialize format distribution chart
     * @param {Array} imageDetails Image details by page
     */
    initFormatDistributionChart: function(imageDetails) {
        const canvas = document.getElementById('imageFormatChart');
        if (!canvas || !imageDetails) return;
        
        // Count formats
        const formatCounts = {};
        let totalImages = 0;
        
        imageDetails.forEach(page => {
            page.images.forEach(image => {
                const format = image.format ? image.format.toUpperCase() : 'Unknown';
                formatCounts[format] = (formatCounts[format] || 0) + 1;
                totalImages++;
            });
        });
        
        // Prepare data
        const labels = Object.keys(formatCounts);
        const data = Object.values(formatCounts);
        
        // Define colors for common formats
        const colorMap = {
            'JPEG': '#E95420',
            'JPG': '#E95420',
            'PNG': '#3498DB',
            'GIF': '#2ECC71',
            'WEBP': '#9B59B6',
            'AVIF': '#F1C40F',
            'SVG': '#1ABC9C',
            'Unknown': '#95A5A6'
        };
        
        const colors = labels.map(format => colorMap[format] || '#34495E');
        
        // Create chart
		// Check if chart instance exists before destroying
		if (window.imageFormatChart instanceof Chart) {
			window.imageFormatChart.destroy();
		}
        
        window.imageFormatChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const percentage = Math.round((value / totalImages) * 100);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
};

// Initialize module when document is ready
$(document).ready(function() {
    ImageAnalysis.initialize();
});