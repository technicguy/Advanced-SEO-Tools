const ExportManager = {
    initialize: function() {
        // Set up export button handlers
        $('#exportPDF').on('click', function(e) {
            e.preventDefault();
            ExportManager.exportToPDF();
        });
        
        $('#exportCSV').on('click', function(e) {
            e.preventDefault();
            ExportManager.exportToCSV();
        });
        
        $('#exportJSON').on('click', function(e) {
            e.preventDefault();
            ExportManager.exportToJSON();
        });
    },
    
    // Get current report data from all tabs
    getReportData: function() {
        return {
            url: appState.currentUrl,
            date: new Date().toISOString(),
            technicalSeo: $('#technicalContent').data('results'),
            onPageSeo: $('#onpageContent').data('results'),
            offPageSeo: $('#offpageContent').data('results'),
            keywordAnalysis: $('#keywordsContent').data('results'),
            contentAnalysis: $('#contentTabContent').data('results'),
            aiRecommendations: $('#aiRecommendationsContent').data('results')
        };
    },
    
    // Export to PDF format
    exportToPDF: function() {
        let url = appState.currentUrl;
        const domain = url.replace(/^https?:\/\//, '').replace(/\/.*$/, '');
        
        // Show loading notification
        UIManager.showNotification('Generating PDF report...', 'info');
        
        // Use html2pdf library if available
        if (typeof html2pdf !== 'undefined') {
            const element = document.createElement('div');
            element.innerHTML = this.generatePDFContent();
            
            const opt = {
                margin: [10, 10],
                filename: `seo-report-${domain}-${this.getFormattedDate()}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().from(element).set(opt).save().then(() => {
                UIManager.showNotification('PDF report generated successfully!', 'success');
            }).catch(error => {
                console.error('PDF generation error:', error);
                UIManager.showNotification('Failed to generate PDF report', 'danger');
            });
        } else {
            // Fallback for when html2pdf is not available
            // Create a printable view that the user can save as PDF using browser's print function
            const printWindow = window.open('', '_blank');
            if (printWindow) {
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>SEO Report for ${domain}</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
                            <style>
                                body { padding: 20px; }
                                .page-break { page-break-after: always; }
                                h1 { margin-bottom: 20px; }
                                .section { margin-bottom: 20px; }
                                .print-only { display: block; }
                            </style>
                        </head>
                        <body>
                            ${this.generatePDFContent()}
                            <script>
                                window.onload = function() { window.print(); }
                            </script>
                        </body>
                    </html>
                `);
                printWindow.document.close();
                UIManager.showNotification('Please use your browser\'s print function to save as PDF', 'info');
            } else {
                UIManager.showNotification('Pop-up blocked. Please allow pop-ups to export PDF', 'warning');
            }
        }
    },
    
    // Export to CSV format
    exportToCSV: function() {
        const data = this.getReportData();
        let url = data.url;
        const domain = url.replace(/^https?:\/\//, '').replace(/\/.*$/, '');
        const date = this.getFormattedDate();
        
        // Prepare CSV data
        let csvContent = 'Category,Metric,Value\n';
        
        // Technical SEO
        if (data.technicalSeo) {
            csvContent += `Technical SEO,Crawlability Score,${data.technicalSeo.crawlability_score || 'N/A'}\n`;
            csvContent += `Technical SEO,Indexability Score,${data.technicalSeo.indexability_score || 'N/A'}\n`;
            csvContent += `Technical SEO,Site Speed Score,${data.technicalSeo.site_speed_score || 'N/A'}\n`;
            csvContent += `Technical SEO,Mobile Friendly Score,${data.technicalSeo.mobile_friendly_score || 'N/A'}\n`;
            csvContent += `Technical SEO,Has SSL,${data.technicalSeo.has_ssl ? 'Yes' : 'No'}\n`;
            csvContent += `Technical SEO,Valid Schema Markup,${data.technicalSeo.schema_markup_valid ? 'Yes' : 'No'}\n`;
            
            // Issues
            if (data.technicalSeo.issues && data.technicalSeo.issues.length > 0) {
                data.technicalSeo.issues.forEach((issue, index) => {
                    csvContent += `Technical SEO Issue,${index + 1},"${issue.description.replace(/"/g, '""')} (${issue.severity})"\n`;
                });
            }
        }
        
        // On-Page SEO
        if (data.onPageSeo) {
            csvContent += `On-Page SEO,Title Tags Score,${data.onPageSeo.title_tags_score || 'N/A'}\n`;
            csvContent += `On-Page SEO,Meta Description Score,${data.onPageSeo.meta_desc_score || 'N/A'}\n`;
            csvContent += `On-Page SEO,Header Tags Score,${data.onPageSeo.header_tags_score || 'N/A'}\n`;
            csvContent += `On-Page SEO,Content Score,${data.onPageSeo.content_score || 'N/A'}\n`;
            csvContent += `On-Page SEO,Image Optimization Score,${data.onPageSeo.image_optimization_score || 'N/A'}\n`;
            csvContent += `On-Page SEO,Internal Linking Score,${data.onPageSeo.internal_linking_score || 'N/A'}\n`;
            csvContent += `On-Page SEO,URL Structure Score,${data.onPageSeo.url_structure_score || 'N/A'}\n`;
            
            // Recommendations
            if (data.onPageSeo.recommendations && data.onPageSeo.recommendations.length > 0) {
                data.onPageSeo.recommendations.forEach((rec, index) => {
                    csvContent += `On-Page Recommendation,${index + 1},"${rec.description.replace(/"/g, '""')} (${rec.priority})"\n`;
                });
            }
        }
        
        // Off-Page SEO
        if (data.offPageSeo) {
            csvContent += `Off-Page SEO,Backlink Score,${data.offPageSeo.backlink_score || 'N/A'}\n`;
            csvContent += `Off-Page SEO,Domain Authority,${data.offPageSeo.domain_authority || 'N/A'}\n`;
            csvContent += `Off-Page SEO,Social Media Score,${data.offPageSeo.social_media_score || 'N/A'}\n`;
            csvContent += `Off-Page SEO,Toxic Backlinks,${data.offPageSeo.toxic_backlinks || 'N/A'}\n`;
        }
        
        // Content Analysis
        if (data.contentAnalysis) {
            csvContent += `Content Analysis,Average Word Count,${data.contentAnalysis.avg_word_count || 'N/A'}\n`;
            csvContent += `Content Analysis,Average Readability,${data.contentAnalysis.avg_readability || 'N/A'}\n`;
            csvContent += `Content Analysis,Total Pages,${data.contentAnalysis.total_pages || 'N/A'}\n`;
            csvContent += `Content Analysis,Thin Content Pages,${data.contentAnalysis.thin_content_pages || 'N/A'}\n`;
            
            // Suggestions
            if (data.contentAnalysis.suggestions && data.contentAnalysis.suggestions.length > 0) {
                data.contentAnalysis.suggestions.forEach((sug, index) => {
                    csvContent += `Content Suggestion,${index + 1},"${sug.description.replace(/"/g, '""')} (${sug.priority})"\n`;
                });
            }
        }
        
        // Create and download CSV file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        let csvurl = URL.createObjectURL(blob);
        link.setAttribute('href', csvurl);
        link.setAttribute('download', `seo-report-${domain}-${date}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        UIManager.showNotification('CSV report downloaded successfully!', 'success');
    },
    
    // Export to JSON format
    exportToJSON: function() {
        const data = this.getReportData();
        let url = data.url;
        const domain = url.replace(/^https?:\/\//, '').replace(/\/.*$/, '');
        const date = this.getFormattedDate();
        
        // Convert to pretty-printed JSON
        const jsonContent = JSON.stringify(data, null, 2);
        
        // Create and download JSON file
        const blob = new Blob([jsonContent], { type: 'application/json' });
        const link = document.createElement('a');
        let jsonurl = URL.createObjectURL(blob);
        link.setAttribute('href', jsonurl);
        link.setAttribute('download', `seo-report-${domain}-${date}.json`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        UIManager.showNotification('JSON report downloaded successfully!', 'success');
    },
    
    // Helper function to generate PDF content
    generatePDFContent: function() {
        const data = this.getReportData();
        let url = data.url;
        const date = new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString();
        
        // Calculate overall score
        let technicalScore = 0;
        let onPageScore = 0;
        let offPageScore = 0;
        
        if (data.technicalSeo) {
            technicalScore = (
                (data.technicalSeo.crawlability_score || 0) + 
                (data.technicalSeo.indexability_score || 0) + 
                (data.technicalSeo.site_speed_score || 0) + 
                (data.technicalSeo.mobile_friendly_score || 0)
            ) / 4;
        }
        
        if (data.onPageSeo) {
            onPageScore = (
                (data.onPageSeo.title_tags_score || 0) + 
                (data.onPageSeo.meta_desc_score || 0) + 
                (data.onPageSeo.header_tags_score || 0) + 
                (data.onPageSeo.content_score || 0) + 
                (data.onPageSeo.image_optimization_score || 0) + 
                (data.onPageSeo.internal_linking_score || 0) + 
                (data.onPageSeo.url_structure_score || 0)
            ) / 7;
        }
        
        if (data.offPageSeo) {
            offPageScore = (
                (data.offPageSeo.backlink_score || 0) + 
                (data.offPageSeo.domain_authority || 0) + 
                (data.offPageSeo.social_media_score || 0)
            ) / 3;
        }
        
        const overallScore = Math.round((technicalScore * 0.3) + (onPageScore * 0.4) + (offPageScore * 0.3));
        
        // Generate HTML content for PDF
        return `
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <h1>SEO Analysis Report</h1>
                        <p class="lead">for ${url}</p>
                        <p>Generated on ${date}</p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title">Executive Summary</h2>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h3>Overall SEO Score: ${overallScore}/100</h3>
                                        <p>Site Performance Rating: ${this.getScoreLabel(overallScore)}</p>
                                        <ul>
                                            <li>Technical SEO: ${Math.round(technicalScore)}/100</li>
                                            <li>On-Page SEO: ${Math.round(onPageScore)}/100</li>
                                            <li>Off-Page SEO: ${Math.round(offPageScore)}/100</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h3>Key Findings</h3>
                                        <ul>
                                            ${this.getKeyFindings(data)}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="page-break"></div>
                
                <!-- Technical SEO Section -->
                ${this.generateTechnicalSeoSection(data.technicalSeo)}
                
                <div class="page-break"></div>
                
                <!-- On-Page SEO Section -->
                ${this.generateOnPageSeoSection(data.onPageSeo)}
                
                <div class="page-break"></div>
                
                <!-- Off-Page SEO Section -->
                ${this.generateOffPageSeoSection(data.offPageSeo)}
                
                <!-- Content Analysis Section -->
                ${this.generateContentSection(data.contentAnalysis)}
                
                <div class="page-break"></div>
                
                <!-- Keyword Analysis Section -->
                ${this.generateKeywordSection(data.keywordAnalysis)}
                
                <!-- AI Recommendations -->
                ${this.generateAiRecommendationsSection(data.aiRecommendations)}
            </div>
        `;
    },
    
    // Helper function to get score label
    getScoreLabel: function(score) {
        if (score >= 80) {
            return 'Excellent';
        } else if (score >= 70) {
            return 'Good';
        } else if (score >= 50) {
            return 'Average';
        } else if (score >= 30) {
            return 'Poor';
        } else {
            return 'Critical';
        }
    },
    
    // Helper function to get key findings
    getKeyFindings: function(data) {
        const findings = [];
        
        // Technical findings
        if (data.technicalSeo) {
            if (data.technicalSeo.site_speed_score < 50) {
                findings.push('<li>Site speed needs significant improvement</li>');
            }
            
            if (!data.technicalSeo.has_ssl) {
                findings.push('<li>Site is not secure (missing SSL certificate)</li>');
            }
            
            if (data.technicalSeo.issues && data.technicalSeo.issues.length > 0) {
                const highIssues = data.technicalSeo.issues.filter(issue => issue.severity === 'high');
                if (highIssues.length > 0) {
                    findings.push(`<li>${highIssues.length} critical technical issues detected</li>`);
                }
            }
        }
        
        // On-page findings
        if (data.onPageSeo) {
            if (data.onPageSeo.title_tags_score < 50) {
                findings.push('<li>Title tags need optimization</li>');
            }
            
            if (data.onPageSeo.meta_desc_score < 50) {
                findings.push('<li>Meta descriptions need improvement</li>');
            }
        }
        
        // Content findings
        if (data.contentAnalysis) {
            if (data.contentAnalysis.avg_word_count < 500) {
                findings.push('<li>Content is too thin across the site</li>');
            }
            
            if (data.contentAnalysis.thin_content_pages > 3) {
                findings.push(`<li>${data.contentAnalysis.thin_content_pages} pages have thin content</li>`);
            }
        }
        
        // Add generic finding if none found
        if (findings.length === 0) {
            findings.push('<li>Overall SEO strategy needs improvement</li>');
            findings.push('<li>Focus on content quality and backlink building</li>');
        }
        
        return findings.join('');
    },
    
    // Helper function to generate technical SEO section
    generateTechnicalSeoSection: function(technicalSeo) {
        if (!technicalSeo) {
            return `
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title">Technical SEO</h2>
                                <p>No technical SEO data available</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        return `
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Technical SEO</h2>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h3>Key Metrics</h3>
                                    <table class="table">
                                        <tr>
                                            <td>Crawlability Score</td>
                                            <td>${technicalSeo.crawlability_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>Indexability Score</td>
                                            <td>${technicalSeo.indexability_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>Site Speed Score</td>
                                            <td>${technicalSeo.site_speed_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>Mobile Friendly Score</td>
                                            <td>${technicalSeo.mobile_friendly_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>SSL Certificate</td>
                                            <td>${technicalSeo.has_ssl ? 'Yes' : 'No'}</td>
                                        </tr>
                                        <tr>
                                            <td>Schema Markup</td>
                                            <td>${technicalSeo.schema_markup_valid ? 'Valid' : 'Invalid'}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h3>Issues</h3>
                                    ${technicalSeo.issues && technicalSeo.issues.length > 0 ? `
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Issue</th>
                                                    <th>Severity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${technicalSeo.issues.map(issue => `
                                                    <tr>
                                                        <td>${issue.description}</td>
                                                        <td>${issue.severity}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    ` : '<p>No issues detected</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },
    
    // Helper function to generate on-page SEO section
    generateOnPageSeoSection: function(onPageSeo) {
        if (!onPageSeo) {
            return `
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title">On-Page SEO</h2>
                                <p>No on-page SEO data available</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        return `
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">On-Page SEO</h2>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h3>Key Metrics</h3>
                                    <table class="table">
                                        <tr>
                                            <td>Title Tags Score</td>
                                            <td>${onPageSeo.title_tags_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>Meta Description Score</td>
                                            <td>${onPageSeo.meta_desc_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>Header Tags Score</td>
                                            <td>${onPageSeo.header_tags_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>Content Score</td>
                                            <td>${onPageSeo.content_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>Image Optimization</td>
                                            <td>${onPageSeo.image_optimization_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>Internal Linking</td>
                                            <td>${onPageSeo.internal_linking_score || 'N/A'}/100</td>
                                        </tr>
                                        <tr>
                                            <td>URL Structure</td>
                                            <td>${onPageSeo.url_structure_score || 'N/A'}/100</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h3>Recommendations</h3>
                                    ${onPageSeo.recommendations && onPageSeo.recommendations.length > 0 ? `
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Recommendation</th>
                                                    <th>Priority</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${onPageSeo.recommendations.map(rec => `
                                                    <tr>
                                                        <td>${rec.description}</td>
                                                        <td>${rec.priority}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    ` : '<p>No recommendations available</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },
    
    // Helper functions for other sections would be similar...
    // Generate off-page SEO section, content section, keyword section
    
    // Helper function to format date for filenames
    getFormattedDate: function() {
        const date = new Date();
        return date.getFullYear() + 
               ('0' + (date.getMonth() + 1)).slice(-2) + 
               ('0' + date.getDate()).slice(-2) + '-' + 
               ('0' + date.getHours()).slice(-2) + 
               ('0' + date.getMinutes()).slice(-2);
    }
    
    // Additional helper functions for the remaining sections would be implemented similarly
};

// Add this to main.js in the document ready function to initialize the export functionality

// Initialize export functionality
ExportManager.initialize();