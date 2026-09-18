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