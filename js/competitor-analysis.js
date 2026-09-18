/**
 * competitor-analysis.js - Frontend display logic for competitive analysis
 * File path: js/competitor-analysis.js
 */

const CompetitorAnalysis = {
	 charts: {},
	
    /**
     * Initialize the Competitive Analysis module
     */
    initialize: function() {
        console.log('Competitive Analysis module initialized');
        
        // Set up event listeners
        this.setupEventListeners();
    },
    
    /**
     * Set up event listeners
     */
     setupEventListeners: function() {
        // Listen for when competitive analysis is requested
        $(document).on('requestCompetitorAnalysis', (event, url) => {
            this.performCompetitorAnalysis(url);
        });
        
        // Listen for re-analysis button clicks
        $(document).on('click', '#rerunCompetitorBtn', function() {
            const url = $('#websiteUrl').val();
            if (url) {
                CompetitorAnalysis.performCompetitorAnalysis(url);
            }
        });
        
        // Listen for add competitor button clicks
        $(document).on('click', '#addCompetitorBtn', function() {
            const competitorUrl = $('#competitorUrl').val().trim();
            if (competitorUrl) {
                CompetitorAnalysis.addCompetitor(competitorUrl);
                $('#competitorUrl').val('');
            }
        });
        
        // Listen for remove competitor button clicks
        $(document).on('click', '.remove-competitor', function() {
            const index = $(this).data('index');
            CompetitorAnalysis.removeCompetitor(index);
        });
        
        // Listen for run analysis button clicks
        $(document).on('click', '#runCompetitorAnalysisBtn', function() {
            const url = $('#websiteUrl').val();
            if (url) {
                CompetitorAnalysis.performCompetitorAnalysis(url, CompetitorAnalysis.competitors);
            }
        });
        
        // Clean up charts when navigating away or closing the page
        $(window).on('unload', function() {
            CompetitorAnalysis.cleanupCharts();
        });
        
        // Also clean up charts before starting a new analysis
        $(document).on('beforeAnalysisStart', function() {
            CompetitorAnalysis.cleanupCharts();
        });
    },
    
    // Store competitors
    competitors: [],

    
    /**
     * Clean up all chart instances
     */
    cleanupCharts: function() {
        // Destroy all charts in the charts object
        Object.values(this.charts).forEach(chart => {
            if (chart) {
                chart.destroy();
            }
        });
        
        // Reset charts object
        this.charts = {};
    },
    
    /**
     * Initialize comparison chart
     * 
     * @param {Object} comparativeAnalysis Comparative analysis data
     */
    initComparisonChart: function(comparativeAnalysis) {
        const ctx = document.getElementById('competitorComparisonChart');
        if (!ctx) return;
        
        // Destroy existing chart if it exists
        if (this.charts.comparison) {
            this.charts.comparison.destroy();
        }
        
        // Check if we have metrics comparison data
        if (!comparativeAnalysis || !comparativeAnalysis.metrics_comparison) return;
        
        const metrics = comparativeAnalysis.metrics_comparison;
        
        // Prepare data for chart
        const categories = ['Content Length', 'Backlinks', 'Domain Authority'];
        const yourSiteData = [
            metrics.word_count ? metrics.word_count.your_site : 0,
            metrics.backlinks ? metrics.backlinks.your_site : 0, 
            metrics.domain_authority ? metrics.domain_authority.your_site : 0
        ];
        const competitorData = [
            metrics.word_count ? metrics.word_count.competitor_avg : 0,
            metrics.backlinks ? metrics.backlinks.competitor_avg : 0,
            metrics.domain_authority ? metrics.domain_authority.competitor_avg : 0
        ];
        
        // Scale the data for better visualization
        // For content length, divide by 100
        // For backlinks, use logarithmic scale
        const scaledYourSiteData = [
            yourSiteData[0] / 100,
            yourSiteData[1] > 0 ? Math.log10(yourSiteData[1]) * 10 : 0,
            yourSiteData[2]
        ];
        
        const scaledCompetitorData = [
            competitorData[0] / 100,
            competitorData[1] > 0 ? Math.log10(competitorData[1]) * 10 : 0,
            competitorData[2]
        ];
        
        // Create chart and store the reference
        this.charts.comparison = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: categories,
                datasets: [
                    {
                        label: 'Your Site',
                        data: scaledYourSiteData,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 0.8)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(54, 162, 235, 0.8)',
                        pointRadius: 4
                    },
                    {
                        label: 'Competitor Average',
                        data: scaledCompetitorData,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 0.8)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(255, 99, 132, 0.8)',
                        pointRadius: 4
                    }
                ]
            },
            options: {
                scales: {
                    r: {
                        ticks: {
                            display: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const datasetLabel = context.dataset.label || '';
                                const value = context.raw;
                                const originalValue = context.datasetIndex === 0 ? 
                                    yourSiteData[context.dataIndex] : 
                                    competitorData[context.dataIndex];
                                
                                // Format value based on metric
                                let formattedValue = originalValue;
                                if (context.dataIndex === 0) { // Content Length
                                    formattedValue = originalValue + ' words';
                                } else if (context.dataIndex === 1) { // Backlinks
                                    formattedValue = originalValue + ' links';
                                }
                                
                                return datasetLabel + ': ' + formattedValue;
                            }
                        }
                    }
                }
            }
        });
    },
    
    /**
     * Display competitive analysis results
     * 
     * @param {Object} data Analysis results
     */
	displayResults: function(data) {
		// Clean up any existing charts first
		this.cleanupCharts();
		
		// Handle null or undefined data
		if (!data) {
			$('#competitorContent').html(`
				<div class="alert alert-warning">
					<i class="fas fa-exclamation-triangle"></i> No competitive analysis data available.
				</div>
			`);
			return;
		}
		
		try {
			// Extract key data
			const mainSite = data.main_site || {};
			const competitors = data.competitors || [];
			const comparativeAnalysis = data.comparative_analysis || {};
			const strengthsWeaknesses = data.strengths_weaknesses || { strengths: [], weaknesses: [] };
			const opportunities = data.opportunities || [];
			const keywordGap = data.keyword_gap || { gap_keywords: [] };
			const contentGap = data.content_gap || { content_suggestions: [] };
			const backlinkOpportunities = data.backlink_opportunities || { opportunities: [] };
			const score = data.overall_score || 0;
			
			// Create HTML content for all sections
			let content = '';
			
			// Add setup section for adding competitors
			content += this.createSetupSection();
			
			// Add comparison section
			content += this.createComparisonSection(mainSite, competitors, comparativeAnalysis);
			
			// Add SWOT analysis section
			content += this.createSWOTSection(strengthsWeaknesses, opportunities);
			
			// Add keyword gap section
			content += this.createKeywordGapSection(keywordGap);
			
			// Add content gap section
			content += this.createContentGapSection(contentGap);
			
			// Add backlink opportunities section
			content += this.createBacklinkSection(backlinkOpportunities);
			
			// Update the content
			$('#competitorContent').html(content);
			
			// Initialize charts after the DOM is ready
			setTimeout(() => {
				this.initComparisonChart(comparativeAnalysis);
				// Initialize other charts as needed
			}, 100);
			
			// Update score badge in accordion header
			UIManager.updateScoreBadge('competitor', score);
			
			// Store the data for later use
			$('#competitorContent').data('results', data);
			
			// Update competitor list display
			this.updateCompetitorsList();
		} catch (error) {
			console.error("Error displaying competitive analysis results:", error);
			$('#competitorContent').html(`
				<div class="alert alert-danger">
					<i class="fas fa-exclamation-triangle"></i> Error displaying competitive analysis: ${error.message}
				</div>
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0">Try Again</h5>
					</div>
					<div class="card-body">
						<button id="rerunCompetitorBtn" class="btn btn-primary">
							<i class="fas fa-sync-alt me-1"></i> Run Analysis Again
						</button>
					</div>
				</div>
			`);
		}
	},
    
    /**
     * Perform competitive analysis
     * 
     * @param {string} url URL to analyze
     * @param {Array} competitors Competitor URLs (optional)
     */
	 performCompetitorAnalysis: function(url, competitors = []) {
		// Clean up any existing charts before starting new analysis
		this.cleanupCharts();
		
		// Show loading indicator
		$('#competitorContent').html(`
			<div class="text-center p-4">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading...</span>
				</div>
				<p class="mt-2">Analyzing your site against competitors... This may take a moment.</p>
			</div>
		`);
		
		// Store competitors list if provided
		if (competitors && competitors.length > 0) {
			this.competitors = competitors;
		}
		
		// Make API call
		ApiService.runCompetitorAnalysis(url, this.competitors)
			.done(response => {
				if (response.success) {
					// Display the results
					this.displayResults(response.data);
					
					// Store for later reference
					$('#competitorContent').data('results', response.data);
					
					// Update competitors list if competitors are in the response
					if (response.data && response.data.competitors) {
						this.competitors = response.data.competitors.map(comp => comp.url);
						this.updateCompetitorsList();
					}
				} else {
					// Display error
					this.displayError(response.message || 'Competitive analysis failed');
				}
			})
			.fail(() => {
				// Display API error
				this.displayError('Competitive analysis failed due to a server error');
			});
	},
    
    // Rest of the CompetitorAnalysis object methods...

	
    /**
     * Add a competitor to the list
     * 
     * @param {string} url Competitor URL
     */
    addCompetitor: function(url) {
        // Validate URL
        if (!this.isValidUrl(url)) {
            UIManager.showNotification('Please enter a valid competitor URL', 'danger');
            return;
        }
        
        // Check if URL is already in the list
        if (this.competitors.includes(url)) {
            UIManager.showNotification('This competitor is already in the list', 'warning');
            return;
        }
        
        // Add to list
        this.competitors.push(url);
        
        // Update competitor list UI
        this.updateCompetitorsList();
    },
    
    /**
     * Remove a competitor from the list
     * 
     * @param {number} index Competitor index
     */
    removeCompetitor: function(index) {
        if (index >= 0 && index < this.competitors.length) {
            this.competitors.splice(index, 1);
            this.updateCompetitorsList();
        }
    },
    
    /**
     * Update competitors list UI
     */
    updateCompetitorsList: function() {
        const $list = $('#competitorsList');
        $list.empty();
        
        if (this.competitors.length === 0) {
            $list.html('<div class="text-muted p-3">No competitors added yet. Add competitors to include in the analysis.</div>');
            return;
        }
        
        this.competitors.forEach((url, index) => {
            const domain = this.extractDomain(url);
            $list.append(`
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-building me-2"></i>
                        <span title="${url}">${domain}</span>
                    </div>
                    <button class="btn btn-sm btn-outline-danger remove-competitor" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
        });
    },
    
  
  
    
    /**
     * Create setup section for adding competitors
     * 
     * @return {string} HTML content
     */
    createSetupSection: function() {
        return `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Competitor Setup</h5>
                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#competitorSetupCollapse">
                        <i class="fas fa-cog"></i> Configure
                    </button>
                </div>
                <div class="collapse" id="competitorSetupCollapse">
                    <div class="card-body">
                        <p class="mb-3">Add your competitors to include in the analysis. If you don't add any, we'll analyze against generic competitors.</p>
                        <div class="mb-3">
                            <div class="input-group">
                                <input type="url" class="form-control" id="competitorUrl" placeholder="Enter competitor URL (e.g., https://competitor.com)">
                                <button class="btn btn-outline-primary" type="button" id="addCompetitorBtn">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6>Your Competitors</h6>
                            <div class="list-group" id="competitorsList">
                                <div class="text-muted p-3">No competitors added yet. Add competitors to include in the analysis.</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary" id="runCompetitorAnalysisBtn">
                                <i class="fas fa-play"></i> Run Analysis
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },
    
    /**
     * Create comparison section
     * 
     * @param {Object} mainSite Main site data
     * @param {Array} competitors Competitors data
     * @param {Object} comparativeAnalysis Comparative analysis data
     * @return {string} HTML content
     */
    createComparisonSection: function(mainSite, competitors, comparativeAnalysis) {
        if (!mainSite || !comparativeAnalysis || !comparativeAnalysis.metrics_comparison) {
            return `
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Competitor Comparison</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i> No comparison data available.
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Extract competitor names
        const competitorNames = competitors.map(competitor => this.extractDomain(competitor.url));
        
        // Create comparison metrics
        const metricsHtml = `
            <div class="row">
                <div class="col-md-8">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="competitorComparisonChart"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="border-bottom pb-2">Metrics Comparison</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Your Site</th>
                                    <th>Competitor Avg</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Content Length</td>
                                    <td>${mainSite.page_data.word_count}</td>
                                    <td>${comparativeAnalysis.avg_word_count}</td>
                                </tr>
                                <tr>
                                    <td>Backlinks</td>
                                    <td>${mainSite.backlink_data.total_backlinks}</td>
                                    <td>${comparativeAnalysis.avg_backlinks}</td>
                                </tr>
                                <tr>
                                    <td>Domain Authority</td>
                                    <td>${mainSite.backlink_data.domain_authority}</td>
                                    <td>${comparativeAnalysis.avg_domain_authority}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        return `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Competitor Comparison</h5>
                </div>
                <div class="card-body">
                    ${metricsHtml}
                </div>
            </div>
        `;
    },
    
    /**
     * Create SWOT section (Strengths, Weaknesses, Opportunities)
     * 
     * @param {Object} strengthsWeaknesses Strengths and weaknesses data
     * @param {Array} opportunities Opportunities data
     * @return {string} HTML content
     */
    createSWOTSection: function(strengthsWeaknesses, opportunities) {
        const strengths = strengthsWeaknesses.strengths || [];
        const weaknesses = strengthsWeaknesses.weaknesses || [];
        
        // Create strengths HTML
        let strengthsHtml = '<div class="list-group mb-0">';
        
        if (strengths.length === 0) {
            strengthsHtml += `
                <div class="list-group-item text-muted">
                    <i class="fas fa-info-circle me-2"></i> No specific strengths identified.
                </div>
            `;
        } else {
            strengths.forEach(strength => {
                const impactClass = strength.impact === 'high' ? 'text-success' : 
                                   strength.impact === 'medium' ? 'text-primary' : 'text-info';
                
                strengthsHtml += `
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="${impactClass} me-2">
                                <i class="fas fa-arrow-circle-up"></i>
                            </div>
                            <div>
                                <strong>${strength.area}</strong>: ${strength.description}
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        strengthsHtml += '</div>';
        
        // Create weaknesses HTML
        let weaknessesHtml = '<div class="list-group mb-0">';
        
        if (weaknesses.length === 0) {
            weaknessesHtml += `
                <div class="list-group-item text-muted">
                    <i class="fas fa-info-circle me-2"></i> No specific weaknesses identified.
                </div>
            `;
        } else {
            weaknesses.forEach(weakness => {
                const impactClass = weakness.impact === 'high' ? 'text-danger' : 
                                   weakness.impact === 'medium' ? 'text-warning' : 'text-info';
                
                weaknessesHtml += `
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="${impactClass} me-2">
                                <i class="fas fa-arrow-circle-down"></i>
                            </div>
                            <div>
                                <strong>${weakness.area}</strong>: ${weakness.description}
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        weaknessesHtml += '</div>';
        
        // Create opportunities HTML
        let opportunitiesHtml = '<div class="list-group mb-0">';
        
        if (opportunities.length === 0) {
            opportunitiesHtml += `
                <div class="list-group-item text-muted">
                    <i class="fas fa-info-circle me-2"></i> No specific opportunities identified.
                </div>
            `;
        } else {
            opportunities.forEach(opportunity => {
                const priorityClass = opportunity.priority === 'high' ? 'text-danger' : 
                                     opportunity.priority === 'medium' ? 'text-warning' : 'text-info';
                
                opportunitiesHtml += `
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="${priorityClass} me-2">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div>
                                <strong>${opportunity.title}</strong>
                                <div>${opportunity.description}</div>
                                <div class="mt-1">
                                    <span class="badge bg-${this.getPriorityClass(opportunity.priority)}">
                                        ${this.capitalizeFirstLetter(opportunity.priority)} Priority
                                    </span>
                                    <span class="badge bg-secondary ms-1">
                                        ${this.capitalizeFirstLetter(opportunity.difficulty)} Difficulty
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        opportunitiesHtml += '</div>';
        
        return `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">SWOT Analysis</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card h-100 border-success">
                                <div class="card-header bg-success bg-opacity-10 text-success">
                                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Strengths</h5>
                                </div>
                                <div class="card-body p-0">
                                    ${strengthsHtml}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-danger">
                                <div class="card-header bg-danger bg-opacity-10 text-danger">
                                    <h5 class="mb-0"><i class="fas fa-minus-circle me-2"></i> Weaknesses</h5>
                                </div>
                                <div class="card-body p-0">
                                    ${weaknessesHtml}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-warning">
                                <div class="card-header bg-warning bg-opacity-10 text-warning">
                                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Opportunities</h5>
                                </div>
                                <div class="card-body p-0">
                                    ${opportunitiesHtml}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },
    
    /**
     * Create keyword gap section
     * 
     * @param {Object} keywordGap Keyword gap data
     * @return {string} HTML content
     */
    createKeywordGapSection: function(keywordGap) {
        const gapKeywords = keywordGap.gap_keywords || [];
        
        // Create keywords table
        let keywordsHtml = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Competitor Usage</th>
                            <th>Opportunity Score</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        if (gapKeywords.length === 0) {
            keywordsHtml += `
                <tr>
                    <td colspan="3" class="text-center text-muted">
                        <i class="fas fa-info-circle me-2"></i> No keyword gap opportunities identified.
                    </td>
                </tr>
            `;
        } else {
            gapKeywords.forEach(keyword => {
                // Calculate opportunity badge class
                const scoreClass = keyword.opportunity_score >= 80 ? 'bg-success' : 
                                 keyword.opportunity_score >= 50 ? 'bg-primary' : 'bg-info';
                
                keywordsHtml += `
                    <tr>
                        <td><strong>${keyword.keyword}</strong></td>
                        <td>${keyword.competitor_count} competitors</td>
                        <td>
                            <span class="badge ${scoreClass}">${keyword.opportunity_score}</span>
                        </td>
                    </tr>
                `;
            });
        }
        
        keywordsHtml += `
                    </tbody>
                </table>
            </div>
        `;
        
        // Add explanation and recommendation
        const explanationHtml = `
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Keyword Gap Analysis:</strong> These keywords are used by multiple competitors but not found on your site.
                Consider incorporating these keywords into your content strategy to compete more effectively.
            </div>
        `;
        
        return `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Keyword Gap Analysis</h5>
                </div>
                <div class="card-body">
                    ${explanationHtml}
                    ${keywordsHtml}
                </div>
            </div>
        `;
    },
    
    /**
     * Create content gap section
     * 
     * @param {Object} contentGap Content gap data
     * @return {string} HTML content
     */
    createContentGapSection: function(contentGap) {
        const contentSuggestions = contentGap.content_suggestions || [];
        
        if (contentSuggestions.length === 0) {
            return `
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Content Gap Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            No significant content gaps identified between your site and competitors.
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Create content suggestions HTML
        let suggestionsHtml = '';
        
        contentSuggestions.forEach((suggestion, index) => {
            // Create content ideas list
            let ideasHtml = '<ul class="mb-0">';
            suggestion.content_ideas.forEach(idea => {
                ideasHtml += `<li>${idea}</li>`;
            });
            ideasHtml += '</ul>';
            
            // Add suggestion card
            suggestionsHtml += `
                <div class="col-md-6 mb-3">
                    <div class="card h-100 border-info">
                        <div class="card-header bg-info bg-opacity-10">
                            <h5 class="mb-0">
                                ${suggestion.topic}
                                <span class="badge bg-info float-end">
                                    ${suggestion.competitor_count} competitors
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Content ideas based on this topic:</p>
                            ${ideasHtml}
                        </div>
                    </div>
                </div>
            `;
        });
        
        return `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Content Gap Analysis</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Content Gap Analysis:</strong> These topics are covered by multiple competitors but not on your site.
                        Consider creating content around these topics to capture similar audiences.
                    </div>
                    <div class="row">
                        ${suggestionsHtml}
                    </div>
                </div>
            </div>
        `;
    },
    
    /**
     * Create backlink section
     * 
     * @param {Object} backlinkOpportunities Backlink opportunities data
     * @return {string} HTML content
     */
    createBacklinkSection: function(backlinkOpportunities) {
        const opportunities = backlinkOpportunities.opportunities || [];
        
        if (opportunities.length === 0) {
            return `
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Backlink Opportunities</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            No specific backlink opportunities identified.
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Create backlink opportunities table
        let opportunitiesHtml = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Domain Authority</th>
                            <th>Linked Competitors</th>
                            <th>Opportunity Score</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        opportunities.forEach(opportunity => {
            // Calculate opportunity badge class
            const scoreClass = opportunity.opportunity_score >= 80 ? 'bg-success' : 
                             opportunity.opportunity_score >= 50 ? 'bg-primary' : 'bg-info';
            
            opportunitiesHtml += `
                <tr>
                    <td><strong>${opportunity.domain}</strong></td>
                    <td>${opportunity.domain_authority}</td>
                    <td>${opportunity.linked_competitors}</td>
                    <td>
                        <span class="badge ${scoreClass}">${opportunity.opportunity_score}</span>
                    </td>
                </tr>
            `;
        });
        
        opportunitiesHtml += `
                    </tbody>
                </table>
            </div>
        `;
        
        return `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Backlink Opportunities</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Backlink Opportunities:</strong> These domains link to your competitors but not to your site.
                        Consider reaching out to these domains for potential backlink opportunities.
                    </div>
                    ${opportunitiesHtml}
                </div>
            </div>
        `;
    },
    
    /**
     * Display error message
     * 
     * @param {string} message Error message
     */
    displayError: function(message) {
        $('#competitorContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i> ${message}
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Competitive Analysis Setup</h5>
                </div>
                <div class="card-body">
                    <p>The competitive analysis could not be completed. You can try adding specific competitors manually:</p>
                    
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="url" class="form-control" id="competitorUrl" placeholder="Enter competitor URL (e.g., https://competitor.com)">
                            <button class="btn btn-outline-primary" type="button" id="addCompetitorBtn">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>Your Competitors</h6>
                        <div class="list-group" id="competitorsList">
                            <div class="text-muted p-3">No competitors added yet. Add competitors to include in the analysis.</div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <button id="rerunCompetitorBtn" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-1"></i> Run Analysis
                        </button>
                    </div>
                </div>
            </div>
        `);
        
        // Update competitors list if we have any
        this.updateCompetitorsList();
    },
    
  
    /**
     * Generate summary text based on score
     * 
     * @param {number} score Overall score
     * @param {Object} strengthsWeaknesses Strengths and weaknesses
     * @param {Array} opportunities Opportunities
     * @return {string} Summary text
     */
    generateSummaryText: function(score, strengthsWeaknesses, opportunities) {
        const strengths = strengthsWeaknesses.strengths || [];
        const weaknesses = strengthsWeaknesses.weaknesses || [];
        const opportunitiesArr = opportunities || [];
        
        if (score >= 80) {
            return 'Your site is performing well compared to competitors. You have a strong competitive position in most key areas.';
        } else if (score >= 60) {
            return 'Your site is performing adequately against competitors, with some strengths and areas for improvement.';
        } else if (score >= 40) {
            if (weaknesses.length > 0 && opportunitiesArr.length > 0) {
                return 'Your site has several competitive gaps compared to competitors. Focus on addressing the identified weaknesses and opportunities.';
            } else {
                return 'Your site is underperforming compared to competitors in several key areas. Review the analysis to identify improvement opportunities.';
            }
        } else {
            return 'Your site is significantly behind competitors in most key metrics. Prioritize addressing the highest impact weaknesses first.';
        }
    },
    
    /**
     * Format metric comparison value
     * 
     * @param {number} value Comparison value
     * @return {string} Formatted comparison
     */
    formatMetricComparison: function(value) {
        if (!value && value !== 0) return 'N/A';
        
        if (value > 0) {
            return `+${value}%`;
        } else if (value < 0) {
            return `${value}%`;
        } else {
            return 'Equal';
        }
    },
    
    /**
     * Get CSS class for comparison value
     * 
     * @param {number} value Comparison value
     * @return {string} CSS class
     */
    getComparisonClass: function(value) {
        if (!value && value !== 0) return '';
        
        if (value > 10) {
            return 'text-success';
        } else if (value < -10) {
            return 'text-danger';
        } else {
            return 'text-muted';
        }
    },
    
    /**
     * Get CSS class for score
     * 
     * @param {number} score The score value
     * @return {string} CSS class
     */
    getScoreClass: function(score) {
        if (score >= 80) return 'score-excellent';
        if (score >= 60) return 'score-good';
        if (score >= 40) return 'score-average';
        return 'score-poor';
    },
    
    /**
     * Get score label based on score value
     * 
     * @param {number} score Score value
     * @return {string} Score label
     */
    getScoreLabel: function(score) {
        if (score >= 80) {
            return 'Excellent';
        } else if (score >= 60) {
            return 'Good';
        } else if (score >= 40) {
            return 'Average';
        } else {
            return 'Below Average';
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
     * Capitalize first letter of a string
     * 
     * @param {string} string String to capitalize
     * @return {string} Capitalized string
     */
    capitalizeFirstLetter: function(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    },
    
    /**
     * Validate URL format
     * 
     * @param {string} url URL to validate
     * @return {boolean} True if valid
     */
    isValidUrl: function(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    },
    
    /**
     * Extract domain from URL
     * 
     * @param {string} url URL
     * @return {string} Domain name
     */
    extractDomain: function(url) {
        try {
            const parsedUrl = new URL(url);
            let domain = parsedUrl.hostname;
            
            // Remove www. if present
            if (domain.startsWith('www.')) {
                domain = domain.substring(4);
            }
            
            return domain;
        } catch (e) {
            // For invalid URLs, return the original string
            return url;
        }
    },
	
	
	
};

// Initialize when document is ready
$(document).ready(function() {
    CompetitorAnalysis.initialize();
});

// Event listener for analysis manager integration
$(document).on('requestCompetitorAnalysis', function(event, url) {
    // Clean up any existing charts before processing
    CompetitorAnalysis.cleanupCharts();
    
    // Check if we have data already loaded
    const existingData = $('#competitorContent').data('results');
    
    if (existingData) {
        // Display the data directly
        CompetitorAnalysis.displayResults(existingData);
    } else {
        // Request new data
        ApiService.runCompetitorAnalysis(url)
            .done(function(response) {
                if (response.success) {
                    // Display the results
                    CompetitorAnalysis.displayResults(response.data);
                    
                    // Store for later reference
                    $('#competitorContent').data('results', response.data);
                } else {
                    // Display error
                    CompetitorAnalysis.displayError(response.message || 'Competitive analysis failed');
                }
            })
            .fail(function() {
                // Display API error
                CompetitorAnalysis.displayError('Competitive analysis failed due to a server error');
            });
    }
});


$(window).on('unload', function() {
    CompetitorAnalysis.cleanupCharts();
});
