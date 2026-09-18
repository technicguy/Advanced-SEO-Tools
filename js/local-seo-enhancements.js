/**
 * local-seo-enhancements.js - Extended functionality for local SEO analysis
 * Adds reanalysis button and schema code generation capabilities
 */

const LocalSEOEnhancements = {
    /**
     * Initialize enhancements
     */
    initialize: function() {
        // Set up event handlers for dynamic elements
        this.setupEventHandlers();

        // Extend the original LocalSEO.displayResults method
        const originalDisplayResults = LocalSEO.displayResults;
        LocalSEO.displayResults = function(data) {
            // Call the original method first
            originalDisplayResults.call(LocalSEO, data);
            
            // Then add our enhancements
            LocalSEOEnhancements.addReanalyzeButton();
            LocalSEOEnhancements.addSchemaGenerator();
        };
    },

    /**
     * Set up event handlers for dynamically created elements
     */
    setupEventHandlers: function() {
        // Handle reanalyze button click
        $(document).on('click', '#localSeoReanalyzeBtn', function() {
            LocalSEOEnhancements.reanalyzeLocalSEO();
        });

        // Handle generate schema button click
        $(document).on('click', '#generateSchemaBtn', function() {
            LocalSEOEnhancements.generateSchema();
        });

        // Handle add location button click
        $(document).on('click', '#addLocationBtn', function() {
            LocalSEOEnhancements.addLocationField();
        });
    },

    /**
     * Add reanalyze button below local SEO score
     */
    addReanalyzeButton: function() {
        const buttonHtml = `
            <button id="localSeoReanalyzeBtn" class="btn btn-primary mt-3">
                <i class="fas fa-sync-alt me-2"></i> Reanalyze Local SEO
            </button>
        `;

        // Add button below the score gauge
        $('.local-seo-gauge').parent().append(buttonHtml);
    },

    /**
     * Add schema code generator section
     */
    addSchemaGenerator: function() {
        const generatorHtml = `
            <div class="card mt-4 mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-code me-2"></i>
                        Local Business Schema Generator
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Generate structured data schema.org markup for your local business locations.</p>
                    
                    <div id="schemaLocations">
                        <div class="location-entry mb-3 border p-3 rounded">
                            <h6 class="border-bottom pb-2 mb-3">Location #1</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Business Name</label>
                                <input type="text" class="form-control business-name" placeholder="e.g., Your Company Name">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Business Type</label>
                                <select class="form-control business-type">
                                    <option value="LocalBusiness">Local Business (generic)</option>
                                    <option value="Restaurant">Restaurant</option>
                                    <option value="Store">Store</option>
                                    <option value="MedicalBusiness">Medical Business</option>
                                    <option value="ProfessionalService">Professional Service</option>
                                    <option value="FinancialService">Financial Service</option>
                                    <option value="LodgingBusiness">Lodging Business</option>
                                </select>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Street Address</label>
                                    <input type="text" class="form-control street-address" placeholder="e.g., 123 Main St">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control city" placeholder="e.g., New York">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">State/Province</label>
                                    <input type="text" class="form-control region" placeholder="e.g., NY">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" class="form-control postal-code" placeholder="e.g., 10001">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Country</label>
                                    <input type="text" class="form-control country" placeholder="e.g., USA">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control phone" placeholder="e.g., +1-123-456-7890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Website URL</label>
                                    <input type="text" class="form-control website" placeholder="e.g., https://example.com">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Google Maps URL <small class="text-muted">(optional)</small></label>
                                <input type="text" class="form-control maps-url" placeholder="e.g., https://g.co/kgs/DZGsvJz">
                                <small class="form-text text-muted">Copy and paste your Google Maps listing URL here</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button id="addLocationBtn" class="btn btn-secondary">
                            <i class="fas fa-plus me-2"></i> Add Another Location
                        </button>
                        
                        <button id="generateSchemaBtn" class="btn btn-success">
                            <i class="fas fa-code me-2"></i> Generate Schema
                        </button>
                    </div>
                    
                    <div id="schemaOutput" class="mt-4" style="display: none;">
                        <h6>Generated Schema.org Markup</h6>
                        <div class="alert alert-info mb-2">
                            Add this code to the <code>&lt;head&gt;</code> section of your website.
                        </div>
                        <div class="position-relative">
                            <pre class="border p-3 bg-light"><code id="schemaCode" class="language-json"></code></pre>
                            <button class="position-absolute top-0 end-0 btn btn-sm btn-primary m-2 copy-schema-btn">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add generator to the local SEO content
        $('#localSeoContent').append(generatorHtml);
        
        // Set up copy button functionality
        $(document).on('click', '.copy-schema-btn', function() {
            const codeToCopy = $('#schemaCode').text();
            navigator.clipboard.writeText(codeToCopy).then(function() {
                // Show feedback
                const originalHtml = $('.copy-schema-btn').html();
                $('.copy-schema-btn').html('<i class="fas fa-check"></i> Copied!');
                setTimeout(function() {
                    $('.copy-schema-btn').html(originalHtml);
                }, 2000);
            });
        });
    },

    /**
     * Trigger reanalysis of local SEO
     */
    reanalyzeLocalSEO: function() {
        // Get the current URL from the app state
        const url = typeof appState !== 'undefined' ? appState.currentUrl : $('#websiteUrl').val();
        
        if (!url) {
            UIManager.showNotification('URL is required for analysis', 'warning');
            return;
        }
        
        // Show loading state
        $('#localSeoReanalyzeBtn').html('<i class="fas fa-spinner fa-spin me-2"></i> Analyzing...').prop('disabled', true);
        $('#localSeoContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Reanalyzing Local SEO...</p>
            </div>
        `);
        
        // Call the API
        ApiService.runLocalSeoAnalysis(url)
            .done(function(response) {
                if (response.success) {
                    // Update UI with new results
                    LocalSEO.displayResults(response.data);
                    UIManager.showNotification('Local SEO analysis completed successfully!', 'success');
                } else {
                    // Show error message
                    $('#localSeoContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error: ${response.message}
                        </div>
                    `);
                    UIManager.showNotification('Failed to complete analysis: ' + response.message, 'danger');
                }
                
                // Reset button state
                $('#localSeoReanalyzeBtn').html('<i class="fas fa-sync-alt me-2"></i> Reanalyze Local SEO').prop('disabled', false);
            })
            .fail(function(xhr, status, error) {
                // Show error message
                $('#localSeoContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error: Server error occurred during analysis
                    </div>
                `);
                UIManager.showNotification('Server error occurred during analysis', 'danger');
                
                // Reset button state
                $('#localSeoReanalyzeBtn').html('<i class="fas fa-sync-alt me-2"></i> Reanalyze Local SEO').prop('disabled', false);
            });
    },

    /**
     * Add another location field to the schema generator
     */
    addLocationField: function() {
        const locationCount = $('#schemaLocations .location-entry').length + 1;
        
        const newLocationHtml = `
            <div class="location-entry mb-3 border p-3 rounded">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="border-bottom pb-2 mb-3">Location #${locationCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-location-btn">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Business Name</label>
                    <input type="text" class="form-control business-name" placeholder="e.g., Your Company Name">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Business Type</label>
                    <select class="form-control business-type">
                        <option value="LocalBusiness">Local Business (generic)</option>
                        <option value="Restaurant">Restaurant</option>
                        <option value="Store">Store</option>
                        <option value="MedicalBusiness">Medical Business</option>
                        <option value="ProfessionalService">Professional Service</option>
                        <option value="FinancialService">Financial Service</option>
                        <option value="LodgingBusiness">Lodging Business</option>
                    </select>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Street Address</label>
                        <input type="text" class="form-control street-address" placeholder="e.g., 123 Main St">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control city" placeholder="e.g., New York">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">State/Province</label>
                        <input type="text" class="form-control region" placeholder="e.g., NY">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postal Code</label>
                        <input type="text" class="form-control postal-code" placeholder="e.g., 10001">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control country" placeholder="e.g., USA">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control phone" placeholder="e.g., +1-123-456-7890">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Website URL</label>
                        <input type="text" class="form-control website" placeholder="e.g., https://example.com">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Google Maps URL <small class="text-muted">(optional)</small></label>
                    <input type="text" class="form-control maps-url" placeholder="e.g., https://g.co/kgs/DZGsvJz">
                    <small class="form-text text-muted">Copy and paste your Google Maps listing URL here</small>
                </div>
            </div>
        `;
        
        // Add new location to the form
        $('#schemaLocations').append(newLocationHtml);
        
        // Set up remove button handler
        $('.remove-location-btn').off('click').on('click', function() {
            $(this).closest('.location-entry').remove();
            
            // Update location numbers
            $('#schemaLocations .location-entry').each(function(index) {
                $(this).find('h6').text(`Location #${index + 1}`);
            });
        });
    },

    /**
     * Generate schema.org JSON-LD markup for local business
     */
    generateSchema: function() {
        const locations = [];
        
        // Process each location
        $('#schemaLocations .location-entry').each(function() {
            const $location = $(this);
            
            // Extract field values
            const businessName = $location.find('.business-name').val().trim();
            const businessType = $location.find('.business-type').val();
            const streetAddress = $location.find('.street-address').val().trim();
            const city = $location.find('.city').val().trim();
            const region = $location.find('.region').val().trim();
            const postalCode = $location.find('.postal-code').val().trim();
            const country = $location.find('.country').val().trim();
            const phone = $location.find('.phone').val().trim();
            const website = $location.find('.website').val().trim();
            const mapsUrl = $location.find('.maps-url').val().trim();
            
            // Validate required fields
            if (!businessName || !streetAddress || !city) {
                UIManager.showNotification('Business name, street address, and city are required for each location', 'warning');
                return;
            }
            
            // Create schema JSON structure for this location
            const locationSchema = {
                "@context": "https://schema.org",
                "@type": businessType || "LocalBusiness",
                "name": businessName,
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": streetAddress,
                    "addressLocality": city,
                    "addressRegion": region,
                    "postalCode": postalCode,
                    "addressCountry": country
                }
            };
            
            // Add optional fields if present
            if (phone) {
                locationSchema.telephone = phone;
            }
            
            if (website) {
                locationSchema.url = website;
            }
            
            // If Google Maps URL is provided, extract coordinates or map URL
            if (mapsUrl) {
                // Try to extract latitude and longitude from Google Maps URL
                const coordsMatch = mapsUrl.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
                if (coordsMatch && coordsMatch.length >= 3) {
                    locationSchema.geo = {
                        "@type": "GeoCoordinates",
                        "latitude": coordsMatch[1],
                        "longitude": coordsMatch[2]
                    };
                } else {
                    // Just store the maps URL as a map reference
                    locationSchema.hasMap = mapsUrl;
                }
            }
            
            locations.push(locationSchema);
        });
        
        // Make sure we have at least one valid location
        if (locations.length === 0) {
            UIManager.showNotification('Please fill in at least one location with required fields', 'warning');
            return;
        }
        
        // Generate the final schema markup
        let schemaMarkup;
        if (locations.length === 1) {
            // Single location - use the location schema directly
            schemaMarkup = JSON.stringify(locations[0], null, 2);
        } else {
            // Multiple locations - create a parent organization with multiple locations
            const businessName = locations[0].name; // Use the first location's name as the parent org
            
            const parentSchema = {
                "@context": "https://schema.org",
                "@type": "Organization",
                "name": businessName,
                "location": locations.map(loc => {
                    // Remove the @context from child locations
                    const childLoc = { ...loc };
                    delete childLoc["@context"];
                    return childLoc;
                })
            };
            
            schemaMarkup = JSON.stringify(parentSchema, null, 2);
        }
        
        // Wrap in script tags for proper HTML insertion
        const scriptWrapped = `<script type="application/ld+json">\n${schemaMarkup}\n</script>`;
        
        // Display the output
        $('#schemaCode').text(scriptWrapped);
        $('#schemaOutput').show();
        
        // Scroll to the output
        $('html, body').animate({
            scrollTop: $('#schemaOutput').offset().top - 100
        }, 500);
    }
};

// Initialize the enhancements when the document is ready
$(document).ready(function() {
    // Wait a short time to ensure the original LocalSEO object is fully loaded
    setTimeout(function() {
        LocalSEOEnhancements.initialize();
    }, 500);
});
