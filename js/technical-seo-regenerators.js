/**
 * technical-seo-regenerators.js - Implements regeneration for sitemap and robots.txt
 * Enhanced version with improved textarea handling and additional features
 */

const TechnicalSEORegenerators = {
    /**
     * Initialize the regeneration functionality
     */
    initialize: function() {
        this.preventOptionsCollapse();
         
        // Listen for the analysisCompleted event
        $(document).on('analysisCompleted', () => {
            this.setupOptionsVisibility();
            this.preventOptionsCollapse();
            this.setupEventListeners();
            this.addReanalyzeButton();
            
            // Run additional check after a delay to catch any delayed rendering
            setTimeout(() => this.forceOptionsVisibility(), 1000);
        });
        
        // Also handle when results are loaded from database
        $(document).on('analysisLoaded', () => {
            this.setupOptionsVisibility();
            this.preventOptionsCollapse();
            this.setupEventListeners();
            this.addReanalyzeButton();
            
            // Run additional check after a delay for database-loaded content
            setTimeout(() => this.forceOptionsVisibility(), 1000);
        });
        
        // Run on document ready as well
        $(document).ready(() => {
            setTimeout(() => {
                this.setupOptionsVisibility();
                this.preventOptionsCollapse();
                this.setupEventListeners();
                this.addReanalyzeButton();
                this.forceOptionsVisibility();
            }, 1000); // Small delay to ensure DOM is fully processed
        });
        
        // Add listeners for accordion expansion, which might reveal recommendations
        $(document).on('shown.bs.collapse', '#technicalCollapse', () => {
            setTimeout(() => this.preventOptionsCollapse(), 300);
        });    
        
        $(document).on('hide.bs.collapse', '#technicalCollapse', (e) => {
            // Find all options containers within the technical collapse area
            const optionsContainers = $('#technicalCollapse .options-container');
            
            // If there are options containers, detach them temporarily
            if (optionsContainers.length > 0) {
                // Store them in a data attribute on the parent
                $('#technicalCollapse').data('optionsContainers', optionsContainers.detach());
                
                // Create a placeholder to reattach them later
                $('#technicalCollapse').append('<div id="options-placeholder"></div>');
            }
        });        
        
        // Reattach options containers when accordion is shown again
        $(document).on('shown.bs.collapse', '#technicalCollapse', (e) => {
            const optionsContainers = $('#technicalCollapse').data('optionsContainers');
            if (optionsContainers && optionsContainers.length > 0) {
                // Replace the placeholder with the detached options containers
                $('#options-placeholder').replaceWith(optionsContainers);
                
                // Clear the stored data
                $('#technicalCollapse').removeData('optionsContainers');
            }
        });        
        
        // Add special handling for AI Recommendations section
        $(document).on('aiRecommendationsStarted', () => {
            // Apply again to ensure options stay visible
            setTimeout(() => this.preventOptionsCollapse(), 300);
        });
        
        // Create a mutation observer to watch for changes to the technical content
        this.setupMutationObserver();        
    },
    
    /**
     * Add a Reanalyze button below the Overall Score
     */
    addReanalyzeButton: function() {
        // Check if the button already exists
        if ($('#reanalyzeBtn').length === 0) {
            // Find the overall score container
            const $scoreContainer = $('.score-gauge');
            if ($scoreContainer.length > 0) {
                // Add Reanalyze button after the score container
                const $reanalyzeBtn = $(
                    `<button id="reanalyzeBtn" class="btn btn-primary mt-3">
                        <i class="fas fa-sync-alt me-1"></i> Reanalyze Website
                    </button>`
                );
                
                // First look for the parent that contains the score gauge
                const $parent = $scoreContainer.closest('.card-body');
                
                if ($parent.length > 0) {
                    // Insert after the score-value element or at the end of the card-body
                    const $scoreValue = $parent.find('.score-value');
                    if ($scoreValue.length > 0) {
                        $scoreValue.after($reanalyzeBtn);
                    } else {
                        $parent.append($reanalyzeBtn);
                    }
                } else {
                    // Fallback - just append after the score gauge
                    $scoreContainer.after($reanalyzeBtn);
                }
                
                // Add click handler
                $('#reanalyzeBtn').on('click', () => {
                    // Get the current URL
                    const url = $('#websiteUrl').val();
                    if (url) {
                        // Simulate the form submission
                        $('#seoAnalysisForm').trigger('submit');
                    } else {
                        this.showNotification('Please enter a website URL first', 'warning');
                    }
                });
            }
        }
    },
    
    // Add this new method to set up a mutation observer
    setupMutationObserver: function() {
        // Target the technical content section
        const technicalContent = document.getElementById('technicalContent');
        if (technicalContent) {
            const observer = new MutationObserver((mutations) => {
                // When mutations occur, check if we need to restore options
                const needsRestore = mutations.some(mutation => {
                    // Check for removed nodes that were options containers
                    return Array.from(mutation.removedNodes).some(node => {
                        return $(node).find('.options-container').length > 0 ||
                               $(node).hasClass('options-container');
                    });
                });
                
                if (needsRestore) {
                    // If options containers were removed, restore them
                    setTimeout(() => this.preventOptionsCollapse(), 100);
                }
            });
            
            // Configure and start the observer
            observer.observe(technicalContent, {
                childList: true,  // Watch for changes to child elements
                subtree: true,    // Watch the entire subtree
                attributes: true, // Watch for attribute changes
            });
        }
    },
    
    // Add this new method to prevent options from being collapsed
    preventOptionsCollapse: function() {
        console.log("Preventing options containers from being collapsed");
        
        // Modify all recommendation items to preserve their options containers
        $('#technicalContent .recommendation-item').each((index, item) => {
            const $item = $(item);
            const title = $item.find('h5').text().toLowerCase();
            
            // Only process relevant recommendations
            if (title.includes('sitemap') || title.includes('robots.txt')) {
                // Find options container
                let $optionsContainer = $item.find('.options-container');
                
                if ($optionsContainer.length === 0) {
                    // Create if it doesn't exist
                    this.createOptionsContainer($item, title.includes('sitemap'));
                    $optionsContainer = $item.find('.options-container');
                }
                
                // Protect from collapse by removing collapse-related classes
                $optionsContainer.removeClass('collapse d-none')
                                 .addClass('no-collapse')
                                 .attr('data-no-collapse', 'true');
                
                // Remove any collapse-related attributes
                $optionsContainer.removeAttr('data-bs-toggle')
                                .removeAttr('data-bs-target')
                                .removeAttr('aria-expanded')
                                .removeAttr('aria-controls');
                
                // Move options container outside of any collapsible parent
                // This is a key step - ensuring the container exists outside the accordion
                const $recommendationParent = $item.parent();
                if ($recommendationParent.hasClass('collapse') || 
                    $recommendationParent.hasClass('accordion-collapse')) {
                    
                    // Clone the options container and attach it after the accordion
                    const $clonedContainer = $optionsContainer.clone(true);
                    $clonedContainer.addClass('detached-options-container');
                    
                    // If there's no existing detached container, add it
                    const existingDetached = $(`#detached-${index}`);
                    if (existingDetached.length === 0) {
                        $recommendationParent.after(
                            `<div id="detached-${index}" class="detached-container"></div>`
                        );
                        $(`#detached-${index}`).append($clonedContainer);
                        
                        // Link the original and detached containers
                        $optionsContainer.attr('data-detached-id', `detached-${index}`);
                        $clonedContainer.attr('data-original-id', $optionsContainer.attr('id') || 'no-id');
                    }
                }
            }
        });
        
        // Make regenerate buttons directly visible
        $('#technicalContent .regenerate-btn').css({
            'display': 'inline-block',
            'visibility': 'visible',
            'opacity': '1'
        });
        
        // Ensure buttons get proper event handlers
        this.setupEventListeners();
    },
    
    /**
     * Force visibility of options containers - used as a fallback method
     * This addresses cases where containers remain hidden after loading reports
     */
    forceOptionsVisibility: function() {
        console.log("Force-showing options containers");
        
        // First find all recommendation items about sitemaps and robots.txt
        $('#technicalContent .recommendation-item').each((index, item) => {
            const $item = $(item);
            const title = $item.find('h5').text().toLowerCase();
            
            if (title.includes('sitemap') || title.includes('robots.txt')) {
                // Find or ensure there's an options container
                let $optionsContainer = $item.find('.options-container');
                
                if ($optionsContainer.length === 0) {
                    // Create the options container if it doesn't exist
                    this.createOptionsContainer($item, title.includes('sitemap'));
                } else {
                    // Make existing container visible
                    $optionsContainer.removeClass('d-none collapse').addClass('show').css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                    
                    // Make any collapse elements inside visible too
                    $optionsContainer.find('.collapse').removeClass('collapse').addClass('show').css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                }
                
                // Ensure the regenerate button is visible
                $item.find('.regenerate-btn').css({
                    'display': 'inline-block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
        });
        
        // Also check specifically for regenerate buttons that might exist elsewhere
        $('#technicalContent .regenerate-btn').each((index, btn) => {
            const $btn = $(btn);
            const $container = $btn.closest('.options-container');
            const $collapse = $btn.closest('.collapse');
            
            // Force container to be visible
            if ($container.length > 0) {
                $container.removeClass('d-none collapse').addClass('show').css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
            
            // Force collapse to be expanded
            if ($collapse.length > 0) {
                $collapse.removeClass('collapse').addClass('show').css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
                
                // Update any associated toggle buttons
                const collapseId = $collapse.attr('id');
                if (collapseId) {
                    $(`[data-bs-target="#${collapseId}"]`).removeClass('collapsed').attr('aria-expanded', 'true');
                }
            }
            
            // Make sure the button itself is visible
            $btn.css({
                'display': 'inline-block',
                'visibility': 'visible',
                'opacity': '1'
            });
        });
    },
    
    /**
     * Ensure options containers are always visible
     */
    setupOptionsVisibility: function() {
        // Find all recommendation items related to sitemap or robots.txt
        $('#technicalContent .recommendation-item').each((index, item) => {
            const $item = $(item);
            const title = $item.find('h5').text().toLowerCase();
            
            // Process based on recommendation type
            if (title.includes('sitemap') || title.includes('robots.txt')) {
                // Find or create options container
                let $optionsContainer = $item.find('.options-container');
                
                if ($optionsContainer.length === 0) {
                    // Options container doesn't exist, need to create it
                    this.createOptionsContainer($item, title.includes('sitemap'));
                } else {
                    // Make sure it's visible and not collapsed
                    $optionsContainer.removeClass('d-none collapse').addClass('show').css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                    
                    // Ensure any collapse inside is properly expanded
                    const $collapse = $optionsContainer.find('.collapse');
                    if ($collapse.length > 0) {
                        // Force the collapse to be shown
                        $collapse.removeClass('collapse').addClass('show').css({
                            'display': 'block',
                            'visibility': 'visible',
                            'opacity': '1'
                        });
                        
                        // Ensure any button that controls this collapse is not collapsed
                        const collapseId = $collapse.attr('id');
                        if (collapseId) {
                            $(`[data-bs-target="#${collapseId}"]`).removeClass('collapsed').attr('aria-expanded', 'true');
                        }
                    }
                }
            }
        });
    },
    
/**
 * Create options container for a recommendation item
 * @param {jQuery} $item - The recommendation item element
 * @param {boolean} isSitemap - Whether this is for sitemap (true) or robots.txt (false)
 */
createOptionsContainer: function($item, isSitemap) {
    // First, check if this item already has options containers and textareas
    const existingOptionsContainer = $item.find('.options-container');
    
    // FIXED: we need to check for a textarea properly
    const existingTextarea = $item.find('textarea.code-textarea').length > 0 ? 
                           $item.find('textarea.code-textarea') : 
                           $item.parent().find('textarea.code-textarea');
                           
    // FIXED: More robust check for existing content
    if (existingOptionsContainer.length > 0 && existingTextarea.length > 0) {
        existingOptionsContainer.css('display', 'block');
        existingTextarea.closest('.mb-3').css('display', 'block');
        return;
    }
    
    // Find code container or create one
    let $codeContainer = $item.find('.code-example');
    if ($codeContainer.length === 0) {
        $codeContainer = $('<div class="code-example mt-3"></div>');
        $item.append($codeContainer);
    }
    
    // Create unique IDs for elements to avoid conflicts
    const uniqueId = 'options-' + Math.random().toString(36).substring(2, 9);
    
    // Create specific IDs for textareas
    const textareaId = isSitemap ? `sitemap-textarea-${uniqueId}` : `robots-textarea-${uniqueId}`;
    
    // FIXED: Check if a textarea already exists somewhere in the parent containers
    let existingTextareaInParent = $item.parents('.accordion-item').find('textarea.code-textarea');
    
    // Create textarea only if no existing textarea is found
    if (existingTextareaInParent.length === 0) {
        const $textareaWrapper = $('<div class="mb-3"></div>');
        const $textarea = $('<textarea></textarea>')
            .attr('id', textareaId)
            .addClass(`form-control code-textarea ${isSitemap ? 'sitemap-textarea' : 'robots-textarea'}`)
            .attr('rows', 10)
            .attr('readonly', true);
        
        $textareaWrapper.append($textarea);
        
        // Add textarea directly to recommendation item, before code container
        $codeContainer.before($textareaWrapper);
        
        // FIXED: Store the textarea ID in the recommendation item for later reference
        $item.attr('data-textarea-id', textareaId);
    } else {
        // FIXED: If we found an existing textarea, use its ID in the regenerate button
        const existingId = existingTextareaInParent.attr('id') || textareaId;
        $item.attr('data-textarea-id', existingId);
    }
    
    // Create options container HTML (without textarea)
    let optionsHtml;
    
    if (isSitemap) {
        // Sitemap options - ENHANCED with proper max pages crawl input and default value
        optionsHtml = `
            <div class="options-container mt-3" style="display:block;">
                <div class="card card-body bg-light">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeImages-${uniqueId}" checked>
                                <label class="form-check-label" for="includeImages-${uniqueId}">
                                    Include Images
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeDates-${uniqueId}" checked>
                                <label class="form-check-label" for="includeDates-${uniqueId}">
                                    Include Last Modified Dates
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="realCrawl-${uniqueId}" checked>
                                <label class="form-check-label" for="realCrawl-${uniqueId}">
                                    Perform Real Website Crawl
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="maxPages-${uniqueId}" class="form-label small">Max pages to crawl (5-100):</label>
                                <input type="number" class="form-control form-control-sm" id="maxPages-${uniqueId}" value="30" min="5" max="100" placeholder="Max Pages">
                                <small class="text-muted d-block">Default: 30 pages</small>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary mt-2 regenerate-btn" data-type="sitemap" data-target="${$item.attr('data-textarea-id')}" style="display:inline-block;">
                        <i class="fas fa-sync-alt"></i> Regenerate Sitemap
                    </button>
                </div>
            </div>
        `;
    } else {
        // Robots.txt options
        optionsHtml = `
            <div class="options-container mt-3" style="display:block;">
                <div class="card card-body bg-light">
                    <div class="row g-2">
                        <div class="col-md-12">
                            <label class="form-label">Disallow Directories:</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input disallow-check" type="checkbox" id="disallowAdmin-${uniqueId}" checked>
                                        <label class="form-check-label" for="disallowAdmin-${uniqueId}">
                                            /admin/
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input disallow-check" type="checkbox" id="disallowCgi-${uniqueId}" checked>
                                        <label class="form-check-label" for="disallowCgi-${uniqueId}">
                                            /cgi-bin/
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input disallow-check" type="checkbox" id="disallowTmp-${uniqueId}" checked>
                                        <label class="form-check-label" for="disallowTmp-${uniqueId}">
                                            /tmp/
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input disallow-check" type="checkbox" id="disallowWpAdmin-${uniqueId}" checked>
                                        <label class="form-check-label" for="disallowWpAdmin-${uniqueId}">
                                            /wp-admin/
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeSitemap-${uniqueId}" checked>
                                <label class="form-check-label" for="includeSitemap-${uniqueId}">
                                    Include Sitemap Reference
                                </label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary mt-2 regenerate-btn" data-type="robots" data-target="${$item.attr('data-textarea-id')}" style="display:inline-block;">
                        <i class="fas fa-sync-alt"></i> Regenerate Robots.txt
                    </button>
                </div>
            </div>
        `;
    }
    
    // Append options container to recommendation item
    $codeContainer.append(optionsHtml);
},
    
    /**
     * Set up event listeners for regenerate buttons
     */
    setupEventListeners: function() {
        // Remove existing click handlers and add new ones
        $('.regenerate-btn').off('click').on('click', (e) => {
            const $button = $(e.currentTarget);
            const type = $button.data('type') || 
                         ($button.text().toLowerCase().includes('sitemap') ? 'sitemap' : 'robots');
            
            if (type === 'sitemap') {
                this.handleSitemapRegeneration($button);
            } else {
                this.handleRobotsRegeneration($button);
            }
        });
    },
    
    /**
     * Handle sitemap regeneration
     * @param {jQuery} $button - The clicked button
     */
    handleSitemapRegeneration: function($button) {
        // Find container elements
        const $container = $button.closest('.card-body');
        const $item = $button.closest('.recommendation-item');
        
        // Get target textarea ID from button's data attribute
        const textareaId = $button.data('target');
        let $textarea;
        
        // FIXED: Better textarea finding logic
        if (textareaId) {
            // First try direct ID selector
            $textarea = $('#' + textareaId);
            
            // If not found, try looking in the parent accordion item
            if (!$textarea.length) {
                $textarea = $item.closest('.accordion-item').find('#' + textareaId);
            }
            
            // If still not found, try looking in the entire technical content
            if (!$textarea.length) {
                $textarea = $('#technicalContent').find('#' + textareaId);
            }
        }
        
        // If textarea not found by ID, look for any sitemap textarea in the same accordion item
        if (!$textarea || $textarea.length === 0) {
            $textarea = $item.closest('.accordion-item').find('.sitemap-textarea').first();
        }
        
        // If still not found, look for any code textarea in the same accordion item
        if ($textarea.length === 0) {
            $textarea = $item.closest('.accordion-item').find('textarea.code-textarea').first();
        }
        
        // FIXED: Last resort - look for any textarea in the technical content
        if ($textarea.length === 0) {
            $textarea = $('#technicalContent').find('textarea.code-textarea').first();
        }
        
        // If we still can't find a textarea, create one
        if ($textarea.length === 0) {
            const newTextareaId = 'sitemap-textarea-' + Math.random().toString(36).substring(2, 9);
            const $textareaWrapper = $('<div class="mb-3"></div>');
            const $newTextarea = $('<textarea></textarea>')
                .attr('id', newTextareaId)
                .addClass('form-control code-textarea sitemap-textarea')
                .attr('rows', 10)
                .attr('readonly', true);
            
            $textareaWrapper.append($newTextarea);
            
            // Add it to the recommendation item
            $item.prepend($textareaWrapper);
            
            // Update the button's target
            $button.attr('data-target', newTextareaId);
            
            // Update reference
            $textarea = $newTextarea;
        }
        
        // ENHANCED: Get the current website URL from the form input
        const websiteUrl = $('#websiteUrl').val() || window.location.origin;
        
        // Get options from checkboxes (handle dynamic IDs)
        const includeImages = $container.find('[id^="includeImages-"]').is(':checked');
        const includeDates = $container.find('[id^="includeDates-"]').is(':checked');
        const realCrawl = $container.find('[id^="realCrawl-"]').is(':checked');
        
        // ENHANCED: Get max pages value with validation
        let maxPages = parseInt($container.find('[id^="maxPages-"]').val() || 30);
        if (isNaN(maxPages) || maxPages < 5) maxPages = 5;
        if (maxPages > 100) maxPages = 100;
        
        // Show loading state
        const originalButtonText = $button.html();
        $button.html('<i class="fas fa-spinner fa-spin"></i> Generating...');
        $button.prop('disabled', true);
        
        // Show loading message in textarea
        $textarea.val('Crawling website and generating sitemap...\nThis may take a moment depending on the website size.');
        
        if (realCrawl) {
            // Use AJAX to call the server-side crawler
            $.ajax({
                url: 'api/site-crawler.php',
                type: 'POST',
                data: {
                    url: websiteUrl,
                    maxPages: maxPages,
                    includeImages: includeImages ? '1' : '0',
                    includeDates: includeDates ? '1' : '0'
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success && response.data && response.data.sitemap) {
                        // FIXED: Ensure textarea is still in DOM
                        if ($textarea.closest('body').length > 0) {
                            // Update textarea with generated sitemap
                            $textarea.val(response.data.sitemap);
                        } else {
                            // If textarea was removed from DOM, try to find it again
                            const $newTextarea = $('#' + $textarea.attr('id'));
                            if ($newTextarea.length > 0) {
                                $newTextarea.val(response.data.sitemap);
                            } else {
                                // If we can't find it, create a new one
                                this.createNewTextarea($item, response.data.sitemap, true);
                            }
                        }
                        
                        // Show success message
                        this.showNotification(`Sitemap generated successfully with ${response.data.pageCount} pages!`, 'success');
                    } else {
                        // Handle error and fall back to client-side generation
                        console.error('Failed to generate sitemap:', response.message);
                        this.showNotification('Server-side crawling failed, using client-side generation instead.', 'warning');
                        
                        this.generateSitemapClientSide($textarea, websiteUrl, includeImages, includeDates, maxPages);
                    }
                },
                error: (xhr, status, error) => {
                    // Handle error and fall back to client-side generation
                    console.error('AJAX error:', error);
                    this.showNotification('Server-side crawling failed, using client-side generation instead.', 'warning');
                    
                    this.generateSitemapClientSide($textarea, websiteUrl, includeImages, includeDates, maxPages);
                },
                complete: () => {
                    // Restore button state
                    $button.html(originalButtonText);
                    $button.prop('disabled', false);
                }
            });
        } else {
            // Use client-side generation
            this.generateSitemapClientSide($textarea, websiteUrl, includeImages, includeDates, maxPages);
            
            // Restore button state
            setTimeout(() => {
                $button.html(originalButtonText);
                $button.prop('disabled', false);
                this.showNotification('Sitemap generated successfully!', 'success');
            }, 1000);
        }
    },
    
    /**
     * Helper method to create a new textarea if needed
     * @param {jQuery} $item - The recommendation item
     * @param {string} content - Content to put in the textarea
     * @param {boolean} isSitemap - Whether this is a sitemap textarea
     * @return {jQuery} The created textarea
     */
    createNewTextarea: function($item, content, isSitemap) {
        const newTextareaId = (isSitemap ? 'sitemap' : 'robots') + '-textarea-' + Math.random().toString(36).substring(2, 9);
        const $textareaWrapper = $('<div class="mb-3"></div>');
        const $newTextarea = $('<textarea></textarea>')
            .attr('id', newTextareaId)
            .addClass(`form-control code-textarea ${isSitemap ? 'sitemap-textarea' : 'robots-textarea'}`)
            .attr('rows', 10)
            .attr('readonly', true)
            .val(content);
        
        $textareaWrapper.append($newTextarea);
        
        // Add it to the recommendation item at top
        $item.prepend($textareaWrapper);
        
        // Update any regenerate buttons
        $item.find(`.regenerate-btn[data-type="${isSitemap ? 'sitemap' : 'robots'}"]`).attr('data-target', newTextareaId);
        
        return $newTextarea;
    },
    
    /**
     * Generate sitemap on the client side
     * @param {jQuery} $textarea - The textarea element
     * @param {string} url - Website URL
     * @param {boolean} includeImages - Whether to include image tags
     * @param {boolean} includeDates - Whether to include lastmod dates
     * @param {number} maxPages - Maximum number of pages to include
     */
    generateSitemapClientSide: function($textarea, url, includeImages, includeDates, maxPages = 30) {
        // Use the actual website URL
        const currentDate = new Date().toISOString().split('T')[0];
        
        // Extract domain and normalize URL
        let domain;
        let normalizedUrl;
        
        try {
            const urlObj = new URL(url);
            domain = urlObj.hostname;
            normalizedUrl = urlObj.toString().replace(/\/$/, '');
        } catch (e) {
            // If URL is invalid, use the origin as fallback
            domain = window.location.hostname;
            normalizedUrl = window.location.origin;
        }
        
        // ENHANCED: Create pages based on the actual domain being analyzed and respecting maxPages
        const pages = [];
        
        // Always include the homepage
        pages.push({ url: normalizedUrl + '/', priority: '1.0', changefreq: 'weekly' });
        
        // Common page patterns for most websites
        const commonPages = [
            { path: '/about', priority: '0.8', changefreq: 'monthly' },
            { path: '/services', priority: '0.8', changefreq: 'monthly' },
            { path: '/products', priority: '0.8', changefreq: 'monthly' },
            { path: '/contact', priority: '0.7', changefreq: 'monthly' },
            { path: '/blog', priority: '0.9', changefreq: 'weekly' },
            { path: '/blog/post-1', priority: '0.6', changefreq: 'monthly' },
            { path: '/blog/post-2', priority: '0.6', changefreq: 'monthly' },
            { path: '/privacy-policy', priority: '0.5', changefreq: 'yearly' },
            { path: '/terms-of-service', priority: '0.5', changefreq: 'yearly' }
        ];
        
        // Add as many pages as specified by maxPages
        const pagesToAdd = Math.min(maxPages - 1, commonPages.length); // -1 because we already added homepage
        for (let i = 0; i < pagesToAdd; i++) {
            pages.push({
                url: normalizedUrl + commonPages[i].path,
                priority: commonPages[i].priority,
                changefreq: commonPages[i].changefreq
            });
        }
        
        // If we need more pages to reach maxPages, add some numbered blog posts
        if (pagesToAdd < maxPages - 1 && pagesToAdd >= commonPages.length) {
            let blogPostStart = 3; // Starting from post-3 since we already have post-1 and post-2
            while (pages.length < maxPages) {
                pages.push({
                    url: normalizedUrl + `/blog/post-${blogPostStart}`,
                    priority: '0.6',
                    changefreq: 'monthly'
                });
                blogPostStart++;
            }
        }
        
        // Create sitemap XML
        let sitemap = '<?xml version="1.0" encoding="UTF-8"?>\n';
        
        if (includeImages) {
            sitemap += '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">\n';
        } else {
            sitemap += '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';
        }
        
        // Add each page
        pages.forEach(page => {
            sitemap += '  <url>\n';
            sitemap += '    <loc>' + page.url + '</loc>\n';
            
            if (includeDates) {
                sitemap += '    <lastmod>' + currentDate + '</lastmod>\n';
            }
            
            sitemap += '    <changefreq>' + page.changefreq + '</changefreq>\n';
            sitemap += '    <priority>' + page.priority + '</priority>\n';
            
            if (includeImages && page.url.includes('/product')) {
                sitemap += '    <image:image>\n';
                sitemap += '      <image:loc>' + normalizedUrl + '/images/product1.jpg</image:loc>\n';
                sitemap += '      <image:title>Product Image</image:title>\n';
                sitemap += '    </image:image>\n';
            }
            
            sitemap += '  </url>\n';
        });
        
        sitemap += '</urlset>';
        
        // FIXED: Check if textarea is still in DOM before updating
        if ($textarea.closest('body').length > 0) {
            // Update textarea with generated sitemap
            $textarea.val(sitemap);
        } else {
            // If textarea has been removed from DOM, find it again by ID
            const textareaId = $textarea.attr('id');
            const $newTextarea = $('#' + textareaId);
            if ($newTextarea.length > 0) {
                $newTextarea.val(sitemap);
            } else {
                // If we can't find it by ID, look for any sitemap textarea in the document
                const $anySitemapTextarea = $('.sitemap-textarea');
                if ($anySitemapTextarea.length > 0) {
                    $anySitemapTextarea.first().val(sitemap);
                } else {
                    console.error('Could not find appropriate textarea for sitemap content');
                }
            }
        }
    }
	
	
	
}