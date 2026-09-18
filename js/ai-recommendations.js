/**
 * ai-recommendations.js - AI-powered recommendations functionality
 * ENHANCED VERSION: Added targeted categories and improved prioritization
 */

const AIRecommendations = {
    // Store available models
    availableModels: [],
    
    // Currently selected model ID
    selectedModelId: null,
    
    // Enhanced recommendation categories
    categories: {
        quickWins: {
            icon: 'fas fa-bolt',
            title: 'Quick Wins',
            description: 'High impact improvements with minimal effort',
            colorClass: 'success',
            order: 1
        },
        technical: {
            icon: 'fas fa-cogs',
            title: 'Technical SEO',
            description: 'Server, speed, and crawlability improvements',
            colorClass: 'danger',
            order: 2
        },
        content: {
            icon: 'fas fa-align-left',
            title: 'Content',
            description: 'Quality, depth, and relevance improvements',
            colorClass: 'info',
            order: 3
        },
        onpage: {
            icon: 'fas fa-file-alt',
            title: 'On-Page SEO',
            description: 'Title tags, meta descriptions, and structure',
            colorClass: 'primary',
            order: 4
        },
        offpage: {
            icon: 'fas fa-link',
            title: 'Off-Page SEO',
            description: 'Backlinks, social signals, and authority',
            colorClass: 'secondary',
            order: 5
        },
        keywords: {
            icon: 'fas fa-key',
            title: 'Keyword Strategy',
            description: 'Targeting, intent, and competition',
            colorClass: 'warning',
            order: 6
        },
        images: {
            icon: 'fas fa-image',
            title: 'Image Optimization',
            description: 'Alt text, size, and format improvements',
            colorClass: 'info',
            order: 7
        },
        local: {
            icon: 'fas fa-map-marker-alt',
            title: 'Local SEO',
            description: 'Google Business Profile and local citation improvements',
            colorClass: 'primary',
            order: 8
        },
        user: {
            icon: 'fas fa-user',
            title: 'User Experience',
            description: 'Engagement, navigation, and conversion improvements',
            colorClass: 'warning',
            order: 9
        },
        mobile: {
            icon: 'fas fa-mobile-alt',
            title: 'Mobile Optimization',
            description: 'Mobile-first and responsive design improvements',
            colorClass: 'success',
            order: 10
        }
    },
    
    // Store implementation tracking stats
    implementationStats: {
        implemented: 0,
        inProgress: 0,
        pending: 0
    },
    
    /**
     * Initialize the AI recommendations module
     */
    initialize: function() {
        // Load available AI models
        this.loadAvailableModels();
        
        // Load any saved implementation data from localStorage
        this.loadImplementationData();
        
        // Handle model selection changes
        $(document).on('change', '#aiModelSelect', function() {
            const modelId = $(this).val();
            AIRecommendations.selectedModelId = modelId || null;
            
            // Update the app state (assuming it's globally accessible)
            if (typeof appState !== 'undefined') {
                appState.aiModel = AIRecommendations.selectedModelId;
                
                // If analysis is already complete, generate recommendations
                if (appState.overallProgress === 100) {
                    AnalysisManager.generateAiRecommendations(appState);
                }
            }
        });
        
        // Modified: Check if recommendations were loaded from the database before regenerating
        $(document).on('analysisCompleted', function() {
            if (AIRecommendations.selectedModelId && 
                typeof appState !== 'undefined' && 
                appState.overallProgress === 100) {
                
                // Check if we already have recommendations in the container
                const hasExistingRecommendations = $('#aiRecommendationsContent').data('loaded-from-database');
                
                if (!hasExistingRecommendations) {
                    // Small delay to ensure UI is ready
                    setTimeout(function() {
                        AnalysisManager.generateAiRecommendations(appState);
                    }, 500);
                }
            }
        });
        
        // New: Handle the analysisLoaded event
        $(document).on('analysisLoaded', function(event, data) {
            // Mark if we have loaded recommendations from the database
            if (data.hasAiRecommendations) {
                $('#aiRecommendationsContent').data('loaded-from-database', true);
                appState.progressSteps.aiRecommendations.completed = true;
                UIManager.updateStepStatus('aiRecommendations', 'completed');
                UIManager.updateOverallProgress(appState);
            } else {
                $('#aiRecommendationsContent').data('loaded-from-database', false);
                
                // If an AI model is selected, we should generate recommendations
                if (AIRecommendations.selectedModelId) {
                    setTimeout(function() {
                        AnalysisManager.generateAiRecommendations(appState);
                    }, 500);
                }
            }
        });
        
        // Add filter handlers for recommendations
        $(document).on('click', '.filter-recommendations', function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            AIRecommendations.filterRecommendations(filter);
        });
    },
    
    /**
     * Load implementation tracking data from localStorage
     */
    loadImplementationData: function() {
        try {
            const savedData = localStorage.getItem('ai_recommendations_implementation');
            if (savedData) {
                const parsedData = JSON.parse(savedData);
                if (parsedData && parsedData.stats) {
                    this.implementationStats = parsedData.stats;
                }
            }
        } catch (error) {
            console.error('Failed to load implementation data:', error);
        }
    },
    
    /**
     * Save implementation tracking data to localStorage
     */
    saveImplementationData: function() {
        try {
            localStorage.setItem('ai_recommendations_implementation', JSON.stringify({
                stats: this.implementationStats,
                lastUpdated: new Date().toISOString()
            }));
        } catch (error) {
            console.error('Failed to save implementation data:', error);
        }
    },
    
    /**
     * Load available AI models from API
     */
    loadAvailableModels: function() {
        ApiService.getAiModels()
            .done(function(response) {
                if (response.success) {
                    // Store models for later use
                    AIRecommendations.availableModels = response.models;
                    
                    // Populate the model selector
                    AIRecommendations.populateModelSelector(response.models);
                }
            })
            .fail(function(error) {
                console.error('Failed to load AI models:', error);
                // Add an error message to the dropdown
                $('#aiModelSelect').html(`
                    <option value="">None - AI services unavailable</option>
                `);
            });
    },
    
    /**
     * Populate the model selector dropdown
     * @param {Array} models - Available AI models
     */
    populateModelSelector: function(models) {
        if (!models || !Array.isArray(models) || models.length === 0) {
            return;
        }

        const onlineModels = models.filter(model => model.type === 'online');
        const localModels = models.filter(model => model.type === 'local');

        let options = '<option value="">None - No AI assistance</option>';
        let defaultModelId = null;

        // Add online models group
        if (onlineModels.length > 0) {
            options += '<optgroup label="Online AI Models">';
            onlineModels.forEach(model => {
                const isSelected = (model.is_default == 1) ? 'selected' : '';
                if (model.is_default == 1) {
                    defaultModelId = model.id;
                }
                options += `<option value="${model.id}" ${isSelected}>${model.name}</option>`;
            });
            options += '</optgroup>';
        }

        // Add local models group
        if (localModels.length > 0) {
            options += '<optgroup label="Local AI Models">';
            localModels.forEach(model => {
                const isSelected = (model.is_default == 1) ? 'selected' : '';
                if (model.is_default == 1) {
                    defaultModelId = model.id;
                }
                options += `<option value="${model.id}" ${isSelected}>${model.name}</option>`;
            });
            options += '</optgroup>';
        }

        // Update the select element
        $('#aiModelSelect').html(options);
        
        // If a default model was found, update the application state
        if (defaultModelId) {
            this.selectedModelId = defaultModelId;
            
            // Update the app state (assuming it's globally accessible)
            if (typeof appState !== 'undefined') {
                appState.aiModel = defaultModelId;
            }
        }
    },
    
    /**
     * Determine the most appropriate category for a recommendation
     * @param {Object} rec - Recommendation object
     * @param {string} originalCategory - Original category from AI
     * @return {string} Best matching category key
     */
    categorizeRecommendation: function(rec, originalCategory) {
        // Quick wins for high priority, fast implementations
        if (rec.importance === 'high' && 
            rec.implementation && 
            rec.implementation.toLowerCase().includes('quick') &&
            !rec.implementation.toLowerCase().includes('complex')) {
            return 'quickWins';
        }
        
        // Technical SEO checks
        const technicalKeywords = ['speed', 'crawl', 'server', 'https', 'ssl', 'redirect', 'sitemap', 'robots'];
        if (originalCategory === 'technical' || 
            this.containsKeywords(rec, technicalKeywords)) {
            return 'technical';
        }
        
        // Content checks
        const contentKeywords = ['content', 'word count', 'readability', 'thin content', 'writing', 'paragraph'];
        if (originalCategory === 'content' || 
            this.containsKeywords(rec, contentKeywords)) {
            return 'content';
        }
        
        // On-page checks
        const onPageKeywords = ['title tag', 'meta description', 'heading', 'h1', 'h2', 'structure'];
        if (originalCategory === 'onpage' || 
            this.containsKeywords(rec, onPageKeywords)) {
            return 'onpage';
        }
        
        // Off-page checks
        const offPageKeywords = ['backlink', 'link building', 'authority', 'external', 'anchor text'];
        if (originalCategory === 'offpage' || 
            this.containsKeywords(rec, offPageKeywords)) {
            return 'offpage';
        }
        
        // Keyword checks
        const keywordKeywords = ['keyword', 'search volume', 'ranking', 'serp', 'long tail'];
        if (originalCategory === 'keywords' || 
            this.containsKeywords(rec, keywordKeywords)) {
            return 'keywords';
        }
        
        // Image checks
        const imageKeywords = ['image', 'alt text', 'png', 'jpg', 'webp', 'compression'];
        if (this.containsKeywords(rec, imageKeywords)) {
            return 'images';
        }
        
        // Local SEO checks
        const localKeywords = ['local', 'business profile', 'gmb', 'map', 'citation'];
        if (this.containsKeywords(rec, localKeywords)) {
            return 'local';
        }
        
        // User experience checks
        const userKeywords = ['user', 'bounce', 'conversion', 'navigate', 'menu', 'cta', 'button'];
        if (this.containsKeywords(rec, userKeywords)) {
            return 'user';
        }
        
        // Mobile checks
        const mobileKeywords = ['mobile', 'responsive', 'viewport', 'touch'];
        if (this.containsKeywords(rec, mobileKeywords)) {
            return 'mobile';
        }
        
        // Default to the original category if available, or technical otherwise
        return originalCategory === 'online' ? originalCategory : 'technical';
    },
    
    /**
     * Check if recommendation contains any of the given keywords
     * @param {Object} rec - Recommendation object
     * @param {Array} keywords - Keywords to search for
     * @return {boolean} True if recommendation contains any of the keywords
     */
    containsKeywords: function(rec, keywords) {
        const searchText = `${rec.title} ${rec.description} ${rec.implementation}`.toLowerCase();
        return keywords.some(keyword => searchText.includes(keyword.toLowerCase()));
    },
    
    /**
     * Calculate an implementation priority score for a recommendation
     * @param {Object} rec - Recommendation object
     * @return {number} Priority score (higher = more important)
     */
    calculatePriorityScore: function(rec) {
        let score = 0;
        
        // Base score from importance
        if (rec.importance === 'high') {
            score += 100;
        } else if (rec.importance === 'medium') {
            score += 50;
        } else {
            score += 25;
        }
        
        // Adjust based on estimated implementation effort
        if (rec.implementation) {
            const impl = rec.implementation.toLowerCase();
            
            // Decrease score for complex implementations
            if (impl.includes('complex') || impl.includes('significant') || impl.includes('extensive')) {
                score -= 20;
            }
            
            // Increase score for simple implementations
            if (impl.includes('simple') || impl.includes('quick') || impl.includes('easy')) {
                score += 15;
            }
        }
        
        // Adjust based on expected impact keywords in the description
        if (rec.description) {
            const desc = rec.description.toLowerCase();
            
            // Increase score for high impact descriptions
            if (desc.includes('significant impact') || desc.includes('dramatic improvement') || 
                desc.includes('essential') || desc.includes('crucial')) {
                score += 25;
            }
        }
        
        return score;
    },
    
    /**
     * Filter recommendations based on category or status
     * @param {string} filter - Filter to apply ('all', category name, or 'implemented')
     */
    filterRecommendations: function(filter) {
        $('.filter-recommendations').removeClass('active');
        $(`.filter-recommendations[data-filter="${filter}"]`).addClass('active');
        
        if (filter === 'all') {
            $('.recommendation-card').show();
        } else if (filter === 'implemented') {
            $('.recommendation-card').hide();
            $('.recommendation-card.implemented').show();
        } else {
            $('.recommendation-card').hide();
            $(`.recommendation-card[data-category="${filter}"]`).show();
        }
    },
    
    /**
     * Display AI-generated recommendations with enhanced categorization
     * @param {Object} recommendations - AI recommendations data
     */
    displayRecommendations: function(recommendations) {
        if (!recommendations) {
            $('#aiRecommendationsContent').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Failed to generate AI recommendations.
                </div>
            `);
            return;
        }
        
        // Find the selected model name
        let modelName = 'AI Model';
        if (this.selectedModelId && this.availableModels.length > 0) {
            const selectedModel = this.availableModels.find(m => m.id == this.selectedModelId);
            if (selectedModel) {
                modelName = selectedModel.name;
            }
        }
        
        // Reset implementation stats
        this.implementationStats = {
            implemented: 0,
            inProgress: 0,
            pending: 0
        };
        
        // Prepare all recommendations from different categories with enhanced categorization
        const allRecommendations = [];
        const processedCategories = new Set();
        
        // Process all standard categories
        const categoryMap = {
            'technical': 'technical',
            'onpage': 'onpage',
            'content': 'content',
            'keywords': 'keywords'
        };
        
        // Process each category of recommendations
        Object.entries(categoryMap).forEach(([apiKey, internalKey]) => {
            if (recommendations[apiKey] && recommendations[apiKey].length > 0) {
                processedCategories.add(internalKey);
                
                recommendations[apiKey].forEach(rec => {
                    // Determine the best category for this recommendation
                    const bestCategory = this.categorizeRecommendation(rec, internalKey);
                    
                    // Add to our processed categories set
                    processedCategories.add(bestCategory);
                    
                    // Calculate priority score for ranking
                    const priorityScore = this.calculatePriorityScore(rec);
                    
                    allRecommendations.push({
                        category: bestCategory,
                        icon: this.categories[bestCategory].icon,
                        categoryClass: `bg-${this.categories[bestCategory].colorClass}`,
                        categoryTitle: this.categories[bestCategory].title,
                        priorityScore: priorityScore,
                        ...rec
                    });
                });
            }
        });
        
        // Sort recommendations by importance and calculated priority score
        allRecommendations.sort((a, b) => {
            // Sort by category order first
            const categoryOrderDiff = this.categories[a.category].order - this.categories[b.category].order;
            if (categoryOrderDiff !== 0) return categoryOrderDiff;
            
            // Then by priority score (higher scores first)
            return b.priorityScore - a.priorityScore;
        });
        
        // Count pending recommendations
        this.implementationStats.pending = allRecommendations.length;
        
        // Create HTML content
        let content = `
            <div class="ai-recommendations-container">
                <!-- Executive Summary -->
                <div class="mb-4 p-4 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h4 class="mb-0"><i class="fas fa-lightbulb text-warning me-2"></i> Executive Summary</h4>
                        <span class="badge bg-primary rounded-pill">Generated by ${modelName}</span>
                    </div>
                    <p class="lead">${recommendations.summary || "Based on our analysis, your website has several areas where SEO improvements can be made. We've organized these into actionable recommendations below, prioritized by potential impact."}</p>
                </div>
                
                <!-- Recommendation Filters -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0"><i class="fas fa-filter me-2"></i> Filter Recommendations</h4>
                    </div>
                    <div class="btn-group recommendation-filter-group mb-3">
                        <button type="button" class="btn btn-outline-primary filter-recommendations active" data-filter="all">
                            All Recommendations
                        </button>
                        <button type="button" class="btn btn-outline-success filter-recommendations" data-filter="quickWins">
                            <i class="fas fa-bolt me-1"></i> Quick Wins
                        </button>
                        <button type="button" class="btn btn-outline-secondary filter-recommendations" data-filter="implemented">
                            <i class="fas fa-check-circle me-1"></i> Implemented
                        </button>
                    </div>
                    <div class="category-filters d-flex flex-wrap gap-2 mb-3">
        `;
        
        // Add buttons for each category that has recommendations
        processedCategories.forEach(category => {
            // Skip 'quickWins' as it has a dedicated button already
            if (category === 'quickWins') return;
            
            const catInfo = this.categories[category];
            content += `
                <button type="button" class="btn btn-sm btn-outline-${catInfo.colorClass} filter-recommendations" data-filter="${category}">
                    <i class="${catInfo.icon} me-1"></i> ${catInfo.title}
                </button>
            `;
        });
        
        content += `
                    </div>
                </div>
                
                <!-- Recommendations Progress -->
                <div class="mb-4 p-3 bg-light rounded">
                    <h5 class="mb-3"><i class="fas fa-tasks me-2"></i> Implementation Progress</h5>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 0%" 
                             id="implementedProgressBar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="fas fa-check-circle text-success"></i> <span id="implementedCount">0</span> Implemented</span>
                        <span><i class="fas fa-clock text-warning"></i> <span id="pendingCount">${allRecommendations.length}</span> Pending</span>
                    </div>
                </div>
                
                <!-- Key Action Items -->
                <div class="mb-4">
                    <h4 class="mb-3 border-bottom pb-2"><i class="fas fa-clipboard-list me-2"></i> Prioritized Recommendations</h4>
        `;
        
        // Add recommendations to the content
        if (allRecommendations.length === 0) {
            content += `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No specific recommendations available.
                </div>
            `;
        } else {
            // Display recommendations in a clean, actionable format
            allRecommendations.forEach((rec, index) => {
                const importanceClass = rec.importance === 'high' ? 'border-danger' : 
                                     rec.importance === 'medium' ? 'border-warning' : 'border-info';
                
                const importanceBadgeClass = rec.importance === 'high' ? 'bg-danger' : 
                                          rec.importance === 'medium' ? 'bg-warning' : 'bg-info';
                
                const recId = `rec-${index}`;
                                          
                content += `
                    <div class="recommendation-card mb-4 border ${importanceClass} rounded-3 shadow-sm overflow-hidden" 
                         id="${recId}" data-category="${rec.category}" data-priority="${rec.priorityScore}">
                        <div class="d-flex">
                            <!-- Category indicator -->
                            <div class="category-indicator ${rec.categoryClass} d-flex flex-column justify-content-center align-items-center text-white">
                                <i class="${rec.icon} fa-2x mb-2"></i>
                                <span class="small">${rec.categoryTitle}</span>
                            </div>
                            
                            <!-- Recommendation content -->
                            <div class="p-3 flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0">${rec.title || 'Recommendation'}</h5>
                                    <div>
                                        <span class="badge ${importanceBadgeClass} me-1">${rec.importance ? this.capitalizeFirstLetter(rec.importance) : 'Medium'}</span>
                                        <span class="badge bg-secondary priority-score" title="Priority Score: Higher is more important">${rec.priorityScore}</span>
                                    </div>
                                </div>
                                
                                <p class="mb-3">${rec.description}</p>
                                
                                ${rec.implementation ? `
                                    <div class="implementation-section">
                                        <h6 class="mb-2"><i class="fas fa-tools me-2"></i> Implementation Guide</h6>
                                        <p class="text-muted mb-0">${rec.implementation}</p>
                                    </div>
                                ` : ''}
                                
                                <div class="mt-3 d-flex justify-content-between align-items-center">
                                    <div class="implementation-status" id="status-${recId}">
                                        <span class="badge bg-light text-dark border">Not Started</span>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary copy-rec-btn me-2" data-action="copy-${index}">
                                            <i class="fas fa-copy me-1"></i> Copy
                                        </button>
                                        <div class="btn-group implementation-action-group">
                                            <button class="btn btn-sm btn-outline-success implement-rec-btn" data-action="implement-${index}" 
                                                data-rec-id="${recId}" data-rec-category="${rec.category}">
                                                <i class="fas fa-check me-1"></i> Mark as Implemented
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item in-progress-rec-btn" href="#" data-rec-id="${recId}">
                                                    <i class="fas fa-spinner me-1"></i> Mark as In Progress
                                                </a></li>
                                                <li><a class="dropdown-item reset-rec-btn" href="#" data-rec-id="${recId}">
                                                    <i class="fas fa-undo me-1"></i> Reset Status
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        // Add next steps and additional resources sections
        content += this.generateResourcesSection(processedCategories);
        
        // Add the CSS styles needed for the new UI to the head if they don't exist yet
        if ($('#ai-recommendations-styles').length === 0) {
            $('head').append(this.generateStylesForRecommendations());
        }
        
        // Update the content
        $('#aiRecommendationsContent').html(content);
        
        // Setup event handlers for the buttons
        this.setupRecommendationActions(allRecommendations);
        
        // Store the recommendations in the container and mark as loaded
        $('#aiRecommendationsContent').data('results', recommendations);
        $('#aiRecommendationsContent').data('loaded-from-database', true);
    },
    
    /**
     * Generate HTML for the resources section
     * @param {Set} categories - Set of categories that have recommendations
     * @return {string} HTML content
     */
    generateResourcesSection: function(categories) {
        // Customize the resources based on the categories present
        const resources = [];
        
        // Always include these basic resources
        resources.push({
            icon: 'fas fa-file-alt',
            title: 'Google\'s Search Engine Optimization Guide',
            url: 'https://developers.google.com/search/docs'
        });
        
        resources.push({
            icon: 'fas fa-tachometer-alt',
            title: 'PageSpeed Insights',
            url: 'https://pagespeed.web.dev/',
            description: 'Performance testing'
        });
        
        // Add category-specific resources
        if (categories.has('technical')) {
            resources.push({
                icon: 'fas fa-sitemap',
                title: 'XML Sitemaps Generator',
                url: 'https://www.xml-sitemaps.com/',
                description: 'Create XML sitemaps for your site'
            });
        }
        
        if (categories.has('mobile')) {
            resources.push({
                icon: 'fas fa-mobile-alt',
                title: 'Mobile-Friendly Test',
                url: 'https://search.google.com/test/mobile-friendly',
                description: 'Test how mobile-friendly your site is'
            });
        }
        
        if (categories.has('onpage')) {
            resources.push({
                icon: 'fas fa-heading',
                title: 'Title Tag & Meta Description Preview Tool',
                url: 'https://moz.com/learn/seo/title-tag',
                description: 'Preview how titles appear in search results'
            });
        }
        
        if (categories.has('local')) {
            resources.push({
                icon: 'fas fa-store',
                title: 'Google Business Profile Manager',
                url: 'https://business.google.com/',
                description: 'Manage your local business listing'
            });
        }
        
        // Generate the HTML
        let html = `
            <div class="row mb-4">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-route me-2"></i> Next Steps</h5>
                        </div>
                        <div class="card-body">
                            <ol class="next-steps-list">
                                <li>Address <strong>high priority</strong> items first, focusing on quick wins that can have immediate impact.</li>
                                <li>Implement changes systematically, keeping track of what was modified.</li>
                                <li>After implementation, wait 2-4 weeks for search engines to recrawl your site.</li>
                                <li>Re-analyze your site to measure improvements and identify new opportunities.</li>
                                <li>Create a regular schedule for SEO maintenance and content updates.</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-book me-2"></i> Helpful Resources</h5>
                        </div>
                        <div class="card-body">
        `;
        
        // Add each resource
        resources.forEach(resource => {
            html += `
                <div class="resource-item bg-light">
                    <div class="d-flex align-items-center">
                        <i class="${resource.icon} text-primary me-2"></i>
                        <span>${resource.title} ${resource.description ? `<span class="text-muted">- ${resource.description}</span>` : ''}</span>
                    </div>
                    <a href="${resource.url}" target="_blank" class="stretched-link" aria-label="Visit ${resource.title}"></a>
                </div>
            `;
        });
        
        html += `
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Generated timestamp -->
            <div class="text-muted text-end small mt-4">
                Generated: ${new Date().toLocaleString()}
            </div>
        `;
        
        return html;
    },
    
    /**
     * Generate CSS styles for the recommendations UI
     * @return {string} CSS styles
     */
    generateStylesForRecommendations: function() {
        return `
            <style id="ai-recommendations-styles">
                /* AI Recommendations Styles */
                .ai-recommendations-container {
                    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                }
                
                /* Executive Summary Section */
                .ai-recommendations-container .lead {
                    font-size: 1.1rem;
                    line-height: 1.6;
                    color: #495057;
                }
                
                /* Filter styling */
                .recommendation-filter-group {
                    margin-bottom: 1rem;
                }
                
                .category-filters {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                }
                
                /* Recommendation Cards */
                .recommendation-card {
                    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
                    background-color: #ffffff;
                }
                
                .recommendation-card:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
                }
                
                .recommendation-card.implemented {
                    border-color: #28a745 !important;
                }
                
                .recommendation-card.implemented .category-indicator {
                    background-color: #28a745 !important;
                }
                
                .recommendation-card.in-progress {
                    border-color: #17a2b8 !important;
                }
                
                .recommendation-card.in-progress .category-indicator {
                    background-color: #17a2b8 !important;
                }
                
                .category-indicator {
                    min-height: 100%;
                    width: 80px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    color: white;
                    padding: 1.5rem 0.75rem;
                    position: relative;
                }
                
                .implementation-section {
                    background-color: #f8f9fa;
                    border-radius: 5px;
                    padding: 1rem;
                    margin-top: 1rem;
                }
                
                .priority-score {
                    font-size: 0.7rem;
                    vertical-align: top;
                }
                
                /* Implementation Progress */
                #implementedProgressBar {
                    transition: width 0.5s ease-in-out;
                }
                
                /* Next Steps and Resources */
                .next-steps-list li {
                    padding: 0.5rem 0;
                    line-height: 1.5;
                }
                
                .resource-item {
                    transition: background-color 0.2s ease;
                    border-radius: 5px;
                    padding: 0.75rem;
                    margin-bottom: 0.75rem;
                    position: relative;
                }
                
                .resource-item:hover {
                    background-color: #e9ecef !important;
                }
                
                /* Make the entire resource item clickable */
                .resource-item .stretched-link::after {
                    position: absolute;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0;
                    z-index: 1;
                    content: "";
                }
                
                /* Enhanced mobile styles */
                @media (max-width: 768px) {
                    .category-indicator {
                        width: 60px;
                    }
                    
                    .recommendation-card h5 {
                        font-size: 1rem;
                    }
                    
                    .recommendation-filter-group {
                        flex-wrap: wrap;
                    }
                    
                    .recommendation-filter-group .btn {
                        flex: 1 0 100%;
                        margin-bottom: 0.5rem;
                    }
                }
            </style>
        `;
    },

    /**
     * Setup event handlers for recommendation actions (copy, implement, etc.)
     * @param {Array} recommendations - List of recommendation objects
     */
    setupRecommendationActions: function(recommendations) {
        // Load any previously saved implementation states
        this.loadSavedImplementationStates();
        
        // Copy button functionality
        $('.copy-rec-btn').on('click', function(e) {
            e.preventDefault();
            const actionId = $(this).data('action');
            const index = parseInt(actionId.split('-')[1]);
            const rec = recommendations[index];
            
            // Create text to copy
            let textToCopy = `${rec.title || 'Recommendation'}\n\n${rec.description}`;
            if (rec.implementation) {
                textToCopy += `\n\nImplementation Guide:\n${rec.implementation}`;
            }
            
            // Copy to clipboard
            const tempTextarea = document.createElement('textarea');
            tempTextarea.value = textToCopy;
            document.body.appendChild(tempTextarea);
            tempTextarea.select();
            document.execCommand('copy');
            document.body.removeChild(tempTextarea);
            
            // Show feedback
            const originalHtml = $(this).html();
            $(this).html('<i class="fas fa-check me-1"></i> Copied!');
            
            setTimeout(() => {
                $(this).html(originalHtml);
            }, 2000);
        });
        
        // Implement button functionality
        $('.implement-rec-btn').on('click', function(e) {
            e.preventDefault();
            const recId = $(this).data('rec-id');
            
            // Mark as implemented - update the card style
            $(`#${recId}`)
                .removeClass('in-progress')
                .addClass('implemented');
            
            // Update status badge
            $(`#status-${recId}`).html(`
                <span class="badge bg-success">Implemented</span>
            `);
            
            // Update button state
            $(this).html('<i class="fas fa-check-circle me-1"></i> Implemented')
                .removeClass('btn-outline-success')
                .addClass('btn-success')
                .prop('disabled', true);
            
            // Store implementation status
            AIRecommendations.updateImplementationStats('implemented');
            AIRecommendations.saveImplementationState(recId, 'implemented');
        });
        
        // In Progress button functionality
        $('.in-progress-rec-btn').on('click', function(e) {
            e.preventDefault();
            const recId = $(this).closest('.dropdown-menu').prev().prev().data('rec-id');
            
            // Mark as in progress - update the card style
            $(`#${recId}`)
                .removeClass('implemented')
                .addClass('in-progress');
            
            // Update status badge
            $(`#status-${recId}`).html(`
                <span class="badge bg-info">In Progress</span>
            `);
            
            // Update button state
            const implementBtn = $(`.implement-rec-btn[data-rec-id="${recId}"]`);
            implementBtn.prop('disabled', false)
                .removeClass('btn-success')
                .addClass('btn-outline-success')
                .html('<i class="fas fa-check me-1"></i> Mark as Implemented');
            
            // Store implementation status
            AIRecommendations.updateImplementationStats('inProgress');
            AIRecommendations.saveImplementationState(recId, 'inProgress');
        });
        
        // Reset button functionality
        $('.reset-rec-btn').on('click', function(e) {
            e.preventDefault();
            const recId = $(this).closest('.dropdown-menu').prev().prev().data('rec-id');
            
            // Reset card style
            $(`#${recId}`)
                .removeClass('implemented')
                .removeClass('in-progress');
            
            // Update status badge
            $(`#status-${recId}`).html(`
                <span class="badge bg-light text-dark border">Not Started</span>
            `);
            
            // Update button state
            const implementBtn = $(`.implement-rec-btn[data-rec-id="${recId}"]`);
            implementBtn.prop('disabled', false)
                .removeClass('btn-success')
                .addClass('btn-outline-success')
                .html('<i class="fas fa-check me-1"></i> Mark as Implemented');
            
            // Store implementation status
            AIRecommendations.updateImplementationStats('reset');
            AIRecommendations.saveImplementationState(recId, 'reset');
        });
    },
    
    /**
     * Update implementation statistics and UI
     * @param {string} action - Type of action ('implemented', 'inProgress', 'reset')
     */
    updateImplementationStats: function(action) {
        // Update stats based on action
        if (action === 'implemented') {
            this.implementationStats.implemented++;
            this.implementationStats.pending--;
        } else if (action === 'inProgress') {
            this.implementationStats.inProgress++;
            this.implementationStats.pending--;
        } else if (action === 'reset') {
            this.implementationStats.pending++;
            
            // Determine which counter to decrement
            if ($('#implementedCount').text() > this.implementationStats.implemented) {
                this.implementationStats.implemented--;
            } else {
                this.implementationStats.inProgress--;
            }
        }
        
        // Ensure no negative values
        this.implementationStats.implemented = Math.max(0, this.implementationStats.implemented);
        this.implementationStats.inProgress = Math.max(0, this.implementationStats.inProgress);
        this.implementationStats.pending = Math.max(0, this.implementationStats.pending);
        
        // Update UI counters
        $('#implementedCount').text(this.implementationStats.implemented);
        $('#pendingCount').text(this.implementationStats.pending);
        
        // Calculate and update progress bar
        const totalRecs = this.implementationStats.implemented + 
                          this.implementationStats.inProgress + 
                          this.implementationStats.pending;
        
        if (totalRecs > 0) {
            const percentComplete = Math.round((this.implementationStats.implemented / totalRecs) * 100);
            $('#implementedProgressBar').css('width', percentComplete + '%')
                                      .attr('aria-valuenow', percentComplete)
                                      .text(percentComplete + '%');
        }
        
        // Save stats
        this.saveImplementationData();
    },
    
    /**
     * Save implementation state for a recommendation
     * @param {string} recId - Recommendation ID
     * @param {string} state - State ('implemented', 'inProgress', 'reset')
     */
    saveImplementationState: function(recId, state) {
        try {
            // Get existing data or initialize new
            let implData = JSON.parse(localStorage.getItem('recommendation_states') || '{}');
            
            // Clean up 'reset' state by removing entry
            if (state === 'reset') {
                if (implData[recId]) {
                    delete implData[recId];
                }
            } else {
                // Save state with timestamp
                implData[recId] = {
                    state: state,
                    timestamp: new Date().toISOString()
                };
            }
            
            // Save back to localStorage
            localStorage.setItem('recommendation_states', JSON.stringify(implData));
        } catch (error) {
            console.error('Failed to save implementation state:', error);
        }
    },
    
    /**
     * Load saved implementation states and update UI
     */
    loadSavedImplementationStates: function() {
        try {
            const savedStates = JSON.parse(localStorage.getItem('recommendation_states') || '{}');
            const recIds = Object.keys(savedStates);
            
            if (recIds.length === 0) return;
            
            // Reset counters
            this.implementationStats.implemented = 0;
            this.implementationStats.inProgress = 0;
            
            // Apply saved states
            recIds.forEach(recId => {
                const state = savedStates[recId].state;
                
                if (state === 'implemented') {
                    $(`#${recId}`).addClass('implemented');
                    $(`#status-${recId}`).html(`<span class="badge bg-success">Implemented</span>`);
                    
                    const implementBtn = $(`.implement-rec-btn[data-rec-id="${recId}"]`);
                    implementBtn.html('<i class="fas fa-check-circle me-1"></i> Implemented')
                        .removeClass('btn-outline-success')
                        .addClass('btn-success')
                        .prop('disabled', true);
                    
                    this.implementationStats.implemented++;
                    this.implementationStats.pending--;
                } else if (state === 'inProgress') {
                    $(`#${recId}`).addClass('in-progress');
                    $(`#status-${recId}`).html(`<span class="badge bg-info">In Progress</span>`);
                    
                    this.implementationStats.inProgress++;
                    this.implementationStats.pending--;
                }
            });
            
            // Update counters in the UI
            $('#implementedCount').text(this.implementationStats.implemented);
            $('#pendingCount').text(this.implementationStats.pending);
            
            // Update progress bar
            const totalRecs = this.implementationStats.implemented + 
                              this.implementationStats.inProgress + 
                              this.implementationStats.pending;
            
            if (totalRecs > 0) {
                const percentComplete = Math.round((this.implementationStats.implemented / totalRecs) * 100);
                $('#implementedProgressBar').css('width', percentComplete + '%')
                                          .attr('aria-valuenow', percentComplete)
                                          .text(percentComplete + '%');
            }
        } catch (error) {
            console.error('Failed to load implementation states:', error);
        }
    },
    
    /**
     * Capitalize first letter of a string
     * @param {string} string - String to capitalize
     * @return {string} Capitalized string
     */
    capitalizeFirstLetter: function(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
};
