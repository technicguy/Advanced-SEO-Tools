/**
 * ui-manager.js - Manages UI updates and interactions
 */

const UIManager = {
    initialize: function(appState) {
        // Any initialization code for the UI
        console.log('UI Manager initialized');
        
        // Add event handlers for UI interactions
        this.setupEventHandlers();
        
        // Update progress steps display in HTML to include Meta Tags Analysis
        this.updateProgressStepsDisplay();
    },
    
    setupEventHandlers: function() {
        // Add global UI event handlers here
        // For example, tooltip initialization
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        // Handle responsive sidebar toggle if needed
        $('#sidebarToggle').on('click', function() {
            $('.sidebar').toggleClass('d-none d-md-block');
        });
        
        // Setup accordion behavior to update the icons when expanding/collapsing
        $('.accordion-button').on('click', function() {
            const isCollapsed = $(this).hasClass('collapsed');
            const icon = $(this).find('i.fas').first();
            
            if (isCollapsed) {
                icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            } else {
                icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }
        });
    },
    
updateStepDetailedProgress: function(step, progress) {
    // Update the UI to show detailed progress
    $(`#${step}DetailProgress`).css('width', progress + '%');
    
    // Update the step status if not already in-progress or completed
    const stepStatus = $(`.step-item[data-step="${step}"]`).attr('class');
    if (!stepStatus.includes('completed') && !stepStatus.includes('failed')) {
        this.updateStepStatus(step, 'in-progress');
    }
},	
	
updateProgressStepsDisplay: function() {
    // Update the progress steps in the UI to include all analysis steps
    const progressStepsHtml = `
        <div class="step-item" data-step="technicalSeo">
            <span class="step-icon"><i class="fas fa-cogs"></i></span>
            <span class="step-text">Technical SEO Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="metaTagsAnalysis">
            <span class="step-icon"><i class="fas fa-tags"></i></span>
            <span class="step-text">Meta Tags Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="onPageSeo">
            <span class="step-icon"><i class="fas fa-file-alt"></i></span>
            <span class="step-text">On-Page SEO Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="offPageSeo">
            <span class="step-icon"><i class="fas fa-link"></i></span>
            <span class="step-text">Off-Page SEO Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="linkAnalysis">
            <span class="step-icon"><i class="fas fa-link"></i></span>
            <span class="step-text">Link Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="imageAnalysis">
            <span class="step-icon"><i class="fas fa-images"></i></span>
            <span class="step-text">Image Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="pageStructure">
            <span class="step-icon"><i class="fas fa-code"></i></span>
            <span class="step-text">Page Structure Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="coreWebVitals">
            <span class="step-icon"><i class="fas fa-tachometer-alt"></i></span>
            <span class="step-text">Core Web Vitals Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="schema">
            <span class="step-icon"><i class="fas fa-code"></i></span>
            <span class="step-text">Schema Markup Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="keywordAnalysis">
            <span class="step-icon"><i class="fas fa-key"></i></span>
            <span class="step-text">Keyword Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="contentAnalysis">
            <span class="step-icon"><i class="fas fa-align-left"></i></span>
            <span class="step-text">Content Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
        <div class="step-item" data-step="competitor">
            <span class="step-icon"><i class="fas fa-chart-line"></i></span>
            <span class="step-text">Competitor Analysis</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
    <div class="step-item" data-step="localSeo">
        <span class="step-icon"><i class="fas fa-map-marker-alt"></i></span>
        <span class="step-text">Local SEO Analysis</span>
        <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
    </div>
    <div class="step-item" data-step="contentGap">
        <span class="step-icon"><i class="fas fa-puzzle-piece"></i></span>
        <span class="step-text">Content Gap Analysis</span>
        <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
    </div>		
		
        <div class="step-item" data-step="aiRecommendations">
            <span class="step-icon"><i class="fas fa-robot"></i></span>
            <span class="step-text">AI Recommendations</span>
            <span class="step-status"><i class="fas fa-clock text-muted"></i></span>
        </div>
    `;
    
    // Update the progress steps container if it exists
    if ($('#progressSteps').length > 0) {
        $('#progressSteps').html(progressStepsHtml);
    }
},
    
    resetProgress: function(appState) {
        appState.overallProgress = 0;
        
        // Reset progress steps
        Object.keys(appState.progressSteps).forEach(step => {
            appState.progressSteps[step].completed = false;
            this.updateStepStatus(step, 'pending');
        });
        
        // Reset progress bar
        this.updateProgressBar(appState);
        
        // Reset progress status
        this.updateProgressStatus('Preparing SEO analysis...');
        
        // Reset results in the accordion sections
        $('#summaryContent, #technicalContent, #metaTagsContent, #onpageContent, #offpageContent, #keywordsContent, #contentTabContent, #aiRecommendationsContent').empty();
        
        // Reset score badges
        $('#technicalScore, #metaTagsScore, #onpageScore, #offpageScore, #keywordsScore, #contentScore').text('-');
        
        // Hide save button
        $('#saveResultsContainer').addClass('d-none');
    },
    
    updateStepStatus: function(step, status) {
        const stepElement = $(`.step-item[data-step="${step}"]`);
        
        // If the step element doesn't exist, skip
        if (stepElement.length === 0) return;
        
        // Remove existing status classes
        stepElement.removeClass('in-progress completed failed');
        
        // Add the new status class
        stepElement.addClass(status);
        
        // Update the status icon
        const statusIcon = stepElement.find('.step-status i');
        statusIcon.removeClass('fa-clock fa-check-circle fa-times-circle text-muted text-success text-danger fa-spinner fa-spin text-warning');
        
        switch (status) {
            case 'in-progress':
                statusIcon.addClass('fa-spinner fa-spin text-warning');
                break;
            case 'completed':
                statusIcon.addClass('fa-check-circle text-success');
                break;
            case 'failed':
                statusIcon.addClass('fa-times-circle text-danger');
                break;
            default:
                statusIcon.addClass('fa-clock text-muted');
        }
    },
    
    updateOverallProgress: function(appState) {
        let totalProgress = 0;
        let totalWeight = 0;
        
        // Calculate weighted progress
        Object.keys(appState.progressSteps).forEach(step => {
            totalWeight += appState.progressSteps[step].weight;
            if (appState.progressSteps[step].completed) {
                totalProgress += appState.progressSteps[step].weight;
            }
        });
        
        // Calculate percentage
        appState.overallProgress = Math.round((totalProgress / totalWeight) * 100);
        
        // Update progress bar
        this.updateProgressBar(appState);
    },
    
    updateProgressBar: function(appState) {
        const progressBar = $('#progressBar');
        progressBar.css('width', appState.overallProgress + '%');
        progressBar.attr('aria-valuenow', appState.overallProgress);
        
        // Update progress bar color based on progress
        progressBar.removeClass('bg-danger bg-warning bg-info bg-success');
        
        if (appState.overallProgress < 25) {
            progressBar.addClass('bg-danger');
        } else if (appState.overallProgress < 50) {
            progressBar.addClass('bg-warning');
        } else if (appState.overallProgress < 75) {
            progressBar.addClass('bg-info');
        } else {
            progressBar.addClass('bg-success');
        }
    },
    
    updateProgressStatus: function(status) {
        $('#progressStatus').html(`<p class="mb-0"><i class="fas fa-info-circle"></i> ${status}</p>`);
    },
    
    handleApiFailure: function(message) {
        $('#progressStatus').html(`
            <div class="alert alert-danger">
                <strong><i class="fas fa-times-circle"></i> Error:</strong> 
                ${message}
            </div>
            <p>Possible reasons:</p>
            <ul>
                <li>The website is blocking automated requests</li>
                <li>The website requires JavaScript to display content</li>
                <li>The website has security measures that prevent content analysis</li>
                <li>The URL might be invalid or inaccessible</li>
            </ul>
            <p>Try another website or check if the URL is correct.</p>
        `);
        
        // Update the progress indicator
        $('#progressBar').addClass('bg-danger').css('width', '100%');
        
        // Hide save button and results container
        $('#saveResultsContainer').addClass('d-none');
        $('#resultsContainer').addClass('d-none');
    },
    
  addCheckAgainOption: function(url, appState) {
    // Update the form and add a "Check Again" button
    const checkAgainBtn = $('<button>', {
        class: 'btn btn-outline-primary ms-2',
        id: 'checkAgainBtn',
        html: '<i class="fas fa-sync"></i> Check Again'
    });
    
    // Remove existing button if it exists
    $('#checkAgainBtn').remove();
    
    // Add the new button
    $('#analyzeSeoBtn').after(checkAgainBtn);
    
    // Add click event
    $('#checkAgainBtn').on('click', function(e) {
        e.preventDefault();
        
        // Flag this as a user-initiated analysis
        appState.isUserInitiatedAnalysis = true;
        
        // Start new analysis with the same URL
        appState.currentUrl = url;
        UIManager.resetProgress(appState);
        $('#analysisProgress').removeClass('d-none');
        $('#resultsContainer').addClass('d-none');
        $('#saveResultsContainer').addClass('d-none');
        
        // Trigger the full analysis
        if (typeof AnalysisManager !== 'undefined' && typeof AnalysisManager.startAnalysis === 'function') {
            AnalysisManager.startAnalysis(appState);
        } else {
            console.error('AnalysisManager not available to start analysis');
            // Fallback to showing an error message
            UIManager.updateProgressStatus('Error: Unable to start analysis. Please refresh the page and try again.');
        }
    });
},
    
    showLoadingSpinner: function(element, message = 'Loading...') {
        $(element).html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">${message}</p>
            </div>
        `);
    },
    
    showError: function(element, message) {
        $(element).html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> ${message}
            </div>
        `);
    },
    
    // Show success notification
    showNotification: function(message, type = 'success') {
        // Create notification element
        const notification = $(`
            <div class="alert alert-${type} alert-dismissible fade show notification-toast" role="alert">
                ${type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>'}
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        // Add to the page
        $('#notificationContainer').append(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.alert('close');
        }, 5000);
    },
    
    // Update score badges in accordion headers
    updateScoreBadge: function(section, score) {
        const badge = $(`#${section}Score`);
        if (badge.length === 0) return;
        
        badge.text(score + '/100');
        
        // Update badge color based on score
        badge.removeClass('bg-danger bg-warning bg-primary bg-success');
        
        if (score < 50) {
            badge.addClass('bg-danger');
        } else if (score < 70) {
            badge.addClass('bg-warning');
        } else if (score < 90) {
            badge.addClass('bg-primary');
        } else {
            badge.addClass('bg-success');
        }
    },
    
    // New methods for accordion-based UI
    
    // Display a specific section's content
updateSectionContent: function(section, content) {
        $(`#${section}Content`).html(content);
        
        // MODIFIED: Don't toggle accordion since we want all sections expanded
        // Just make sure the section is visible
        const accordionButton = $(`#${section}Heading button`);
        const accordionCollapse = $(`#${section}Collapse`);
        
        // Ensure the section is expanded
        if (accordionButton.hasClass('collapsed')) {
            accordionButton.removeClass('collapsed');
            accordionCollapse.addClass('show');
        }
    },
    
    // Create a section card with title, score, and content
    createSectionCard: function(title, score, content, icon = 'fas fa-info-circle') {
        const scoreClass = score < 50 ? 'text-danger' : 
                          score < 70 ? 'text-warning' : 
                          score < 90 ? 'text-primary' : 'text-success';
        
        return `
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="${icon} me-2"></i> ${title}</h5>
                        <span class="badge ${scoreClass} bg-light border border-${scoreClass.replace('text-', '')}">${score}/100</span>
                    </div>
                </div>
                <div class="card-body">
                    ${content}
                </div>
            </div>
        `;
    },
    
    
     // Create a status item with icon based on status
    createStatusItem: function(status, message) {
        let icon, textClass;
        
        switch(status.toLowerCase()) {
            case 'good':
            case 'passed':
                icon = 'fa-check-circle';
                textClass = 'text-success';
                break;
            case 'warning':
                icon = 'fa-exclamation-triangle';
                textClass = 'text-warning';
                break;
            case 'error':
            case 'failed':
                icon = 'fa-times-circle';
                textClass = 'text-danger';
                break;
            default:
                icon = 'fa-info-circle';
                textClass = 'text-info';
        }
        
        return `
            <div class="d-flex align-items-start mb-2">
                <div class="me-2">
                    <i class="fas ${icon} ${textClass} fa-lg"></i>
                </div>
                <div>
                    ${message}
                </div>
            </div>
        `;
    },
    
    // Control the save button visibility based on app state
    updateSaveButtonVisibility: function(appState) {
        console.log('Updating save button visibility. User initiated:', appState.isUserInitiatedAnalysis);
        
        if (appState.isUserInitiatedAnalysis) {
            $('#saveResultsContainer').removeClass('d-none');
            console.log('Save button should be visible now');
        } else {
            $('#saveResultsContainer').addClass('d-none');
            console.log('Save button should be hidden now');
        }
    }
	
	
};
