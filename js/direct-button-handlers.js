/**
 * direct-button-handlers.js - Directly intercepts Get SEO Report and Check Again button clicks
 * This ensures options containers are displayed after reports are loaded
 */

$(document).ready(function() {
    // Intercept the "Get SEO Report" button click
    $('#analyzeSeoBtn').on('click', function() {
        console.log("Get SEO Report button clicked - setting up delayed options visibility check");
        
        // Set multiple delayed checks to catch when the report is loaded
        setTimeout(forceOptionsVisibility, 5000);  // 5 seconds
        setTimeout(forceOptionsVisibility, 10000); // 10 seconds
        setTimeout(forceOptionsVisibility, 15000); // 15 seconds
    });
    
    // Intercept the "Check Again" button when it's added to the DOM
    $(document).on('click', '#checkAgainBtn', function() {
        console.log("Check Again button clicked - setting up delayed options visibility check");
        
        // Set multiple delayed checks to catch when the report is loaded
        setTimeout(forceOptionsVisibility, 5000);  // 5 seconds
        setTimeout(forceOptionsVisibility, 10000); // 10 seconds
        setTimeout(forceOptionsVisibility, 15000); // 15 seconds
    });
    
    // Also listen for accordion expansion which reveals the technical content
    $(document).on('shown.bs.collapse', '#technicalCollapse', function() {
        console.log("Technical section expanded - checking for options containers");
        setTimeout(forceOptionsVisibility, 1000);
    });
    
    /**
     * Force visibility of options containers, specifically targeting XML Sitemap and Robots.txt items
     */
    function forceOptionsVisibility() {
        console.log("Forcing options visibility for sitemap and robots.txt items");
        
        // First look for all recommendation items related to sitemaps or robots.txt
        $('#technicalContent .recommendation-item').each(function() {
            const $item = $(this);
            const itemText = $item.text().toLowerCase();
            const $heading = $item.find('h5');
            const headingText = $heading.length > 0 ? $heading.text().toLowerCase() : '';
            
            // Check if this item is about sitemap or robots.txt
            if (itemText.includes('sitemap') || itemText.includes('robots.txt') || 
                headingText.includes('sitemap') || headingText.includes('robots.txt')) {
                
                console.log("Found recommendation item about: " + headingText);
                
                // Find code container or create one if it doesn't exist
                let $codeContainer = $item.find('.code-example');
                if ($codeContainer.length === 0) {
                    console.log("No code container found, creating one");
                    $codeContainer = $('<div class="code-example mt-3"></div>');
                    $item.append($codeContainer);
                }
                
                // Is this for sitemap or robots.txt?
                const isSitemap = itemText.includes('sitemap') || headingText.includes('sitemap');
                
                // Create unique ID for this instance
                const uniqueId = 'options-' + Math.random().toString(36).substr(2, 9);
                
                // Check if options container already exists
                let $optionsContainer = $codeContainer.find('.options-container');
                
                if ($optionsContainer.length === 0) {
                    console.log("Creating new options container for: " + (isSitemap ? "sitemap" : "robots.txt"));
                    
                    // Create new options container HTML
                    let optionsHtml = '';
                    
                    if (isSitemap) {
                        // Sitemap options
                        optionsHtml = `
                            <div class="options-container mt-3" style="display:block !important; visibility:visible !important;">
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
                                            <input type="number" class="form-control form-control-sm" id="maxPages-${uniqueId}" value="20" min="5" max="100" placeholder="Max Pages">
                                            <small class="text-muted">Max pages to crawl (5-100)</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-primary mt-2 regenerate-btn" data-type="sitemap" style="display:inline-block !important; visibility:visible !important;">
                                        <i class="fas fa-sync-alt"></i> Regenerate Sitemap
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        // Robots.txt options
                        optionsHtml = `
                            <div class="options-container mt-3" style="display:block !important; visibility:visible !important;">
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
                                    <button class="btn btn-sm btn-primary mt-2 regenerate-btn" data-type="robots" style="display:inline-block !important; visibility:visible !important;">
                                        <i class="fas fa-sync-alt"></i> Regenerate Robots.txt
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Append the options container to the code container
                    $codeContainer.append(optionsHtml);
                    
                    // Make sure the newly added elements are visible
                    $codeContainer.find('.options-container').css({
                        'display': 'block !important',
                        'visibility': 'visible !important',
                        'opacity': '1 !important'
                    }).attr('style', 'display:block !important; visibility:visible !important;');
                    
                    // Ensure button handlers are set up
                    $codeContainer.find('.regenerate-btn').on('click', function() {
                        const type = $(this).data('type') || ($(this).text().toLowerCase().includes('sitemap') ? 'sitemap' : 'robots');
                        
                        if (type === 'sitemap') {
                            if (typeof TechnicalSEORegenerators !== 'undefined') {
                                TechnicalSEORegenerators.handleSitemapRegeneration($(this));
                            } else {
                                console.error("TechnicalSEORegenerators not found!");
                            }
                        } else {
                            if (typeof TechnicalSEORegenerators !== 'undefined') {
                                TechnicalSEORegenerators.handleRobotsRegeneration($(this));
                            } else {
                                console.error("TechnicalSEORegenerators not found!");
                            }
                        }
                    });
                } else {
                    console.log("Options container already exists, ensuring it's visible");
                    
                    // Make sure existing options container is visible
                    $optionsContainer.css({
                        'display': 'block !important',
                        'visibility': 'visible !important',
                        'opacity': '1 !important'
                    }).attr('style', 'display:block !important; visibility:visible !important;');
                    
                    // Also make sure any collapse elements within it are visible
                    $optionsContainer.find('.collapse').removeClass('collapse').addClass('collapse show').css({
                        'display': 'block !important',
                        'visibility': 'visible !important'
                    });
                    
                    // And ensure buttons are visible
                    $optionsContainer.find('.regenerate-btn').css({
                        'display': 'inline-block !important',
                        'visibility': 'visible !important'
                    }).attr('style', 'display:inline-block !important; visibility:visible !important;');
                }
            }
        });
        
        // Re-bind event handlers to regenerate buttons
        if (typeof TechnicalSEORegenerators !== 'undefined') {
            TechnicalSEORegenerators.setupEventListeners();
        }
    }
    
    // Run an initial check
    setTimeout(forceOptionsVisibility, 2000);
});
