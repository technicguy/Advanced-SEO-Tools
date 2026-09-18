/**
 * content-analysis.js - Enhanced content analysis functionality with AI recommendations
 */

// Define ContentAnalysis in the global scope to ensure it's accessible everywhere
window.ContentAnalysis = {
    displayResults: function(data) {
        // Check if data is null or missing critical components
        if (!data || (!data.avg_word_count && !data.suggestions)) {
            this.displayError();
            return;
        }
        
        // Calculate overall content quality score
        let contentQualityScore = this.calculateContentScore(data);
        
        // Generate recommendations if they don't exist
        if (!data.suggestions || !Array.isArray(data.suggestions) || data.suggestions.length === 0) {
            data.suggestions = this.generateContentRecommendations(data);
        }
        
        // Generate enhanced AI recommendations with examples
        const aiRecommendations = this.generateAIRecommendations(data);
        
        // Create the content status level
        let contentQualityStatus = 'Poor';
        let contentQualityColor = 'danger';
        
        if (contentQualityScore >= 80) {
            contentQualityStatus = 'Excellent';
            contentQualityColor = 'success';
        } else if (contentQualityScore >= 65) {
            contentQualityStatus = 'Good';
            contentQualityColor = 'primary';
        } else if (contentQualityScore >= 50) {
            contentQualityStatus = 'Average';
            contentQualityColor = 'warning';
        }
        
        // Create the HTML content
        let content = `
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Content Quality Score</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="score-gauge mb-3">
                                <canvas id="contentQualityGauge" width="150" height="150"></canvas>
                                <div class="score-value">${contentQualityScore}</div>
                            </div>
                            <h5 class="text-${contentQualityColor}">${contentQualityStatus}</h5>
                            <p class="text-muted small">Based on readability, length, and structure</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Content Analysis Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    ${this.renderMetricItem('Average Word Count', data.avg_word_count, this.getWordCountFeedback(data.avg_word_count))}
                                    ${this.renderMetricItem('Readability Score', data.avg_readability, this.getReadabilityFeedback(data.avg_readability))}
                                </div>
                                <div class="col-md-6">
                                    ${this.renderMetricItem('Total Pages Analyzed', data.total_pages || 1)}
                                    ${this.renderMetricItem('Pages with Thin Content', data.thin_content_pages || 0, this.getThinContentFeedback(data.thin_content_pages, data.total_pages))}
                                </div>
                            </div>
                            
                            ${data.main_page_data && data.main_page_data.top_keywords && data.main_page_data.top_keywords.length > 0 ? `
                                <div class="mt-4">
                                    <h6>Top Content Keywords</h6>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        ${data.main_page_data.top_keywords.map(keyword => 
                                            `<span class="badge bg-secondary px-3 py-2 mb-1">${keyword}</span>`
                                        ).join(' ')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Content Issues</h5>
                        </div>
                        <div class="card-body">
                            ${this.renderContentIssues(data)}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Content Suggestions</h5>
                        </div>
                        <div class="card-body">
                            ${this.renderSuggestionsList(data.suggestions)}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">AI-Generated Content Recommendations</h5>
                </div>
                <div class="card-body">
                    ${this.renderAIRecommendations(aiRecommendations)}
                </div>
            </div>
        `;
        
        // Update the content in the content tab
        $('#contentTabContent').html(content).data('results', {
            ...data,
            overall_score: contentQualityScore
        });
        
        // Initialize the gauge chart
        this.initGaugeChart('contentQualityGauge', contentQualityScore);
        
        // Update score badge in accordion header
        if (typeof UIManager !== 'undefined' && UIManager.updateScoreBadge) {
            UIManager.updateScoreBadge('content', contentQualityScore);
        }
    },
    
    displayError: function() {
        const errorContent = `
            <div class="alert alert-warning mb-4">
                <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                Could not retrieve content analysis data for this website. The site may be blocking automated content analysis.
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Content Analysis - General Recommendations</h5>
                </div>
                <div class="card-body">
                    <p>Since direct content analysis couldn't be performed, here are some general content recommendations:</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card border-info mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Content Length</h5>
                                </div>
                                <div class="card-body">
                                    <p>Create comprehensive content that thoroughly addresses user search intent:</p>
                                    <ul>
                                        <li>Aim for 1000+ words for key landing pages</li>
                                        <li>Write 1500+ words for cornerstone content</li>
                                        <li>Product pages should have at least 500 words</li>
                                        <li>Blog posts should be at least 800-1000 words</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-info mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Content Structure</h5>
                                </div>
                                <div class="card-body">
                                    <p>Organize content for better readability and SEO:</p>
                                    <ul>
                                        <li>Use proper heading hierarchy (H1, H2, H3)</li>
                                        <li>Include bullet points and numbered lists</li>
                                        <li>Keep paragraphs short (3-4 sentences max)</li>
                                        <li>Use descriptive subheadings that include keywords</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-info mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Content Elements</h5>
                                </div>
                                <div class="card-body">
                                    <p>Enhance content with multimedia and formatting:</p>
                                    <ul>
                                        <li>Add relevant images with alt text</li>
                                        <li>Include videos where appropriate</li>
                                        <li>Use tables for comparing information</li>
                                        <li>Add infographics for complex concepts</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-info mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Content Freshness</h5>
                                </div>
                                <div class="card-body">
                                    <p>Keep your content updated and relevant:</p>
                                    <ul>
                                        <li>Regularly update important pages</li>
                                        <li>Add recent statistics and information</li>
                                        <li>Remove outdated content or information</li>
                                        <li>Add "last updated" dates to key pages</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">AI-Generated Content Example</h5>
                </div>
                <div class="card-body">
                    <h6>Example Blog Post Structure:</h6>
                    
                    <div class="p-3 bg-light rounded mt-3 mb-4">
                        <h2>Title: How to [Achieve Specific Result] with [Your Product/Service]</h2>
                        
                        <div class="mt-2">
                            <strong>Introduction (100-150 words):</strong>
                            <p>Brief explanation of the problem your audience faces and why it matters. Highlight the goal they want to achieve and introduce how your content will help them get there. Include a short overview of what the reader will learn.</p>
                        </div>
                        
                        <div class="mt-2">
                            <strong>Section 1: Understanding [The Problem/Challenge] (200-300 words)</strong>
                            <p>Detailed explanation of the problem, including statistics or examples that show its importance. Address common misconceptions and explain why traditional approaches might not work effectively.</p>
                        </div>
                        
                        <div class="mt-2">
                            <strong>Section 2: The Key Components of [Solution] (300-400 words)</strong>
                            <p>Break down the solution into step-by-step components. Explain each element clearly with examples and include supporting evidence or data points where relevant.</p>
                        </div>
                        
                        <div class="mt-2">
                            <strong>Section 3: How to Implement [Solution] (300-400 words)</strong>
                            <p>Practical guidance on implementation with specific actionable tips. Include potential challenges and how to overcome them.</p>
                        </div>
                        
                        <div class="mt-2">
                            <strong>Section 4: Case Study/Example (200-250 words)</strong>
                            <p>Real-world example showing how the solution works in practice, with specific results and outcomes.</p>
                        </div>
                        
                        <div class="mt-2">
                            <strong>Conclusion (100-150 words)</strong>
                            <p>Summarize key takeaways and reinforce the main benefits. Include a clear call-to-action guiding readers on next steps.</p>
                        </div>
                    </div>
                    
                    <p class="small text-muted">Total word count: ~1200-1650 words, which is ideal for SEO and reader engagement.</p>
                </div>
            </div>
        `;
        
        $('#contentTabContent').html(errorContent).data('results', {});
    },
    
    renderMetricItem: function(label, value, feedback = '') {
        return `
            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">${label}</span>
                    <span class="badge ${this.getMetricBadgeClass(label, value)}">${value}</span>
                </div>
                ${feedback ? `<small class="text-muted d-block mt-1">${feedback}</small>` : ''}
            </div>
        `;
    },
    
    renderContentIssues: function(data) {
        // Generate content issues based on data
        const issues = [];
        
        // Check word count
        if (data.avg_word_count < 300) {
            issues.push({
                title: 'Extremely Thin Content',
                description: 'Your pages have very low word counts (under 300 words), which may be seen as thin content by search engines.',
                severity: 'high'
            });
        } else if (data.avg_word_count < 600) {
            issues.push({
                title: 'Thin Content',
                description: 'Your average word count is below the recommended minimum (600+ words) for ranking well in search results.',
                severity: 'medium'
            });
        }
        
        // Check readability
        if (data.avg_readability < 40) {
            issues.push({
                title: 'Poor Readability',
                description: 'Your content is difficult to read, which can lead to high bounce rates and poor user engagement.',
                severity: 'high'
            });
        } else if (data.avg_readability < 60) {
            issues.push({
                title: 'Average Readability',
                description: 'Your content\'s readability could be improved to better engage visitors and reduce bounce rates.',
                severity: 'medium'
            });
        }
        
        // Check thin content pages
        if (data.thin_content_pages && data.total_pages) {
            const thinContentPercentage = (data.thin_content_pages / data.total_pages) * 100;
            
            if (thinContentPercentage >= 50) {
                issues.push({
                    title: 'Widespread Thin Content',
                    description: `${data.thin_content_pages} out of ${data.total_pages} pages (${Math.round(thinContentPercentage)}%) have thin content, which can negatively impact your site's overall quality score.`,
                    severity: 'high'
                });
            } else if (thinContentPercentage >= 20) {
                issues.push({
                    title: 'Multiple Thin Content Pages',
                    description: `${data.thin_content_pages} out of ${data.total_pages} pages (${Math.round(thinContentPercentage)}%) have thin content that should be improved.`,
                    severity: 'medium'
                });
            }
        }
        
        // Add generic issues if needed
        if (issues.length === 0) {
            issues.push({
                title: 'No Major Content Issues Detected',
                description: 'Your content appears to meet basic quality standards. Focus on the suggestions to further improve content quality and engagement.',
                severity: 'low'
            });
        }
        
        // Render the issues as a list
        if (issues.length === 0) {
            return `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> No major content issues detected.
                </div>
            `;
        }
        
        let html = '';
        
        // Group issues by severity
        const highIssues = issues.filter(issue => issue.severity === 'high');
        const mediumIssues = issues.filter(issue => issue.severity === 'medium');
        const lowIssues = issues.filter(issue => issue.severity === 'low');
        
        if (highIssues.length > 0) {
            html += `
                <div class="mb-3">
                    <h6 class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> Critical Issues</h6>
                    <ul class="list-group">
                        ${highIssues.map(issue => `
                            <li class="list-group-item list-group-item-danger">
                                <div class="fw-bold">${issue.title}</div>
                                <div class="small">${issue.description}</div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
        
        if (mediumIssues.length > 0) {
            html += `
                <div class="mb-3">
                    <h6 class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Warnings</h6>
                    <ul class="list-group">
                        ${mediumIssues.map(issue => `
                            <li class="list-group-item list-group-item-warning">
                                <div class="fw-bold">${issue.title}</div>
                                <div class="small">${issue.description}</div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
        
        if (lowIssues.length > 0) {
            html += `
                <div class="mb-3">
                    <h6 class="text-info"><i class="fas fa-info-circle me-1"></i> Information</h6>
                    <ul class="list-group">
                        ${lowIssues.map(issue => `
                            <li class="list-group-item list-group-item-info">
                                <div class="fw-bold">${issue.title}</div>
                                <div class="small">${issue.description}</div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
        
        return html;
    },
    
    renderSuggestionsList: function(suggestions) {
        if (!suggestions || !Array.isArray(suggestions) || suggestions.length === 0) {
            return '<p class="text-center text-muted">No specific suggestions available</p>';
        }
        
        return `
            <ul class="list-group">
                ${suggestions.map(suggestion => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ${suggestion.description}
                        <span class="badge bg-${this.getPriorityClass(suggestion.priority)} rounded-pill">${suggestion.priority}</span>
                    </li>
                `).join('')}
            </ul>
        `;
    },
    
    renderAIRecommendations: function(recommendations) {
        if (!recommendations || recommendations.length === 0) {
            return `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No specific AI recommendations available.
                </div>
            `;
        }
        
        return `
            <div class="row">
                ${recommendations.map(rec => `
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-${this.getPriorityClass(rec.priority)}">
                            <div class="card-header bg-${this.getPriorityClass(rec.priority)} bg-opacity-10">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-${this.getPriorityClass(rec.priority)}">${rec.title}</h5>
                                    <span class="badge bg-${this.getPriorityClass(rec.priority)}">${rec.priority} priority</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <p>${rec.description}</p>
                                
                                ${rec.example ? `
                                    <div class="mt-3">
                                        <h6 class="text-muted">Example:</h6>
                                        <div class="bg-light p-2 rounded">
                                            <p class="mb-0 small">${rec.example}</p>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${rec.implementation ? `
                                    <div class="mt-3">
                                        <h6 class="text-muted">How to Implement:</h6>
                                        <p class="mb-0 small">${rec.implementation}</p>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },
    
    calculateContentScore: function(data) {
        let score = 50; // Default starting score
        
        // Factor 1: Word count (0-40 points)
        let wordCountScore = 0;
        
        if (data.avg_word_count >= 1500) {
            wordCountScore = 40;
        } else if (data.avg_word_count >= 1000) {
            wordCountScore = 35;
        } else if (data.avg_word_count >= 750) {
            wordCountScore = 30;
        } else if (data.avg_word_count >= 500) {
            wordCountScore = 25;
        } else if (data.avg_word_count >= 300) {
            wordCountScore = 15;
        } else {
            wordCountScore = 5;
        }
        
        // Factor 2: Readability (0-40 points)
        let readabilityScore = 0;
        
        if (data.avg_readability >= 80) {
            readabilityScore = 40;
        } else if (data.avg_readability >= 70) {
            readabilityScore = 35;
        } else if (data.avg_readability >= 60) {
            readabilityScore = 30;
        } else if (data.avg_readability >= 50) {
            readabilityScore = 25;
        } else if (data.avg_readability >= 40) {
            readabilityScore = 15;
        } else {
            readabilityScore = 5;
        }
        
        // Factor 3: Thin content pages penalty (0-20 points deduction)
        let thinContentPenalty = 0;
        
        if (data.thin_content_pages && data.total_pages) {
            const thinContentPercentage = (data.thin_content_pages / data.total_pages) * 100;
            
            if (thinContentPercentage >= 70) {
                thinContentPenalty = 20;
            } else if (thinContentPercentage >= 50) {
                thinContentPenalty = 15;
            } else if (thinContentPercentage >= 30) {
                thinContentPenalty = 10;
            } else if (thinContentPercentage >= 10) {
                thinContentPenalty = 5;
            }
        }
        
        // Calculate final score
        score = wordCountScore + readabilityScore;
        score = Math.max(0, score - thinContentPenalty);
        
        // Ensure score is between 0-100
        return Math.min(100, Math.max(0, score));
    },
    
    generateContentRecommendations: function(data) {
        const recommendations = [];
        
        // Word count recommendations
        if (data.avg_word_count < 300) {
            recommendations.push({
                description: 'Significantly increase content length to at least 500-750 words per page',
                priority: 'high'
            });
        } else if (data.avg_word_count < 600) {
            recommendations.push({
                description: 'Increase content length to 1000+ words for important pages',
                priority: 'medium'
            });
        } else if (data.avg_word_count < 1000) {
            recommendations.push({
                description: 'Consider expanding key pages to 1500+ words for comprehensive coverage',
                priority: 'low'
            });
        }
        
        // Readability recommendations
        if (data.avg_readability < 40) {
            recommendations.push({
                description: 'Improve content readability by using shorter sentences and simpler language',
                priority: 'high'
            });
        } else if (data.avg_readability < 60) {
            recommendations.push({
                description: 'Enhance readability with better formatting, shorter paragraphs, and clearer language',
                priority: 'medium'
            });
        }
        
        // Thin content recommendations
        if (data.thin_content_pages && data.thin_content_pages > 0) {
            recommendations.push({
                description: `Address thin content issues on ${data.thin_content_pages} identified pages`,
                priority: data.thin_content_pages > 5 ? 'high' : 'medium'
            });
        }
        
        // Add general recommendations
        recommendations.push({
            description: 'Add more multimedia content (images, videos, infographics)',
            priority: 'medium'
        });
        
        recommendations.push({
            description: 'Improve content structure with clear headings and subheadings',
            priority: 'medium'
        });
        
        recommendations.push({
            description: 'Add FAQ sections to address common user questions',
            priority: 'low'
        });
        
        recommendations.push({
            description: 'Update older content to keep it fresh and relevant',
            priority: 'medium'
        });
        
        return recommendations;
    },
    
    generateAIRecommendations: function(data) {
        // Generate detailed, actionable AI recommendations with examples
        const recommendations = [];
        
        // Word Count Recommendation
        if (data.avg_word_count < 500) {
            recommendations.push({
                title: 'Expand Content Length',
                description: 'Your content is too short, which limits your ability to thoroughly cover topics and rank well in search results.',
                example: 'Expand your "Services" page from 300 words to 1000+ words by adding detailed service descriptions, FAQs, process explanations, and case study snippets.',
                implementation: 'Identify your top 5 most important pages and double their word count while maintaining quality. Focus on adding valuable details, examples, and comprehensive answers to user questions.',
                priority: 'high'
            });
        } else if (data.avg_word_count < 750) {
            recommendations.push({
                title: 'Enhance Content Depth',
                description: 'Your content has an adequate base length but could benefit from more depth to fully satisfy user search intent.',
                example: 'Transform your 600-word product page into a 1200+ word comprehensive resource by adding usage tips, comparison tables, customer success stories, and detailed specifications.',
                implementation: 'Review top-performing pages in your industry and identify content gaps in your own pages. Add relevant sections that provide more value to users searching for your target keywords.',
                priority: 'medium'
            });
        }
        
        // Readability Recommendation
        if (data.avg_readability < 50) {
            recommendations.push({
                title: 'Improve Content Readability',
                description: 'Your content is difficult to read, which increases bounce rates and reduces engagement.',
                example: 'Before: "The implementation of technical SEO protocols is essential for the optimization of website indexability and subsequent enhancement of search result positioning."\n\nAfter: "Using proper technical SEO helps search engines find and rank your website better."',
                implementation: 'Use shorter sentences (15-20 words max), simpler vocabulary, and active voice. Break up long paragraphs into 2-3 sentence chunks. Use readability tools like Hemingway Editor to simplify complex content.',
                priority: 'high'
            });
        }
        
        // Content Structure Recommendation
        recommendations.push({
            title: 'Optimize Content Structure',
            description: 'Well-structured content improves user experience and helps search engines understand your page hierarchy and key information.',
            example: 'Structure your "Ultimate Guide" as follows:\n• H1: Main title\n• Introduction (2-3 paragraphs)\n• H2: Key Section 1\n• H3: Subsection 1.1\n• H3: Subsection 1.2\n• H2: Key Section 2\n• Bullet points for quick tips\n• Numbered list for step-by-step processes\n• Conclusion with key takeaways',
            implementation: 'Use a clear heading hierarchy (H1, H2, H3) with keywords in headings. Break up text with bullet points, numbered lists, and short paragraphs. Include a table of contents for long-form content (1500+ words).',
            priority: 'medium'
        });
        
        // Content Enhancement Recommendation
        recommendations.push({
            title: 'Add Content Enhancements',
            description: 'Enhanced content with multimedia and interactive elements increases engagement, time on page, and sharing.',
            example: 'For your "How-To Guide," include:\n• Custom images showing each step\n• A 2-minute video demonstration\n• An infographic summarizing the process\n• A downloadable PDF checklist\n• A comparison table of different methods',
            implementation: 'Add at least one image per 300 words. Include videos where appropriate. Create infographics for complex concepts. Use tables to compare options or features. Add downloadable resources as lead magnets.',
            priority: 'medium'
        });
        
        // Thin Content Recommendation
        if (data.thin_content_pages && data.thin_content_pages > 0) {
            recommendations.push({
                title: 'Fix Thin Content Pages',
                description: `You have ${data.thin_content_pages} pages with thin content that could be improved or consolidated.`,
                example: 'Options for thin content pages:\n1. Expand: Add valuable content to reach 750+ words\n2. Merge: Combine several related thin pages into one comprehensive page\n3. Redirect: If merging, set up 301 redirects from old URLs\n4. Noindex: If pages serve a purpose but aren\'t content-focused (e.g., utility pages)',
                implementation: 'Audit all thin content pages and categorize them for: expansion, consolidation, redirection, or applying noindex. Prioritize high-traffic and high-potential pages for expansion.',
                priority: 'high'
            });
        }
        
        // Content Freshness Recommendation
        recommendations.push({
            title: 'Maintain Content Freshness',
            description: 'Regularly updated content signals relevance to search engines and provides value to returning visitors.',
            example: 'Quarterly content refresh checklist:\n• Update statistics and data points\n• Add new industry developments\n• Refresh examples and case studies\n• Check and update any broken links\n• Add new sections addressing recent questions\n• Update "Last modified" date on the page',
            implementation: 'Create a content calendar to regularly audit and update important pages. Prioritize high-traffic pages and cornerstone content. Add recently published research and current trends to show freshness signals.',
            priority: 'medium'
        });
        
        return recommendations;
    },
    
    getWordCountFeedback: function(wordCount) {
        if (wordCount < 300) {
            return 'Very thin content (500+ words recommended)';
        } else if (wordCount < 600) {
            return 'Below optimal length (1000+ words ideal for key pages)';
        } else if (wordCount < 1000) {
            return 'Adequate length (consider expanding important pages)';
        } else {
            return 'Good content length (meets best practices)';
        }
    },
    
    getReadabilityFeedback: function(readability) {
        if (readability < 40) {
            return 'Difficult to read (simplify language and structure)';
        } else if (readability < 60) {
            return 'Moderate readability (could be improved)';
        } else if (readability < 80) {
            return 'Good readability (accessible to most readers)';
        } else {
            return 'Excellent readability (very accessible)';
        }
    },
    
    getThinContentFeedback: function(thinPages, totalPages) {
        if (!thinPages || thinPages === 0) {
            return 'No thin content issues detected';
        }
        
        const percentage = totalPages ? Math.round((thinPages / totalPages) * 100) : 0;
        
        if (percentage > 50) {
            return `Critical: ${percentage}% of pages have thin content`;
        } else if (percentage > 20) {
            return `Concerning: ${percentage}% of pages have thin content`;
        } else {
            return `Minor issue: ${percentage}% of pages have thin content`;
        }
    },
    
    getMetricBadgeClass: function(label, value) {
        if (label === 'Average Word Count') {
            if (value >= 1000) return 'bg-success';
            if (value >= 600) return 'bg-primary';
            if (value >= 300) return 'bg-warning text-dark';
            return 'bg-danger';
        } else if (label === 'Readability Score') {
            if (value >= 80) return 'bg-success';
            if (value >= 60) return 'bg-primary';
            if (value >= 40) return 'bg-warning text-dark';
            return 'bg-danger';
        } else if (label === 'Pages with Thin Content') {
            if (value === 0) return 'bg-success';
            if (value <= 2) return 'bg-primary';
            if (value <= 5) return 'bg-warning text-dark';
            return 'bg-danger';
        }
        
        return 'bg-secondary';
    },
    
    getPriorityClass: function(priority) {
        if (priority === 'high') return 'danger';
        if (priority === 'medium') return 'warning';
        return 'info';
    },
    
    initGaugeChart: function(canvasId, score) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Clear the canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw the background arc
        ctx.beginPath();
        ctx.arc(canvas.width / 2, canvas.height / 2, canvas.width / 2 - 15, Math.PI, 2 * Math.PI);
        ctx.lineWidth = 30;
        ctx.strokeStyle = '#f0f0f0';
        ctx.stroke();
        
        // Draw the score arc
        const scorePercentage = score / 100;
        const scoreAngle = Math.PI + (Math.PI * scorePercentage);
        
        ctx.beginPath();
        ctx.arc(canvas.width / 2, canvas.height / 2, canvas.width / 2 - 15, Math.PI, scoreAngle);
        ctx.lineWidth = 30;
        
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
    }
};