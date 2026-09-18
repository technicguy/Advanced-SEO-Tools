<?php
// Include database configuration
require_once 'config.php';

// Fetch saved domains
$sql = "SELECT id, domain_name, url, last_checked FROM websites ORDER BY last_checked DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error); // Check for query errors
}


$domains = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $domains[] = $row;
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced SEO Tools</title>
    <!-- Bootstrap 5.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/link-analysis.css">
	<link rel="stylesheet" href="css/image-analysis.css">
	<link rel="stylesheet" href="css/ai-recommendations.css">
	<link rel="stylesheet" href="css/local-seo.css">
	<link rel="stylesheet" href="css/content-gap.css">
	<link rel="stylesheet" href="css/local-seo-enhancements.css">
	<link rel="stylesheet" href="css/animations.css">
</head>
<body>
<div id="notificationContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <a href="setting/index.php" class="btn btn-outline-primary mx-3 mb-3">
                        <i class="fas fa-cog"></i> Settings <i class="fas fa-arrow-right"></i> 
                    </a>
                
                    <div class="px-3 py-2">
                        <label for="aiModelSelect" class="form-label">Select AI Model:</label>
                        <select class="form-select" id="aiModelSelect">
                            <option value="">None - No AI assistance</option>
                            <!-- Models will be populated dynamically -->
                        </select>
                    </div>				
                    
                    <div class="px-3 mb-3">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="domainSearchInput" placeholder="Search domains...">
                            <button class="btn btn-outline-secondary" type="button" id="clearDomainSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>				
                    
                    <h4 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Saved Domains</span>
                        <a class="link-secondary" href="#" id="refreshDomains">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </h4>
                    <ul class="nav flex-column" id="domainsList">
                        <?php foreach($domains as $domain): ?>
                            <li class="nav-item">
                                <a class="nav-link domain-item" href="#" data-id="<?php echo $domain['id']; ?>">
                                    <?php echo htmlspecialchars($domain['domain_name']); ?>
                                    <span class="text-muted d-block small">Last checked: <?php echo date('M d, Y H:i', strtotime($domain['last_checked'])); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Advanced SEO Tools</h1>
                </div>

                <!-- URL Input Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Analyze Website SEO</h5>
                    </div>
                    <div class="card-body">
                        <form id="seoAnalysisForm">
                            <div class="input-group mb-3">
                                <input type="url" class="form-control" id="websiteUrl" placeholder="Enter website URL (e.g., https://example.com)" required>
                                <button class="btn btn-primary" type="submit" id="analyzeSeoBtn">
                                    Get SEO Reports
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Progress and Results Section -->
                <div id="analysisProgress" class="d-none">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Analysis Progress</h5>
                        </div>
                        <div class="card-body">
                            <div class="progress mb-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div id="progressStatus" class="mb-3"></div>
                            <div id="progressSteps">
							  <div class="step-item" data-step="technicalSeo">
								<span class="step-icon"><i class="fas fa-cogs"></i></span>
								<span class="step-text">Technical SEO Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="metaTagsAnalysis">
								<span class="step-icon"><i class="fas fa-tags"></i></span>
								<span class="step-text">Meta Tags Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="onPageSeo">
								<span class="step-icon"><i class="fas fa-file-alt"></i></span>
								<span class="step-text">On-Page SEO Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="offPageSeo">
								<span class="step-icon"><i class="fas fa-link"></i></span>
								<span class="step-text">Off-Page SEO Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="linkAnalysis">
								<span class="step-icon"><i class="fas fa-link"></i></span>
								<span class="step-text">Link Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="imageAnalysis">
								<span class="step-icon"><i class="fas fa-images"></i></span>
								<span class="step-text">Image Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="pageStructure">
								<span class="step-icon"><i class="fas fa-code"></i></span>
								<span class="step-text">Page Structure Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="coreWebVitals">
								<span class="step-icon"><i class="fas fa-tachometer-alt"></i></span>
								<span class="step-text">Core Web Vitals Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="schema">
								<span class="step-icon"><i class="fas fa-code"></i></span>
								<span class="step-text">Schema Markup Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="keywordAnalysis">
								<span class="step-icon"><i class="fas fa-key"></i></span>
								<span class="step-text">Keyword Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="contentAnalysis">
								<span class="step-icon"><i class="fas fa-align-left"></i></span>
								<span class="step-text">Content Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="competitor">
								<span class="step-icon"><i class="fas fa-chart-line"></i></span>
								<span class="step-text">Competitor Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="localSeo">
								<span class="step-icon"><i class="fas fa-map-marker-alt"></i></span>
								<span class="step-text">Local SEO Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>
							<div class="step-item" data-step="contentGap">
								<span class="step-icon"><i class="fas fa-puzzle-piece"></i></span>
								<span class="step-text">Content Gap Analysis</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>							
							<div class="step-item" data-step="aiRecommendations">
								<span class="step-icon"><i class="fas fa-robot"></i></span>
								<span class="step-text">AI Recommendations</span>
								<span class="step-status"><i class="fas fa-clock text-muted"></i></span>
							</div>

				
                        </div>

                    </div>
                
				 <div class="card-footer">
                     
						<div class="text-center mt-3 mb-3 d-none" id="saveResultsContainer">
							<button class="btn btn-success btn-lg" id="saveResultsBtn">
								<i class="fas fa-save"></i> Save Analysis Results
							</button>
						</div>
                  </div>
				</div>

                <!-- Results Section - NEW DESIGN USING SECTIONS INSTEAD OF TABS -->
                <div id="resultsContainer" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2><i class="fas fa-chart-line me-2"></i>SEO Analysis Results</h2>
                        <div class="btn-group export-btn" role="group">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" id="exportPDF"><i class="far fa-file-pdf"></i> Export as PDF</a></li>
                                <li><a class="dropdown-item" href="#" id="exportCSV"><i class="fas fa-file-csv"></i> Export as CSV</a></li>
                                <li><a class="dropdown-item" href="#" id="exportJSON"><i class="far fa-file-code"></i> Export as JSON</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Summary Dashboard Card -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Summary Dashboard</h3>
                        </div>
                        <div class="card-body" id="summaryContent">
                            <!-- Summary content will be loaded here -->
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p>Loading summary data...</p>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Results Accordion -->
                    <div class="accordion shadow-sm mb-4" id="seoResultsAccordion">
                        
                        <!-- Technical SEO Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="technicalHeading">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse" aria-expanded="true" aria-controls="technicalCollapse">
                                    <i class="fas fa-cogs me-2"></i> Technical SEO
                                    <span class="badge bg-primary rounded-pill ms-2" id="technicalScore">-</span>
                                </button>
                            </h2>
                            <div id="technicalCollapse" class="accordion-collapse collapse show" aria-labelledby="technicalHeading" data-bs-parent="#seoResultsAccordion">
                                <div class="accordion-body" id="technicalContent">
                                    <!-- Technical SEO content will be loaded here -->
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p>Loading technical SEO data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Meta Tags Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="metaTagsHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#metaTagsCollapse" aria-expanded="false" aria-controls="metaTagsCollapse">
                                    <i class="fas fa-tags me-2"></i> Meta Tags Analysis
                                    <span class="badge bg-primary rounded-pill ms-2" id="metaTagsScore">-</span>
                                </button>
                            </h2>
                            <div id="metaTagsCollapse" class="accordion-collapse collapse" aria-labelledby="metaTagsHeading" data-bs-parent="#seoResultsAccordion">
                                <div class="accordion-body" id="metaTagsContent">
                                    <!-- Meta Tags content will be loaded here -->
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p>Loading meta tags data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- On-Page SEO Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="onpageHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#onpageCollapse" aria-expanded="false" aria-controls="onpageCollapse">
                                    <i class="fas fa-file-alt me-2"></i> On-Page SEO
                                    <span class="badge bg-primary rounded-pill ms-2" id="onpageScore">-</span>
                                </button>
                            </h2>
                            <div id="onpageCollapse" class="accordion-collapse collapse" aria-labelledby="onpageHeading" data-bs-parent="#seoResultsAccordion">
                                <div class="accordion-body" id="onpageContent">
                                    <!-- On-Page SEO content will be loaded here -->
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p>Loading on-page SEO data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
						
			

                        <!-- Off-Page SEO Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="offpageHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#offpageCollapse" aria-expanded="false" aria-controls="offpageCollapse">
                                    <i class="fas fa-link me-2"></i> Off-Page SEO
                                    <span class="badge bg-primary rounded-pill ms-2" id="offpageScore">-</span>
                                </button>
                            </h2>
                            <div id="offpageCollapse" class="accordion-collapse collapse" aria-labelledby="offpageHeading" data-bs-parent="#seoResultsAccordion">
                                <div class="accordion-body" id="offpageContent">
                                    <!-- Off-Page SEO content will be loaded here -->
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p>Loading off-page SEO data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
						
						
<!-- Link Section -->						
						
<div class="accordion-item">
    <h2 class="accordion-header" id="linkAnalysisHeading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#linkAnalysisCollapse" aria-expanded="false" aria-controls="linkAnalysisCollapse">
            <i class="fas fa-link me-2"></i> Link Analysis
            <span class="badge bg-primary rounded-pill ms-2" id="linkAnalysisScore">-</span>
        </button>
    </h2>
    <div id="linkAnalysisCollapse" class="accordion-collapse collapse" aria-labelledby="linkAnalysisHeading" data-bs-parent="#seoResultsAccordion">
        <div class="accordion-body" id="linkAnalysisContent">
            <!-- Link Analysis content will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading link analysis data...</p>
            </div>
        </div>
    </div>
</div>						
<!-- Image Analysis Section -->
<div class="accordion-item">
    <h2 class="accordion-header" id="imageAnalysisHeading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#imageAnalysisCollapse" aria-expanded="false" aria-controls="imageAnalysisCollapse">
            <i class="fas fa-images me-2"></i> Image Analysis
            <span class="badge bg-primary rounded-pill ms-2" id="imageAnalysisScore">-</span>
        </button>
    </h2>
    <div id="imageAnalysisCollapse" class="accordion-collapse collapse" aria-labelledby="imageAnalysisHeading" data-bs-parent="#seoResultsAccordion">
        <div class="accordion-body" id="imageAnalysisContent">
            <!-- Image Analysis content will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading image analysis data...</p>
            </div>
        </div>
    </div>
</div>		
				
<!-- Page Structure Analysis Section -->
<div class="accordion-item">
    <h2 class="accordion-header" id="pageStructureHeading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pageStructureCollapse" aria-expanded="false" aria-controls="pageStructureCollapse">
            <i class="fas fa-code me-2"></i> Page Structure Analysis
            <span class="badge bg-primary rounded-pill ms-2" id="pageStructureScore">-</span>
        </button>
    </h2>
    <div id="pageStructureCollapse" class="accordion-collapse collapse" aria-labelledby="pageStructureHeading" data-bs-parent="#seoResultsAccordion">
        <div class="accordion-body" id="pageStructureContent">
            <!-- Page Structure Analysis content will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading page structure analysis data...</p>
            </div>
        </div>
    </div>
</div>									

          
<!-- Core Web Vitals Section -->
<div class="accordion-item">
    <h2 class="accordion-header" id="coreWebVitalsHeading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#coreWebVitalsCollapse" aria-expanded="false" aria-controls="coreWebVitalsCollapse">
            <i class="fas fa-tachometer-alt me-2"></i> Core Web Vitals
            <span class="badge bg-primary rounded-pill ms-2" id="coreWebVitalsScore">-</span>
        </button>
    </h2>
    <div id="coreWebVitalsCollapse" class="accordion-collapse collapse" aria-labelledby="coreWebVitalsHeading" data-bs-parent="#seoResultsAccordion">
        <div class="accordion-body" id="coreWebVitalsContent">
            <!-- Core Web Vitals content will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading Core Web Vitals data...</p>
            </div>
        </div>
    </div>
</div>

<!-- Schema Markup Section -->
<div class="accordion-item">
    <h2 class="accordion-header" id="schemaHeading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#schemaCollapse" aria-expanded="false" aria-controls="schemaCollapse">
            <i class="fas fa-code me-2"></i> Schema Markup
            <span class="badge bg-primary rounded-pill ms-2" id="schemaScore">-</span>
        </button>
    </h2>
    <div id="schemaCollapse" class="accordion-collapse collapse" aria-labelledby="schemaHeading" data-bs-parent="#seoResultsAccordion">
        <div class="accordion-body" id="schemaContent">
            <!-- Schema Markup content will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading Schema Markup data...</p>
            </div>
        </div>
    </div>
</div>

<!-- Competitor Analysis Section -->
<div class="accordion-item">
    <h2 class="accordion-header" id="competitorHeading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#competitorCollapse" aria-expanded="false" aria-controls="competitorCollapse">
            <i class="fas fa-chart-line me-2"></i> Competitor Analysis
            <span class="badge bg-primary rounded-pill ms-2" id="competitorScore">-</span>
        </button>
    </h2>
    <div id="competitorCollapse" class="accordion-collapse collapse" aria-labelledby="competitorHeading" data-bs-parent="#seoResultsAccordion">
        <div class="accordion-body" id="competitorContent">
            <!-- Competitor Analysis content will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading Competitor Analysis data...</p>
            </div>
        </div>
    </div>
</div>



<!-- Local SEO Analysis Section -->
<div class="accordion-item">
    <h2 class="accordion-header" id="localSeoHeading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#localSeoCollapse" aria-expanded="false" aria-controls="localSeoCollapse">
            <i class="fas fa-map-marker-alt me-2"></i> Local SEO Analysis
            <span class="badge bg-primary rounded-pill ms-2" id="localSeoScore">-</span>
        </button>
    </h2>
    <div id="localSeoCollapse" class="accordion-collapse collapse" aria-labelledby="localSeoHeading" data-bs-parent="#seoResultsAccordion">
        <div class="accordion-body" id="localSeoContent">
            <!-- Local SEO content will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading Local SEO analysis data...</p>
            </div>
        </div>
    </div>
</div>

<!-- Content Gap Analysis Section -->
<div class="accordion-item">
    <h2 class="accordion-header" id="contentGapHeading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contentGapCollapse" aria-expanded="false" aria-controls="contentGapCollapse">
            <i class="fas fa-puzzle-piece me-2"></i> Content Gap Analysis
            <span class="badge bg-primary rounded-pill ms-2" id="contentGapScore">-</span>
        </button>
    </h2>
    <div id="contentGapCollapse" class="accordion-collapse collapse" aria-labelledby="contentGapHeading" data-bs-parent="#seoResultsAccordion">
        <div class="accordion-body" id="contentGapContent">
            <!-- Content Gap content will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading Content Gap analysis data...</p>
            </div>
        </div>
    </div>
</div>


		  <!-- Keywords Analysis Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="keywordsHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#keywordsCollapse" aria-expanded="false" aria-controls="keywordsCollapse">
                                    <i class="fas fa-key me-2"></i> Keywords Analysis
                                    <span class="badge bg-primary rounded-pill ms-2" id="keywordsScore">-</span>
                                </button>
                            </h2>
                            <div id="keywordsCollapse" class="accordion-collapse collapse" aria-labelledby="keywordsHeading" data-bs-parent="#seoResultsAccordion">
                                <div class="accordion-body" id="keywordsContent">
                                    <!-- Keywords Analysis content will be loaded here -->
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p>Loading keywords data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Analysis Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="contentHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contentCollapse" aria-expanded="false" aria-controls="contentCollapse">
                                    <i class="fas fa-align-left me-2"></i> Content Analysis
                                    <span class="badge bg-primary rounded-pill ms-2" id="contentScore">-</span>
                                </button>
                            </h2>
                            <div id="contentCollapse" class="accordion-collapse collapse" aria-labelledby="contentHeading" data-bs-parent="#seoResultsAccordion">
                                <div class="accordion-body" id="contentTabContent">
                                    <!-- Content Analysis content will be loaded here -->
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p>Loading content analysis data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Recommendations Card -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h3 class="mb-0"><i class="fas fa-robot me-2"></i> AI Recommendations</h3>
                        </div>
                        <div class="card-body" id="aiRecommendationsContent">
                            <!-- AI Recommendations content will be loaded here -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Select an AI model from the sidebar to get intelligent SEO recommendations.
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

<!-- Bootstrap 5.2 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<!-- Include vis.js for link visualization -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.js"></script>
    
<!-- Utility and service scripts -->
<script src="js/utils.js"></script>
<script src="js/visualization.js"></script><!-- Make sure visualization.js is loaded FIRST -->
<script src="js/export-manager.js"></script>
<script src="js/api-service.js"></script>

<!-- Feature-specific modules - Make sure content-analysis.js is loaded first -->
<script src="js/content-analysis.js"></script>
<script src="js/technical-seo.js"></script>
<script src="js/technical-seo-recommendations.js"></script>	
<script src="js/technical-seo-regenerators.js"></script>
<script src="js/technical-reanalyze-button.js"></script>	
<script src="js/meta-tags-analysis.js"></script>
<script src="js/onpage-seo.js"></script>
<script src="js/offpage-seo.js"></script>
<script src="js/keyword-analysis.js"></script>
<script src="js/ai-recommendations.js"></script>

<!-- Add these lines before the main.js script -->
<script src="js/meta-tags-enhancements.js"></script>
<script src="js/meta-tags-analysis-extension.js"></script>

<script src="js/link-analysis.js"></script>
<script src="js/image-analysis.js"></script>
<script src="js/page-structure.js"></script>

<!-- Make sure these are added in the right order -->
<script src="js/core-web-vitals.js"></script>
<script src="js/schema-analysis.js"></script>
<script src="js/competitor-analysis.js"></script>
<script src="js/local-seo.js"></script>
<script src="js/local-seo-enhancements.js"></script>
<script src="js/content-gap.js"></script>

<!-- Manager scripts - Load these after all feature modules -->
<script src="js/ui-manager.js"></script>
<script src="js/domain-manager.js"></script>
<script src="js/analysis-manager.js"></script>
<script src="js/progressive-loader.js"></script>

<!-- Main script last -->
<script src="js/main.js"></script>

   <script>
   /**
    * Script to handle additional UI functionality:
    * - Remove accordion functionality
    * - Add internal linking from progress steps
    */
   $(document).ready(function() {
       // Function to expand all sections
       function expandAllSections() {
           // Remove 'collapsed' class from all accordion buttons
           $('.accordion-button').removeClass('collapsed').attr('aria-expanded', 'true');
           
           // Add 'show' class to all accordion collapses
           $('.accordion-collapse').addClass('show');
           
           // Remove data-bs-toggle attribute to disable toggling
           $('.accordion-button').removeAttr('data-bs-toggle');
       }
       
       // Function to add section IDs
       function addSectionIds() {
           // Add IDs to sections for internal linking if they don't exist yet
           if (!$('#technicalCollapse').closest('.accordion-item').attr('id')) {
               $('#technicalCollapse').closest('.accordion-item').attr('id', 'technicalSeoSection');
               $('#metaTagsCollapse').closest('.accordion-item').attr('id', 'metaTagsAnalysisSection');
               $('#onpageCollapse').closest('.accordion-item').attr('id', 'onPageSeoSection');
               $('#offpageCollapse').closest('.accordion-item').attr('id', 'offPageSeoSection');
               $('#linkAnalysisCollapse').closest('.accordion-item').attr('id', 'linkAnalysisSection');
               $('#imageAnalysisCollapse').closest('.accordion-item').attr('id', 'imageAnalysisSection');
               $('#pageStructureCollapse').closest('.accordion-item').attr('id', 'pageStructureSection');
               $('#coreWebVitalsCollapse').closest('.accordion-item').attr('id', 'coreWebVitalsSection');
               $('#schemaCollapse').closest('.accordion-item').attr('id', 'schemaSection');
               $('#keywordsCollapse').closest('.accordion-item').attr('id', 'keywordAnalysisSection');
               $('#contentCollapse').closest('.accordion-item').attr('id', 'contentAnalysisSection');
               $('#competitorCollapse').closest('.accordion-item').attr('id', 'competitorSection');
			   $('#localSeoCollapse').closest('.accordion-item').attr('id', 'localSeoSection');
			   $('#contentGapCollapse').closest('.accordion-item').attr('id', 'contentGapSection');
           }
       }
       
       // Function to setup step click handlers
       function setupStepClickHandlers() {
           // Make step items clickable with pointer cursor
           $('.step-item').css('cursor', 'pointer');
           
           // Remove any existing click handlers first
           $('.step-item').off('click');
           
           // Add click handlers for each step
           $('.step-item[data-step="technicalSeo"]').on('click', function() {
               scrollToSection('technicalSeoSection');
           });
           
           $('.step-item[data-step="metaTagsAnalysis"]').on('click', function() {
               scrollToSection('metaTagsAnalysisSection');
           });
           
           $('.step-item[data-step="onPageSeo"]').on('click', function() {
               scrollToSection('onPageSeoSection');
           });
           
           $('.step-item[data-step="offPageSeo"]').on('click', function() {
               scrollToSection('offPageSeoSection');
           });
           
           $('.step-item[data-step="linkAnalysis"]').on('click', function() {
               scrollToSection('linkAnalysisSection');
           });
           
           $('.step-item[data-step="imageAnalysis"]').on('click', function() {
               scrollToSection('imageAnalysisSection');
           });
           
           $('.step-item[data-step="pageStructure"]').on('click', function() {
               scrollToSection('pageStructureSection');
           });
           
           $('.step-item[data-step="coreWebVitals"]').on('click', function() {
               scrollToSection('coreWebVitalsSection');
           });
           
           $('.step-item[data-step="schema"]').on('click', function() {
               scrollToSection('schemaSection');
           });
           
           $('.step-item[data-step="keywordAnalysis"]').on('click', function() {
               scrollToSection('keywordAnalysisSection');
           });
           
           $('.step-item[data-step="contentAnalysis"]').on('click', function() {
               scrollToSection('contentAnalysisSection');
           });
           
           $('.step-item[data-step="competitor"]').on('click', function() {
               scrollToSection('competitorSection');
           });
		   
			$('.step-item[data-step="localSeo"]').on('click', function() {
				scrollToSection('localSeoSection');
			});

			$('.step-item[data-step="contentGap"]').on('click', function() {
				scrollToSection('contentGapSection');
			});		   
		   
       }
       
       // Function to scroll to a section
       function scrollToSection(sectionId) {
           const $section = $('#' + sectionId);
           
           if ($section.length) {
               // Ensure results container is visible
               $('#resultsContainer').removeClass('d-none');
               
               // Scroll to the section with an offset for header
               $('html, body').animate({
                   scrollTop: $section.offset().top - 70
               }, 500);
               
               // Add highlight effect
               $section.addClass('highlight-section');
               setTimeout(function() {
                   $section.removeClass('highlight-section');
               }, 1500);
           }
       }
       
       // Add CSS for section highlighting
       function addHighlightStyle() {
           if ($('#highlightStyle').length === 0) {
               $('head').append(`
                   <style id="highlightStyle">
                       @keyframes highlight-pulse {
                           0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
                           70% { box-shadow: 0 0 0 15px rgba(13, 110, 253, 0); }
                           100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
                       }
                       
                       .highlight-section {
                           animation: highlight-pulse 1.5s 1;
                           border: 1px solid rgba(13, 110, 253, 0.5);
                           border-radius: 8px;
                       }
                       
                       /* Make step items look clickable */
                       .step-item {
                           cursor: pointer;
                           transition: all 0.2s ease;
                       }
                       
                       .step-item:hover {
                           background-color: #e9ecef;
                           transform: translateY(-2px);
                           box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                       }
                       
                       /* Always show accordion content */
                       .accordion-button.collapsed {
                           background-color: #f8f9fa !important;
                       }
                       
                       .accordion-button::after {
                           display: none !important;
                       }
                       
                       /* Improve accordion headers */
                       .accordion-button {
                           background-color: #f8f9fa !important;
                           border-radius: 8px !important;
                           margin-bottom: 0.5rem !important;
                           box-shadow: none !important;
                       }
                   </style>
               `);
           }
       }
       
       // Initial setup
       addHighlightStyle();
       expandAllSections();
       addSectionIds();
       setupStepClickHandlers();
       
       // Keep applied when new analysis is done
       $(document).on('analysisCompleted', function() {
           setTimeout(function() {
               expandAllSections();
               addSectionIds();
               setupStepClickHandlers();
           }, 500);
       });
       
       // Also run when analysis is loaded from saved state
       $(document).on('analysisLoaded', function() {
           setTimeout(function() {
               expandAllSections();
               addSectionIds();
               setupStepClickHandlers();
           }, 500);
       });
   });
   </script>
</body>
</html>