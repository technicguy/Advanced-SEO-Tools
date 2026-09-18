/**
 * content-gap.js - Handles display and interaction for content gap analysis
 */

const ContentGap = {
    // Store analysis data
    analysisData: null,
    
    /**
     * Display content gap analysis results
     * @param {Object} data Analysis data from API
     */
    displayResults: function(data) {
        // Store data for reference
        this.analysisData = data;
        $('#contentGapContent').data('results', data);
        
        // Update score in the accordion header
        UIManager.updateScoreBadge('contentGap', data.overall_score);
        
        // Create HTML for results
        let html = `
            <div class="content-gap-results">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Content Gap Score</h5>
                                <div class="gauge-container mb-3">
                                    <canvas id="contentGapGauge" width="150" height="150"></canvas>
                                    <div class="score-value">${data.overall_score}</div>
                                </div>
                                <p class="card-text ${Utils.getScoreClass(data.overall_score)}">${Utils.getScoreLabel(data.overall_score)}</p>
                                <div class="text-muted small">Opportunity Score: ${data.opportunity_score}/100</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Content Gap Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="content-gap-metrics mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="content-gap-metric mb-3">
                                                <h6>Missing Topics</h6>
                                                <div class="d-flex align-items-center">
                                                    <div class="h2 mb-0 me-2">${data.missing_topics.length}</div>
                                                    <div class="text-muted">topics identified</div>
                                                </div>
                                            </div>
                                            <div class="content-gap-metric mb-3">
                                                <h6>Keywords Gap</h6>
                                                <div class="d-flex align-items-center">
                                                    <div class="h2 mb-0 me-2">${data.keyword_gaps.length}</div>
                                                    <div class="text-muted">keywords missing</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="content-gap-metric mb-3">
                                                <h6>Content Length Gap</h6>
                                                <div class="d-flex align-items-center">
                                                    <div class="h2 mb-0 me-2">${data.content_length_comparison.length_gap_percentage > 0 ? 
                                                       Math.round(data.content_length_comparison.length_gap_percentage) + '%' : '0%'}</div>
                                                    <div class="text-muted">shorter than competitors</div>
                                                </div>
                                            </div>
                                            <div class="content-gap-metric mb-3">
                                                <h6>Competitors Analyzed</h6>
                                                <div class="d-flex align-items-center">
                                                    <div class="h2 mb-0 me-2">${Object.keys(data.competitor_data || {}).length}</div>
                                                    <div class="text-muted">competitors</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        `;
        
        // Add content length comparison chart if data exists
        if (data.content_length_comparison) {
            html += `
                <div>
                    <h6>Content Length Comparison</h6>
                    <div class="chart-container" style="height: 200px">
                        <canvas id="contentLengthComparisonChart"></canvas>
                    </div>
                </div>
            `;
        }
        
        html += `
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Missing Topics Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-puzzle-piece text-primary me-2"></i>
                            Missing Topics 
                            <span class="badge bg-primary">${data.missing_topics.length}</span>
                        </h5>
                    </div>
                    <div class="card-body">
        `;
        
        if (data.missing_topics.length === 0) {
            html += `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Great job! Your website covers most topics that your competitors do.
                </div>
            `;
        } else {
            html += `
                <p class="text-muted mb-3">
                    These topics are covered by your competitors but missing from your website.
                    The opportunity score indicates the potential value of creating content for each topic.
                </p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Topic</th>
                                <th>Competitors Covering</th>
                                <th>Opportunity Score</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.missing_topics.forEach(topic => {
                const opportunityClass = topic.opportunity_score >= 80 ? 'text-success' : 
                                      topic.opportunity_score >= 60 ? 'text-primary' : 'text-muted';
                
                html += `
                    <tr>
                        <td><strong>${topic.topic}</strong></td>
                        <td>${topic.competitors_covering.length}</td>
                        <td class="${opportunityClass}"><strong>${topic.opportunity_score}/100</strong></td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        html += `
                    </div>
                </div>
                
                <!-- Keyword Gaps Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-key text-warning me-2"></i>
                            Keyword Gaps
                            <span class="badge bg-warning text-dark">${data.keyword_gaps.length}</span>
                        </h5>
                    </div>
                    <div class="card-body">
        `;
        
        if (data.keyword_gaps.length === 0) {
            html += `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    No significant keyword gaps found.
                </div>
            `;
        } else {
            html += `
                <p class="text-muted mb-3">
                    These are keywords that your competitors are ranking for, but your website is not targeting.
                </p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Keyword</th>
                                <th>Search Volume</th>
                                <th>Competition</th>
                                <th>Best Competitor Rank</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.keyword_gaps.forEach(keyword => {
                // Determine competition level class
                const competitionClass = keyword.competition < 0.3 ? 'text-success' : 
                                      keyword.competition < 0.7 ? 'text-warning' : 'text-danger';
                
                // Determine volume level class
                const volumeClass = keyword.search_volume > 1000 ? 'text-success' : 
                                 keyword.search_volume > 300 ? 'text-primary' : 'text-muted';
                
                html += `
                    <tr>
                        <td><strong>${keyword.keyword}</strong></td>
                        <td class="${volumeClass}">${keyword.search_volume}</td>
                        <td class="${competitionClass}">${(keyword.competition * 10).toFixed(1)}/10</td>
                        <td>${keyword.best_competitor_rank}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        html += `
                    </div>
                </div>
                
                <!-- Recommendations Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lightbulb text-primary me-2"></i>
                            Recommendations
                            <span class="badge bg-primary">${data.recommendations.length}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="content-gap-recommendations">
        `;
        
        if (data.recommendations.length === 0) {
            html += `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No content gap recommendations at this time.
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
                                <p class="text-muted small mb-0">${rec.implementation}</p>
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
                
                <!-- Topic Distribution Chart -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
					<h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie text-info me-2"></i>
                    Content Topic Distribution
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container" style="height: 300px">
                            <canvas id="topicDistributionChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="topics-legend">
                            <h6 class="mb-3">Topics Coverage</h6>
                            <div class="topics-covered mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Your Website</span>
                                    <strong>${data.topics_covered.length} topics</strong>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: ${(data.topics_covered.length / (data.topics_covered.length + data.missing_topics.length) * 100)}%" 
                                         aria-valuenow="${data.topics_covered.length}" aria-valuemin="0" 
                                         aria-valuemax="${data.topics_covered.length + data.missing_topics.length}"></div>
                                </div>
                            </div>
                            <div class="topics-missing mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Missing Topics</span>
                                    <strong>${data.missing_topics.length} topics</strong>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-danger" role="progressbar" 
                                         style="width: ${(data.missing_topics.length / (data.topics_covered.length + data.missing_topics.length) * 100)}%" 
                                         aria-valuenow="${data.missing_topics.length}" aria-valuemin="0" 
                                         aria-valuemax="${data.topics_covered.length + data.missing_topics.length}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        `;
        
        // Update the content
        $('#contentGapContent').html(html);
        
        // Initialize gauge chart
        this.initGaugeChart('contentGapGauge', data.overall_score);
        
        // Initialize content length comparison chart
        if (data.content_length_comparison) {
            this.initContentLengthChart(data.content_length_comparison);
        }
        
        // Initialize topic distribution chart
        this.initTopicDistributionChart(data);
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
    },
    
    /**
     * Initialize content length comparison chart
     * @param {Object} contentLengthData Content length comparison data
     */
    initContentLengthChart: function(contentLengthData) {
        const canvas = document.getElementById('contentLengthComparisonChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Prepare data for the chart
        const labels = ['Your Website'];
        const data = [contentLengthData.site];
        const backgroundColors = ['rgba(54, 162, 235, 0.7)'];
        
        // Add competitor data
        let competitorIndex = 1;
        for (const [competitorUrl, length] of Object.entries(contentLengthData.competitors)) {
            labels.push(`Competitor ${competitorIndex}`);
            data.push(length);
            backgroundColors.push('rgba(255, 99, 132, 0.7)');
            competitorIndex++;
        }
        
        // Create the chart
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Content Length (words)',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(color => color.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Words'
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
                                return context.dataset.label + ': ' + context.formattedValue + ' words';
                            }
                        }
                    }
                }
            }
        });
    },
    
    /**
     * Initialize topic distribution chart
     * @param {Object} data Content gap analysis data
     */
    initTopicDistributionChart: function(data) {
        const canvas = document.getElementById('topicDistributionChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Prepare data for the chart
        const chartData = {
            labels: ['Covered Topics', 'Missing Topics'],
            datasets: [{
                data: [data.topics_covered.length, data.missing_topics.length],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',  // Green for covered topics
                    'rgba(220, 53, 69, 0.7)'   // Red for missing topics
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        };
        
        // Create the chart
        new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
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
                                const label = context.label || '';
                                const value = context.formattedValue || '';
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((context.raw / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
};