/**
 * schema-analysis.js - Frontend display logic for schema markup analysis
 * File path: js/schema-analysis.js
 */

const SchemaAnalysis = {
    /**
     * Initialize the Schema Markup Analysis module
     */
    initialize: function() {
        console.log('Schema Markup Analysis module initialized');
        
        // Set up event listeners
        this.setupEventListeners();
    },
    
    /**
     * Set up event listeners
     */
    setupEventListeners: function() {
        // Listen for when Schema Markup analysis is requested
        $(document).on('requestSchemaAnalysis', (event, url) => {
            this.performSchemaAnalysis(url);
        });
        
        // Listen for re-analysis button clicks
        $(document).on('click', '#rerunSchemaBtn', function() {
            const url = $('#websiteUrl').val();
            if (url) {
                SchemaAnalysis.performSchemaAnalysis(url);
            }
        });
    },
    
    /**
     * Perform Schema Markup analysis
     * 
     * @param {string} url URL to analyze
     */
    performSchemaAnalysis: function(url) {
        // Show loading indicator
        $('#schemaContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Analyzing Schema Markup... This may take a moment.</p>
            </div>
        `);
        
        // Call API
        $.ajax({
            url: 'api/schema-analysis-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.displayResults(response.data);
                } else {
                    this.displayError(response.message || 'Unknown error occurred during schema markup analysis.');
                }
            },
            error: (xhr, status, error) => {
                this.displayError('Server error: ' + error);
            }
        });
    },
    
    /**
     * Display Schema Markup analysis results
     * 
     * @param {Object} data Analysis results
     */
    displayResults: function(data) {
        // Handle null or undefined data
        if (!data) {
            $('#schemaContent').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No schema markup data available.
                </div>
            `);
            return;
        }
        
        try {
            // Extract key data
            const hasSchema = data.has_schema;
            const score = data.overall_score || 0;
            const schemas = data.schemas || [];
            const schemaTypes = data.schema_types || [];
            const hasJsonld = data.has_jsonld;
            const hasMicrodata = data.has_microdata;
            const hasRdfa = data.has_rdfa;
            const issues = data.issues || [];
            const recommendations = data.recommendations || [];
            
            // Create HTML content for different sections
            const overviewSection = this.createOverviewSection(data);
            const schemaTypesSection = this.createSchemaTypesSection(schemas, schemaTypes);
            const issuesSection = this.createIssuesSection(issues);
            const recommendationsSection = this.createRecommendationsSection(recommendations);
            
            // Build the full content
            const content = `
                <div class="schema-analysis-container">
                    ${overviewSection}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            ${schemaTypesSection}
                        </div>
                        <div class="col-md-6">
                            ${issuesSection}
                        </div>
                    </div>
                    ${recommendationsSection}
                </div>
            `;
            
            // Update the content
            $('#schemaContent').html(content);
            
            // Update score badge in accordion header
            UIManager.updateScoreBadge('schema', score);
            
            // Store the data for later use
            $('#schemaContent').data('results', data);
            
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        } catch (error) {
            console.error("Error displaying Schema Markup results:", error);
            $('#schemaContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error displaying Schema Markup analysis: ${error.message}
                </div>
            `);
        }
    },
    
    /**
     * Create overview section HTML
     * 
     * @param {Object} data Analysis data
     * @return {string} HTML content
     */
    createOverviewSection: function(data) {
        const score = data.overall_score || 0;
        const hasSchema = data.has_schema;
        const hasJsonld = data.has_jsonld;
        const hasMicrodata = data.has_microdata;
        const hasRdfa = data.has_rdfa;
        const schemaCount = data.schemas ? data.schemas.length : 0;
        const typesCount = data.schema_types ? data.schema_types.length : 0;
        
        return `
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Schema Markup Score</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="schema-score mb-3">
                                <div class="score-circle ${this.getScoreClass(score)}">
                                    <div class="score-number">${score}</div>
                                </div>
                            </div>
                            <p class="mb-2 fw-bold">${this.getScoreLabel(score)}</p>
                            <div class="mt-3">
                                <button id="rerunSchemaBtn" class="btn btn-primary">
                                    <i class="fas fa-sync-alt me-1"></i> Reanalyze
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Schema Markup Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2">Status</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Schema Markup:</span>
                                            <span class="badge ${hasSchema ? 'bg-success' : 'bg-danger'}">
                                                ${hasSchema ? 'Detected' : 'Not Detected'}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>JSON-LD:</span>
                                            <span class="badge ${hasJsonld ? 'bg-success' : 'bg-secondary'}">
                                                ${hasJsonld ? 'Detected' : 'Not Detected'}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Microdata:</span>
                                            <span class="badge ${hasMicrodata ? 'bg-success' : 'bg-secondary'}">
                                                ${hasMicrodata ? 'Detected' : 'Not Detected'}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>RDFa:</span>
                                            <span class="badge ${hasRdfa ? 'bg-success' : 'bg-secondary'}">
                                                ${hasRdfa ? 'Detected' : 'Not Detected'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2">Summary</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Total Schemas:</span>
                                            <span class="badge bg-primary">${schemaCount}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Schema Types:</span>
                                            <span class="badge bg-primary">${typesCount}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Format:</span>
                                            <span class="badge bg-info">
                                                ${this.getSchemaFormat(hasJsonld, hasMicrodata, hasRdfa)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert ${hasSchema ? 'alert-info' : 'alert-warning'} mb-0">
                                <i class="${hasSchema ? 'fas fa-info-circle' : 'fas fa-exclamation-triangle'}"></i> 
                                ${hasSchema 
                                    ? 'Schema markup helps search engines understand your content better and enables rich results in search engine results pages.' 
                                    : 'No schema markup detected. Adding schema markup can improve how search engines understand and display your content.'}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },
    
    /**
     * Create schema types section HTML
     * 
     * @param {Array} schemas Schema objects
     * @param {Array} schemaTypes Schema type names
     * @return {string} HTML content
     */
    createSchemaTypesSection: function(schemas, schemaTypes) {
        if (!schemas || schemas.length === 0) {
            return `
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Schema Types</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i> No schema types detected.
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Create schema type list
        let typesHtml = '';
        if (schemaTypes && schemaTypes.length > 0) {
            typesHtml = `
                <div class="schema-types-list mb-3">
                    <h6 class="border-bottom pb-2">Detected Schema Types</h6>
                    <div class="d-flex flex-wrap gap-2">
            `;
            
            // Add each schema type as a badge
            schemaTypes.forEach(type => {
                typesHtml += `
                    <span class="badge bg-primary p-2">
                        <i class="fas fa-code me-1"></i> ${type}
                    </span>
                `;
            });
            
            typesHtml += `
                    </div>
                </div>
            `;
        }
        
        // Create schema details table
        let detailsHtml = `
            <div class="schema-details mt-3">
                <h6 class="border-bottom pb-2">Schema Details</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Format</th>
                                <th>Properties</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        // Add each schema to the table
        schemas.forEach(schema => {
            const validityClass = schema.validity === 'valid' ? 'success' : 
                                  schema.validity === 'warning' ? 'warning' : 'danger';
            const validityText = schema.validity === 'valid' ? 'Valid' : 
                                schema.validity === 'warning' ? 'Warning' : 'Invalid';
            
            // Create tooltip text for missing properties
            let tooltipText = '';
            if (schema.missing_required.length > 0) {
                tooltipText += 'Missing required: ' + schema.missing_required.join(', ');
            }
            if (schema.missing_recommended.length > 0) {
                if (tooltipText) tooltipText += '\n';
                tooltipText += 'Missing recommended: ' + schema.missing_recommended.join(', ');
            }
            
            detailsHtml += `
                <tr>
                    <td>${schema.type}</td>
                    <td>${schema.format}</td>
                    <td>${schema.properties}</td>
                    <td>
                        <span class="badge bg-${validityClass}" 
                              ${tooltipText ? 'data-bs-toggle="tooltip" data-bs-placement="top" title="' + tooltipText + '"' : ''}>
                            ${validityText}
                        </span>
                    </td>
                </tr>
            `;
        });
        
        detailsHtml += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        // Combine into final HTML
        return `
            <div class="card mb-4 h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Schema Types</h5>
                </div>
                <div class="card-body">
                    ${typesHtml}
                    ${detailsHtml}
                </div>
            </div>
        `;
    },
    
    /**
     * Create issues section HTML
     * 
     * @param {Array} issues Issues array
     * @return {string} HTML content
     */
    createIssuesSection: function(issues) {
        if (!issues || issues.length === 0) {
            return `
                <div class="card mb-4 h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Issues</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i> No issues detected with your schema markup.
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Create issues list
        let issuesHtml = '';
        
        issues.forEach(issue => {
            const severityClass = issue.severity === 'high' ? 'danger' : 
                                 issue.severity === 'medium' ? 'warning' : 'info';
            const severityIcon = issue.severity === 'high' ? 'exclamation-circle' : 
                               issue.severity === 'medium' ? 'exclamation-triangle' : 'info-circle';
            
            issuesHtml += `
                <div class="alert alert-${severityClass} d-flex align-items-center mb-2">
                    <div class="flex-shrink-0 me-2">
                        <i class="fas fa-${severityIcon}"></i>
                    </div>
                    <div>
                        ${issue.description}
                    </div>
                </div>
            `;
        });
        
        // Combine into final HTML
        return `
            <div class="card mb-4 h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Issues</h5>
                </div>
                <div class="card-body">
                    <div class="schema-issues">
                        ${issuesHtml}
                    </div>
                </div>
            </div>
        `;
    },
    
    /**
     * Create recommendations section HTML
     * 
     * @param {Array} recommendations Recommendations array
     * @return {string} HTML content
     */
    createRecommendationsSection: function(recommendations) {
        if (!recommendations || recommendations.length === 0) {
            return `
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Recommendations</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i> Your schema markup looks good! No specific recommendations at this time.
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Create recommendations list
        let recsHtml = '';
        
        recommendations.forEach(rec => {
            const priorityClass = rec.priority === 'high' ? 'danger' : 
                                 rec.priority === 'medium' ? 'warning' : 'info';
            
            recsHtml += `
                <div class="card mb-3 border-${priorityClass}">
                    <div class="card-header bg-${priorityClass} bg-opacity-10">
                        <h5 class="mb-0">
                            <span class="badge bg-${priorityClass} me-2">${this.capitalizeFirstLetter(rec.priority)}</span>
                            ${rec.title}
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>${rec.description}</p>
                        ${rec.implementation ? `
                            <div class="implementation-guide mt-3">
                                ${rec.implementation}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        // Combine into final HTML
        return `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recommendations</h5>
                </div>
                <div class="card-body">
                    <div class="schema-recommendations">
                        ${recsHtml}
                    </div>
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
        $('#schemaContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i> ${message}
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Schema Markup Analysis Troubleshooting</h5>
                </div>
                <div class="card-body">
                    <p>The schema markup analysis could not be completed. Here are some possible reasons:</p>
                    <ul>
                        <li>The website may be blocking automated crawling</li>
                        <li>The server may be responding slowly or is unreachable</li>
                        <li>The website may have complex JavaScript that prevents schema detection</li>
                    </ul>
                    <div class="mt-3">
                        <button id="rerunSchemaBtn" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-1"></i> Try Again
                        </button>
                    </div>
                </div>
            </div>
        `);
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
     * Get score label based on score value
     * 
     * @param {number} score Score value
     * @return {string} Score label
     */
    getScoreLabel: function(score) {
        if (score >= 90) {
            return 'Excellent';
        } else if (score >= 70) {
            return 'Good';
        } else if (score >= 50) {
            return 'Needs Improvement';
        } else {
            return 'Poor';
        }
    },
    
    /**
     * Get schema format text
     * 
     * @param {boolean} hasJsonld Has JSON-LD
     * @param {boolean} hasMicrodata Has Microdata
     * @param {boolean} hasRdfa Has RDFa
     * @return {string} Format text
     */
    getSchemaFormat: function(hasJsonld, hasMicrodata, hasRdfa) {
        const formats = [];
        
        if (hasJsonld) formats.push('JSON-LD');
        if (hasMicrodata) formats.push('Microdata');
        if (hasRdfa) formats.push('RDFa');
        
        return formats.length > 0 ? formats.join(', ') : 'None';
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
    SchemaAnalysis.initialize();
});

// Event listener for analysis manager integration
$(document).on('requestSchemaAnalysis', function(event, url) {
    // Check if we have data already loaded
    const existingData = $('#schemaContent').data('results');
    
    if (existingData) {
        // Display the data directly
        SchemaAnalysis.displayResults(existingData);
    } else {
        // Request new data
        ApiService.runSchemaAnalysis(url)
            .done(function(response) {
                if (response.success) {
                    // Display the results
                    SchemaAnalysis.displayResults(response.data);
                    
                    // Store for later reference
                    $('#schemaContent').data('results', response.data);
                } else {
                    // Display error
                    SchemaAnalysis.displayError(response.message || 'Schema markup analysis failed');
                }
            })
            .fail(function() {
                // Display API error
                SchemaAnalysis.displayError('Schema markup analysis failed due to a server error');
            });
    }
});
