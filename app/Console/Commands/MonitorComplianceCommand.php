<?php
// app/Console/Commands/MonitorComplianceCommand.php

namespace App\Console\Commands;

use App\Services\ComplianceMonitor;
use Illuminate\Console\Command;

class MonitorComplianceCommand extends Command
{
    protected $signature = 'compliance:monitor {--alert : Send alerts if issues are found}';
    protected $description = 'Monitor the compliance system health and optionally send alerts';

    public function handle(ComplianceMonitor $monitor)
    {
        $this->info('Checking compliance system health...');
        
        $health = $monitor->checkSystemHealth();
        
        $this->line("Overall Status: {$health['overall_status']}");
        
        if (!empty($health['issues'])) {
            $this->warn('Issues Found:');
            foreach ($health['issues'] as $issue) {
                $this->line("  • {$issue['message']}");
            }
        } else {
            $this->info('No issues found.');
        }

        if (!empty($health['metrics'])) {
            $this->line("\nFeed Metrics (Last 24h):");
            foreach ($health['metrics'] as $feedName => $metrics) {
                $this->line("  {$feedName}: {$metrics['successful_attempts']}/{$metrics['total_attempts']} ({$metrics['success_rate']}% success rate)");
            }
        }

        if ($this->option('alert') && $health['overall_status'] !== 'healthy') {
            $this->info('Sending alerts...');
            $monitor->sendAlert($health);
        }

        return $health['overall_status'] === 'healthy' ? 0 : 1;
    }
}