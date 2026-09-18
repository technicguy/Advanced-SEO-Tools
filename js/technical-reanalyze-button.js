/**
 * technical-reanalyze-button.js - Adds a Reanalyze button specifically for Technical SEO Analysis
 */

const TechnicalReanalyzeButton = {
    /**
     * Initialize the Technical SEO reanalyze button functionality
     */
    initialize: function() {
        // Add button when analysis is completed
        $(document).on('analysisCompleted', () => {
            this.addReanalyzeButton();
        });
        
        // Also add when analysis is loaded from database
        $(document).on('analysisLoaded', () => {
            this.addReanalyzeButton();
        });
        
        // Check periodically if the button should be added to the score display
        setInterval(() => {
            // Only add if it doesn't exist yet and summary content is not empty
            if ($('#technicalReanalyzeBtn').length === 0 && 
                $('.score-gauge').length > 0) {
                this.addReanalyzeButton();
            }
            
            // Also add button to Technical SEO section header if needed
            if ($('#technicalReanalyzeBtnHeader').length === 0 &&
                $('#technicalHeading').length > 0) {
                this.addTechnicalSectionButton();
            }
        }, 2000);
    },
    
    /**
     * Add the Reanalyze button below the overall score
     */
    addReanalyzeButton: function() {
        // Find the score value container
        const $scoreValue = $('.score-value');
        
        if ($scoreValue.length > 0 && $('#technicalReanalyzeBtn').length === 0) {
            // Create the button
            const $button = $(`
                <button id="technicalReanalyzeBtn" class="btn btn-primary mt-3 mb-2">
                    <i class="fas fa-sync-alt me-2"></i> Reanalyze Technical SEO
                </button>
            `);
            
            // Find a good place to add the button
            let $targetContainer = $scoreValue.closest('.card-body');
            
            // If score is in a specific container with a score class, add after that
            const $scoreContainer = $scoreValue.closest('.score-gauge');
            if ($scoreContainer.length > 0) {
                $scoreContainer.after($button);
            } 
            // Otherwise try adding after the score value itself
            else if ($scoreValue.length > 0) {
                $scoreValue.after($button);
            }
            // Last resort - add to the end of the card body
            else if ($targetContainer.length > 0) {
                $targetContainer.append($button);
            } 
            // Fallback - just add to the summary content
            else {
                $('#summaryContent').append($button);
            }
            
            // Add click handler
            $('#technicalReanalyzeBtn').on('click', this.handleTechnicalReanalyze);
            
            console.log('Technical Reanalyze button added below overall score');
        }
    },
    
    /**
     * Add a button to the Technical SEO section header
     */
    addTechnicalSectionButton: function() {
        // Find the Technical SEO header
        const $header = $('#technicalHeading button');
        
        if ($header.length > 0 && $('#technicalReanalyzeBtnHeader').length === 0) {
            // Create button with different styling for the header
            const $button = $(`
                <button id="technicalReanalyzeBtnHeader" class="btn btn-sm btn-outline-primary ms-2">
                    <i class="fas fa-sync-alt"></i> Reanalyze
                </button>
            `);
            
            // Insert into the header
            // Find the badge first
            const $badge = $header.find('.badge');
            if ($badge.length > 0) {
                // Insert after the badge
                $badge.after($button);
            } else {
                // Insert at the end of the header
                $header.append($button);
            }
            
            // Add click handler (same as the main button)
            $('#technicalReanalyzeBtnHeader').on('click', this.handleTechnicalReanalyze);
            
            console.log('Technical Reanalyze button added to section header');
        }
    },
    
    /**
     * Handle the technical reanalyze button click
     * This specifically only reanalyzes the Technical SEO section
     */
    handleTechnicalReanalyze: function(e) {
        e.preventDefault();
        e.stopPropagation(); // Important to prevent accordion toggling
        
        // Get the current website URL
        const websiteUrl = $('#websiteUrl').val();
        
        if (!websiteUrl) {
            // Show notification if no URL is entered
            if (typeof UIManager !== 'undefined' && UIManager.showNotification) {
                UIManager.showNotification('Please enter a website URL first', 'warning');
            } else {
                alert('Please enter a website URL first');
            }
            return;
        }
        
        // Update button state
        const $button = $(this);
        const originalText = $button.html();
        $button.html('<i class="fas fa-spinner fa-spin me-2"></i> Reanalyzing...').prop('disabled', true);
        
        // Reset Technical SEO section
        $('#technicalContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Reanalyzing Technical SEO...</p>
            </div>
        `);
        
        // Make sure the Technical SEO section is expanded
        if ($('#technicalCollapse').length > 0 && !$('#technicalCollapse').hasClass('show')) {
            $('#technicalHeading button').click();
        }
        
        // Run just the technical SEO analysis
        if (typeof appState !== 'undefined' && typeof AnalysisManager !== 'undefined') {
            // Just update the Technical SEO step
            UIManager.updateStepStatus('technicalSeo', 'in-progress');
            
            // Reset the technical score badge
            $('#technicalScore').text('-');
            
            // Make sure the URL is in the app state
            appState.currentUrl = websiteUrl;
            
            // Run just the Technical SEO analysis
            AnalysisManager.runTechnicalSeoAnalysis(appState);
            
            // Restore button after a delay
            setTimeout(() => {
                $button.html(originalText).prop('disabled', false);
            }, 3000);
        } else {
            // Fallback if AnalysisManager is not available
            // Make a direct API call
            $.ajax({
                url: 'api/technical-seo-api.php',
                type: 'POST',
                data: { url: websiteUrl },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Display the results
                        if (typeof TechnicalSEO !== 'undefined' && TechnicalSEO.displayResults) {
                            TechnicalSEO.displayResults(response.data);
                        } else {
                            // Basic fallback display
                            const score = response.data.overall_score || 0;
                            $('#technicalContent').html(`
                                <div class="alert alert-success">
                                    <h5>Technical SEO Analysis Complete</h5>
                                    <p>Overall Score: ${score}/100</p>
                                </div>
                                <div class="results-container">
                                    ${JSON.stringify(response.data, null, 2)}
                                </div>
                            `);
                        }
                        
                        // Update the score badge
                        const score = response.data.overall_score || 0;
                        if ($('#technicalScore').length > 0) {
                            $('#technicalScore').text(score);
                        }
                    } else {
                        // Show error
                        $('#technicalContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> Technical SEO analysis failed: ${response.message}
                            </div>
                        `);
                    }
                },
                error: function() {
                    // Show error
                    $('#technicalContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Technical SEO analysis failed due to a server error
                        </div>
                    `);
                },
                complete: function() {
                    // Restore button
                    $button.html(originalText).prop('disabled', false);
                }
            });
        }
    }
};

// Initialize the technical reanalyze button functionality
$(document).ready(function() {
    TechnicalReanalyzeButton.initialize();
});