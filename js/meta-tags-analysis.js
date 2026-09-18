/**
 * meta-tags-analysis.js - Meta tags analysis functionality
 */

const MetaTagsAnalysis = {
    initialize: function() {
        console.log('Meta Tags Analysis initialized');
    },
    
    displayResults: function(data) {
        // Check if data is missing or incomplete
        if (!data || !data.meta_tags) {
            this.displayError('Could not retrieve meta tags data for this website.');
            return;
        }
        
        const metaTags = data.meta_tags;
        
        // Calculate overall score
        let totalScore = 0;
        let scoreComponents = 0;
        
        // Score components (0-100 scale)
        let titleScore = 0;
        let descriptionScore = 0;
        let canonicalScore = 0;
        let viewportScore = 0;
        let robotsScore = 0;
        let ogTagsScore = 0;
        let twitterTagsScore = 0;
        
        // Title evaluation
        if (metaTags.title) {
            const titleLength = metaTags.title_length || metaTags.title.length;
            if (titleLength >= 30 && titleLength <= 60) {
                titleScore = 100;
            } else if (titleLength >= 20 && titleLength <= 70) {
                titleScore = 70;
            } else {
                titleScore = 40;
            }
            totalScore += titleScore;
            scoreComponents++;
        }
        
        // Description evaluation
        if (metaTags.description) {
            const descLength = metaTags.description_length || metaTags.description.length;
            if (descLength >= 120 && descLength <= 160) {
                descriptionScore = 100;
            } else if (descLength >= 70 && descLength <= 320) {
                descriptionScore = 70;
            } else {
                descriptionScore = 40;
            }
            totalScore += descriptionScore;
            scoreComponents++;
        }
        
        // Canonical tag
        if (metaTags.canonical) {
            canonicalScore = 100;
            totalScore += canonicalScore;
            scoreComponents++;
        }
        
        // Viewport
        if (metaTags.viewport) {
            viewportScore = 100;
            totalScore += viewportScore;
            scoreComponents++;
        }
        
        // Robots
        if (metaTags.robots) {
            robotsScore = 100;
            totalScore += robotsScore;
            scoreComponents++;
        }
        
        // Open Graph tags
        if (metaTags.has_open_graph) {
            ogTagsScore = 100;
            totalScore += ogTagsScore;
            scoreComponents++;
        }
        
        // Twitter Card tags
        if (metaTags.has_twitter_cards) {
            twitterTagsScore = 100;
            totalScore += twitterTagsScore;
            scoreComponents++;
        }
        
        // Calculate overall score
        const overallScore = scoreComponents > 0 ? Math.round(totalScore / scoreComponents) : 0;
        
        // Generate recommendations based on scores
        const recommendations = this.generateRecommendations(metaTags, {
            title: titleScore,
            description: descriptionScore,
            canonical: canonicalScore,
            viewport: viewportScore,
            robots: robotsScore,
            ogTags: ogTagsScore,
            twitterTags: twitterTagsScore
        });
        
        // Store data with scores for later use
        const enhancedData = {
            ...data,
            overall_score: overallScore,
            title_tags_score: titleScore,
            meta_description_score: descriptionScore,
            canonical_tags_score: canonicalScore,
            og_tags_score: ogTagsScore,
            recommendations: recommendations
        };
        
        // Generate the HTML content
        let content = `
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Overall Meta Tags Score</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <div class="score-gauge">
                                    <canvas id="metaTagsGauge" width="120" height="120"></canvas>
                                    <div class="score-value">${overallScore}</div>
                                </div>
                            </div>
                            <h5 class="${this.getScoreClass(overallScore)}">${this.getScoreLabel(overallScore)}</h5>
                            <p class="text-muted">Based on key meta tag elements</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Meta Tags Analysis Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    ${this.renderScoreItem('Title Tag', titleScore, metaTags.title ? `Present (${metaTags.title_length} chars)` : 'Missing')}
                                    ${this.renderScoreItem('Meta Description', descriptionScore, metaTags.description ? `Present (${metaTags.description_length} chars)` : 'Missing')}
                                    ${this.renderScoreItem('Canonical Tag', canonicalScore, metaTags.canonical ? 'Present' : 'Missing')}
                                    ${this.renderScoreItem('Viewport', viewportScore, metaTags.viewport ? 'Present' : 'Missing')}
                                </div>
                                <div class="col-md-6">
                                    ${this.renderScoreItem('Robots Tag', robotsScore, metaTags.robots ? 'Present' : 'Missing')}
                                    ${this.renderScoreItem('Open Graph Tags', ogTagsScore, metaTags.has_open_graph ? 'Present' : 'Missing')}
                                    ${this.renderScoreItem('Twitter Cards', twitterTagsScore, metaTags.has_twitter_cards ? 'Present' : 'Missing')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Meta Tags Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="20%">Meta Tag</th>
                                            <th width="60%">Value</th>
                                            <th width="20%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${this.renderMetaTagRow('Title', metaTags.title, titleScore, this.getTitleFeedback(metaTags.title_length))}
                                        ${this.renderMetaTagRow('Meta Description', metaTags.description, descriptionScore, this.getDescriptionFeedback(metaTags.description_length))}
                                        ${this.renderMetaTagRow('Canonical', metaTags.canonical, canonicalScore)}
                                        ${this.renderMetaTagRow('Viewport', metaTags.viewport, viewportScore)}
                                        ${this.renderMetaTagRow('Robots', metaTags.robots, robotsScore)}
                                        ${metaTags.keywords ? this.renderMetaTagRow('Keywords', metaTags.keywords, 50, 'Meta keywords are less important for SEO today') : ''}
                                        ${metaTags.has_open_graph ? this.renderOpenGraphTags(metaTags) : this.renderMetaTagRow('Open Graph', 'Not found', 0, 'Missing Open Graph tags for social sharing')}
                                        ${metaTags.has_twitter_cards ? this.renderTwitterTags(metaTags) : this.renderMetaTagRow('Twitter Cards', 'Not found', 0, 'Missing Twitter Card tags for social sharing')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">AI-Generated Recommendations</h5>
                        </div>
                        <div class="card-body">
                            ${this.renderRecommendations(recommendations)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Update the content in the meta tags tab
        $('#metaTagsContent').html(content).data('results', enhancedData);
        
        // Initialize the gauge charts
        this.initGaugeChart('metaTagsGauge', overallScore);
        
        // Update score badge in accordion header
        if (typeof UIManager !== 'undefined' && UIManager.updateScoreBadge) {
            UIManager.updateScoreBadge('metaTags', overallScore);
        }
    },
    
    displayError: function(message) {
        const content = `
            <div class="alert alert-warning mb-4">
                <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                ${message}
            </div>
            <div class="card results-card">
                <div class="card-header">
                    <h5 class="mb-0">Meta Tags Analysis - General Recommendations</h5>
                </div>
                <div class="card-body">
                    <p>Since we couldn't analyze meta tags directly, here are some general recommendations:</p>
                    <ul>
                        <li>Ensure each page has a unique, descriptive title tag (30-60 characters)</li>
                        <li>Add meta descriptions to all important pages (120-160 characters)</li>
                        <li>Implement canonical tags to prevent duplicate content issues</li>
                        <li>Add viewport meta tag for mobile responsiveness</li>
                        <li>Use Open Graph and Twitter Card tags for better social media sharing</li>
                        <li>Include a robots meta tag when you need to control crawler behavior</li>
                    </ul>
                </div>
            </div>
            
            <div class="card results-card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">AI-Generated Title & Description Examples</h5>
                </div>
                <div class="card-body">
                    <h6>Example Title Tags:</h6>
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light">
                            <strong>Homepage:</strong> Company Name | Primary Service or Product | Location
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light">
                            <strong>Service Page:</strong> Service Name | Key Benefit | Company Name
                        </div>
                    </div>
                    
                    <h6 class="mt-4">Example Meta Descriptions:</h6>
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light">
                            <strong>Homepage:</strong> Company Name offers premium [products/services] with [unique selling proposition]. Get [benefit] today! Contact us for [value proposition].
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light">
                            <strong>Service Page:</strong> Our [service name] provides [key benefit] for [target audience]. Featuring [unique feature], we guarantee [promise]. Call for a free consultation!
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Update content and store empty results
        $('#metaTagsContent').html(content).data('results', {});
    },
    
    renderScoreItem: function(label, score, detailText) {
        return `
            <div class="d-flex align-items-center mb-3">
                <div style="width: 120px;">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar ${this.getScoreBarClass(score)}" 
                             role="progressbar" 
                             style="width: ${score}%" 
                             aria-valuenow="${score}" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="ms-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">${label}</span>
                        <span class="badge ${this.getScoreBadgeClass(score)} ms-2">${score}%</span>
                    </div>
                    <small class="text-muted">${detailText}</small>
                </div>
            </div>
        `;
    },
    
    renderMetaTagRow: function(name, value, score, feedback = '') {
        const statusClass = this.getScoreStatusClass(score);
        const statusBadge = this.getStatusBadge(score);
        
        return `
            <tr>
                <td><strong>${name}</strong></td>
                <td>
                    <div class="text-wrap" style="max-height: 100px; overflow-y: auto;">
                        ${value ? `<code>${this.escapeHtml(value)}</code>` : '<span class="text-danger">Not found</span>'}
                    </div>
                    ${feedback ? `<small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> ${feedback}</small>` : ''}
                </td>
                <td class="text-center">${statusBadge}</td>
            </tr>
        `;
    },
    
    renderOpenGraphTags: function(metaTags) {
        const openGraphTags = [];
        
        if (metaTags.og_title) openGraphTags.push(`<div><strong>og:title:</strong> <code>${this.escapeHtml(metaTags.og_title)}</code></div>`);
        if (metaTags.og_description) openGraphTags.push(`<div><strong>og:description:</strong> <code>${this.escapeHtml(metaTags.og_description)}</code></div>`);
        if (metaTags.og_image) openGraphTags.push(`<div><strong>og:image:</strong> <code>${this.escapeHtml(metaTags.og_image)}</code></div>`);
        if (metaTags.og_url) openGraphTags.push(`<div><strong>og:url:</strong> <code>${this.escapeHtml(metaTags.og_url)}</code></div>`);
        if (metaTags.og_type) openGraphTags.push(`<div><strong>og:type:</strong> <code>${this.escapeHtml(metaTags.og_type)}</code></div>`);
        
        return `
            <tr>
                <td><strong>Open Graph</strong></td>
                <td>
                    <div class="text-wrap" style="max-height: 150px; overflow-y: auto;">
                        ${openGraphTags.length > 0 ? openGraphTags.join('<hr class="my-2">') : '<span class="text-danger">No Open Graph tags found</span>'}
                    </div>
                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> Open Graph tags improve how your content appears when shared on social media</small>
                </td>
                <td class="text-center">${this.getStatusBadge(openGraphTags.length >= 3 ? 100 : 50)}</td>
            </tr>
        `;
    },
    
    renderTwitterTags: function(metaTags) {
        const twitterTags = [];
        
        if (metaTags.twitter_card) twitterTags.push(`<div><strong>twitter:card:</strong> <code>${this.escapeHtml(metaTags.twitter_card)}</code></div>`);
        if (metaTags.twitter_title) twitterTags.push(`<div><strong>twitter:title:</strong> <code>${this.escapeHtml(metaTags.twitter_title)}</code></div>`);
        if (metaTags.twitter_description) twitterTags.push(`<div><strong>twitter:description:</strong> <code>${this.escapeHtml(metaTags.twitter_description)}</code></div>`);
        if (metaTags.twitter_image) twitterTags.push(`<div><strong>twitter:image:</strong> <code>${this.escapeHtml(metaTags.twitter_image)}</code></div>`);
        
        return `
            <tr>
                <td><strong>Twitter Cards</strong></td>
                <td>
                    <div class="text-wrap" style="max-height: 150px; overflow-y: auto;">
                        ${twitterTags.length > 0 ? twitterTags.join('<hr class="my-2">') : '<span class="text-danger">No Twitter Card tags found</span>'}
                    </div>
                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> Twitter Card tags control how your content appears when shared on Twitter</small>
                </td>
                <td class="text-center">${this.getStatusBadge(twitterTags.length >= 3 ? 100 : 50)}</td>
            </tr>
        `;
    },
    
    renderRecommendations: function(recommendations) {
        if (!recommendations || recommendations.length === 0) {
            return `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No specific recommendations available. Your meta tags appear to be well-optimized.
                </div>
            `;
        }
        
        let html = '<div class="row">';
        
        recommendations.forEach((rec, index) => {
            const priorityClass = rec.priority === 'high' ? 'danger' : (rec.priority === 'medium' ? 'warning' : 'info');
            
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card h-100 border-${priorityClass}">
                        <div class="card-header bg-${priorityClass} bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-${priorityClass}">${rec.title}</h6>
                                <span class="badge bg-${priorityClass}">${rec.priority} priority</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>${rec.description}</p>
                            ${rec.example ? `
                                <div class="mt-3">
                                    <span class="fw-bold">Example:</span>
                                    <div class="p-2 bg-light border rounded mt-1">
                                        <code>${this.escapeHtml(rec.example)}</code>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    },
    
    generateRecommendations: function(metaTags, scores) {
        const recommendations = [];
        
        // Title recommendations
        if (!metaTags.title) {
            recommendations.push({
                title: 'Add title tag',
                description: 'Your page is missing a title tag, which is critical for SEO and user experience. Add a descriptive title that includes your primary keyword.',
                priority: 'high',
                example: '<title>Your Primary Keyword | Brand Name</title>'
            });
        } else if (metaTags.title_length < 30) {
            recommendations.push({
                title: 'Improve title tag length',
                description: 'Your title tag is too short. Aim for 30-60 characters to fully utilize this important SEO element.',
                priority: 'medium',
                example: metaTags.title + ' | Additional Relevant Keywords'
            });
        } else if (metaTags.title_length > 60) {
            recommendations.push({
                title: 'Optimize title tag length',
                description: 'Your title tag exceeds the recommended length and may be truncated in search results. Keep it between 30-60 characters.',
                priority: 'medium',
                example: this.truncateString(metaTags.title, 55) + ' | Brand'
            });
        }
        
        // Meta description recommendations
        if (!metaTags.description) {
            recommendations.push({
                title: 'Add meta description',
                description: 'Your page is missing a meta description. While not a direct ranking factor, it improves click-through rates from search results.',
                priority: 'high',
                example: '<meta name="description" content="Your compelling description with primary keyword and call to action in 150-160 characters.">'
            });
        } else if (metaTags.description_length < 120) {
            recommendations.push({
                title: 'Expand meta description',
                description: 'Your meta description is too short. Aim for 120-160 characters to properly describe your page content and encourage clicks.',
                priority: 'medium',
                example: metaTags.description + ' [Additional compelling information about your page with a clear call-to-action]'
            });
        } else if (metaTags.description_length > 160) {
            recommendations.push({
                title: 'Shorten meta description',
                description: 'Your meta description exceeds the recommended length and may be truncated in search results. Keep it between 120-160 characters.',
                priority: 'low',
                example: this.truncateString(metaTags.description, 155) + '...'
            });
        }
        
        // Canonical tag recommendations
        if (!metaTags.canonical) {
            recommendations.push({
                title: 'Add canonical tag',
                description: 'Implement a canonical tag to prevent duplicate content issues and specify your preferred URL for this content.',
                priority: 'medium',
                example: '<link rel="canonical" href="https://www.example.com/your-page/" />'
            });
        }
        
        // Open Graph recommendations
        if (!metaTags.has_open_graph) {
            recommendations.push({
                title: 'Add Open Graph meta tags',
                description: 'Implement Open Graph tags to optimize how your content appears when shared on social media platforms like Facebook and LinkedIn.',
                priority: 'medium',
                example: '<meta property="og:title" content="Your Title Here">\n<meta property="og:description" content="Your description here">\n<meta property="og:image" content="https://example.com/image.jpg">\n<meta property="og:url" content="https://example.com/page-url">'
            });
        }
        
        // Twitter Card recommendations
        if (!metaTags.has_twitter_cards) {
            recommendations.push({
                title: 'Add Twitter Card meta tags',
                description: 'Implement Twitter Card tags to control how your content appears when shared on Twitter.',
                priority: 'medium',
                example: '<meta name="twitter:card" content="summary_large_image">\n<meta name="twitter:title" content="Your Title Here">\n<meta name="twitter:description" content="Your description here">\n<meta name="twitter:image" content="https://example.com/image.jpg">'
            });
        }
        
        // Viewport recommendations
        if (!metaTags.viewport) {
            recommendations.push({
                title: 'Add viewport meta tag',
                description: 'Add a viewport meta tag to ensure your page is mobile-friendly, which is essential for mobile SEO.',
                priority: 'high',
                example: '<meta name="viewport" content="width=device-width, initial-scale=1">'
            });
        }
        
        return recommendations;
    },
    
    getTitleFeedback: function(length) {
        if (!length) return 'Missing title tag, which is critical for SEO';
        if (length < 30) return 'Title is too short (recommended length: 30-60 characters)';
        if (length > 60) return 'Title exceeds recommended length and may be truncated in search results';
        return 'Title length is optimal';
    },
    
    getDescriptionFeedback: function(length) {
        if (!length) return 'Missing meta description, which helps with click-through rates';
        if (length < 120) return 'Description is too short (recommended length: 120-160 characters)';
        if (length > 160) return 'Description exceeds recommended length and may be truncated in search results';
        return 'Description length is optimal';
    },
    
    getScoreLabel: function(score) {
        if (score >= 90) return 'Excellent';
        if (score >= 70) return 'Good';
        if (score >= 50) return 'Average';
        if (score >= 30) return 'Poor';
        return 'Critical';
    },
    
    getScoreClass: function(score) {
        if (score >= 90) return 'text-success';
        if (score >= 70) return 'text-primary';
        if (score >= 50) return 'text-warning';
        return 'text-danger';
    },
    
    getScoreBarClass: function(score) {
        if (score >= 90) return 'bg-success';
        if (score >= 70) return 'bg-primary';
        if (score >= 50) return 'bg-warning';
        return 'bg-danger';
    },
    
    getScoreBadgeClass: function(score) {
        if (score >= 90) return 'bg-success';
        if (score >= 70) return 'bg-primary';
        if (score >= 50) return 'bg-warning';
        return 'bg-danger';
    },
    
    getScoreStatusClass: function(score) {
        if (score >= 90) return 'success';
        if (score >= 70) return 'primary';
        if (score >= 50) return 'warning';
        return 'danger';
    },
    
    getStatusBadge: function(score) {
        if (score >= 90) {
            return '<span class="badge bg-success">Excellent</span>';
        } else if (score >= 70) {
            return '<span class="badge bg-primary">Good</span>';
        } else if (score >= 50) {
            return '<span class="badge bg-warning text-dark">Average</span>';
        } else if (score > 0) {
            return '<span class="badge bg-danger">Poor</span>';
        } else {
            return '<span class="badge bg-secondary">Missing</span>';
        }
    },
    
    initGaugeChart: function(canvasId, score) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Clear the canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw the background arc
        ctx.beginPath();
        ctx.arc(canvas.width / 2, canvas.height / 2, canvas.width / 2 - 10, Math.PI, 2 * Math.PI);
        ctx.lineWidth = 20;
        ctx.strokeStyle = '#f0f0f0';
        ctx.stroke();
        
        // Draw the score arc
        const scorePercentage = score / 100;
        const scoreAngle = Math.PI + (Math.PI * scorePercentage);
        
        ctx.beginPath();
        ctx.arc(canvas.width / 2, canvas.height / 2, canvas.width / 2 - 10, Math.PI, scoreAngle);
        ctx.lineWidth = 20;
        
        // Create gradient based on score
        let gradient;
        if (score < 50) {
            gradient = ctx.createLinearGradient(0, 0, canvas.width, 0);
            gradient.addColorStop(0, '#dc3545');
            gradient.addColorStop(1, '#ffc107');
        } else {
            gradient = ctx.createLinearGradient(0, 0, canvas.width, 0);
            gradient.addColorStop(0, '#ffc107');
            gradient.addColorStop(1, '#28a745');
        }
        
        ctx.strokeStyle = gradient;
        ctx.stroke();
    },
    
    escapeHtml: function(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },
    
    truncateString: function(str, maxLength) {
        if (!str) return '';
        if (str.length <= maxLength) return str;
        return str.substring(0, maxLength).trim();
    }
};
