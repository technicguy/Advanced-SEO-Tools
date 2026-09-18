/**
 * main.js - Main entry point for the SEO Analysis application
 */

// Ensure ContentAnalysis and Visualization are globally accessible
window.ContentAnalysis = ContentAnalysis || {};
window.Visualization = Visualization || {};

// Application state
const appState = {
    currentWebsiteId: null,
    currentUrl: null,
    overallProgress: 0,
    isUserInitiatedAnalysis: false,
    progressSteps: {
        technicalSeo: { weight: 0.0625, completed: false },
        metaTagsAnalysis: { weight: 0.0625, completed: false },
        onPageSeo: { weight: 0.0625, completed: false },
        offPageSeo: { weight: 0.0625, completed: false },
        linkAnalysis: { weight: 0.0625, completed: false },
        imageAnalysis: { weight: 0.0625, completed: false },
        pageStructure: { weight: 0.0625, completed: false },
        coreWebVitals: { weight: 0.0625, completed: false },
        schema: { weight: 0.0625, completed: false },
        competitor: { weight: 0.0625, completed: false },
        localSeo: { weight: 0.0625, completed: false },      // New step
        contentGap: { weight: 0.0625, completed: false },    // New step
        keywordAnalysis: { weight: 0.0625, completed: false },
        contentAnalysis: { weight: 0.0625, completed: false },
        aiRecommendations: { weight: 0.10, completed: false }
    },
    aiModel: null
};


$(document).ready(function() {
    // Add notification container if not present
    if ($('#notificationContainer').length === 0) {
        $('body').append('<div id="notificationContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
    }
    
    // Initialize components
    UIManager.initialize(appState);
    DomainManager.initialize(appState);
    MetaTagsAnalysis.initialize();
    
    // Initialize AI recommendations
    AIRecommendations.initialize();

    // Form submission
    $('#seoAnalysisForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get the URL from the input
        appState.currentUrl = $('#websiteUrl').val().trim();
        
        // Validate URL
        if (!Utils.isValidUrl(appState.currentUrl)) {
            UIManager.showNotification('Please enter a valid URL with http:// or https://', 'danger');
            return;
        }
        
        // Add http:// if missing
        if (!/^https?:\/\//i.test(appState.currentUrl)) {
            appState.currentUrl = 'http://' + appState.currentUrl;
            $('#websiteUrl').val(appState.currentUrl);
        }
        
        // Flag this as a user-initiated analysis
        appState.isUserInitiatedAnalysis = true;
        
        // Reset progress
        UIManager.resetProgress(appState);
        
        // Show the progress section and hide results container first
        $('#analysisProgress').removeClass('d-none');
        $('#resultsContainer').addClass('d-none');
        
        // Reset all score badges
        $('#technicalScore, #metaTagsScore, #onpageScore, #offpageScore, #keywordsScore, #contentScore').text('-');
        
        // Start the analysis process
        AnalysisManager.startAnalysis(appState);
    });
    
    // Domain list item click
    $(document).on('click', '.domain-item', function(e) {
        e.preventDefault();
        
        // Get the domain ID
        const domainId = $(this).data('id');
        
        // Flag this as NOT a user-initiated analysis
        appState.isUserInitiatedAnalysis = false;
        
        // Load the domain's analysis results
        DomainManager.loadDomainResults(domainId, appState);
        
        // Update active state
        $('.domain-item').removeClass('active');
        $(this).addClass('active');
    });
    
    // Refresh domains list
    $('#refreshDomains').on('click', function(e) {
        e.preventDefault();
        DomainManager.refreshDomainsList(appState);
    });
    
    // Save results button


	// Save results button handler
    $('#saveResultsBtn').on('click', function() {
        // Show saving state
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        // Gather all analysis results
		const resultsData = {
			url: appState.currentUrl,
			technicalSeo: $('#technicalContent').data('results'),
			metaTagsAnalysis: $('#metaTagsContent').data('results'),
			onPageSeo: $('#onpageContent').data('results'),
			offPageSeo: $('#offpageContent').data('results'),
			keywordAnalysis: $('#keywordsContent').data('results'),
			contentAnalysis: $('#contentTabContent').data('results'),
			linkAnalysis: $('#linkAnalysisContent').data('results'),
			imageAnalysis: $('#imageAnalysisContent').data('results'),
			pageStructure: $('#pageStructureContent').data('results'),
			coreWebVitals: $('#coreWebVitalsContent').data('results'),
			competitor: $('#competitorContent').data('results'),
			schema: $('#schemaContent').data('results'),
			localSeo: $('#localSeoContent').data('results'),
			contentGap: $('#contentGapContent').data('results'),
			aiRecommendations: $('#aiRecommendationsContent').data('results')
		};
        
        // Debug log to console the data being saved
        console.log('Saving results data:', resultsData);
        
        ApiService.saveResults(resultsData)
            .done(function(response) {
                if (response.success) {
                    // Update button state
                    $('#saveResultsBtn').html('<i class="fas fa-check"></i> Saved!').removeClass('btn-success').addClass('btn-outline-success');
                    
                    // Set a timeout to revert the button
                    setTimeout(function() {
                        $('#saveResultsBtn').html('<i class="fas fa-save"></i> Save Analysis Results').removeClass('btn-outline-success').addClass('btn-success').prop('disabled', false);
                    }, 3000);
                    
                    // Update the domains list
                    DomainManager.refreshDomainsList();
                    
                    // After successful save, the analysis is no longer newly generated
                    appState.isUserInitiatedAnalysis = false;
                    
                    // Show notification
                    UIManager.showNotification('Analysis results saved successfully!', 'success');
                    
                    // Update app state with the website ID
                    appState.currentWebsiteId = response.websiteId;
                    
                    // Add "Check Again" option after saving
                    UIManager.addCheckAgainOption(appState.currentUrl, appState);
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
    });

    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Set up accordion behavior
    $(document).on('click', '.accordion-button', function() {
        // Close other accordion items
        $('.accordion-collapse').not($(this).attr('data-bs-target')).removeClass('show');
        $('.accordion-button').not(this).addClass('collapsed');
    });
    
    // Initial domains list refresh
    DomainManager.refreshDomainsList(appState);
    
    // Domain search functionality
    $('#domainSearchInput').on('input', function() {
        const searchTerm = $(this).val().trim();
        DomainManager.filterDomainsBySearch(searchTerm);
    });
    
    // Clear search button
    $('#clearDomainSearch').on('click', function() {
        $('#domainSearchInput').val('');
        DomainManager.filterDomainsBySearch('');
    });
	
	// Initialize Bootstrap components dynamically added to the DOM
    $(document).on('analysisCompleted', function() {
        // Ensure accordions are properly initialized
        $('.accordion-button').on('click', function() {
            const target = $(this).data('bs-target');
            $(target).collapse('toggle');
        });
		
        setTimeout(function() {
            if (appState.isUserInitiatedAnalysis) {
                console.log('FORCING save button to be visible');
                $('#saveResultsContainer').removeClass('d-none');
            } else {
                console.log('Analysis was not user initiated, keeping save button hidden');
            }
        }, 100);		
		
		
    });	
	
	
});