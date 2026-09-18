/**
 * api-service.js - Handles all API calls for the SEO Analysis application
 */

const ApiService = {
    // Technical SEO analysis
    runTechnicalSeoAnalysis: function(url) {
        return $.ajax({
            url: 'api/technical-seo-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
	
	// Meta tags analysis
	runMetaTagsAnalysis: function(url) {
		return $.ajax({
			url: 'api/meta-tags-api.php',
			type: 'POST',
			data: { url: url },
			dataType: 'json'
		});
	},	
    
    // On-page SEO analysis
    runOnPageSeoAnalysis: function(url) {
        return $.ajax({
            url: 'api/onpage-seo-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
    
   // Off-page SEO analysis
    runOffPageSeoAnalysis: function(url) {
        return $.ajax({
            url: 'api/offpage-seo-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
    
	// Link analysis
	runLinkAnalysis: function(url, maxPages = 10) {
		return $.ajax({
			url: 'api/link-analysis-api.php',
			type: 'POST',
			data: { 
				url: url,
				max_pages: maxPages
			},
			dataType: 'json'
		});
	},		
    // Keyword analysis
    runKeywordAnalysis: function(url) {
        return $.ajax({
            url: 'api/keyword-analysis-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
    
    // Content analysis
    runContentAnalysis: function(url) {
        return $.ajax({
            url: 'api/content-analysis-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
	// Page structure analysis
	runPageStructureAnalysis: function(url, maxPages = 5) {
		return $.ajax({
			url: 'api/page-structure-api.php',
			type: 'POST',
			data: { 
				url: url,
				max_pages: maxPages
			},
			dataType: 'json'
		});
	},   
    // Meta tags analysis
    runMetaTagsAnalysis: function(url) {
        return $.ajax({
            url: 'api/meta-tags-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
    
    // Link analysis
    runLinkAnalysis: function(url) {
        return $.ajax({
            url: 'api/link-analysis-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
    
    // Image analysis
    runImageAnalysis: function(url) {
        return $.ajax({
            url: 'api/image-analysis-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
    
    // Page structure analysis
    runPageStructureAnalysis: function(url) {
        return $.ajax({
            url: 'api/page-structure-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
	
	// Performance analysis (Core Web Vitals)
	runPerformanceAnalysis: function(url) {
		return $.ajax({
			url: 'api/performance-api.php',
			type: 'POST',
			data: { url: url },
			dataType: 'json'
		});
	},

	// Schema markup analysis
	runSchemaAnalysis: function(url) {
		return $.ajax({
			url: 'api/schema-analysis-api.php',
			type: 'POST',
			data: { url: url },
			dataType: 'json'
		});
	},

	// Competitive analysis
	runCompetitorAnalysis: function(url, competitors = []) {
		return $.ajax({
			url: 'api/competitor-analysis-api.php',
			type: 'POST',
			data: { 
				url: url,
				competitors: JSON.stringify(competitors),
				max_competitors: 3
			},
			dataType: 'json'
		});
	},	
	
		
	// Local SEO analysis
	runLocalSeoAnalysis: function(url) {
		return $.ajax({
			url: 'api/local-seo-api.php',
			type: 'POST',
			data: { url: url },
			dataType: 'json'
		});
	},

	// Content Gap analysis
	runContentGapAnalysis: function(url, competitors = []) {
		return $.ajax({
			url: 'api/content-gap-api.php',
			type: 'POST',
			data: { 
				url: url,
				competitors: JSON.stringify(competitors)
			},
			dataType: 'json'
		});
	},	
    
    // Performance metrics analysis
    runPerformanceAnalysis: function(url) {
        return $.ajax({
            url: 'api/performance-api.php',
            type: 'POST',
            data: { url: url },
            dataType: 'json'
        });
    },
    

    // Competitor analysis
    runCompetitorAnalysis: function(url, competitors) {
        return $.ajax({
            url: 'api/competitor-analysis-api.php',
            type: 'POST',
            data: { 
                url: url,
                competitors: competitors 
            },
            dataType: 'json'
        });
    },
    
    // Save analysis results
    saveResults: function(resultsData) {
        return $.ajax({
            url: 'api/save-results-api.php',
            type: 'POST',
            data: { results: JSON.stringify(resultsData) },
            dataType: 'json'
        });
    },
    
    // Get domains list
    getDomains: function() {
        return $.ajax({
            url: 'api/get-domains-api.php',
            type: 'GET',
            dataType: 'json'
        });
    },
    
    // Get domain results
    getDomainResults: function(domainId) {
        return $.ajax({
            url: 'api/get-results-api.php',
            type: 'GET',
            data: { id: domainId },
            dataType: 'json'
        });
    },
    
    // Get AI models
    getAiModels: function() {
        return $.ajax({
            url: 'api/get-ai-models-api.php',
            type: 'GET',
            dataType: 'json'
        });
    },
    
    // Generate AI recommendations
    generateAiRecommendations: function(data, modelId) {
        return $.ajax({
            url: 'api/generate-ai-recommendations-api.php',
            type: 'POST',
            data: { 
                seo_data: JSON.stringify(data),
                model_id: modelId
            },
            dataType: 'json'
        });
    },
    
    // Generic API call function with error handling
    fetchData: function(url, method = 'GET', data = null) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: method,
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(new Error(response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(`Request failed: ${status} - ${error}`));
                }
            });
        });
    }
};
