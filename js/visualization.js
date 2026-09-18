/**
 * visualization.js - Data visualization functionality
 * ENHANCED VERSION: Added interactive charts and comparison views
 */

const Visualization = {
    // Store chart references for updates
    charts: {},
    
    // Store historical data for comparisons
    historicalData: {},
    
    /**
     * Initialize visualization module
     * Loads historical data if available
     */
    initialize: function() {
        // Load any saved historical data from localStorage
        this.loadHistoricalData();
        
        // Add event listeners for comparison toggles
        $(document).on('change', '.comparison-toggle', function() {
            const chartId = $(this).data('chart');
            Visualization.toggleComparison(chartId);
        });
        
        // Add event listener for chart type switching
        $(document).on('click', '.chart-type-switcher', function(e) {
            e.preventDefault();
            
            const chartId = $(this).data('chart');
            const chartType = $(this).data('type');
            
            Visualization.switchChartType(chartId, chartType);
            
            // Update active state
            $(this).siblings('.chart-type-switcher').removeClass('active');
            $(this).addClass('active');
        });
    },
    
    /**
     * Load historical data from localStorage
     */
    loadHistoricalData: function() {
        try {
            const savedData = localStorage.getItem('seo_historical_data');
            if (savedData) {
                this.historicalData = JSON.parse(savedData);
            }
        } catch (error) {
            console.error('Failed to load historical data:', error);
            this.historicalData = {};
        }
    },
    
    /**
     * Save current data as historical data
     * @param {string} url - URL of the analyzed website
     * @param {Object} data - Analysis data to save
     */
    saveHistoricalData: function(url, data) {
        if (!url || !data) return;
        
        try {
            // Format URL as key (remove protocol and trailing slash)
            const urlKey = url.replace(/^https?:\/\//, '').replace(/\/$/, '');
            
            // Create entry with timestamp if it doesn't exist
            if (!this.historicalData[urlKey]) {
                this.historicalData[urlKey] = [];
            }
            
            // Add current data with timestamp
            this.historicalData[urlKey].push({
                timestamp: new Date().toISOString(),
                data: data
            });
            
            // Limit to last 5 analyses
            if (this.historicalData[urlKey].length > 5) {
                this.historicalData[urlKey] = this.historicalData[urlKey].slice(-5);
            }
            
            // Save to localStorage
            localStorage.setItem('seo_historical_data', JSON.stringify(this.historicalData));
        } catch (error) {
            console.error('Failed to save historical data:', error);
        }
    },
    
    /**
     * Get historical data for a URL
     * @param {string} url - URL to get historical data for
     * @return {Array} Historical data entries
     */
    getHistoricalData: function(url) {
        if (!url) return [];
        
        try {
            // Format URL as key (remove protocol and trailing slash)
            const urlKey = url.replace(/^https?:\/\//, '').replace(/\/$/, '');
            
            return this.historicalData[urlKey] || [];
        } catch (error) {
            console.error('Failed to get historical data:', error);
            return [];
        }
    },
    
    /**
     * Toggle between current data and historical comparison
     * @param {string} chartId - ID of the chart to toggle
     */
    toggleComparison: function(chartId) {
        const chart = this.charts[chartId];
        if (!chart) return;
        
        const isComparisonActive = $(`#comparison-toggle-${chartId}`).prop('checked');
        
        if (isComparisonActive) {
            // Get URL from app state
            const url = typeof appState !== 'undefined' ? appState.currentUrl : null;
            if (!url) return;
            
            // Get historical data
            const historical = this.getHistoricalData(url);
            if (historical.length === 0) {
                $(`#comparison-message-${chartId}`).html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No historical data available for comparison.
                    </div>
                `);
                return;
            }
            
            // Get the most recent historical entry
            const previousData = historical[historical.length - 2]?.data;
            if (!previousData) {
                $(`#comparison-message-${chartId}`).html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> This is your first analysis. No previous data to compare with.
                    </div>
                `);
                return;
            }
            
            // Update chart with comparison data
            this.updateChartWithComparison(chartId, previousData);
            
            // Show comparison date
            const previousDate = new Date(historical[historical.length - 2].timestamp).toLocaleDateString();
            $(`#comparison-message-${chartId}`).html(`
                <div class="alert alert-success">
                    <i class="fas fa-history"></i> Showing comparison with data from ${previousDate}
                </div>
            `);
        } else {
            // Switch back to current data only
            this.updateChartWithoutComparison(chartId);
            $(`#comparison-message-${chartId}`).empty();
        }
    },
    
    /**
     * Update chart to show comparison with historical data
     * @param {string} chartId - ID of the chart to update
     * @param {Object} previousData - Previous analysis data
     */
    updateChartWithComparison: function(chartId, previousData) {
        const chart = this.charts[chartId];
        if (!chart) return;
        
        // Different handling based on chart type
        if (chart.config.type === 'radar') {
            this.updateRadarChartComparison(chart, previousData);
        } else if (chart.config.type === 'bar') {
            this.updateBarChartComparison(chart, previousData);
        } else if (chart.config.type === 'line') {
            this.updateLineChartComparison(chart, previousData);
        } else if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
            this.updateDoughnutChartComparison(chart, previousData);
        }
    },
    
    /**
     * Update radar chart with comparison data
     * @param {Object} chart - Chart.js chart object
     * @param {Object} previousData - Previous analysis data
     */
    updateRadarChartComparison: function(chart, previousData) {
        // Create a copy of the current datasets
        const currentDatasets = [...chart.data.datasets];
        
        // Add previous data as new dataset
        if (previousData) {
            currentDatasets.forEach(dataset => {
                // Create a dataset for the previous data with translucent color
                const previousDatasetColor = dataset.borderColor.replace('0.8', '0.3');
                
                chart.data.datasets.push({
                    label: dataset.label + ' (Previous)',
                    data: this.extractDataFromPreviousAnalysis(previousData, dataset.label),
                    backgroundColor: 'rgba(200, 200, 200, 0.2)',
                    borderColor: previousDatasetColor,
                    borderWidth: 1,
                    borderDash: [5, 5],
                    pointBackgroundColor: previousDatasetColor,
                    pointRadius: 3
                });
            });
        }
        
        chart.update();
    },
    
    /**
     * Update bar chart with comparison data
     * @param {Object} chart - Chart.js chart object
     * @param {Object} previousData - Previous analysis data
     */
    updateBarChartComparison: function(chart, previousData) {
        // Clone current datasets
        const currentData = chart.data.datasets[0].data.slice();
        
        // Extract previous data
        const previousValues = this.extractDataFromPreviousAnalysis(previousData, chart.data.datasets[0].label);
        
        // Create a grouped bar chart
        chart.data.datasets = [
            {
                label: 'Current',
                data: currentData,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Previous',
                data: previousValues,
                backgroundColor: 'rgba(200, 200, 200, 0.5)',
                borderColor: 'rgba(200, 200, 200, 1)',
                borderWidth: 1
            }
        ];
        
        // Update options to support grouped bars
        if (chart.config.options.scales && chart.config.options.scales.x) {
            chart.config.options.scales.x.stacked = false;
        }
        if (chart.config.options.scales && chart.config.options.scales.y) {
            chart.config.options.scales.y.stacked = false;
        }
        
        chart.update();
    },
    
    /**
     * Update line chart with comparison data
     * @param {Object} chart - Chart.js chart object
     * @param {Object} previousData - Previous analysis data
     */
    updateLineChartComparison: function(chart, previousData) {
        // Clone current datasets
        const currentData = chart.data.datasets[0].data.slice();
        
        // Extract previous data
        const previousValues = this.extractDataFromPreviousAnalysis(previousData, chart.data.datasets[0].label);
        
        // Update the chart with both datasets
        chart.data.datasets = [
            {
                label: 'Current',
                data: currentData,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                fill: true
            },
            {
                label: 'Previous',
                data: previousValues,
                borderColor: 'rgba(200, 200, 200, 1)',
                backgroundColor: 'rgba(200, 200, 200, 0.1)',
                borderDash: [5, 5],
                fill: true
            }
        ];
        
        chart.update();
    },
    
    /**
     * Update doughnut/pie chart with comparison data
     * @param {Object} chart - Chart.js chart object
     * @param {Object} previousData - Previous analysis data
     */
    updateDoughnutChartComparison: function(chart, previousData) {
        // For doughnut charts, create a separate comparison chart
        const chartContainer = chart.canvas.parentNode;
        
        // Create a comparison container if it doesn't exist
        let comparisonContainer = $(chartContainer).next('.comparison-chart-container');
        if (comparisonContainer.length === 0) {
            comparisonContainer = $('<div class="comparison-chart-container mt-4 mb-4"></div>');
            $(chartContainer).after(comparisonContainer);
        }
        
        // Extract previous data
        const previousValues = this.extractDataFromPreviousAnalysis(previousData, chart.data.datasets[0].label);
        
        // Create comparison chart
        comparisonContainer.html(`
            <h6 class="text-center text-muted mb-2">Previous Analysis</h6>
            <canvas id="${chart.canvas.id}-comparison"></canvas>
        `);
        
        const comparisonCanvas = document.getElementById(`${chart.canvas.id}-comparison`);
        this.charts[`${chart.canvas.id}-comparison`] = new Chart(comparisonCanvas, {
            type: chart.config.type,
            data: {
                labels: chart.data.labels,
                datasets: [{
                    data: previousValues,
                    backgroundColor: chart.data.datasets[0].backgroundColor,
                    borderColor: chart.data.datasets[0].borderColor,
                    borderWidth: chart.data.datasets[0].borderWidth
                }]
            },
            options: chart.config.options
        });
    },
    
    /**
     * Update chart to remove comparison
     * @param {string} chartId - ID of the chart to update
     */
    updateChartWithoutComparison: function(chartId) {
        const chart = this.charts[chartId];
        if (!chart) return;
        
        // Keep only the first dataset (current data)
        if (chart.data.datasets.length > 1) {
            chart.data.datasets = [chart.data.datasets[0]];
        }
        
        // Update options back to default if needed
        if (chart.config.options.scales && chart.config.options.scales.x) {
            chart.config.options.scales.x.stacked = true;
        }
        if (chart.config.options.scales && chart.config.options.scales.y) {
            chart.config.options.scales.y.stacked = true;
        }
        
        chart.update();
        
        // Remove any comparison charts
        $(`#${chartId}-comparison`).parents('.comparison-chart-container').remove();
    },
    
    /**
     * Extract data from previous analysis based on dataset label
     * @param {Object} previousData - Previous analysis data
     * @param {string} datasetLabel - Label of the dataset to extract
     * @return {Array} Data values
     */
    extractDataFromPreviousAnalysis: function(previousData, datasetLabel) {
        if (!previousData) return [];
        
        // Extract data based on the dataset label
        if (datasetLabel.includes('Technical SEO')) {
            return this.extractTechnicalSeoData(previousData);
        } else if (datasetLabel.includes('On-Page SEO')) {
            return this.extractOnPageSeoData(previousData);
        } else if (datasetLabel.includes('Off-Page SEO')) {
            return this.extractOffPageSeoData(previousData);
        } else if (datasetLabel.includes('Content')) {
            return this.extractContentData(previousData);
        } else if (datasetLabel.includes('Link')) {
            return this.extractLinkData(previousData);
        } else if (datasetLabel.includes('Image')) {
            return this.extractImageData(previousData);
        } else if (datasetLabel.includes('Page Structure')) {
            return this.extractPageStructureData(previousData);
        }
        
        // Default: return empty array
        return [];
    },
    
    /**
     * Extract technical SEO data from previous analysis
     * @param {Object} previousData - Previous analysis data
     * @return {Array} Technical SEO data values
     */
    extractTechnicalSeoData: function(previousData) {
        if (!previousData.technicalSeo) return [];
        
        return [
            previousData.technicalSeo.crawlability_score || 0,
            previousData.technicalSeo.indexability_score || 0,
            previousData.technicalSeo.site_speed_score || 0,
            previousData.technicalSeo.mobile_friendly_score || 0
        ];
    },
    
    /**
     * Extract on-page SEO data from previous analysis
     * @param {Object} previousData - Previous analysis data
     * @return {Array} On-page SEO data values
     */
    extractOnPageSeoData: function(previousData) {
        if (!previousData.onPageSeo) return [];
        
        return [
            previousData.onPageSeo.title_tags_score || 0,
            previousData.onPageSeo.meta_desc_score || 0,
            previousData.onPageSeo.header_tags_score || 0,
            previousData.onPageSeo.content_score || 0,
            previousData.onPageSeo.image_optimization_score || 0,
            previousData.onPageSeo.internal_linking_score || 0,
            previousData.onPageSeo.url_structure_score || 0
        ];
    },
    
    /**
     * Extract off-page SEO data from previous analysis
     * @param {Object} previousData - Previous analysis data
     * @return {Array} Off-page SEO data values
     */
    extractOffPageSeoData: function(previousData) {
        if (!previousData.offPageSeo) return [];
        
        return [
            previousData.offPageSeo.backlink_score || 0,
            previousData.offPageSeo.domain_authority || 0,
            previousData.offPageSeo.social_media_score || 0
        ];
    },
    
    /**
     * Extract content data from previous analysis
     * @param {Object} previousData - Previous analysis data
     * @return {Array} Content data values
     */
    extractContentData: function(previousData) {
        if (!previousData.contentAnalysis) return [];
        
        // Typically, content scores might include readability, length, quality
        return [
            previousData.contentAnalysis.avg_readability || 0,
            previousData.contentAnalysis.avg_word_count / 10 || 0 // Scaled for chart visibility
        ];
    },
    
    /**
     * Extract link data from previous analysis
     * @param {Object} previousData - Previous analysis data
     * @return {Array} Link data values
     */
    extractLinkData: function(previousData) {
        if (!previousData.linkAnalysis) return [];
        
        return [
            previousData.linkAnalysis.score_components?.internal_linking_score || 0,
            previousData.linkAnalysis.score_components?.external_linking_score || 0,
            previousData.linkAnalysis.score_components?.broken_links_score || 0,
            previousData.linkAnalysis.score_components?.link_quality_score || 0
        ];
    },
    
    /**
     * Extract image data from previous analysis
     * @param {Object} previousData - Previous analysis data
     * @return {Array} Image data values
     */
    extractImageData: function(previousData) {
        if (!previousData.imageAnalysis) return [];
        
        return [
            previousData.imageAnalysis.score_components?.alt_text_score || 0,
            previousData.imageAnalysis.score_components?.dimensions_score || 0,
            previousData.imageAnalysis.score_components?.lazy_loading_score || 0,
            previousData.imageAnalysis.score_components?.responsive_image_score || 0,
            previousData.imageAnalysis.score_components?.optimization_score || 0,
            previousData.imageAnalysis.score_components?.modern_format_score || 0
        ];
    },
    
    /**
     * Extract page structure data from previous analysis
     * @param {Object} previousData - Previous analysis data
     * @return {Array} Page structure data values
     */
    extractPageStructureData: function(previousData) {
        if (!previousData.pageStructure) return [];
        
        return [
            previousData.pageStructure.heading_structure_score || 0,
            previousData.pageStructure.semantic_html_score || 0,
            previousData.pageStructure.content_html_ratio_score || 0,
            previousData.pageStructure.schema_markup_score || 0
        ];
    },
    
    /**
     * Switch chart type (e.g., from radar to bar)
     * @param {string} chartId - ID of the chart to switch
     * @param {string} newType - New chart type
     */
    switchChartType: function(chartId, newType) {
        const chart = this.charts[chartId];
        if (!chart) return;
        
        // Preserve the current data
        const data = chart.data;
        const options = chart.options;
        
        // Destroy the current chart
        chart.destroy();
        
        // Adjust options for the new chart type
        const newOptions = this.getOptionsForChartType(newType, options);
        
        // Create a new chart with the same data but different type
        this.charts[chartId] = new Chart(document.getElementById(chartId), {
            type: newType,
            data: data,
            options: newOptions
        });
    },
    
    /**
     * Get appropriate options for a chart type
     * @param {string} chartType - Type of chart
     * @param {Object} currentOptions - Current chart options
     * @return {Object} Adjusted options for the chart type
     */
    getOptionsForChartType: function(chartType, currentOptions) {
        const baseOptions = { ...currentOptions };
        
        // Add type-specific options
        switch (chartType) {
            case 'radar':
                baseOptions.scales = {
                    r: {
                        min: 0,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            showLabelBackdrop: false
                        }
                    }
                };
                break;
                
            case 'bar':
                baseOptions.scales = {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Score'
                        }
                    }
                };
                break;
                
            case 'line':
                baseOptions.scales = {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Score'
                        }
                    }
                };
                baseOptions.elements = {
                    line: {
                        tension: 0.4 // Add some curve to the lines
                    }
                };
                break;
                
            case 'doughnut':
            case 'pie':
                // Remove scales for pie/doughnut charts
                delete baseOptions.scales;
                baseOptions.cutout = chartType === 'doughnut' ? '70%' : 0;
                break;
        }
        
        return baseOptions;
    },
    
    /**
     * Generate summary dashboard with enhanced visualizations
     */
    generateSummary: function() {
        // Get data from all sections
        const technicalData = $('#technicalContent').data('results');
        const metaTagsData = $('#metaTagsContent').data('results');
        const onPageData = $('#onpageContent').data('results');
        const offPageData = $('#offpageContent').data('results');
        const keywordData = $('#keywordsContent').data('results');
        const contentData = $('#contentTabContent').data('results');
        const linkAnalysisData = $('#linkAnalysisContent').data('results');
        const imageAnalysisData = $('#imageAnalysisContent').data('results');
        const pageStructureData = $('#pageStructureContent').data('results');
        const coreWebVitalsData = $('#coreWebVitalsContent').data('results');
        const schemaData = $('#schemaContent').data('results');
        const competitorData = $('#competitorContent').data('results');
            
        // Check if we have enough data to generate a summary
        const hasData = technicalData || metaTagsData || onPageData || offPageData || 
                        keywordData || contentData || linkAnalysisData || imageAnalysisData || 
                        pageStructureData || coreWebVitalsData || schemaData || competitorData;
            
        if (!hasData) {
            $('#summaryContent').html(`
                <div class="alert alert-warning">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                    Not enough data available to generate a summary. Some parts of the analysis may have failed.
                </div>
                <p>Try analyzing a different website or check if the URL is correct.</p>
            `);
            return;
        }


        // Make the Visualization object available globally
        window.Visualization = Visualization;
            
        // Calculate technical SEO score
        let technicalScore = 0;
        let technicalComponents = 0;
        
        if (technicalData) {
            const components = [
                'crawlability_score', 
                'indexability_score', 
                'site_speed_score', 
                'mobile_friendly_score'
            ];
            
            components.forEach(component => {
                if (technicalData[component] !== undefined) {
                    technicalScore += parseInt(technicalData[component]);
                    technicalComponents++;
                }
            });
            
            technicalScore = technicalComponents > 0 ? Math.round(technicalScore / technicalComponents) : 0;
        }
        
        // Calculate meta tags score
        let metaTagsScore = 0;
        let metaTagsComponents = 0;
        
        if (metaTagsData) {
            const metaOverallScore = metaTagsData.overall_score || 0;
            if (metaOverallScore > 0) {
                metaTagsScore = metaOverallScore;
                metaTagsComponents = 1;
            } else {
                // Try to calculate from components
                const components = [
                    'title_tags_score', 
                    'meta_description_score', 
                    'canonical_tags_score', 
                    'og_tags_score'
                ];
                
                components.forEach(component => {
                    if (metaTagsData[component] !== undefined) {
                        metaTagsScore += parseInt(metaTagsData[component]);
                        metaTagsComponents++;
                    }
                });
                
                metaTagsScore = metaTagsComponents > 0 ? Math.round(metaTagsScore / metaTagsComponents) : 0;
            }
        }
        
        // Calculate on-page SEO score
        let onPageScore = 0;
        let onPageComponents = 0;
        
        if (onPageData) {
            const components = [
                'title_tags_score', 
                'meta_desc_score', 
                'header_tags_score', 
                'content_score', 
                'image_optimization_score', 
                'internal_linking_score', 
                'url_structure_score'
            ];
            
            components.forEach(component => {
                if (onPageData[component] !== undefined) {
                    onPageScore += parseInt(onPageData[component]);
                    onPageComponents++;
                }
            });
            
            onPageScore = onPageComponents > 0 ? Math.round(onPageScore / onPageComponents) : 0;
        }
        
        // Calculate off-page SEO score
        let offPageScore = 0;
        let offPageComponents = 0;
        
        if (offPageData) {
            const components = [
                'backlink_score', 
                'domain_authority', 
                'social_media_score'
            ];
            
            components.forEach(component => {
                if (offPageData[component] !== undefined) {
                    offPageScore += parseInt(offPageData[component]);
                    offPageComponents++;
                }
            });
            
            offPageScore = offPageComponents > 0 ? Math.round(offPageScore / onPageComponents) : 0;
        }
        
        // Calculate Link score        
        // Get link analysis data properly
        let linkAnalysisScore = 0;
        if (linkAnalysisData && linkAnalysisData.overall_score) {
            linkAnalysisScore = linkAnalysisData.overall_score;
        } else if (linkAnalysisData && linkAnalysisData.score_components) {
            const components = linkAnalysisData.score_components;
            linkAnalysisScore = Math.round(
                (components.internal_linking_score || 0) * 0.4 + 
                (components.external_linking_score || 0) * 0.2 + 
                (components.broken_links_score || 0) * 0.3 + 
                (components.link_quality_score || 0) * 0.1
            );
        }   
                
        // Calculate Image score    
        // Get image analysis data properly
        let imageAnalysisScore = 0;
        if (imageAnalysisData && imageAnalysisData.overall_score) {
            // Use the overall score if available
            imageAnalysisScore = imageAnalysisData.overall_score;
        } else if (imageAnalysisData && imageAnalysisData.score_components) {
            // Calculate from component scores if overall score is not available
            const components = imageAnalysisData.score_components;
            
            // Apply weights to different components based on their importance
            imageAnalysisScore = Math.round(
                (components.alt_text_score || 0) * 0.25 + 
                (components.dimensions_score || 0) * 0.15 + 
                (components.lazy_loading_score || 0) * 0.15 + 
                (components.responsive_image_score || 0) * 0.15 + 
                (components.optimization_score || 0) * 0.20 + 
                (components.modern_format_score || 0) * 0.10
            );
        }
        
        // Calculate Page Structure score
        let pageStructureScore = 0;
        if (pageStructureData && pageStructureData.overall_score) {
            pageStructureScore = pageStructureData.overall_score;
        } else if (pageStructureData) {
            // Try to calculate from components
            let componentSum = 0;
            let componentCount = 0;
            
            if (pageStructureData.heading_structure_score) {
                componentSum += pageStructureData.heading_structure_score;
                componentCount++;
            }
            
            if (pageStructureData.semantic_html_score) {
                componentSum += pageStructureData.semantic_html_score;
                componentCount++;
            }
            
            if (pageStructureData.content_html_ratio_score) {
                componentSum += pageStructureData.content_html_ratio_score;
                componentCount++;
            }
            
            if (pageStructureData.schema_markup_score) {
                componentSum += pageStructureData.schema_markup_score;
                componentCount++;
            }
            
            pageStructureScore = componentCount > 0 ? Math.round(componentSum / componentCount) : 0;
        }
        
        // Calculate Core Web Vitals score
        let coreWebVitalsScore = 0;
        if (coreWebVitalsData && coreWebVitalsData.overall_score) {
            coreWebVitalsScore = coreWebVitalsData.overall_score;
        } else if (coreWebVitalsData && coreWebVitalsData.metrics) {
            // Try to calculate from metrics
            let metricSum = 0;
            let metricCount = 0;
            
            const metrics = ['lcp', 'fid', 'cls', 'ttfb'];
            metrics.forEach(metric => {
                if (coreWebVitalsData.metrics[metric] && coreWebVitalsData.metrics[metric].score) {
                    metricSum += coreWebVitalsData.metrics[metric].score;
                    metricCount++;
                }
            });
            
            coreWebVitalsScore = metricCount > 0 ? Math.round(metricSum / metricCount) : 0;
        }
        
        // Calculate Schema score
        let schemaScore = 0;
        if (schemaData && schemaData.overall_score) {
            schemaScore = schemaData.overall_score;
        }
        
        // Calculate competitor analysis score
        let competitorScore = 0;
        if (competitorData && competitorData.overall_score) {
            competitorScore = competitorData.overall_score;
        }
        
        // Calculate content score
        let contentScore = 0;
        
        if (contentData) {
            const avgReadability = contentData.avg_readability || 0;
            const avgWordCount = contentData.avg_word_count || 0;
            
            // Calculate readability component (scale of 0-100)
            const readabilityComponent = Math.min(100, avgReadability);
            
            // Calculate word count component (scale of 0-100)
            // Consider 1000+ words as ideal (100 score), scale linearly below that
            const wordCountComponent = Math.min(100, (avgWordCount / 1000) * 100);
            
            // Average the components
            contentScore = Math.round((readabilityComponent + wordCountComponent) / 2);
        }
        
        // Calculate keyword score
        let keywordScore = 50; // Default score
        
        if (keywordData && keywordData.keywords && keywordData.keywords.length > 0) {
            // Calculate average ranking position
            let totalRanking = 0;
            let keywordsWithRanking = 0;
            
            keywordData.keywords.forEach(keyword => {
                if (keyword.current_ranking > 0) {
                    totalRanking += keyword.current_ranking;
                    keywordsWithRanking++;
                }
            });
            
            const avgRanking = keywordsWithRanking > 0 ? totalRanking / keywordsWithRanking : 0;
            
            // Convert to score (1st position = 100, 100th position = 0)
            if (avgRanking > 0) {
                keywordScore = Math.max(0, 100 - avgRanking);
            }
        }
        
        // Calculate weighted overall score
        const scores = [];
        
        if (technicalScore > 0) scores.push({ score: technicalScore, weight: 0.15, name: 'Technical SEO' });
        if (metaTagsScore > 0) scores.push({ score: metaTagsScore, weight: 0.05, name: 'Meta Tags' });
        if (onPageScore > 0) scores.push({ score: onPageScore, weight: 0.10, name: 'On-Page SEO' });
        if (offPageScore > 0) scores.push({ score: offPageScore, weight: 0.10, name: 'Off-Page SEO' });
        if (contentScore > 0) scores.push({ score: contentScore, weight: 0.10, name: 'Content Quality' });
        if (keywordScore > 0) scores.push({ score: keywordScore, weight: 0.10, name: 'Keywords' });
        if (linkAnalysisScore > 0) scores.push({ score: linkAnalysisScore, weight: 0.10, name: 'Link Structure' });
        if (imageAnalysisScore > 0) scores.push({ score: imageAnalysisScore, weight: 0.05, name: 'Images' });
        if (pageStructureScore > 0) scores.push({ score: pageStructureScore, weight: 0.05, name: 'Page Structure' });
        if (coreWebVitalsScore > 0) scores.push({ score: coreWebVitalsScore, weight: 0.10, name: 'Core Web Vitals' });
        if (schemaScore > 0) scores.push({ score: schemaScore, weight: 0.05, name: 'Schema Markup' });
        if (competitorScore > 0) scores.push({ score: competitorScore, weight: 0.05, name: 'Competitor Analysis' });
                
        let totalWeight = 0;
        let weightedScore = 0;
        
        scores.forEach(item => {
            weightedScore += item.score * item.weight;
            totalWeight += item.weight;
        });
        
        // Calculate the final score
        const overallScore = totalWeight > 0 ? Math.round(weightedScore / totalWeight) : 0;
        
        // Generate HTML
        const content = `
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Overall SEO Score</h5>
                            <div class="score-gauge mb-3">
                                <canvas id="overallScoreGauge" width="120" height="120"></canvas>
                                <div class="score-value">${overallScore}</div>
                            </div>
                            <p class="card-text ${Utils.getScoreClass(overallScore)}">${Utils.getScoreLabel(overallScore)}</p>
                            
                            <!-- NEW: Save current analysis to historical data -->
                            <button class="btn btn-sm btn-outline-primary mt-2" id="saveToHistorical">
                                <i class="fas fa-save me-1"></i> Save as Baseline
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">SEO Summary</h5>
                                
                                <!-- NEW: Toggle comparison switch -->
                                <div class="form-check form-switch">
                                    <input class="form-check-input comparison-toggle" type="checkbox" id="comparison-toggle-scoreComparisonChart" data-chart="scoreComparisonChart">
                                    <label class="form-check-label" for="comparison-toggle-scoreComparisonChart">Show Comparison</label>
                                </div>
                            </div>
                            
                            <!-- NEW: Message area for comparison info -->
                            <div id="comparison-message-scoreComparisonChart" class="mb-3"></div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="score-item">
                                        <div class="score-label">Technical SEO</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(technicalScore)}" 
                                                role="progressbar" style="width: ${technicalScore}%" 
                                                aria-valuenow="${technicalScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${technicalScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Meta Tags</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(metaTagsScore)}" 
                                                role="progressbar" style="width: ${metaTagsScore}%" 
                                                aria-valuenow="${metaTagsScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${metaTagsScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">On-Page SEO</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(onPageScore)}" 
                                                role="progressbar" style="width: ${onPageScore}%" 
                                                aria-valuenow="${onPageScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${onPageScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Off-Page SEO</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(offPageScore)}" 
                                                role="progressbar" style="width: ${offPageScore}%" 
                                                aria-valuenow="${offPageScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${offPageScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Link Structure</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(linkAnalysisScore)}" 
                                                role="progressbar" style="width: ${linkAnalysisScore}%" 
                                                aria-valuenow="${linkAnalysisScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${linkAnalysisScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Image Optimization</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(imageAnalysisScore)}" 
                                                role="progressbar" style="width: ${imageAnalysisScore}%" 
                                                aria-valuenow="${imageAnalysisScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${imageAnalysisScore}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="score-item">
                                        <div class="score-label">Page Structure</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(pageStructureScore)}" 
                                                role="progressbar" style="width: ${pageStructureScore}%" 
                                                aria-valuenow="${pageStructureScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${pageStructureScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Core Web Vitals</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(coreWebVitalsScore)}" 
                                                role="progressbar" style="width: ${coreWebVitalsScore}%" 
                                                aria-valuenow="${coreWebVitalsScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${coreWebVitalsScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Schema Markup</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(schemaScore)}" 
                                                role="progressbar" style="width: ${schemaScore}%" 
                                                aria-valuenow="${schemaScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${schemaScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Competitor Analysis</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(competitorScore)}" 
                                                role="progressbar" style="width: ${competitorScore}%" 
                                                aria-valuenow="${competitorScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${competitorScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Content Quality</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(contentScore)}" 
                                                role="progressbar" style="width: ${contentScore}%" 
                                                aria-valuenow="${contentScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${contentScore}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="score-item">
                                        <div class="score-label">Keywords</div>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar ${Utils.getScoreColorClass(keywordScore)}" 
                                                role="progressbar" style="width: ${keywordScore}%" 
                                                aria-valuenow="${keywordScore}" aria-valuemin="0" aria-valuemax="100">
                                                ${keywordScore}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">SEO Score Breakdown</h5>
                                
                                <!-- NEW: Chart type switcher -->
                                <div class="btn-group" role="group" aria-label="Chart type switcher">
                                    <button type="button" class="btn btn-sm btn-outline-primary chart-type-switcher active" 
                                            data-chart="scoreComparisonChart" data-type="radar">
                                        <i class="fas fa-chart-pie me-1"></i> Radar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary chart-type-switcher" 
                                            data-chart="scoreComparisonChart" data-type="bar">
                                        <i class="fas fa-chart-bar me-1"></i> Bar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary chart-type-switcher" 
                                            data-chart="scoreComparisonChart" data-type="line">
                                        <i class="fas fa-chart-line me-1"></i> Line
                                    </button>
                                </div>
                            </div>
                            <div style="height: 300px">
                                <canvas id="scoreComparisonChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Key SEO Metrics</h5>
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <div class="metric-box">
                                        <h3>${technicalData?.site_speed_score || 'N/A'}</h3>
                                        <p>Site Speed Score</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="metric-box">
                                        <h3>${offPageData?.domain_authority || 'N/A'}</h3>
                                        <p>Domain Authority</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="metric-box">
                                        <h3>${keywordData?.keywords?.length || 'N/A'}</h3>
                                        <p>Keywords Analyzed</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="metric-box">
                                        <h3>${typeof linkAnalysisData?.broken_links === 'object' ? '0' : linkAnalysisData?.broken_links || '0'}</h3>
                                        <p>Broken Links</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="metric-box">
                                        <h3>${typeof imageAnalysisData?.total_images === 'object' ? 'N/A' : imageAnalysisData?.total_images || 'N/A'}</h3>
                                        <p>Total Images Analyzed</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="metric-box">
                                        <h3>${imageAnalysisData?.summary?.images_with_alt_percent || 'N/A'}%</h3>
                                        <p>Images with Alt Text</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="metric-box">
                                        <h3>${contentData?.avg_word_count || 'N/A'}</h3>
                                        <p>Avg. Word Count</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="metric-box">
                                        <h3>${coreWebVitalsData?.metrics?.lcp?.value ? (coreWebVitalsData.metrics.lcp.value / 1000).toFixed(2) + 's' : 'N/A'}</h3>
                                        <p>Largest Contentful Paint</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#summaryContent').html(content);
        
        // Initialize gauge chart
        this.initGaugeChart('overallScoreGauge', overallScore);
        
        // Create additional charts
        this.createComparisonChart();
        this.createKeywordChart();
        
        // Save current analysis data for potential historical comparison
        if (typeof appState !== 'undefined' && appState.currentUrl) {
            // Build a complete dataset from all analysis components
            const analysisData = {
                technicalSeo: technicalData,
                metaTagsAnalysis: metaTagsData,
                onPageSeo: onPageData,
                offPageSeo: offPageData,
                keywordAnalysis: keywordData,
                contentAnalysis: contentData,
                linkAnalysis: linkAnalysisData,
                imageAnalysis: imageAnalysisData,
                pageStructure: pageStructureData,
                coreWebVitals: coreWebVitalsData,
                schema: schemaData,
                competitor: competitorData,
                overallScore: overallScore,
                timestamp: new Date().toISOString()
            };
            
            // Auto-save the first analysis for a domain
            const historical = this.getHistoricalData(appState.currentUrl);
            if (historical.length === 0) {
                this.saveHistoricalData(appState.currentUrl, analysisData);
            }
            
            // Setup listener for the save button
            $('#saveToHistorical').on('click', function() {
                Visualization.saveHistoricalData(appState.currentUrl, analysisData);
                UIManager.showNotification('Analysis saved as baseline for future comparisons', 'success');
            });
        }
    },
    
    initGaugeChart: function(canvasId, score) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return; // Safety check
        
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = canvas.width / 2 - 10;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw background circle
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        ctx.fillStyle = '#f0f0f0';
        ctx.fill();
        
        // Draw score arc
        const startAngle = Math.PI;
        const endAngle = startAngle + (score / 100) * Math.PI;
        
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.lineWidth = 20;
        ctx.strokeStyle = this.getScoreGradient(ctx, score, centerX, centerY, radius);
        ctx.stroke();
        
        // Add drop shadow for visual enhancement
        ctx.shadowOffsetX = 2;
        ctx.shadowOffsetY = 2;
        ctx.shadowBlur = 5;
        ctx.shadowColor = 'rgba(0,0,0,0.3)';
        
        // Draw needle if score is greater than zero
        if (score > 0) {
            // Needle angle (from startAngle to endAngle)
            const needleAngle = startAngle + (score / 100) * Math.PI;
            
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(
                centerX + Math.cos(needleAngle) * (radius - 5),
                centerY + Math.sin(needleAngle) * (radius - 5)
            );
            ctx.lineWidth = 2;
            ctx.strokeStyle = '#333';
            ctx.stroke();
            
            // Draw needle center
            ctx.beginPath();
            ctx.arc(centerX, centerY, 5, 0, 2 * Math.PI);
            ctx.fillStyle = '#333';
            ctx.fill();
        }
        
        // Reset shadow
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 0;
        ctx.shadowBlur = 0;
    },
    
    getScoreGradient: function(ctx, score, centerX, centerY, radius) {
        let gradient = ctx.createLinearGradient(0, 0, 0, centerY * 2);
        
        if (score < 50) {
            gradient.addColorStop(0, '#dc3545'); // Red
            gradient.addColorStop(1, '#ffc107'); // Yellow
        } else {
            gradient.addColorStop(0, '#ffc107'); // Yellow
            gradient.addColorStop(1, '#28a745'); // Green
        }
        
        return gradient;
    },
    
    createChart: function(canvasId, type, data, options) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null; // Safety check
        
        // Destroy existing chart if it exists
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }
        
        const ctx = canvas.getContext('2d');
        this.charts[canvasId] = new Chart(ctx, {
            type: type,
            data: data,
            options: options
        });
        
        return this.charts[canvasId];
    },
    
    /**
     * Create comparison chart with radar visualization
     */
    createComparisonChart: function() {
        const technicalData = $('#technicalContent').data('results');
        const onPageData = $('#onpageContent').data('results');
        const offPageData = $('#offpageContent').data('results');
        
        if (!technicalData && !onPageData && !offPageData) {
            return null;
        }
        
        // Add chart container to summary content if it doesn't exist
        if ($('#scoreComparisonChart').length === 0) {
            $('#summaryContent').append(`
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">SEO Score Breakdown</h5>
                                <div style="height: 300px">
                                    <canvas id="scoreComparisonChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
        
        // Prepare data for radar chart
        const technicalScores = technicalData ? {
            crawlability: technicalData.crawlability_score || 0,
            indexability: technicalData.indexability_score || 0,
            siteSpeed: technicalData.site_speed_score || 0,
            mobileFriendly: technicalData.mobile_friendly_score || 0
        } : {};
        
        const onPageScores = onPageData ? {
            titleTags: onPageData.title_tags_score || 0,
            metaDesc: onPageData.meta_desc_score || 0,
            headerTags: onPageData.header_tags_score || 0,
            content: onPageData.content_score || 0,
            images: onPageData.image_optimization_score || 0,
            internalLinks: onPageData.internal_linking_score || 0,
            urlStructure: onPageData.url_structure_score || 0
        } : {};
        
        const offPageScores = offPageData ? {
            backlinks: offPageData.backlink_score || 0,
            domainAuthority: offPageData.domain_authority || 0,
            socialMedia: offPageData.social_media_score || 0
        } : {};
        
        // Create consolidated data for chart
        const datasets = [];
        const labels = [];
        
        // Technical scores
        Object.keys(technicalScores).forEach(key => {
            labels.push(Utils.camelToTitleCase(key));
        });
        
        // On-page scores
        Object.keys(onPageScores).forEach(key => {
            if (!labels.includes(Utils.camelToTitleCase(key))) {
                labels.push(Utils.camelToTitleCase(key));
            }
        });
        
        // Off-page scores
        Object.keys(offPageScores).forEach(key => {
            if (!labels.includes(Utils.camelToTitleCase(key))) {
                labels.push(Utils.camelToTitleCase(key));
            }
        });
        
        // Create dataset values
        const technicalValues = labels.map(label => {
            const key = label.replace(/\s/g, '').toLowerCase();
            for (const [k, v] of Object.entries(technicalScores)) {
                if (k.toLowerCase() === key) {
                    return v;
                }
            }
            return 0;
        });
        
        const onPageValues = labels.map(label => {
            const key = label.replace(/\s/g, '').toLowerCase();
            for (const [k, v] of Object.entries(onPageScores)) {
                if (k.toLowerCase() === key) {
                    return v;
                }
            }
            return 0;
        });
        
        const offPageValues = labels.map(label => {
            const key = label.replace(/\s/g, '').toLowerCase();
            for (const [k, v] of Object.entries(offPageScores)) {
                if (k.toLowerCase() === key) {
                    return v;
                }
            }
            return 0;
        });
        
        // Add datasets with enhanced styling
        datasets.push({
            label: 'Technical SEO',
            data: technicalValues,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 0.8)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(54, 162, 235, 0.8)',
            pointRadius: 4,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: 'rgba(54, 162, 235, 1)'
        });
        
        datasets.push({
            label: 'On-Page SEO',
            data: onPageValues,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 0.8)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(75, 192, 192, 0.8)',
            pointRadius: 4,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: 'rgba(75, 192, 192, 1)'
        });
        
        datasets.push({
            label: 'Off-Page SEO',
            data: offPageValues,
            backgroundColor: 'rgba(255, 159, 64, 0.2)',
            borderColor: 'rgba(255, 159, 64, 0.8)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(255, 159, 64, 0.8)',
            pointRadius: 4,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: 'rgba(255, 159, 64, 1)'
        });
        
        // Create the chart with enhanced interactivity
        return this.createChart('scoreComparisonChart', 'radar', {
            labels: labels,
            datasets: datasets
        }, {
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        showLabelBackdrop: false,
                        font: {
                            size: 10
                        }
                    },
                    pointLabels: {
                        font: {
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 15,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.formattedValue + '/100';
                        }
                    },
                    displayColors: true,
                    padding: 10
                }
            },
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        });
    },
    
    // Add keyword difficulty vs. search volume chart
    createKeywordChart: function() {
        const keywordData = $('#keywordsContent').data('results');
        
        if (!keywordData || !keywordData.keywords || keywordData.keywords.length === 0) {
            return null;
        }
        
        // Add chart container to keywords tab if it doesn't exist
        if ($('#keywordAnalysisChart').length === 0) {
            $('#keywordsContent').append(`
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Keyword Opportunity Analysis</h5>
                                    
                                    <!-- NEW: Chart type switcher -->
                                    <div class="btn-group" role="group" aria-label="Chart type switcher">
                                        <button type="button" class="btn btn-sm btn-outline-primary chart-type-switcher active" 
                                                data-chart="keywordAnalysisChart" data-type="bubble">
                                            <i class="fas fa-circle me-1"></i> Bubble
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary chart-type-switcher" 
                                                data-chart="keywordAnalysisChart" data-type="scatter">
                                            <i class="fas fa-dot-circle me-1"></i> Scatter
                                        </button>
                                    </div>
                                </div>
                                <div style="height: 400px">
                                    <canvas id="keywordAnalysisChart"></canvas>
                                </div>
                                
                                <!-- NEW: Interactive filters -->
                                <div class="keyword-filters mt-3">
                                    <h6 class="text-muted mb-2">Filter Keywords:</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary keyword-filter active" data-filter="all">All</button>
                                        <button type="button" class="btn btn-outline-secondary keyword-filter" data-filter="ranked">Ranked</button>
                                        <button type="button" class="btn btn-outline-secondary keyword-filter" data-filter="unranked">Unranked</button>
                                        <button type="button" class="btn btn-outline-secondary keyword-filter" data-filter="opportunity">Opportunities</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
        
        // Prepare data for bubble chart
        const datasets = [];
        const colors = [
            'rgba(54, 162, 235, 0.7)',  // Blue
            'rgba(75, 192, 192, 0.7)',  // Teal
            'rgba(255, 99, 132, 0.7)',  // Pink
            'rgba(255, 159, 64, 0.7)'   // Orange
        ];
        
        // Group by current ranking (0 = not ranked, 1-10 = first page, 11+ = lower pages)
        const notRanked = keywordData.keywords.filter(kw => !kw.current_ranking || kw.current_ranking === 0);
        const firstPage = keywordData.keywords.filter(kw => kw.current_ranking > 0 && kw.current_ranking <= 10);
        const lowerPages = keywordData.keywords.filter(kw => kw.current_ranking > 10);
        
        // Add dataset for not ranked keywords
        if (notRanked.length > 0) {
            datasets.push({
                label: 'Not Ranked',
                data: notRanked.map(kw => ({
                    x: kw.difficulty,
                    y: kw.search_volume,
                    r: 8, // Fixed radius for visibility
                    keyword: kw.keyword,
                    category: 'unranked',
                    isOpportunity: this.isKeywordOpportunity(kw)
                })),
                backgroundColor: colors[0]
            });
        }
        
        // Add dataset for first page keywords
        if (firstPage.length > 0) {
            datasets.push({
                label: 'First Page (1-10)',
                data: firstPage.map(kw => ({
                    x: kw.difficulty,
                    y: kw.search_volume,
                    r: 8, // Fixed radius for visibility
                    keyword: kw.keyword,
                    ranking: kw.current_ranking,
                    category: 'ranked',
                    isOpportunity: this.isKeywordOpportunity(kw)
                })),
                backgroundColor: colors[1]
            });
        }
        
        // Add dataset for lower page keywords
        if (lowerPages.length > 0) {
            datasets.push({
                label: 'Lower Pages (11+)',
                data: lowerPages.map(kw => ({
                    x: kw.difficulty,
                    y: kw.search_volume,
                    r: 8, // Fixed radius for visibility
                    keyword: kw.keyword,
                    ranking: kw.current_ranking,
                    category: 'ranked',
                    isOpportunity: this.isKeywordOpportunity(kw)
                })),
                backgroundColor: colors[2]
            });
        }
        
        // Create the chart with enhanced interactions
        const chart = this.createChart('keywordAnalysisChart', 'bubble', {
            datasets: datasets
        }, {
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Keyword Difficulty',
                        font: {
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Search Volume',
                        font: {
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 15,
                        padding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const keyword = context.raw.keyword;
                            const difficulty = context.raw.x;
                            const volume = context.raw.y;
                            const ranking = context.raw.ranking || 'Not Ranked';
                            
                            return [
                                `Keyword: ${keyword}`,
                                `Difficulty: ${difficulty}`,
                                `Search Volume: ${volume}`,
                                `Current Ranking: ${ranking}`
                            ];
                        }
                    },
                    displayColors: true,
                    padding: 10
                }
            },
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        });
        
        // Setup event handlers for keyword filters
        $('.keyword-filter').on('click', function() {
            $('.keyword-filter').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            
            // Update chart based on filter
            if (filter === 'all') {
                // Show all datasets
                chart.data.datasets = datasets;
            } else if (filter === 'ranked') {
                // Show only ranked keywords
                chart.data.datasets = datasets.filter(ds => 
                    ds.label === 'First Page (1-10)' || ds.label === 'Lower Pages (11+)'
                );
            } else if (filter === 'unranked') {
                // Show only unranked keywords
                chart.data.datasets = datasets.filter(ds => ds.label === 'Not Ranked');
            } else if (filter === 'opportunity') {
                // Show only opportunity keywords (determined by a function)
                const opportunityDatasets = [];
                
                datasets.forEach(ds => {
                    const opportunityData = ds.data.filter(item => item.isOpportunity);
                    
                    if (opportunityData.length > 0) {
                        opportunityDatasets.push({
                            label: ds.label + ' Opportunities',
                            data: opportunityData,
                            backgroundColor: ds.backgroundColor
                        });
                    }
                });
                
                chart.data.datasets = opportunityDatasets;
            }
            
            chart.update();
        });
        
        return chart;
    },
    
    /**
     * Determine if a keyword represents a good opportunity
     * @param {Object} keyword - Keyword object
     * @return {boolean} - True if the keyword is a good opportunity
     */
    isKeywordOpportunity: function(keyword) {
        // Low difficulty (below 40) and decent search volume (above 100)
        if (keyword.difficulty < 40 && keyword.search_volume > 100) {
            return true;
        }
        
        // Already ranking but not in top 3 (good improvement opportunity)
        if (keyword.current_ranking && keyword.current_ranking > 3 && keyword.current_ranking <= 20) {
            return true;
        }
        
        // High volume (over 500) and medium difficulty (below 60)
        if (keyword.search_volume > 500 && keyword.difficulty < 60) {
            return true;
        }
        
        return false;
    },
    
    /**
     * Generate priority actions from various analysis data
     * @param {Object} analysisData - Combined analysis data
     * @return {string} HTML content with priority actions
     */
    getPriorityActions: function(analysisData) {
        const actions = [];
        
        // Get data from the analysis object
        const { technicalData, metaTagsData, onPageData, offPageData, contentData, imageAnalysisData, linkAnalysisData } = analysisData;
        
        // Image analysis actions
        if (imageAnalysisData && imageAnalysisData.recommendations && imageAnalysisData.recommendations.length > 0) {
            const highPriorityImageIssues = imageAnalysisData.recommendations.filter(issue => issue.priority === 'high');
            if (highPriorityImageIssues.length > 0) {
                actions.push(`<li class="text-danger"><strong>Images:</strong> ${highPriorityImageIssues[0].description}</li>`);
            }
        }
        
        // Link analysis actions
        if (linkAnalysisData && linkAnalysisData.broken_links > 0) {
            actions.push(`<li class="text-danger"><strong>Links:</strong> Fix ${linkAnalysisData.broken_links} broken links on your website</li>`);
        } else if (linkAnalysisData && linkAnalysisData.recommendations && linkAnalysisData.recommendations.length > 0) {
            const highPriorityLinkIssues = linkAnalysisData.recommendations.filter(issue => issue.priority === 'high');
            if (highPriorityLinkIssues.length > 0) {
                actions.push(`<li class="text-warning"><strong>Links:</strong> ${highPriorityLinkIssues[0].description}</li>`);
            }
        }         
            
        // Technical SEO actions
        if (technicalData && technicalData.issues && technicalData.issues.length > 0) {
            const highPriorityIssues = technicalData.issues.filter(issue => issue.severity === 'high');
            if (highPriorityIssues.length > 0) {
                actions.push(`<li class="text-danger"><strong>Technical:</strong> ${highPriorityIssues[0].description}</li>`);
            }
        } else if (technicalData && technicalData.site_speed_score && technicalData.site_speed_score < 70) {
            actions.push(`<li class="text-danger"><strong>Technical:</strong> Improve site loading speed (currently ${technicalData.site_speed_score}/100)</li>`);
        }
        
        // Meta Tags actions
        if (metaTagsData && metaTagsData.meta_tags && (!metaTagsData.meta_tags.description || metaTagsData.meta_tags.description_length < 50)) {
            actions.push(`<li class="text-warning"><strong>Meta Tags:</strong> Add or improve meta descriptions</li>`);
        }
        
        // On-page SEO actions
        if (onPageData && onPageData.recommendations && onPageData.recommendations.length > 0) {
            const highPriorityRecs = onPageData.recommendations.filter(rec => rec.priority === 'high');
            if (highPriorityRecs.length > 0) {
                actions.push(`<li class="text-danger"><strong>On-Page:</strong> ${highPriorityRecs[0].description}</li>`);
            }
        } else if (onPageData && onPageData.title_tags_score && onPageData.title_tags_score < 70) {
            actions.push(`<li class="text-warning"><strong>On-Page:</strong> Optimize title tags to include target keywords</li>`);
        }
        
        // Content actions
        if (contentData && contentData.suggestions && contentData.suggestions.length > 0) {
            const highPrioritySugs = contentData.suggestions.filter(sug => sug.priority === 'high');
            if (highPrioritySugs.length > 0) {
                actions.push(`<li class="text-danger"><strong>Content:</strong> ${highPrioritySugs[0].description}</li>`);
            }
        } else if (contentData && contentData.avg_word_count && contentData.avg_word_count < 500) {
            actions.push(`<li class="text-warning"><strong>Content:</strong> Increase content length to at least 1000 words on key pages</li>`);
        }
        
        // Off-page actions
        if (offPageData && offPageData.backlink_score && offPageData.backlink_score < 50) {
            actions.push(`<li class="text-warning"><strong>Off-Page:</strong> Improve backlink profile with high-quality relevant links</li>`);
        }
        
        // If we have less than 3 high priority actions, add some medium priority ones
        if (actions.length < 3) {
            if (onPageData && onPageData.recommendations && onPageData.recommendations.length > 0) {
                const medPriorityRecs = onPageData.recommendations.filter(rec => rec.priority === 'medium');
                if (medPriorityRecs.length > 0 && actions.length < 3) {
                    actions.push(`<li class="text-warning"><strong>On-Page:</strong> ${medPriorityRecs[0].description}</li>`);
                }
            }
            
            if (contentData && contentData.suggestions && contentData.suggestions.length > 0 && actions.length < 3) {
                const medPrioritySugs = contentData.suggestions.filter(sug => sug.priority === 'medium');
                if (medPrioritySugs.length > 0) {
                    actions.push(`<li class="text-warning"><strong>Content:</strong> ${medPrioritySugs[0].description}</li>`);
                }
            }
        }
        
        // If still no actions, provide generic ones
        if (actions.length === 0) {
            actions.push('<li class="text-info"><strong>Technical:</strong> Improve website loading speed for better user experience</li>');
            actions.push('<li class="text-info"><strong>Content:</strong> Create more comprehensive content on key pages (1000+ words)</li>');
            actions.push('<li class="text-info"><strong>Off-Page:</strong> Build quality backlinks from relevant websites</li>');
        }
        
        return actions.join('');
    }
};

// Initialize visualization features when document is ready
$(document).ready(function() {
    Visualization.initialize();
});
