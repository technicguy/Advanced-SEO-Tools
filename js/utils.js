/**
 * utils.js - Utility functions for the SEO Analysis application
 */

// Ensure ContentAnalysis exists globally
if (typeof window.ContentAnalysis === 'undefined') {
    console.log('Creating global ContentAnalysis fallback');
    window.ContentAnalysis = {
        displayResults: function(data) {
            console.log('ContentAnalysis fallback used', data);
            $('#contentTabContent').html(`
                <div class="alert alert-warning mb-4">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                    Content analysis module is not fully loaded. Using fallback display.
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Content Overview</h5>
                    </div>
                    <div class="card-body">
                        <p>Average Word Count: ${data.avg_word_count || 'N/A'}</p>
                        <p>Average Readability: ${data.avg_readability || 'N/A'}</p>
                        <p>Total Pages: ${data.total_pages || 'N/A'}</p>
                        <p>Pages with Thin Content: ${data.thin_content_pages || 'N/A'}</p>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recommendations</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">Aim for 1000+ words for key pages</li>
                            <li class="list-group-item">Use proper heading hierarchy (H1, H2, H3)</li>
                            <li class="list-group-item">Include bullet points and lists to improve readability</li>
                            <li class="list-group-item">Use relevant images with alt text</li>
                            <li class="list-group-item">Update content regularly to keep it fresh</li>
                        </ul>
                    </div>
                </div>
            `);
            
            // Store the data for later use
            $('#contentTabContent').data('results', data);
        }
    };
}

const Utils = {
    /**
     * Validates if a string is a valid URL
     * @param {string} url - URL to validate
     * @returns {boolean} - True if valid URL, false otherwise
     */
    isValidUrl: function(url) {
        try {
            const parsedUrl = new URL(url);
            return ['http:', 'https:'].includes(parsedUrl.protocol);
        } catch (e) {
            return false;
        }
    },
    
    /**
     * Generates a random ID
     * @param {string} prefix - Prefix for the ID
     * @returns {string} - Random ID
     */
    generateId: function(prefix = 'id_') {
        return prefix + Math.random().toString(36).substring(2, 15);
    },
    
    /**
     * Capitalizes the first letter of a string
     * @param {string} str - String to capitalize
     * @returns {string} - Capitalized string
     */
    capitalizeFirstLetter: function(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    },
    
    /**
     * Converts camelCase to Title Case
     * @param {string} str - camelCase string
     * @returns {string} - Title Case string
     */
    camelToTitleCase: function(str) {
        if (!str) return '';
        const result = str.replace(/([A-Z])/g, ' $1');
        return this.capitalizeFirstLetter(result);
    },
    
    /**
     * Gets CSS class for a readability score
     * @param {number} score - Readability score
     * @returns {string} - CSS class
     */
    getReadabilityClass: function(score) {
        if (score >= 80) return 'score-good';
        if (score >= 60) return 'score-good';
        if (score >= 40) return 'score-average';
        return 'score-poor';
    },
    
    /**
     * Gets score label based on score value
     * @param {number} score - Score value
     * @returns {string} - Score label
     */
    getScoreLabel: function(score) {
        if (score >= 90) return 'Excellent';
        if (score >= 70) return 'Good';
        if (score >= 50) return 'Average';
        if (score >= 30) return 'Poor';
        return 'Critical';
    },
    
    /**
     * Gets score CSS class based on score value
     * @param {number} score - Score value
     * @returns {string} - CSS class
     */
    getScoreClass: function(score) {
        if (score >= 90) return 'text-success';
        if (score >= 70) return 'text-primary';
        if (score >= 50) return 'text-warning';
        return 'text-danger';
    },
    
    /**
     * Gets score color class for progress bars
     * @param {number} score - Score value
     * @returns {string} - CSS class
     */
    getScoreColorClass: function(score) {
        if (score >= 90) return 'bg-success';
        if (score >= 70) return 'bg-primary';
        if (score >= 50) return 'bg-warning';
        return 'bg-danger';
    },
    
    /**
     * Formats a date to a human-readable string
     * @param {string|Date} date - Date to format
     * @returns {string} - Formatted date
     */
    formatDate: function(date) {
        if (!date) return 'N/A';
        
        try {
            const d = new Date(date);
            return d.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (e) {
            return 'Invalid Date';
        }
    },
    
    /**
     * Truncates a string to a maximum length and adds ellipsis
     * @param {string} str - String to truncate
     * @param {number} maxLength - Maximum length
     * @returns {string} - Truncated string
     */
    truncateString: function(str, maxLength) {
        if (!str || str.length <= maxLength) return str;
        return str.substring(0, maxLength) + '...';
    },
    
    /**
     * Escapes HTML special characters
     * @param {string} html - HTML string to escape
     * @returns {string} - Escaped HTML
     */
    escapeHtml: function(html) {
        if (!html) return '';
        
        return html
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },
    
    /**
     * Parses URL to extract domain name
     * @param {string} url - URL to parse
     * @returns {string} - Domain name
     */
    extractDomain: function(url) {
        if (!url) return '';
        
        try {
            const parsedUrl = new URL(url);
            let domain = parsedUrl.hostname;
            
            // Remove www. if present
            if (domain.startsWith('www.')) {
                domain = domain.substring(4);
            }
            
            return domain;
        } catch (e) {
            // If URL parsing fails, try a simple regex approach
            const domainMatch = url.match(/^(?:https?:\/\/)?(?:www\.)?([^\/]+)/i);
            return domainMatch ? domainMatch[1] : url;
        }
    },
    
    /**
     * Calculates content quality score based on metrics
     * @param {object} data - Content analysis data
     * @returns {number} - Content quality score (0-100)
     */
    getContentScore: function(data) {
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
    },
    
    /**
     * Calculates the potential value of a keyword based on its search volume, difficulty, and current ranking
     * @param {number} searchVolume - Monthly search volume
     * @param {number} difficulty - Keyword difficulty score (0-100)
     * @param {number|string} currentRanking - Current ranking position (or 'Not Ranked')
     * @returns {string} - Potential value (High, Medium, Low)
     */
    getKeywordPotential: function(searchVolume, difficulty, currentRanking) {
        // Convert currentRanking to a number if it's not already
        const ranking = typeof currentRanking === 'number' ? currentRanking : 0;
        
        // Calculate potential based on a combination of factors
        // Higher search volume, lower difficulty, and worse current ranking = higher potential
        let potentialScore = 0;
        
        // Search volume factor (0-100)
        const volumeScore = Math.min(100, searchVolume / 100);
        
        // Difficulty factor (100-0, inverted so lower difficulty = higher score)
        const difficultyScore = 100 - difficulty;
        
        // Ranking factor (higher for unranked or poorly ranked keywords)
        let rankingScore = 0;
        if (ranking === 0) {
            // Not ranked = high opportunity
            rankingScore = 100;
        } else if (ranking > 10) {
            // Outside first page = good opportunity
            rankingScore = 80;
        } else if (ranking > 3) {
            // On first page but not top 3 = medium opportunity
            rankingScore = 50;
        } else {
            // Already in top 3 = lower opportunity
            rankingScore = 20;
        }
        
        // Calculate overall potential (weighted average)
        potentialScore = (volumeScore * 0.4) + (difficultyScore * 0.4) + (rankingScore * 0.2);
        
        // Convert score to High/Medium/Low rating
        if (potentialScore >= 70) {
            return 'High';
        } else if (potentialScore >= 40) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }
};