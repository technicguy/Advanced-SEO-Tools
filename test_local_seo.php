<?php
// test_local_seo.php
require_once 'config.php';
require_once 'includes/local-seo-analyzer.php';

$url = 'https://www.google.com'; // Test with a known URL

try {
    echo "Starting Local SEO Analysis for $url...\n";
    $analysis = LocalSEOAnalyzer::analyze($url);
    echo "Analysis successful!\n";
    print_r($analysis);
} catch (Throwable $e) {
    echo "FATAL ERROR CAUGHT: " . $e->getMessage() . "\n";
    echo "In " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
