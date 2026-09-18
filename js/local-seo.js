/**
 * local-seo.js - Handles display and interaction for local SEO analysis
 */

const LocalSEO = {
    // Store analysis data
    analysisData: null,
    
    /**
     * Display local SEO analysis results
     * @param {Object} data Analysis data from API
     */
    displayResults: function(data) {
        // Store data for reference
        this.analysisData = data;
        $('#localSeoContent').data('results', data);
        
        // Update score in the accordion header
        UIManager.updateScoreBadge('localSeo', data.overall_score);
        
        // Check if we need to show a warning about fallback data
        let warningMessage = '';
        if (data.google_business_data && data.google_business_data.fallback) {
            warningMessage = `
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> ${data.google_business_data.warning}
                </div>
            `;
        }
        
        // Create HTML for results
        let html = `
            ${warningMessage}
            <div class="local-seo-results">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Local SEO Score</h5>
                                <div class="local-seo-gauge mb-3">
                                    <canvas id="localSeoScoreGauge" width="150" height="150"></canvas>
                                    <div class="score-value">${data.overall_score}</div>
                                </div>
                                <p class="card-text ${Utils.getScoreClass(data.overall_score)}">${Utils.getScoreLabel(data.overall_score)}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Local SEO Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="local-seo-checklist mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="local-seo-check-item mb-2 ${data.has_google_business_profile ? 'text-success' : 'text-danger'}">
                                                <i class="fas fa-${data.has_google_business_profile ? 'check-circle' : 'times-circle'}"></i>
                                                Google Business Profile
                                            </div>
                                            <div class="local-seo-check-item mb-2 ${data.has_local_business_schema ? 'text-success' : 'text-danger'}">
                                                <i class="fas fa-${data.has_local_business_schema ? 'check-circle' : 'times-circle'}"></i>
                                                Local Business Schema
                                            </div>
                                            <div class="local-seo-check-item mb-2 ${data.has_location_pages ? 'text-success' : 'text-danger'}">
                                                <i class="fas fa-${data.has_location_pages ? 'check-circle' : 'times-circle'}"></i>
                                                Location Pages
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="local-seo-check-item mb-2 ${data.has_nap_consistency ? 'text-success' : 'text-danger'}">
                                                <i class="fas fa-${data.has_nap_consistency ? 'check-circle' : 'times-circle'}"></i>
                                                NAP Consistency
                                            </div>
                                            <div class="local-seo-check-item mb-2 ${data.has_local_keywords ? 'text-success' : 'text-danger'}">
                                                <i class="fas fa-${data.has_local_keywords ? 'check-circle' : 'times-circle'}"></i>
                                                Local Keywords
                                            </div>
                                            <div class="local-seo-check-item mb-2 ${data.has_embedded_map ? 'text-success' : 'text-danger'}">
                                                <i class="fas fa-${data.has_embedded_map ? 'check-circle' : 'times-circle'}"></i>
                                                Embedded Map
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Citation Score -->
                                <div class="mt-3">
                                    <h6>Local Citation Score</h6>
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar ${Utils.getScoreColorClass(data.citation_score || 0)}" 
                                            role="progressbar" style="width: ${data.citation_score || 0}%" 
                                            aria-valuenow="${data.citation_score || 0}" aria-valuemin="0" aria-valuemax="100">
                                            ${data.citation_score || 0}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Issues and Recommendations Section -->
                <div class="row mb-4">
                    <!-- Issues -->
                    <div class="col-md-6 mb-4 mb-md-0">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    Local SEO Issues 
                                    <span class="badge bg-warning text-dark">${data.issues ? data.issues.length : 0}</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="local-seo-issues">
        `;
        
        if (!data.issues || data.issues.length === 0) {
            html += `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    No local SEO issues detected!
                </div>
            `;
        } else {
            html += `<ul class="list-group list-group-flush">`;
            data.issues.forEach(issue => {
                const severityClass = issue.severity === 'high' ? 'text-danger' : 
                                     issue.severity === 'medium' ? 'text-warning' : 'text-info';
                
                html += `
                    <li class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-2">
                                <i class="fas fa-exclamation-circle ${severityClass} fa-lg"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>${issue.description}</strong>
                                ${issue.details ? `<p class="mb-0 text-muted small">${issue.details}</p>` : ''}
                            </div>
                        </div>
                    </li>
                `;
            });
            html += `</ul>`;
        }
        
        html += `
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recommendations -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-lightbulb text-primary me-2"></i>
                                    Recommendations
                                    <span class="badge bg-primary">${data.recommendations ? data.recommendations.length : 0}</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="local-seo-recommendations">
        `;
        
        if (!data.recommendations || data.recommendations.length === 0) {
            html += `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No additional recommendations at this time.
                </div>
            `;
        } else {
            data.recommendations.forEach(rec => {
                const importanceClass = rec.importance === 'high' ? 'border-danger' : 
                                     rec.importance === 'medium' ? 'border-warning' : 'border-info';
                
                html += `
                    <div class="card mb-3 ${importanceClass}">
                        <div class="card-body">
                            <h6 class="card-title">${rec.title}</h6>
                            <p class="card-text">${rec.description}</p>
                            <div class="implementation-guide mt-2">
                                <h6 class="text-muted"><i class="fas fa-tools me-1"></i> Implementation:</h6>
                                <p class="text-muted small mb-0">${rec.implementation.replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        html += `
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        `;
        
        // Google Business Profile Section (if exists)
        if (data.has_google_business_profile && data.google_business_data) {
            const gbp = data.google_business_data;
            
            html += `
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fab fa-google text-primary me-2"></i>
                            Google Business Profile
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong>Business Name:</strong> ${gbp.name || 'N/A'}
                                </div>
                                <div class="mb-3">
                                    <strong>Address:</strong> ${gbp.address || 'N/A'}
                                </div>
                                <div class="mb-3">
                                    <strong>Phone:</strong> ${gbp.phone || 'N/A'}
                                </div>
                                <div class="mb-3">
                                    <strong>Categories:</strong> ${gbp.categories && gbp.categories.length > 0 ? gbp.categories.join(', ') : 'N/A'}
                                </div>
                                <div class="mb-3">
                                    <strong>Verification Status:</strong>
                                    <span class="${gbp.verified ? 'text-success' : 'text-danger'}">
                                        <i class="fas fa-${gbp.verified ? 'check-circle' : 'times-circle'} me-1"></i>
                                        ${gbp.verified ? 'Verified' : 'Not Verified'}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6>Rating & Reviews</h6>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="rating-stars me-2">
                                            ${this.generateStarRating(gbp.average_rating)}
                                        </div>
                                        <div>${gbp.average_rating || 0} (${gbp.total_reviews || 0} reviews)</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h6>Business Hours</h6>
                                    <table class="table table-sm">
                                        <tbody>
            `;
            
            if (gbp.business_hours && Object.keys(gbp.business_hours).length > 0) {
                const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                days.forEach(day => {
                    html += `
                        <tr>
                            <td>${day.charAt(0).toUpperCase() + day.slice(1)}</td>
                            <td>${gbp.business_hours[day] || 'Closed'}</td>
                        </tr>
                    `;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="2" class="text-center text-muted">Business hours not available</td>
                    </tr>
                `;
            }
            
            html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Local Citations Section
        if (data.local_citations && data.local_citations.length > 0) {
            html += `
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-external-link-alt me-2"></i>
                            Local Citations
                            <span class="badge bg-secondary">${data.local_citations.length}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Directory</th>
                                        <th>Status</th>
                                        <th>Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            data.local_citations.forEach(citation => {
                html += `
                    <tr>
                        <td>${citation.source}</td>
                        <td><span class="badge ${citation.status === 'active' ? 'bg-success' : 'bg-warning'}">${citation.status}</span></td>
                        <td>${this.generateStarRating(citation.rating)}</td>
                    </tr>
                `;
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // NAP Consistency Section (if issues exist)
        if (data.has_nap_consistency === false && data.nap_data && data.nap_data.inconsistencies) {
            html += `
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            NAP Inconsistencies
                            <span class="badge bg-warning text-dark">${data.nap_data.inconsistencies.length}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th>Field</th>
                                        <th>Expected</th>
                                        <th>Found</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            data.nap_data.inconsistencies.forEach(inconsistency => {
                html += `
                    <tr>
                        <td>${inconsistency.source}</td>
                        <td>${inconsistency.field}</td>
                        <td>${inconsistency.expected}</td>
                        <td class="text-danger">${inconsistency.found}</td>
                    </tr>
                `;
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Local Keywords Section (if found)
        if (data.has_local_keywords && data.local_keywords_data && data.local_keywords_data.keywords_found && data.local_keywords_data.keywords_found.length > 0) {
            html += `
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tag text-info me-2"></i>
                            Local Keywords Found
                            <span class="badge bg-info">${data.local_keywords_data.keywords_found.length}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Keyword</th>
                                        <th>Occurrences</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            data.local_keywords_data.keywords_found.forEach(keyword => {
                html += `
                    <tr>
                        <td>${keyword.keyword}</td>
                        <td>${keyword.count}</td>
                    </tr>
                `;
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // End of HTML
        html += `</div>`;
        
        // Update the content
        $('#localSeoContent').html(html);
        
        // Initialize gauge chart
        this.initGaugeChart('localSeoScoreGauge', data.overall_score);
    },
    
    /**
     * Generate HTML for star rating
     * @param {number} rating Rating out of 5
     * @return {string} HTML for star rating
     */
    generateStarRating: function(rating) {
        if (!rating) rating = 0;
        
        let html = '';
        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
        
        // Full stars
        for (let i = 0; i < fullStars; i++) {
            html += '<i class="fas fa-star text-warning"></i>';
        }
        
        // Half star if needed
        if (halfStar) {
            html += '<i class="fas fa-star-half-alt text-warning"></i>';
        }
        
        // Empty stars
        for (let i = 0; i < emptyStars; i++) {
            html += '<i class="far fa-star text-warning"></i>';
        }
        
        return html;
    },
    
    /**
     * Initialize gauge chart for score visualization
     * @param {string} canvasId Canvas element ID
     * @param {number} score Score to display (0-100)
     */
    initGaugeChart: function(canvasId, score) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Settings
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 10;
        
        // Draw background arc
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, Math.PI, 2 * Math.PI, false);
        ctx.lineWidth = 15;
        ctx.strokeStyle = '#f0f0f0';
        ctx.stroke();
        
        // Draw score arc
        const startAngle = Math.PI;
        const endAngle = startAngle + (score / 100) * Math.PI;
        
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle, false);
        ctx.lineWidth = 15;
        
        // Create gradient based on score
        let gradient;
        if (score < 50) {
            gradient = ctx.createLinearGradient(0, 0, canvas.width, 0);
            gradient.addColorStop(0, "#dc3545");
            gradient.addColorStop(1, "#ffc107");
        } else {
            gradient = ctx.createLinearGradient(0, 0, canvas.width, 0);
            gradient.addColorStop(0, "#28a745");
            gradient.addColorStop(1, "#20c997");
        }
        
        ctx.strokeStyle = gradient;
        ctx.stroke();
        
        // Draw needle
        const needleAngle = startAngle + (score / 100) * Math.PI;
        const needleLength = radius - 20;
        
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.lineTo(
            centerX + needleLength * Math.cos(needleAngle),
            centerY + needleLength * Math.sin(needleAngle)
        );
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#333';
        ctx.stroke();
        
        // Draw needle center
        ctx.beginPath();
        ctx.arc(centerX, centerY, 5, 0, 2 * Math.PI, false);
        ctx.fillStyle = '#333';
        ctx.fill();
    }
};