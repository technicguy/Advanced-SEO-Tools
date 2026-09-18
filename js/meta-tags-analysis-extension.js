/**
 * meta-tags-analysis-extension.js - Extends MetaTagsAnalysis with enhanced functionality
 * File path: js/meta-tags-analysis-extension.js
 */

// Store a reference to the original displayResults method
if (typeof MetaTagsAnalysis !== 'undefined') {
    const originalDisplayResults = MetaTagsAnalysis.displayResults;
    
    // Override the displayResults method to add our enhancements
    MetaTagsAnalysis.displayResults = function(data) {
        // Call the original method first to render the basic content
        originalDisplayResults.call(this, data);
        
        // Store data for later use
        $('#metaTagsContent').data('results', data);
        
        // Trigger an event to signal that meta tags results are displayed
        $(document).trigger('metaTagsDisplayed', [data]);
    };
}

// Add styles for the enhanced components
function addMetaTagsStyles() {
    if (document.getElementById('meta-tags-enhancement-styles')) {
        return;
    }
    
    const style = document.createElement('style');
    style.id = 'meta-tags-enhancement-styles';
    style.textContent = `
        .code-block {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            white-space: pre-wrap;
            overflow-x: auto;
            position: relative;
        }
        
        .copy-code-btn {
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .copy-code-btn:hover {
            opacity: 1;
        }
        
        .meta-tag-recommendation {
            margin-bottom: 20px;
            border-left: 3px solid #007bff;
            padding-left: 15px;
        }
    `;
    
    document.head.appendChild(style);
}

// Initialize when document is ready
$(document).ready(function() {
    addMetaTagsStyles();
});
