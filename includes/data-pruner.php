<?php
/**
 * data-pruner.php - Database maintenance utility
 */

require_once __DIR__ . '/../config.php';

class DataPruner {
    private $db;
    private $retentionDays;

    public function __construct($db, $retentionDays = 30) {
        $this->db = $db;
        $this->retentionDays = $retentionDays;
    }

    /**
     * Remove old records from the database
     * @return array Results of the pruning operation
     */
    public function prune() {
        $results = [];
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays} days"));

        $tablesToPrune = [
            'technical_seo'     => 'scan_date',
            'onpage_seo'        => 'scan_date',
            'offpage_seo'       => 'scan_date',
            'keyword_analysis'  => 'scan_date',
            'content_analysis'  => 'scan_date',
            'ai_recommendations'=> 'created_at',
            'link_analysis'     => 'scan_date',
            'image_analysis'    => 'scan_date',
            'page_structure'    => 'scan_date',
            'performance_metrics' => 'scan_date',
            'competitive_analysis' => 'scan_date',
            'local_seo'         => 'scan_date',
            'content_gap'       => 'scan_date',
            'meta_tags_analysis'=> 'scan_date',
            'schema_markup'     => 'scan_date',
            'competitor_analysis' => 'scan_date',
            'scheduled_audits'  => 'created_at',
        ];

        foreach ($tablesToPrune as $table => $column) {
            $sql = "DELETE FROM $table WHERE $column < ?";
            if ($stmt = $this->db->prepare($sql)) {
                $stmt->bind_param("s", $cutoffDate);
                $stmt->execute();
                $results[$table] = $stmt->affected_rows;
                $stmt->close();
            } else {
                $results[$table] = ['error' => $this->db->error];
            }
        }

        return $results;
    }

    /**
     * Prune logs from the log files
     * @param string $logPath Path to the logs directory
     */
    public function pruneLogs($logPath = LOGS_PATH) {
        $files = glob($logPath . '/**/*.log');
        foreach ($files as $file) {
            if (filemtime($file) < strtotime("-{$this->retentionDays} days")) {
                unlink($file);
            }
        }
    }

    /**
     * Set the retention period
     * @param int $days
     */
    public function setRetentionDays($days) {
        $this->retentionDays = $days;
    }
}
