<?php

declare(strict_types=1);

namespace App\Shared\Metrics;

use Illuminate\Support\Facades\Redis;
use Throwable;

final class DomainMetrics
{
    /**
     * In-memory store for fast access and testing.
     *
     * @var array<string, array<string, float|int>>
     */
    private static array $counters = [];

    /**
     * @var array<string, array<string, float|int>>
     */
    private static array $gauges = [];

    /**
     * @var array<string, list<float>>
     */
    private static array $histograms = [];

    public static function incrementCasesCreated(string $serviceId = 'all'): void
    {
        self::incrementCounter('pishkhan_cases_created_total', ['service_id' => $serviceId]);
    }

    public static function observeAssignmentDuration(float $seconds): void
    {
        self::observeHistogram('pishkhan_case_assignment_duration_seconds', $seconds);
    }

    public static function incrementCaseReturns(string $reasonCode): void
    {
        self::incrementCounter('pishkhan_case_returns_total', ['reason_code' => $reasonCode]);
    }

    public static function incrementSlaBreaches(string $officeId): void
    {
        self::incrementCounter('pishkhan_sla_breaches_total', ['office_id' => $officeId]);
    }

    public static function setActiveReverbConnections(int $count): void
    {
        self::setGauge('pishkhan_reverb_connections_active', $count);
    }

    public static function incrementExpiredActionRequiredCases(): void
    {
        self::incrementCounter('pishkhan_expired_action_required_cases_total');
    }

    public static function incrementOtpSent(): void
    {
        self::incrementCounter('pishkhan_otp_sent_total');
    }

    public static function incrementOtpDelivered(): void
    {
        self::incrementCounter('pishkhan_otp_delivered_total');
    }

    public static function setLedgerBalanceCheck(bool $balanced): void
    {
        self::setGauge('pishkhan_ledger_balance_check', $balanced ? 1 : 0);
    }

    public static function setAuditChainStatus(bool $valid): void
    {
        self::setGauge('pishkhan_audit_chain_status', $valid ? 1 : 0);
    }

    /**
     * Render all recorded metrics in OpenMetrics / Prometheus plain text format.
     */
    public static function render(): string
    {
        $out = [];

        // 1. Cases created
        $out[] = '# HELP pishkhan_cases_created_total Total cases created';
        $out[] = '# TYPE pishkhan_cases_created_total counter';
        $out[] = self::renderCounter('pishkhan_cases_created_total', ['service_id' => 'all']);

        // 2. Assignment duration
        $out[] = '# HELP pishkhan_case_assignment_duration_seconds Dispatch assignment duration in seconds';
        $out[] = '# TYPE pishkhan_case_assignment_duration_seconds histogram';
        $out[] = self::renderHistogram('pishkhan_case_assignment_duration_seconds', [1.0, 5.0, 15.0, 30.0, 60.0, 180.0, 300.0, 600.0]);

        // 3. Case returns
        $out[] = '# HELP pishkhan_case_returns_total Case return occurrences by return reason code';
        $out[] = '# TYPE pishkhan_case_returns_total counter';
        $out[] = self::renderMetricGroup('pishkhan_case_returns_total', self::$counters['pishkhan_case_returns_total'] ?? []);

        // 4. SLA breaches
        $out[] = '# HELP pishkhan_sla_breaches_total Total SLA breach occurrences per office';
        $out[] = '# TYPE pishkhan_sla_breaches_total counter';
        $out[] = self::renderMetricGroup('pishkhan_sla_breaches_total', self::$counters['pishkhan_sla_breaches_total'] ?? []);

        // 5. Reverb connections
        $out[] = '# HELP pishkhan_reverb_connections_active Active Reverb WebSocket connections';
        $out[] = '# TYPE pishkhan_reverb_connections_active gauge';
        $reverbVal = self::$gauges['pishkhan_reverb_connections_active'][''] ?? 0;
        $out[] = "pishkhan_reverb_connections_active {$reverbVal}";

        // 6. Expired action required cases
        $out[] = '# HELP pishkhan_expired_action_required_cases_total Total action_required cases expired past 72h';
        $out[] = '# TYPE pishkhan_expired_action_required_cases_total counter';
        $expiredVal = self::$counters['pishkhan_expired_action_required_cases_total'][''] ?? 0;
        $out[] = "pishkhan_expired_action_required_cases_total {$expiredVal}";

        // 7. OTP metrics
        $out[] = '# HELP pishkhan_otp_sent_total Total OTP messages sent';
        $out[] = '# TYPE pishkhan_otp_sent_total counter';
        $sentOtp = self::$counters['pishkhan_otp_sent_total'][''] ?? 0;
        $out[] = "pishkhan_otp_sent_total {$sentOtp}";

        $out[] = '# HELP pishkhan_otp_delivered_total Total OTP messages successfully delivered';
        $out[] = '# TYPE pishkhan_otp_delivered_total counter';
        $delivOtp = self::$counters['pishkhan_otp_delivered_total'][''] ?? 0;
        $out[] = "pishkhan_otp_delivered_total {$delivOtp}";

        // 8. Health and integrity checks
        $out[] = '# HELP pishkhan_ledger_balance_check Double-entry ledger balance state (1=balanced, 0=imbalance)';
        $out[] = '# TYPE pishkhan_ledger_balance_check gauge';
        $ledgerVal = self::$gauges['pishkhan_ledger_balance_check'][''] ?? 1;
        $out[] = "pishkhan_ledger_balance_check {$ledgerVal}";

        $out[] = '# HELP pishkhan_audit_chain_status Immutable audit log hash chain status (1=intact, 0=broken)';
        $out[] = '# TYPE pishkhan_audit_chain_status gauge';
        $auditVal = self::$gauges['pishkhan_audit_chain_status'][''] ?? 1;
        $out[] = "pishkhan_audit_chain_status {$auditVal}";

        return implode("\n", array_filter($out))."\n";
    }

    public static function reset(): void
    {
        self::$counters = [];
        self::$gauges = [];
        self::$histograms = [];
    }

    /**
     * @param  array<string, string>  $labels
     */
    private static function incrementCounter(string $metric, array $labels = [], int $amount = 1): void
    {
        $labelKey = self::formatLabels($labels);
        self::$counters[$metric][$labelKey] = (self::$counters[$metric][$labelKey] ?? 0) + $amount;

        try {
            Redis::hincrby("metrics:counter:{$metric}", $labelKey, $amount);
        } catch (Throwable) {
            // Non-blocking fallback if Redis is unavailable
        }
    }

    /**
     * @param  array<string, string>  $labels
     */
    private static function setGauge(string $metric, float|int $value, array $labels = []): void
    {
        $labelKey = self::formatLabels($labels);
        self::$gauges[$metric][$labelKey] = $value;

        try {
            Redis::hset("metrics:gauge:{$metric}", $labelKey, (string) $value);
        } catch (Throwable) {
            // Non-blocking fallback
        }
    }

    private static function observeHistogram(string $metric, float $value): void
    {
        self::$histograms[$metric][] = $value;
    }

    /**
     * @param  array<string, string>  $labels
     */
    private static function formatLabels(array $labels): string
    {
        if ($labels === []) {
            return '';
        }

        $parts = [];
        foreach ($labels as $k => $v) {
            $parts[] = sprintf('%s="%s"', $k, addcslashes($v, '"\\'));
        }

        return '{'.implode(',', $parts).'}';
    }

    /**
     * @param  array<string, string>  $defaultLabels
     */
    private static function renderCounter(string $metric, array $defaultLabels = []): string
    {
        $entries = self::$counters[$metric] ?? [];
        if ($entries === []) {
            $labelStr = self::formatLabels($defaultLabels);

            return "{$metric}{$labelStr} 0";
        }

        $lines = [];
        foreach ($entries as $labelKey => $val) {
            $lines[] = "{$metric}{$labelKey} {$val}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, float|int>  $entries
     */
    private static function renderMetricGroup(string $metric, array $entries): string
    {
        if ($entries === []) {
            return '';
        }

        $lines = [];
        foreach ($entries as $labelKey => $val) {
            $lines[] = "{$metric}{$labelKey} {$val}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<float>  $buckets
     */
    private static function renderHistogram(string $metric, array $buckets): string
    {
        $observations = self::$histograms[$metric] ?? [];
        $count = count($observations);
        $sum = array_sum($observations);

        $lines = [];
        foreach ($buckets as $le) {
            $bucketCount = count(array_filter($observations, fn (float $val) => $val <= $le));
            $lines[] = sprintf('%s_bucket{le="%.1f"} %d', $metric, $le, $bucketCount);
        }

        $lines[] = sprintf('%s_bucket{le="+Inf"} %d', $metric, $count);
        $lines[] = sprintf('%s_sum %0.4f', $metric, $sum);
        $lines[] = sprintf('%s_count %d', $metric, $count);

        return implode("\n", $lines);
    }
}
