/**
 * keyword-analysis.js - Keyword analysis functionality
 */

const KeywordAnalysis = {
    displayResults: function(data) {
        // Check if data or keywords are null/undefined
        if (!data || !data.keywords || !Array.isArray(data.keywords) || data.keywords.length === 0) {
            const errorContent = `
                <div class="alert alert-warning mb-4">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                    Could not retrieve keyword data for this website. The site may be blocking automated keyword extraction.
                </div>
                <div class="card results-card">
                    <div class="card-header">
                        Keyword Analysis - Generic Recommendations
                    </div>
                    <div class="card-body">
                        <p>Since direct keyword analysis couldn't be performed, here are some general keyword strategy recommendations:</p>
                        <ul>
                            <li>Focus on long-tail keywords relevant to your industry</li>
                            <li>Include your main keywords in page titles, headers, and first paragraph</li>
                            <li>Use keyword variations naturally throughout your content</li>
                            <li>Consider search intent behind keywords (informational, navigational, transactional)</li>
                            <li>Research competitors' top-performing keywords</li>
                        </ul>
                    </div>
                </div>
            `;
            
            $('#keywordsContent').html(errorContent).data('results', { keywords: [] });
            return;
        }
        
        // Regular display logic for when we have keyword data
        let content = '';
        
        if (data.note) {
            content += `
                <div class="alert alert-info mb-4">
                    <strong><i class="fas fa-info-circle"></i> Note:</strong> 
                    ${data.note}
                </div>
            `;
        }
        
        content += `
            <div class="row">
                <div class="col-md-12">
                    <div class="card results-card">
                        <div class="card-header">
                            Keyword Analysis ${data.industry ? `(${Utils.capitalizeFirstLetter(data.industry)} Industry)` : ''}
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Keyword</th>
                                            <th>Search Volume</th>
                                            <th>Difficulty</th>
                                            <th>Current Ranking</th>
                                            <th>Potential</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.keywords.map(keyword => `
                                            <tr>
                                                <td>${keyword.keyword}</td>
                                                <td>${keyword.search_volume}</td>
                                                <td>
                                                    <div class="progress" style="height: 15px;">
                                                        <div class="progress-bar ${keyword.difficulty < 30 ? 'bg-success' : (keyword.difficulty < 70 ? 'bg-warning' : 'bg-danger')}" 
                                                            role="progressbar" style="width: ${keyword.difficulty}%" 
                                                            aria-valuenow="${keyword.difficulty}" aria-valuemin="0" aria-valuemax="100">
                                                            ${keyword.difficulty}%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>${keyword.current_ranking > 0 ? keyword.current_ranking : 'Not Ranked'}</td>
                                                <td>${Utils.getKeywordPotential(keyword.search_volume, keyword.difficulty, keyword.current_ranking)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                            
                            ${data.keywords.length > 5 ? `
                                <div class="mt-4">
                                    <h6>Keyword Opportunities</h6>
                                    <p>Based on the analysis, we've identified the following keyword opportunities:</p>
                                    <ul>
                                        ${data.keywords
                                            .filter(k => k.search_volume > 500 && k.difficulty < 60 && (!k.current_ranking || k.current_ranking > 10))
                                            .slice(0, 3)
                                            .map(k => `<li><strong>${k.keyword}</strong> - Good potential for ranking with targeted content</li>`)
                                            .join('')
                                        }
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#keywordsContent').html(content).data('results', data);
    }
};
