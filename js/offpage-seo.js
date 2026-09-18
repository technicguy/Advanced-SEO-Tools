/**
 * offpage-seo.js - Off-Page SEO analysis functionality
 */

const OffPageSEO = {
    displayResults: function(data) {
        // Check if data is null or missing critical components
        if (!data || (!data.domain_authority && !data.backlink_score && !data.top_backlinks)) {
            const errorContent = `
                <div class="alert alert-warning mb-4">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                    Could not retrieve off-page SEO data for this website. This may be due to limited access without paid APIs.
                </div>
                <div class="card results-card">
                    <div class="card-header">
                        Off-Page SEO - Generic Recommendations
                    </div>
                    <div class="card-body">
                        <p>Since direct off-page analysis couldn't be performed, here are some general off-page SEO recommendations:</p>
                        <ul>
                            <li>Build high-quality backlinks from relevant, authoritative sites</li>
                            <li>Develop a consistent social media presence</li>
                            <li>Create shareable content that naturally attracts links</li>
                            <li>Engage in guest blogging on industry-relevant sites</li>
                            <li>Monitor and disavow toxic backlinks</li>
                            <li>Build local citations if you have a physical business</li>
                        </ul>
                    </div>
                </div>
            `;
            
            $('#offpageContent').html(errorContent).data('results', {});
            return;
        }
        
        // Regular display logic
        let content = '';
        
        content += `
            <div class="row">
                <div class="col-md-6">
                    <div class="card results-card">
                        <div class="card-header">
                            Off-Page SEO Metrics
                        </div>
                        <div class="card-body">
                            <div class="score-item">
                                <div class="score-label">Backlink Score</div>
                                <div class="score-badge ${Utils.getScoreClass(data.backlink_score)}">${data.backlink_score}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">Domain Authority</div>
                                <div class="score-badge ${Utils.getScoreClass(data.domain_authority)}">${data.domain_authority}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">Social Media Presence</div>
                                <div class="score-badge ${Utils.getScoreClass(data.social_media_score)}">${data.social_media_score}/100</div>
                            </div>
                            <div class="score-item">
                                <div class="score-label">Toxic Backlinks</div>
                                <div class="score-badge ${data.toxic_backlinks > 10 ? 'score-poor' : (data.toxic_backlinks > 0 ? 'score-average' : 'score-good')}">${data.toxic_backlinks}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card results-card">
                        <div class="card-header">
                            Top Backlinks
                        </div>
                        <div class="card-body">
                            ${data.top_backlinks && Array.isArray(data.top_backlinks) && data.top_backlinks.length > 0 ? `
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Source</th>
                                                <th>Domain Authority</th>
                                                <th>Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.top_backlinks.map(backlink => `
                                                <tr>
                                                    <td><a href="${backlink.url}" target="_blank" rel="noopener noreferrer">${backlink.domain}</a></td>
                                                    <td>${backlink.domain_authority}</td>
                                                    <td>${backlink.type}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            ` : `
                                <p class="text-center text-muted">No backlink data available</p>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#offpageContent').html(content).data('results', data);
    }
};
