/**
 * analysis-manager.js - Manages the SEO analysis workflow
 */

const AnalysisManager = {
	
    // Configuration for API retries
    retryConfig: {
        maxRetries: 3,           // Maximum number of retry attempts
        retryDelay: 2000,        // Base delay in milliseconds
        backoffFactor: 1.5,      // Exponential backoff factor
        retryableStatusCodes: [408, 429, 500, 502, 503, 504] // HTTP status codes to retry
    },
    
    // Initialize step progress trackers
	stepProgress: {
		technicalSeo: { progress: 0, subSteps: ['crawlability', 'indexability', 'performance', 'mobile'] },
		metaTagsAnalysis: { progress: 0, subSteps: ['titles', 'descriptions', 'canonicals', 'og-tags'] },
		onPageSeo: { progress: 0, subSteps: ['titles', 'meta', 'headings', 'content', 'internal-links'] },
		offPageSeo: { progress: 0, subSteps: ['backlinks', 'authority', 'social-signals'] },
		linkAnalysis: { progress: 0, subSteps: ['internal', 'external', 'broken', 'anchor-text'] },
		imageAnalysis: { progress: 0, subSteps: ['alt-tags', 'dimensions', 'compression', 'lazy-loading'] },
		pageStructure: { progress: 0, subSteps: ['headings', 'semantics', 'content-ratio', 'mobile-layout'] },
		coreWebVitals: { progress: 0, subSteps: ['lcp', 'fid', 'cls', 'ttfb'] },
		schema: { progress: 0, subSteps: ['detection', 'validation', 'completeness'] },
		competitor: { progress: 0, subSteps: ['identification', 'comparison', 'gaps'] },
		localSeo: { progress: 0, subSteps: ['business-profile', 'citations', 'nap-consistency', 'local-pages'] },
		contentGap: { progress: 0, subSteps: ['topic-analysis', 'competitor-content', 'content-length', 'recommendations'] },
		keywordAnalysis: { progress: 0, subSteps: ['volume', 'ranking', 'difficulty', 'intent'] },
		contentAnalysis: { progress: 0, subSteps: ['quality', 'readability', 'structure', 'relevance'] },
		aiRecommendations: { progress: 0, subSteps: ['data-preparation', 'model-query', 'processing'] }
	},
	
	
    startAnalysis: function(appState) {
        // Reset all step progress trackers
        this.resetStepProgress();
        
        // Update progress status
        UIManager.updateProgressStatus(`Starting SEO analysis for ${appState.currentUrl}`);
        
        // Show results container but keep it empty
        $('#resultsContainer').removeClass('d-none');
        
        // Initialize summary section
        $('#summaryContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Analysis in progress...</p>
                <p class="text-muted small">Summary will be available once all analyses are completed</p>
            </div>
        `);
        
        // Run the analyses in sequence
        this.runTechnicalSeoAnalysis(appState);
    },
    
    /**
     * Reset all step progress trackers
     */
    resetStepProgress: function() {
        Object.keys(this.stepProgress).forEach(step => {
            this.stepProgress[step].progress = 0;
        });
    },
     updateStepProgress: function(step, subStep, appState) {
        if (!this.stepProgress[step]) return;
        
        const stepTracker = this.stepProgress[step];
        const subStepIndex = stepTracker.subSteps.indexOf(subStep);
        
        if (subStepIndex !== -1) {
            // Calculate progress as percentage based on sub-step position
            const increment = 100 / stepTracker.subSteps.length;
            stepTracker.progress = Math.min(100, (subStepIndex + 1) * increment);
            
            // Update the UI with detailed status
            UIManager.updateProgressStatus(`Running ${this.formatStepName(step)}: ${this.formatSubStepName(subStep)} (${Math.round(stepTracker.progress)}%)`);
            
            // Update the step icon with more detailed progress
            UIManager.updateStepDetailedProgress(step, stepTracker.progress);
        }
    },
    formatStepName: function(step) {
        return step
            .replace(/([A-Z])/g, ' $1') // Add spaces before capital letters
            .replace(/^./, str => str.toUpperCase()) // Capitalize first letter
            .trim();
    },	
    formatSubStepName: function(subStep) {
        return subStep
            .split('-')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    },

    makeApiCallWithRetry: function(apiCall, stepName, successCallback, failureCallback, retryCount = 0) {
        apiCall()
            .done(function(response) {
                if (response.success) {
                    successCallback(response);
                } else {
                    // Check if we should retry based on the error message
                    if (retryCount < AnalysisManager.retryConfig.maxRetries && 
                        (response.retryable || AnalysisManager.isRetryableError(response.message))) {
                        
                        // Calculate delay with exponential backoff
                        const delay = AnalysisManager.retryConfig.retryDelay * 
                                     Math.pow(AnalysisManager.retryConfig.backoffFactor, retryCount);
                        
                        // Update UI to show retry status
                        UIManager.updateProgressStatus(`${stepName} failed: ${response.message}. Retrying in ${Math.round(delay/1000)} seconds... (Attempt ${retryCount + 1}/${AnalysisManager.retryConfig.maxRetries})`);
                        
                        // Retry after delay
                        setTimeout(function() {
                            AnalysisManager.makeApiCallWithRetry(
                                apiCall, stepName, successCallback, failureCallback, retryCount + 1
                            );
                        }, delay);
                    } else {
                        // No more retries, call failure callback
                        failureCallback(response.message || 'Unknown error');
                    }
                }
            })
            .fail(function(xhr, status, error) {
                // Check if we should retry based on status code
                if (retryCount < AnalysisManager.retryConfig.maxRetries && 
                    (AnalysisManager.retryConfig.retryableStatusCodes.includes(xhr.status) || status === 'timeout')) {
                    
                    // Calculate delay with exponential backoff
                    const delay = AnalysisManager.retryConfig.retryDelay * 
                                 Math.pow(AnalysisManager.retryConfig.backoffFactor, retryCount);
                    
                    // Update UI to show retry status
                    UIManager.updateProgressStatus(`${stepName} failed with ${xhr.status} ${status}. Retrying in ${Math.round(delay/1000)} seconds... (Attempt ${retryCount + 1}/${AnalysisManager.retryConfig.maxRetries})`);
                    
                    // Retry after delay
                    setTimeout(function() {
                        AnalysisManager.makeApiCallWithRetry(
                            apiCall, stepName, successCallback, failureCallback, retryCount + 1
                        );
                    }, delay);
                } else {
                    // No more retries, call failure callback
                    failureCallback(`Server error: ${xhr.status} ${status}`);
                }
            });
    },
    isRetryableError: function(message) {
        const retryableMessages = [
            'timeout', 
            'connection', 
            'network', 
            'temporarily unavailable',
            'rate limit',
            'too many requests',
            'server error',
            'service unavailable'
        ];
        
        return retryableMessages.some(errMsg => 
            message.toLowerCase().includes(errMsg.toLowerCase())
        );
    },
    
    runTechnicalSeoAnalysis: function(appState) {
        UIManager.updateStepStatus('technicalSeo', 'in-progress');
        UIManager.updateProgressStatus('Running Technical SEO analysis...');
        
        // Show loading state in the Technical SEO section
        $('#technicalContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Running Technical SEO analysis...</p>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                         style="width: 0%" id="technicalSeoDetailProgress"></div>
                </div>
            </div>
        `);
        
        // Expand the Technical SEO accordion section
        if ($('#technicalHeading button').hasClass('collapsed')) {
            $('#technicalHeading button').click();
        }
        
        // Update progress for initial sub-step
        this.updateStepProgress('technicalSeo', 'crawlability', appState);
        
        // Make API call with retry mechanism
        this.makeApiCallWithRetry(
            // API call function
            function() {
                return ApiService.runTechnicalSeoAnalysis(appState.currentUrl);
            },
            
            // Step name for status messages
            "Technical SEO analysis",
            
            // Success callback
            function(response) {
                UIManager.updateStepStatus('technicalSeo', 'completed');
                appState.progressSteps.technicalSeo.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Store and display the results
                TechnicalSEO.displayResults(response.data);
                
                // Update the score badge
                const score = response.data.overall_score || calculateTechnicalScore(response.data);
                UIManager.updateScoreBadge('technical', score);
                
                // Continue with meta tags analysis
                AnalysisManager.runMetaTagsAnalysis(appState);
            },
            
            // Failure callback
            function(errorMessage) {
                UIManager.updateStepStatus('technicalSeo', 'failed');
                UIManager.updateProgressStatus('Technical SEO analysis failed: ' + errorMessage);
                
                // Display error in the Technical SEO section
                $('#technicalContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Technical SEO analysis failed: ${errorMessage}
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> We've moved on to the next analysis, but you may want to try again later.
                    </div>
                `);
                
                // Continue with meta tags analysis anyway
                AnalysisManager.runMetaTagsAnalysis(appState);
            }
        );
    },

    runMetaTagsAnalysis: function(appState) {
        UIManager.updateStepStatus('metaTagsAnalysis', 'in-progress');
        UIManager.updateProgressStatus('Analyzing Meta Tags...');
        
        // Show loading state in the Meta Tags section
        $('#metaTagsContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Analyzing Meta Tags...</p>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                         style="width: 0%" id="metaTagsDetailProgress"></div>
                </div>
            </div>
        `);
        
        // Expand the Meta Tags accordion section
        if ($('#metaTagsHeading button').hasClass('collapsed')) {
            $('#metaTagsHeading button').click();
        }
        
        // Update progress for initial sub-step
        this.updateStepProgress('metaTagsAnalysis', 'titles', appState);
        
        // Make API call with retry mechanism
        this.makeApiCallWithRetry(
            function() {
                return ApiService.runMetaTagsAnalysis(appState.currentUrl);
            },
            "Meta Tags analysis",
            function(response) {
                UIManager.updateStepStatus('metaTagsAnalysis', 'completed');
                appState.progressSteps.metaTagsAnalysis.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Store and display the results
                MetaTagsAnalysis.displayResults(response.data);
                
                // Update the score badge
                const score = response.data.overall_score || calculateMetaTagsScore(response.data);
                UIManager.updateScoreBadge('metaTags', score);
                
                // Continue with on-page SEO analysis
                AnalysisManager.runOnPageSeoAnalysis(appState);
            },
            function(errorMessage) {
                UIManager.updateStepStatus('metaTagsAnalysis', 'failed');
                UIManager.updateProgressStatus('Meta Tags analysis failed: ' + errorMessage);
                
                // Display error in the Meta Tags section
                $('#metaTagsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Meta Tags analysis failed: ${errorMessage}
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> We've moved on to the next analysis, but you may want to try again later.
                    </div>
                `);
                
                // Continue with on-page SEO analysis anyway
                AnalysisManager.runOnPageSeoAnalysis(appState);
            }
        );
    },

    runOnPageSeoAnalysis: function(appState) {
        UIManager.updateStepStatus('onPageSeo', 'in-progress');
        UIManager.updateProgressStatus('Running On-Page SEO analysis...');
        
        // Show loading state in the On-Page SEO section
        $('#onpageContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Running On-Page SEO analysis...</p>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                         style="width: 0%" id="onPageSeoDetailProgress"></div>
                </div>
            </div>
        `);
        
        // Expand the On-Page SEO accordion section
        if ($('#onpageHeading button').hasClass('collapsed')) {
            $('#onpageHeading button').click();
        }
        
        // Update progress for initial sub-step
        this.updateStepProgress('onPageSeo', 'titles', appState);
        
        // Make API call with retry mechanism
        this.makeApiCallWithRetry(
            function() {
                return ApiService.runOnPageSeoAnalysis(appState.currentUrl);
            },
            "On-Page SEO analysis",
            function(response) {
                // Update progress for next sub-steps
                AnalysisManager.updateStepProgress('onPageSeo', 'meta', appState);
                AnalysisManager.updateStepProgress('onPageSeo', 'headings', appState);
                AnalysisManager.updateStepProgress('onPageSeo', 'content', appState);
                AnalysisManager.updateStepProgress('onPageSeo', 'internal-links', appState);
                
                UIManager.updateStepStatus('onPageSeo', 'completed');
                appState.progressSteps.onPageSeo.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Store and display the results
                OnPageSEO.displayResults(response.data);
                
                // Update the score badge
                const score = response.data.overall_score || calculateOnPageScore(response.data);
                UIManager.updateScoreBadge('onpage', score);
                
                // Continue with off-page SEO analysis
                AnalysisManager.runOffPageSeoAnalysis(appState);
            },
            function(errorMessage) {
                UIManager.updateStepStatus('onPageSeo', 'failed');
                UIManager.updateProgressStatus('On-Page SEO analysis failed: ' + errorMessage);
                
                // Display error in the On-Page SEO section
                $('#onpageContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> On-Page SEO analysis failed: ${errorMessage}
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> We've moved on to the next analysis, but you may want to try again later.
                    </div>
                `);
                
                // Continue with off-page SEO analysis anyway
                AnalysisManager.runOffPageSeoAnalysis(appState);
            }
        );
    },
 
    
 runOffPageSeoAnalysis: function(appState) {
    UIManager.updateStepStatus('offPageSeo', 'in-progress');
    UIManager.updateProgressStatus('Running Off-Page SEO analysis...');
    
    // Show loading state in the Off-Page SEO section
    $('#offpageContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Off-Page SEO analysis...</p>
        </div>
    `);
    
    // Expand the Off-Page SEO accordion section
    if ($('#offpageHeading button').hasClass('collapsed')) {
        $('#offpageHeading button').click();
    }
    
    ApiService.runOffPageSeoAnalysis(appState.currentUrl)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('offPageSeo', 'completed');
                appState.progressSteps.offPageSeo.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Store and display the results
                OffPageSEO.displayResults(response.data);
                
                // Update the score badge
                const score = response.data.overall_score || calculateOffPageScore(response.data);
                UIManager.updateScoreBadge('offpage', score);
                
                // Continue with link analysis instead of keyword analysis
                AnalysisManager.runLinkAnalysis(appState);
            } else {
                UIManager.updateStepStatus('offPageSeo', 'failed');
                UIManager.updateProgressStatus('Off-Page SEO analysis failed: ' + response.message);
                
                // Display error in the Off-Page SEO section
                $('#offpageContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Off-Page SEO analysis failed: ${response.message}
                    </div>
                `);
                
                // Continue with link analysis anyway
                AnalysisManager.runLinkAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('offPageSeo', 'failed');
            UIManager.updateProgressStatus('Off-Page SEO analysis failed due to a server error');
            
            // Display error in the Off-Page SEO section
            $('#offpageContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Off-Page SEO analysis failed due to a server error
                </div>
            `);
            
            // Continue with link analysis anyway
            AnalysisManager.runLinkAnalysis(appState);
        });
},
	
// Add to the AnalysisManager object after other analysis functions
runLinkAnalysis: function(appState) {
    UIManager.updateStepStatus('linkAnalysis', 'in-progress');
    UIManager.updateProgressStatus('Running Link Analysis...');
    
    // Show loading state in the Link Analysis section
    $('#linkAnalysisContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Link Analysis...</p>
        </div>
    `);
    
    // Expand the Link Analysis accordion section
    if ($('#linkAnalysisHeading button').hasClass('collapsed')) {
        $('#linkAnalysisHeading button').click();
    }
    
    // Get max pages parameter (default to 10)
    const maxPages = 10;
    
    ApiService.runLinkAnalysis(appState.currentUrl, maxPages)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('linkAnalysis', 'completed');
                appState.progressSteps.linkAnalysis.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Trigger event for Link Analysis module to handle the display
                $(document).trigger('requestLinkAnalysis', [appState.currentUrl]);
                
                // Update the score badge
                const score = response.data.overall_score || 0;
                UIManager.updateScoreBadge('linkAnalysis', score);
                
                // Continue with the next analysis step
                AnalysisManager.runImageAnalysis(appState);
            } else {
                UIManager.updateStepStatus('linkAnalysis', 'failed');
                UIManager.updateProgressStatus('Link Analysis failed: ' + response.message);
                
                // Display error in the Link Analysis section
                $('#linkAnalysisContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Link Analysis failed: ${response.message}
                    </div>
                `);
                
                // Continue with the next analysis step anyway
                AnalysisManager.runImageAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('linkAnalysis', 'failed');
            UIManager.updateProgressStatus('Link Analysis failed due to a server error');
            
            // Display error in the Link Analysis section
            $('#linkAnalysisContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Link Analysis failed due to a server error
                </div>
            `);
            
            // Continue with the next analysis step anyway
            AnalysisManager.runImageAnalysis(appState);
        });
},	

runImageAnalysis: function(appState) {
    UIManager.updateStepStatus('imageAnalysis', 'in-progress');
    UIManager.updateProgressStatus('Running Image Analysis...');
    
    // Show loading state in the Image Analysis section
    $('#imageAnalysisContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Image Analysis...</p>
        </div>
    `);
    
    // Expand the Image Analysis accordion section
    if ($('#imageAnalysisHeading button').hasClass('collapsed')) {
        $('#imageAnalysisHeading button').click();
    }
    
    // Get max pages parameter (default to 5)
    const maxPages = 5;
    
    ApiService.runImageAnalysis(appState.currentUrl, maxPages)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('imageAnalysis', 'completed');
                appState.progressSteps.imageAnalysis.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Display results using the Image Analysis module
                ImageAnalysis.displayResults(response.data);
                
                // Update the score badge
                const score = response.data.overall_score || 0;
                UIManager.updateScoreBadge('imageAnalysis', score);
                
                // Trigger event for other modules that might need this data
                $(document).trigger('imageAnalysisCompleted', [response.data]);
                
                // Continue with the next analysis step
                AnalysisManager.runPageStructureAnalysis(appState);
            } else {
                UIManager.updateStepStatus('imageAnalysis', 'failed');
                UIManager.updateProgressStatus('Image Analysis failed: ' + response.message);
                
                // Display error in the Image Analysis section
                $('#imageAnalysisContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Image Analysis failed: ${response.message}
                    </div>
                `);
                
                // Continue with the next analysis step anyway
                AnalysisManager.runPageStructureAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('imageAnalysis', 'failed');
            UIManager.updateProgressStatus('Image Analysis failed due to a server error');
            
            // Display error in the Image Analysis section
            $('#imageAnalysisContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Image Analysis failed due to a server error
                </div>
            `);
            
            // Continue with the next analysis step anyway
            AnalysisManager.runPageStructureAnalysis(appState);
        });
},	
    
runPageStructureAnalysis: function(appState) {
    UIManager.updateStepStatus('pageStructure', 'in-progress');
    UIManager.updateProgressStatus('Running Page Structure Analysis...');
    
    // Show loading state in the Page Structure section
    $('#pageStructureContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Page Structure Analysis...</p>
        </div>
    `);
    
    // Expand the Page Structure accordion section
    if ($('#pageStructureHeading button').hasClass('collapsed')) {
        $('#pageStructureHeading button').click();
    }
    
    // Get max pages parameter (default to 5)
    const maxPages = 5;
    
    ApiService.runPageStructureAnalysis(appState.currentUrl, maxPages)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('pageStructure', 'completed');
                appState.progressSteps.pageStructure.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Trigger event for Page Structure module to handle the display
                $(document).trigger('requestPageStructureAnalysis', [appState.currentUrl]);
                
                // Store data for reference
                $('#pageStructureContent').data('results', response.data);
                
                // Update the score badge
                const score = response.data.overall_score || 0;
                UIManager.updateScoreBadge('pageStructure', score);
                
                // CHANGE: Continue with Core Web Vitals analysis instead of keyword analysis
                AnalysisManager.runCoreWebVitalsAnalysis(appState);
            } else {
                UIManager.updateStepStatus('pageStructure', 'failed');
                UIManager.updateProgressStatus('Page Structure Analysis failed: ' + response.message);
                
                // Display error in the Page Structure section
                $('#pageStructureContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Page Structure Analysis failed: ${response.message}
                    </div>
                `);
                
                // CHANGE: Continue with Core Web Vitals analysis instead of keyword analysis
                AnalysisManager.runCoreWebVitalsAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('pageStructure', 'failed');
            UIManager.updateProgressStatus('Page Structure Analysis failed due to a server error');
            
            // Display error in the Page Structure section
            $('#pageStructureContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Page Structure Analysis failed due to a server error
                </div>
            `);
            
            // CHANGE: Continue with Core Web Vitals analysis instead of keyword analysis
            AnalysisManager.runCoreWebVitalsAnalysis(appState);
        });
},	 


/**
 * Run Core Web Vitals analysis
 * @param {Object} appState Application state
 */
runCoreWebVitalsAnalysis: function(appState) {
    UIManager.updateStepStatus('coreWebVitals', 'in-progress');
    UIManager.updateProgressStatus('Running Core Web Vitals analysis...');
    
    // Show loading state in the Core Web Vitals section
    $('#coreWebVitalsContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Core Web Vitals analysis...</p>
        </div>
    `);
    
    // Expand the Core Web Vitals accordion section
    if ($('#coreWebVitalsHeading button').hasClass('collapsed')) {
        $('#coreWebVitalsHeading button').click();
    }
    
    ApiService.runPerformanceAnalysis(appState.currentUrl)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('coreWebVitals', 'completed');
                appState.progressSteps.coreWebVitals.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Trigger event for Core Web Vitals module to handle the display
                $(document).trigger('requestCoreWebVitalsAnalysis', [appState.currentUrl]);
                
                // Store data for reference
                $('#coreWebVitalsContent').data('results', response.data);
                
                // Update the score badge
                const score = response.data.overall_score || 0;
                UIManager.updateScoreBadge('coreWebVitals', score);
                
                // Continue with the next analysis step
                AnalysisManager.runSchemaAnalysis(appState);
            } else {
                UIManager.updateStepStatus('coreWebVitals', 'failed');
                UIManager.updateProgressStatus('Core Web Vitals analysis failed: ' + response.message);
                
                // Display error in the Core Web Vitals section
                $('#coreWebVitalsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Core Web Vitals analysis failed: ${response.message}
                    </div>
                `);
                
                // Continue with the next analysis step anyway
                AnalysisManager.runSchemaAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('coreWebVitals', 'failed');
            UIManager.updateProgressStatus('Core Web Vitals analysis failed due to a server error');
            
            // Display error in the Core Web Vitals section
            $('#coreWebVitalsContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Core Web Vitals analysis failed due to a server error
                </div>
            `);
            
            // Continue with the next analysis step anyway
            AnalysisManager.runSchemaAnalysis(appState);
        });
},


/**
 * Run Schema Markup analysis
 * @param {Object} appState Application state
 */
runSchemaAnalysis: function(appState) {
    UIManager.updateStepStatus('schema', 'in-progress');
    UIManager.updateProgressStatus('Running Schema Markup analysis...');
    
    // Show loading state in the Schema Markup section
    $('#schemaContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Schema Markup analysis...</p>
        </div>
    `);
    
    // Expand the Schema Markup accordion section
    if ($('#schemaHeading button').hasClass('collapsed')) {
        $('#schemaHeading button').click();
    }
    
    ApiService.runSchemaAnalysis(appState.currentUrl)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('schema', 'completed');
                appState.progressSteps.schema.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Trigger event for Schema Markup module to handle the display
                $(document).trigger('requestSchemaAnalysis', [appState.currentUrl]);
                
                // Store data for reference
                $('#schemaContent').data('results', response.data);
                
                // Update the score badge
                const score = response.data.overall_score || 0;
                UIManager.updateScoreBadge('schema', score);
                
                // Continue with the next analysis step
                AnalysisManager.runCompetitorAnalysis(appState);
            } else {
                UIManager.updateStepStatus('schema', 'failed');
                UIManager.updateProgressStatus('Schema Markup analysis failed: ' + response.message);
                
                // Display error in the Schema Markup section
                $('#schemaContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Schema Markup analysis failed: ${response.message}
                    </div>
                `);
                
                // Continue with the next analysis step anyway
                AnalysisManager.runCompetitorAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('schema', 'failed');
            UIManager.updateProgressStatus('Schema Markup analysis failed due to a server error');
            
            // Display error in the Schema Markup section
            $('#schemaContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Schema Markup analysis failed due to a server error
                </div>
            `);
            
            // Continue with the next analysis step anyway
            AnalysisManager.runCompetitorAnalysis(appState);
        });
},

/**
 * Run Competitor Analysis
 * @param {Object} appState Application state
 */
runCompetitorAnalysis: function(appState) {
    UIManager.updateStepStatus('competitor', 'in-progress');
    UIManager.updateProgressStatus('Running Competitor Analysis...');
    
    // Show loading state in the Competitor Analysis section
    $('#competitorContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Competitor Analysis... This may take a moment.</p>
        </div>
    `);
    
    // Expand the Competitor Analysis accordion section
    if ($('#competitorHeading button').hasClass('collapsed')) {
        $('#competitorHeading button').click();
    }
    
    // Get competitors if available
    let competitors = [];
    if (window.CompetitorAnalysis && window.CompetitorAnalysis.competitors) {
        competitors = window.CompetitorAnalysis.competitors;
    }
    
    ApiService.runCompetitorAnalysis(appState.currentUrl, competitors)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('competitor', 'completed');
                appState.progressSteps.competitor.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Trigger event for Competitor Analysis module to handle the display
                $(document).trigger('requestCompetitorAnalysis', [appState.currentUrl]);
                
                // Store data for reference
                $('#competitorContent').data('results', response.data);
                
                // Update the score badge
                const score = response.data.overall_score || 0;
                UIManager.updateScoreBadge('competitor', score);
                
                // Complete the analysis since this is the last step
                AnalysisManager.runLocalSeoAnalysis(appState);
            } else {
                UIManager.updateStepStatus('competitor', 'failed');
                UIManager.updateProgressStatus('Competitor Analysis failed: ' + response.message);
                
                // Display error in the Competitor Analysis section
                $('#competitorContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Competitor Analysis failed: ${response.message}
                    </div>
                `);
                
                // Complete the analysis anyway
                AnalysisManager.runLocalSeoAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('competitor', 'failed');
            UIManager.updateProgressStatus('Competitor Analysis failed due to a server error');
            
            // Display error in the Competitor Analysis section
            $('#competitorContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Competitor Analysis failed due to a server error
                </div>
            `);
            
            // Complete the analysis anyway
            AnalysisManager.runLocalSeoAnalysis(appState);
        });
},





/**
 * Run Local SEO analysis
 * @param {Object} appState Application state
 */
runLocalSeoAnalysis: function(appState) {
    UIManager.updateStepStatus('localSeo', 'in-progress');
    UIManager.updateProgressStatus('Running Local SEO analysis...');
    
    // Show loading state in the Local SEO section
    $('#localSeoContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Local SEO analysis...</p>
        </div>
    `);
    
    // Expand the Local SEO accordion section
    if ($('#localSeoHeading button').hasClass('collapsed')) {
        $('#localSeoHeading button').click();
    }
    
    // Update progress for initial sub-step
    this.updateStepProgress('localSeo', 'business-profile', appState);
    
    ApiService.runLocalSeoAnalysis(appState.currentUrl)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('localSeo', 'completed');
                appState.progressSteps.localSeo.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Update progress for sub-steps
                AnalysisManager.updateStepProgress('localSeo', 'citations', appState);
                AnalysisManager.updateStepProgress('localSeo', 'nap-consistency', appState);
                AnalysisManager.updateStepProgress('localSeo', 'local-pages', appState);
                
                // Display results using the Local SEO module
                LocalSEO.displayResults(response.data);
                
                // Update the score badge
                const score = response.data.overall_score || 0;
                UIManager.updateScoreBadge('localSeo', score);
                
                // Continue with Content Gap analysis
                AnalysisManager.runContentGapAnalysis(appState);
            } else {
                UIManager.updateStepStatus('localSeo', 'failed');
                UIManager.updateProgressStatus('Local SEO analysis failed: ' + response.message);
                
                // Display error in the Local SEO section
                $('#localSeoContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Local SEO analysis failed: ${response.message}
                    </div>
                `);
                
                // Continue with the next analysis step anyway
                AnalysisManager.runContentGapAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('localSeo', 'failed');
            UIManager.updateProgressStatus('Local SEO analysis failed due to a server error');
            
            // Display error in the Local SEO section
            $('#localSeoContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Local SEO analysis failed due to a server error
                </div>
            `);
            
            // Continue with the next analysis step anyway
            AnalysisManager.runContentGapAnalysis(appState);
        });
},


/**
 * Run Content Gap analysis
 * @param {Object} appState Application state
 */
runContentGapAnalysis: function(appState) {
    UIManager.updateStepStatus('contentGap', 'in-progress');
    UIManager.updateProgressStatus('Running Content Gap analysis...');
    
    // Show loading state in the Content Gap section
    $('#contentGapContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Running Content Gap analysis...</p>
        </div>
    `);
    
    // Expand the Content Gap accordion section
    if ($('#contentGapHeading button').hasClass('collapsed')) {
        $('#contentGapHeading button').click();
    }
    
    // Update progress for initial sub-step
    this.updateStepProgress('contentGap', 'topic-analysis', appState);
    // Get competitors if available from competitor analysis
    let competitors = [];
    if (window.CompetitorAnalysis && window.CompetitorAnalysis.competitors) {
        competitors = window.CompetitorAnalysis.competitors;
    }
    
    ApiService.runContentGapAnalysis(appState.currentUrl, competitors)
        .done(function(response) {
            if (response.success) {
                UIManager.updateStepStatus('contentGap', 'completed');
                appState.progressSteps.contentGap.completed = true;
                UIManager.updateOverallProgress(appState);
                
                // Update progress for sub-steps
                AnalysisManager.updateStepProgress('contentGap', 'competitor-content', appState);
                AnalysisManager.updateStepProgress('contentGap', 'content-length', appState);
                AnalysisManager.updateStepProgress('contentGap', 'recommendations', appState);
                
                // Display results using the Content Gap module
                ContentGap.displayResults(response.data);
                
                // Update the score badge
                const score = response.data.overall_score || 0;
                UIManager.updateScoreBadge('contentGap', score);
                
                // Continue with keyword analysis
                AnalysisManager.runKeywordAnalysis(appState);
            } else {
                UIManager.updateStepStatus('contentGap', 'failed');
                UIManager.updateProgressStatus('Content Gap analysis failed: ' + response.message);
                
                // Display error in the Content Gap section
                $('#contentGapContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Content Gap analysis failed: ${response.message}
                    </div>
                `);
                
                // Continue with the next analysis step anyway
                AnalysisManager.runKeywordAnalysis(appState);
            }
        })
        .fail(function() {
            UIManager.updateStepStatus('contentGap', 'failed');
            UIManager.updateProgressStatus('Content Gap analysis failed due to a server error');
            
            // Display error in the Content Gap section
            $('#contentGapContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Content Gap analysis failed due to a server error
                </div>
            `);
            
            // Continue with the next analysis step anyway
            AnalysisManager.runKeywordAnalysis(appState);
        });
},





    runKeywordAnalysis: function(appState) {
        UIManager.updateStepStatus('keywordAnalysis', 'in-progress');
        UIManager.updateProgressStatus('Running Keyword Analysis...');
        
        // Show loading state in the Keywords section
        $('#keywordsContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Running Keyword Analysis...</p>
            </div>
        `);
        
        // Expand the Keywords accordion section
        if ($('#keywordsHeading button').hasClass('collapsed')) {
            $('#keywordsHeading button').click();
        }
        
        ApiService.runKeywordAnalysis(appState.currentUrl)
            .done(function(response) {
                if (response.success) {
                    UIManager.updateStepStatus('keywordAnalysis', 'completed');
                    appState.progressSteps.keywordAnalysis.completed = true;
                    UIManager.updateOverallProgress(appState);
                    
                    // Store and display the results
                    KeywordAnalysis.displayResults(response.data);
                    
                    // Update the score badge
                    const score = response.data.overall_score || calculateKeywordScore(response.data);
                    UIManager.updateScoreBadge('keywords', score);
                    
                    // Continue with content analysis
                    AnalysisManager.runContentAnalysis(appState);
                } else {
                    UIManager.updateStepStatus('keywordAnalysis', 'failed');
                    UIManager.updateProgressStatus('Keyword analysis failed: ' + response.message);
                    
                    // Display error in the Keywords section
                    $('#keywordsContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Keyword analysis failed: ${response.message}
                        </div>
                    `);
                    
                    // Continue with content analysis anyway
                    AnalysisManager.runContentAnalysis(appState);
                }
            })
            .fail(function() {
                UIManager.updateStepStatus('keywordAnalysis', 'failed');
                UIManager.updateProgressStatus('Keyword analysis failed due to a server error');
                
                // Display error in the Keywords section
                $('#keywordsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Keyword analysis failed due to a server error
                    </div>
                `);
                
                // Continue with content analysis anyway
                AnalysisManager.runContentAnalysis(appState);
            });
    },
    
    runContentAnalysis: function(appState) {
        UIManager.updateStepStatus('contentAnalysis', 'in-progress');
        UIManager.updateProgressStatus('Running Content Analysis...');
        
        // Show loading state in the Content section
        $('#contentTabContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Running Content Analysis...</p>
            </div>
        `);
        
        // Expand the Content accordion section
        if ($('#contentHeading button').hasClass('collapsed')) {
            $('#contentHeading button').click();
        }
        
        ApiService.runContentAnalysis(appState.currentUrl)
            .done(function(response) {
                if (response.success) {
                    UIManager.updateStepStatus('contentAnalysis', 'completed');
                    appState.progressSteps.contentAnalysis.completed = true;
                    UIManager.updateOverallProgress(appState);
                    
                    // Store and display the results
                    ContentAnalysis.displayResults(response.data);
                    
                    // Update the score badge
                    const score = response.data.overall_score || calculateContentScore(response.data);
                    UIManager.updateScoreBadge('content', score);
                    
                    // Complete the analysis
                    AnalysisManager.completeAnalysis(appState);
                } else {
                    UIManager.updateStepStatus('contentAnalysis', 'failed');
                    UIManager.updateProgressStatus('Content analysis failed: ' + response.message);
                    
                    // Display error in the Content section
                    $('#contentTabContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Content analysis failed: ${response.message}
                        </div>
                    `);
                    
                    // Complete the analysis anyway
                    AnalysisManager.completeAnalysis(appState);
                }
            })
            .fail(function() {
                UIManager.updateStepStatus('contentAnalysis', 'failed');
                UIManager.updateProgressStatus('Content analysis failed due to a server error');
                
                // Display error in the Content section
                $('#contentTabContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Content analysis failed due to a server error
                    </div>
                `);
                
                // Complete the analysis anyway
                AnalysisManager.completeAnalysis(appState);
            });
    },
      
    generateAiRecommendations: function(appState) {
		// In the completeAnalysis function, when gathering data:
		const seoData = {
			url: appState.currentUrl,
			technicalSeo: $('#technicalContent').data('results'),
			metaTagsAnalysis: $('#metaTagsContent').data('results'),
			onPageSeo: $('#onpageContent').data('results'),
			offPageSeo: $('#offpageContent').data('results'),
			linkAnalysis: $('#linkAnalysisContent').data('results'),
			imageAnalysis: $('#imageAnalysisContent').data('results'), // Add this line
			keywordAnalysis: $('#keywordsContent').data('results'),
			contentAnalysis: $('#contentTabContent').data('results'),
			aiRecommendations: $('#aiRecommendationsContent').data('results')
		};
        
        UIManager.updateProgressStatus('Generating AI recommendations...');
        
        // Show loading state in the AI recommendations section
        $('#aiRecommendationsContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading AI recommendations...</span>
                </div>
                <p class="mt-2">Generating intelligent SEO recommendations...</p>
                <p class="text-muted small">This might take a minute as we analyze your data</p>
            </div>
        `);
        
        ApiService.generateAiRecommendations(seoData, appState.aiModel)
            .done(function(response) {
                if (response.success) {
                    AIRecommendations.displayRecommendations(response.data);
                    // Mark AI recommendations step as completed
                    appState.progressSteps.aiRecommendations.completed = true;
                    UIManager.updateStepStatus('aiRecommendations', 'completed');
                    UIManager.updateOverallProgress(appState);
                    UIManager.updateProgressStatus('AI recommendations generated successfully!');
                } else {
                    // Mark AI recommendations step as completed
                    appState.progressSteps.aiRecommendations.completed = true;
                    UIManager.updateStepStatus('aiRecommendations', 'completed');
                    UIManager.updateOverallProgress(appState);					
                    
                    UIManager.updateProgressStatus('Failed to generate AI recommendations: ' + response.message);
                    
                    $('#aiRecommendationsContent').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Failed to generate AI recommendations: ${response.message}
                        </div>
                        <p>Try selecting a different AI model or try again later.</p>
                    `);
                }
            })
            .fail(function() {
                // Mark AI recommendations step as completed
                appState.progressSteps.aiRecommendations.completed = true;
                UIManager.updateStepStatus('aiRecommendations', 'completed');
                UIManager.updateOverallProgress(appState);				
                
                UIManager.updateProgressStatus('Failed to generate AI recommendations due to a server error');
                $('#aiRecommendationsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> Failed to generate AI recommendations due to a server error.
                    </div>
                    <p>Please try again later or select a different AI model.</p>
                `);
            });
    },
    
	
	updateSaveButtonVisibility: function(appState) {
		console.log('Updating save button visibility. User initiated:', appState.isUserInitiatedAnalysis);
		
		if (appState.isUserInitiatedAnalysis) {
			$('#saveResultsContainer').removeClass('d-none');
			console.log('Save button should be visible now');
		} else {
			$('#saveResultsContainer').addClass('d-none');
			console.log('Save button should be hidden now');
		}
	},	
	
completeAnalysis: function(appState) {
    UIManager.updateProgressStatus('SEO analysis completed successfully!');
    // Show save button only if this is a user-initiated analysis
    UIManager.updateSaveButtonVisibility(appState);
    
    // Make sure Visualization is defined before trying to use it
    if (typeof window.Visualization !== 'undefined' && typeof window.Visualization.generateSummary === 'function') {
        // Generate summary data
        try {
            window.Visualization.generateSummary();
        } catch (error) {
            console.error('Error generating summary:', error);
            $('#summaryContent').html(`
                <div class="alert alert-warning">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                    Could not generate summary. An error occurred.
                </div>
                <p>You can still view individual analysis sections below.</p>
            `);
        }
    } else {
        console.error('Visualization object or generateSummary method is not defined');
        $('#summaryContent').html(`
            <div class="alert alert-warning">
                <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                Could not generate summary. Visualization module not loaded properly.
            </div>
            <p>You can still view individual analysis sections below.</p>
        `);
    }
    
    // Rest of the function as before...
    $('.accordion-collapse').removeClass('show');
    $('.accordion-button').addClass('collapsed');
    
    $(document).trigger('analysisCompleted');
    
    // If an AI model is selected and this is a user-initiated analysis, generate AI recommendations
    if (appState.aiModel && appState.isUserInitiatedAnalysis) {
        // Mark this step as in-progress before generating recommendations
        UIManager.updateStepStatus('aiRecommendations', 'in-progress');
        AnalysisManager.generateAiRecommendations(appState);
    } else {
        // Skip AI recommendations if no model is selected or not a user-initiated analysis
        appState.progressSteps.aiRecommendations.completed = true;
        UIManager.updateOverallProgress(appState);
    }
}


}

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
