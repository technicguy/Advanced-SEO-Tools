/**
 * domain-manager.js - Manages domain data and operations
 */

const DomainManager = {
    initialize: function(appState) {
        // Any initialization code related to domains management
        console.log('Domain Manager initialized');
    },
    
    refreshDomainsList: function(appState) {
        $('#domainsList').empty();
        $('#domainsList').append(`
            <div class="text-center p-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading domains...</p>
            </div>
        `);
        
        ApiService.getDomains()
            .done(function(response) {
                if (response.success) {
                    $('#domainsList').empty();
                    
                    if (response.domains.length === 0) {
                        $('#domainsList').append(`
                            <li class="nav-item">
                                <div class="nav-link text-muted">
                                    <i class="fas fa-info-circle"></i> No saved domains yet
                                </div>
                            </li>
                        `);
                    } else {
                        response.domains.forEach(function(domain) {
                            $('#domainsList').append(`
                                <li class="nav-item">
                                    <a class="nav-link domain-item" href="#" data-id="${domain.id}">
                                        ${domain.domain_name}
                                        <span class="text-muted d-block small">Last checked: ${new Date(domain.last_checked).toLocaleString()}</span>
                                    </a>
                                </li>
                            `);
                        });
                    }
                } else {
                    $('#domainsList').html(`
                        <li class="nav-item">
                            <div class="nav-link text-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${response.message}
                            </div>
                        </li>
                    `);
                }
            })
            .fail(function() {
                $('#domainsList').html(`
                    <li class="nav-item">
                        <div class="nav-link text-danger">
                            <i class="fas fa-exclamation-triangle"></i> Failed to load domains
                        </div>
                    </li>
                `);
            });
    },
    
/**
 * Loads results for a specified domain
 * This is an updated function for domain-manager.js
 * @param {number} domainId - Domain ID to load
 * @param {object} appState - Application state object
 */
loadDomainResults: function(domainId, appState) {
    appState.isUserInitiatedAnalysis = false;
    
    // Show progress section
    $('#analysisProgress').removeClass('d-none');
    $('#resultsContainer').addClass('d-none');
    
    // Reset progress and show loading state
    UIManager.resetProgress(appState);
    UIManager.updateProgressStatus('Loading analysis results...');
    
    ApiService.getDomainResults(domainId)
        .done(function(response) {
            if (response.success) {
                // Update app state
                appState.currentWebsiteId = domainId;
                appState.currentUrl = response.url;
                
                // Mark all steps as completed
                Object.keys(appState.progressSteps).forEach(step => {
                    // Mark AI recommendations as completed only if we have data
                    if (step === 'aiRecommendations' && !response.aiRecommendations) {
                        appState.progressSteps[step].completed = false;
                    } else {
                        appState.progressSteps[step].completed = true;
                        UIManager.updateStepStatus(step, 'completed');
                    }
                });
                
                // Update URL in the input field
                $('#websiteUrl').val(response.url);
                
                // Update overall progress
                UIManager.updateOverallProgress(appState);
                
                // Clear existing content to prevent duplicates
				// Clear existing content to prevent duplicates
                $('#technicalContent, #metaTagsContent, #onpageContent, #offpageContent, #linkAnalysisContent, #imageAnalysisContent, #pageStructureContent,#coreWebVitalsContent, #schemaContent, #keywordsContent, #contentTabContent, #localSeoContent, #contentGapContent').empty();
                  
                // Display results
                $('#resultsContainer').removeClass('d-none');
                $('#saveResultsContainer').addClass('d-none'); // No need to save again
                
                // Load the technical SEO data
                if (response.technicalSeo) {
                    TechnicalSEO.displayResults(response.technicalSeo);
                }
                
                // Load meta tags data
                if (response.metaTagsAnalysis) {
                    MetaTagsAnalysis.displayResults(response.metaTagsAnalysis);
                }
                
                // Load the on-page SEO data
                if (response.onPageSeo) {
                    OnPageSEO.displayResults(response.onPageSeo);
                }
                
                // Load the off-page SEO data
                if (response.offPageSeo) {
                    OffPageSEO.displayResults(response.offPageSeo);
                }
                
                // Load the link analysis data
                if (response.linkAnalysis) {
                    try {
                        // Trigger event for Link Analysis module to handle the display
                        $(document).trigger('requestLinkAnalysis', [response.url]);
                        
                        // Store the data for reference
                        $('#linkAnalysisContent').data('results', response.linkAnalysis);
                        
                        // Update the score badge
                        const score = response.linkAnalysis.overall_score || 0;
                        UIManager.updateScoreBadge('linkAnalysis', score);
                    } catch (error) {
                        console.error("Error displaying link analysis:", error);
                        $('#linkAnalysisContent').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Link analysis module encountered an error: ${error.message}
                            </div>
                        `);
                    }
                } else {
                    // No link analysis data available
                    $('#linkAnalysisContent').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No link analysis data available for this domain.
                        </div>
                    `);
                }
                
                // Load the image analysis data
                if (response.imageAnalysis) {
                    try {
                        // Display the image analysis results
                        ImageAnalysis.displayResults(response.imageAnalysis);
                    } catch (error) {
                        console.error("Error displaying image analysis:", error);
                        $('#imageAnalysisContent').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Image analysis module encountered an error: ${error.message}
                            </div>
                        `);
                    }
                } else {
                    // No image analysis data available
                    $('#imageAnalysisContent').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No image analysis data available for this domain.
                        </div>
                    `);
                }
                

		// Load the page structure data
		if (response.pageStructure) {
			try {
				// Trigger event for Page Structure module to handle the display
				$(document).trigger('requestPageStructureAnalysis', [response.url]);
				
				// Store the data for reference
				$('#pageStructureContent').data('results', response.pageStructure);
				
				// Update the score badge
				const score = response.pageStructure.overall_score || 0;
				UIManager.updateScoreBadge('pageStructure', score);
			} catch (error) {
				console.error("Error displaying page structure:", error);
				$('#pageStructureContent').html(`
					<div class="alert alert-warning">
						<i class="fas fa-exclamation-triangle"></i> Page structure module encountered an error: ${error.message}
					</div>
				`);
			}
		} else {
			// No page structure data available
			$('#pageStructureContent').html(`
				<div class="alert alert-info">
					<i class="fas fa-info-circle"></i> No page structure data available for this domain.
				</div>
			`);
		}
		
		
                // Core Web Vitals
                if (response.coreWebVitals) {
                    try {
                        // Store the data for reference
                        $('#coreWebVitalsContent').data('results', response.coreWebVitals);
                        
                        // Display the Core Web Vitals results
                        if (typeof CoreWebVitals !== 'undefined' && typeof CoreWebVitals.displayResults === 'function') {
                            CoreWebVitals.displayResults(response.coreWebVitals);
                        } else {
                            // Trigger event for Core Web Vitals module to handle the display
                            $(document).trigger('requestCoreWebVitalsAnalysis', [response.url]);
                        }
                        
                        // Update the score badge
                        const score = response.coreWebVitals.overall_score || 0;
                        UIManager.updateScoreBadge('coreWebVitals', score);
                    } catch (error) {
                        console.error("Error displaying core web vitals:", error);
                        $('#coreWebVitalsContent').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Core Web Vitals module encountered an error: ${error.message}
                            </div>
                        `);
                    }
                } else {
                    // No Core Web Vitals data available
                    $('#coreWebVitalsContent').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No Core Web Vitals data available for this domain.
                        </div>
                    `);
                }		
		
		
                // Schema markup
                if (response.schema) {
                    try {
                        // Store the data for reference
                        $('#schemaContent').data('results', response.schema);
                        
                        // Display the Schema markup results
                        if (typeof SchemaAnalysis !== 'undefined' && typeof SchemaAnalysis.displayResults === 'function') {
                            SchemaAnalysis.displayResults(response.schema);
                        } else {
                            // Trigger event for Schema module to handle the display
                            $(document).trigger('requestSchemaAnalysis', [response.url]);
                        }
                        
                        // Update the score badge
                        const score = response.schema.overall_score || 0;
                        UIManager.updateScoreBadge('schema', score);
                    } catch (error) {
                        console.error("Error displaying schema markup analysis:", error);
                        $('#schemaContent').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Schema markup analysis module encountered an error: ${error.message}
                            </div>
                        `);
                    }
                } else {
                    // No schema markup data available
                    $('#schemaContent').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No schema markup data available for this domain.
                        </div>
                    `);
                }		
		
		
				
		

			// Load the competitor analysis data
			if (response.competitor) {
				try {
					// Trigger event for Competitor Analysis module to handle the display
					$(document).trigger('requestCompetitorAnalysis', [response.url]);
					
					// Store the data for reference
					$('#competitorContent').data('results', response.competitor);
					
					// Update the score badge
					const score = response.competitor.overall_score || 0;
					UIManager.updateScoreBadge('competitor', score);
				} catch (error) {
					console.error("Error displaying competitor analysis:", error);
					$('#competitorContent').html(`
						<div class="alert alert-warning">
							<i class="fas fa-exclamation-triangle"></i> Competitor analysis module encountered an error: ${error.message}
						</div>
					`);
				}
			} else {
				// No competitor analysis data available
				$('#competitorContent').html(`
					<div class="alert alert-info">
						<i class="fas fa-info-circle"></i> No competitor analysis data available for this domain.
					</div>
				`);
			}

				// Load the schema markup data
				if (response.schema) {
					try {
						// Trigger event for Schema module to handle the display
						$(document).trigger('requestSchemaAnalysis', [response.url]);
						
						// Store the data for reference
						$('#schemaContent').data('results', response.schema);
						
						// Update the score badge
						const score = response.schema.overall_score || 0;
						UIManager.updateScoreBadge('schema', score);
					} catch (error) {
						console.error("Error displaying schema markup analysis:", error);
						$('#schemaContent').html(`
							<div class="alert alert-warning">
								<i class="fas fa-exclamation-triangle"></i> Schema markup analysis module encountered an error: ${error.message}
							</div>
						`);
					}
				} else {
					// No schema markup data available
					$('#schemaContent').html(`
						<div class="alert alert-info">
							<i class="fas fa-info-circle"></i> No schema markup data available for this domain.
						</div>
					`);
				}

				// Load the core web vitals data
				if (response.coreWebVitals) {
					try {
						// Trigger event for Core Web Vitals module to handle the display
						$(document).trigger('requestCoreWebVitalsAnalysis', [response.url]);
						
						// Store the data for reference
						$('#coreWebVitalsContent').data('results', response.coreWebVitals);
						
						// Update the score badge
						const score = response.coreWebVitals.overall_score || 0;
						UIManager.updateScoreBadge('coreWebVitals', score);
					} catch (error) {
						console.error("Error displaying core web vitals:", error);
						$('#coreWebVitalsContent').html(`
							<div class="alert alert-warning">
								<i class="fas fa-exclamation-triangle"></i> Core Web Vitals module encountered an error: ${error.message}
							</div>
						`);
					}
				} else {
					// No core web vitals data available
					$('#coreWebVitalsContent').html(`
						<div class="alert alert-info">
							<i class="fas fa-info-circle"></i> No Core Web Vitals data available for this domain.
						</div>
					`);
				}

                // Local SEO - FIX ADDED HERE
                if (response.localSeo) {
                    try {
                        // Store the data for reference
                        $('#localSeoContent').data('results', response.localSeo);
                        
                        // Display the Local SEO results using the LocalSEO object's displayResults method
                        if (typeof LocalSEO !== 'undefined' && typeof LocalSEO.displayResults === 'function') {
                            LocalSEO.displayResults(response.localSeo);
                        } else {
                            // Fallback display if the module is not loaded
                            $('#localSeoContent').html(`
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Local SEO module not properly initialized.
                                </div>
                            `);
                        }
                        
                        // Update the score badge
                        const score = response.localSeo.overall_score || 0;
                        UIManager.updateScoreBadge('localSeo', score);
                    } catch (error) {
                        console.error("Error displaying local SEO analysis:", error);
                        $('#localSeoContent').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Local SEO module encountered an error: ${error.message}
                            </div>
                        `);
                    }
                } else {
                    // No Local SEO data available
                    $('#localSeoContent').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No Local SEO data available for this domain.
                        </div>
                    `);
                }
                
                // Content Gap - FIX ADDED HERE
                if (response.contentGap) {
                    try {
                        // Store the data for reference
                        $('#contentGapContent').data('results', response.contentGap);
                        
                        // Display the Content Gap results
                        if (typeof ContentGap !== 'undefined' && typeof ContentGap.displayResults === 'function') {
                            ContentGap.displayResults(response.contentGap);
                        } else {
                            // Fallback display if the module is not loaded
                            $('#contentGapContent').html(`
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Content Gap module not properly initialized.
                                </div>
                            `);
                        }
                        
                        // Update the score badge
                        const score = response.contentGap.overall_score || 0;
                        UIManager.updateScoreBadge('contentGap', score);
                    } catch (error) {
                        console.error("Error displaying content gap analysis:", error);
                        $('#contentGapContent').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Content Gap module encountered an error: ${error.message}
                            </div>
                        `);
                    }
                } else {
                    // No Content Gap data available
                    $('#contentGapContent').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No Content Gap data available for this domain.
                        </div>
                    `);
                }

                // Load the keyword analysis data
                if (response.keywordAnalysis) {
                    KeywordAnalysis.displayResults(response.keywordAnalysis);
                }
                
                // Load the content analysis data                   
                if (response.contentAnalysis) {
                    try {
                        // Try to use the ContentAnalysis object from the window
                        window.ContentAnalysis.displayResults(response.contentAnalysis);
                    } catch (error) {
                        console.error("Error displaying content analysis:", error);
                        $('#contentTabContent').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Content analysis module encountered an error: ${error.message}
                            </div>
                        `);
                    }
                }
                
                // Load AI recommendations if available
                // Load AI recommendations if available
                if (response.aiRecommendations) {
                    // Store the model ID to keep it selected
                    const modelId = response.aiRecommendations.model_id;
                    if (modelId) {
                        // Set the model in the dropdown
                        $('#aiModelSelect').val(modelId);
                        AIRecommendations.selectedModelId = modelId;
                        appState.aiModel = modelId;
                    }
                    
                    // Display the saved recommendations
                    AIRecommendations.displayRecommendations(response.aiRecommendations.recommendations);
                } else {
                    // Reset AI recommendations content
                    $('#aiRecommendationsContent').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Select an AI model from the sidebar to get intelligent SEO recommendations.
                        </div>
                    `);
                }
                
                // Generate summary
                Visualization.generateSummary();
                
                // Add check again option
                UIManager.addCheckAgainOption(response.url, appState);
                
                // Update progress status
                UIManager.updateProgressStatus('Analysis results loaded successfully');
                
                // Ensure Technical SEO options are properly displayed
                setTimeout(() => {
                    if (typeof TechnicalSEORegenerators !== 'undefined') {
                        TechnicalSEORegenerators.forceOptionsVisibility();
                    }
                }, 1500);
                
                // Trigger analysis loaded event
                $(document).trigger('analysisLoaded', {
                    hasAiRecommendations: !!response.aiRecommendations
                });
            } else {
                UIManager.handleApiFailure(response.message);
            }
        })
        .fail(function() {
            UIManager.handleApiFailure('Failed to load domain results due to a server error.');
        });
},
    
    saveAnalysisResults: function(appState) {
        // Show saving state
        $('#saveResultsBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        // Gather all analysis results
        const resultsData = {
            url: appState.currentUrl,
            technicalSeo: $('#technicalContent').data('results'),
            metaTagsAnalysis: $('#metaTagsContent').data('results'),
            onPageSeo: $('#onpageContent').data('results'),
            offPageSeo: $('#offpageContent').data('results'),
            keywordAnalysis: $('#keywordsContent').data('results'),
            contentAnalysis: $('#contentTabContent').data('results'),
            aiRecommendations: $('#aiRecommendationsContent').data('results')
        };
        
        ApiService.saveResults(resultsData)
            .done(function(response) {
                if (response.success) {
                    // Update button state
                    $('#saveResultsBtn').html('<i class="fas fa-check"></i> Saved!').removeClass('btn-success').addClass('btn-outline-success');
                    
                    // Set a timeout to revert the button
                    setTimeout(function() {
                        $('#saveResultsBtn').html('<i class="fas fa-save"></i> Save Analysis Results').removeClass('btn-outline-success').addClass('btn-success');
                    }, 3000);
                    
                    // Update the domains list
                    DomainManager.refreshDomainsList();
                    
                    // Show notification
                    UIManager.showNotification('Analysis results saved successfully!', 'success');
                    
                    // Update app state with the website ID
                    appState.currentWebsiteId = response.websiteId;
                } else {
                    // Reset button state
                    $('#saveResultsBtn').html('<i class="fas fa-save"></i> Save Analysis Results').prop('disabled', false);
                    
                    // Show error notification
                    UIManager.showNotification('Failed to save results: ' + response.message, 'danger');
                }
            })
            .fail(function() {
                // Reset button state
                $('#saveResultsBtn').html('<i class="fas fa-save"></i> Save Analysis Results').prop('disabled', false);
                
                // Show error notification
                UIManager.showNotification('Failed to save results due to a server error.', 'danger');
            });
    },
    
    filterDomainsBySearch: function(searchTerm) {
        // Filter domains list by search term
        if (!searchTerm) {
            $('.domain-item').show();
            return;
        }
        
        searchTerm = searchTerm.toLowerCase();
        
        $('.domain-item').each(function() {
            const domainName = $(this).text().toLowerCase();
            if (domainName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
};

// Helper functions to calculate scores from data if overall_score is not provided
function calculateTechnicalScore(data) {
    if (!data) return 0;
    
    let totalScore = 0;
    let count = 0;
    
    if (data.crawlability_score) { totalScore += data.crawlability_score; count++; }
    if (data.indexability_score) { totalScore += data.indexability_score; count++; }
    if (data.site_speed_score) { totalScore += data.site_speed_score; count++; }
    if (data.mobile_friendly_score) { totalScore += data.mobile_friendly_score; count++; }
    
    return count > 0 ? Math.round(totalScore / count) : 50; // Default to 50 if no scores
}

function calculateMetaTagsScore(data) {
    if (!data) return 0;
    
    let totalScore = 0;
    let count = 0;
    
    if (data.title_tags_score) { totalScore += data.title_tags_score; count++; }
    if (data.meta_description_score) { totalScore += data.meta_description_score; count++; }
    if (data.canonical_tags_score) { totalScore += data.canonical_tags_score; count++; }
    if (data.og_tags_score) { totalScore += data.og_tags_score; count++; }
    
    return count > 0 ? Math.round(totalScore / count) : 50; // Default to 50 if no scores
}

function calculateOnPageScore(data) {
    if (!data) return 0;
    
    let totalScore = 0;
    let count = 0;
    
    if (data.title_tags_score) { totalScore += data.title_tags_score; count++; }
    if (data.meta_desc_score) { totalScore += data.meta_desc_score; count++; }
    if (data.header_tags_score) { totalScore += data.header_tags_score; count++; }
    if (data.content_score) { totalScore += data.content_score; count++; }
    if (data.image_optimization_score) { totalScore += data.image_optimization_score; count++; }
    if (data.internal_linking_score) { totalScore += data.internal_linking_score; count++; }
    if (data.url_structure_score) { totalScore += data.url_structure_score; count++; }
    
    return count > 0 ? Math.round(totalScore / count) : 50; // Default to 50 if no scores
}

function calculateOffPageScore(data) {
    if (!data) return 0;
    
    let totalScore = 0;
    let count = 0;
    
    if (data.backlink_score) { totalScore += data.backlink_score; count++; }
    if (data.domain_authority) { totalScore += data.domain_authority; count++; }
    if (data.social_media_score) { totalScore += data.social_media_score; count++; }
    
    return count > 0 ? Math.round(totalScore / count) : 50; // Default to 50 if no scores
}

function calculateKeywordScore(data) {
    if (!data) return 0;
    
    // For keywords, use ranking performance or competition score if available
    if (data.ranking_performance) return data.ranking_performance;
    if (data.competition_score) return 100 - data.competition_score; // Invert competition score
    
    return 50; // Default score
}

function calculateContentScore(data) {
    if (!data) return 0;
    
    let score = 50; // Default score
    
    // Use content quality metrics if available
    if (data.avg_readability && data.avg_word_count) {
        // Calculate based on readability and word count
        const readabilityScore = data.avg_readability;
        const wordCountScore = Math.min(100, (data.avg_word_count / 1000) * 100);
        
        score = Math.round((readabilityScore + wordCountScore) / 2);
    }
    
    return score;
}