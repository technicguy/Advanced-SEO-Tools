/**
 * onpage-seo.js - On-Page SEO analysis functionality
 */

const OnPageSEO = {
    displayResults: function(data) {
        // Check if data is null or missing critical components
        if (!data || (!data.title_tags_score && !data.recommendations)) {
            const errorContent = `
                <div class="alert alert-warning mb-4">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                    Could not retrieve on-page SEO data for this website. The site may be blocking automated content analysis.
                </div>
                <div class="card results-card">
                    <div class="card-header">
                        On-Page SEO - Generic Recommendations
                    </div>
                    <div class="card-body">
                        <p>Since direct on-page analysis couldn't be performed, here are some general on-page SEO recommendations:</p>
                        <ul>
                            <li>Use unique, descriptive title tags (50-60 characters)</li>
                            <li>Write compelling meta descriptions (150-160 characters)</li>
                            <li>Use proper heading structure (H1, H2, H3)</li>
                            <li>Optimize image alt tags for accessibility and SEO</li>
                            <li>Create content that's at least 700-1000 words for key pages</li>
                            <li>Include internal links to other relevant pages</li>
                        </ul>
                    </div>
                </div>
            `;
            
            $('#onpageContent').html(errorContent).data('results', {});
            return;
        }
        
        // Regular display logic
        let content = '';
        
        // Show error message if explicitly provided
        if (data.error) {
            content += `
                <div class="alert alert-warning mb-4">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                    ${data.error}
                </div>
            `;
        }
    
        content += `
            <div class="row">
                <div class="col-md-6">
                    <div class="card results-card">
                        <div class="card-header">
                            On-Page SEO Scores
                        </div>
                        <div class="card-body">
                            <div class="score-item">
                                <div class="score-label">Title Tags</div>
                                <div class="score-badge ${Utils.getScoreClass(data.title_tags_score)}">${data.title_tags_score}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">Meta Descriptions</div>
                                <div class="score-badge ${Utils.getScoreClass(data.meta_desc_score)}">${data.meta_desc_score}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">Header Tags</div>
                                <div class="score-badge ${Utils.getScoreClass(data.header_tags_score)}">${data.header_tags_score}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">Content Quality</div>
                                <div class="score-badge ${Utils.getScoreClass(data.content_score)}">${data.content_score}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">Image Optimization</div>
                                <div class="score-badge ${Utils.getScoreClass(data.image_optimization_score)}">${data.image_optimization_score}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">Internal Linking</div>
                                <div class="score-badge ${Utils.getScoreClass(data.internal_linking_score)}">${data.internal_linking_score}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">URL Structure</div>
                                <div class="score-badge ${Utils.getScoreClass(data.url_structure_score)}">${data.url_structure_score}/100</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card results-card">
                        <div class="card-header">
                            On-Page SEO Recommendations
                        </div>
                        <div class="card-body">
                            ${data.recommendations && Array.isArray(data.recommendations) && data.recommendations.length > 0 ? `
                                <ul class="list-group">
                                    ${data.recommendations.map(rec => `
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            ${rec.description}
                                            <span class="badge bg-${rec.priority === 'high' ? 'danger' : (rec.priority === 'medium' ? 'warning' : 'info')} rounded-pill">${rec.priority}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            ` : `
                                <p class="text-center text-muted">No specific recommendations available</p>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#onpageContent').html(content).data('results', data);
    }
};
