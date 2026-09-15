<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Shared\Metrics\DomainMetrics;
use Tests\TestCase;

final class MetricsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DomainMetrics::reset();
    }

    public function test_metrics_endpoint_returns_prometheus_exposition_format(): void
    {
        $response = $this->get('/metrics');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');

        $content = $response->getContent();
        $this->assertIsString($content);

        // Core Domain Metrics §9.6
        $this->assertStringContainsString('pishkhan_cases_created_total', $content);
        $this->assertStringContainsString('pishkhan_case_assignment_duration_seconds', $content);
        $this->assertStringContainsString('pishkhan_case_returns_total', $content);
        $this->assertStringContainsString('pishkhan_sla_breaches_total', $content);
        $this->assertStringContainsString('pishkhan_reverb_connections_active', $content);
        $this->assertStringContainsString('pishkhan_expired_action_required_cases_total', $content);
        $this->assertStringContainsString('pishkhan_otp_sent_total', $content);
        $this->assertStringContainsString('pishkhan_otp_delivered_total', $content);
        $this->assertStringContainsString('pishkhan_ledger_balance_check', $content);
        $this->assertStringContainsString('pishkhan_audit_chain_status', $content);
    }

    public function test_domain_metrics_are_correctly_recorded_and_rendered(): void
    {
        DomainMetrics::incrementCasesCreated('srv-passport');
        DomainMetrics::incrementCasesCreated('srv-passport');
        DomainMetrics::observeAssignmentDuration(4.5);
        DomainMetrics::incrementCaseReturns('DOC_BLUR');
        DomainMetrics::incrementSlaBreaches('office-101');
        DomainMetrics::setActiveReverbConnections(42);
        DomainMetrics::setLedgerBalanceCheck(true);
        DomainMetrics::setAuditChainStatus(true);

        $response = $this->get('/api/v1/metrics');
        $response->assertStatus(200);

        $content = (string) $response->getContent();

        $this->assertStringContainsString('pishkhan_cases_created_total{service_id="srv-passport"} 2', $content);
        $this->assertStringContainsString('pishkhan_case_assignment_duration_seconds_bucket{le="5.0"} 1', $content);
        $this->assertStringContainsString('pishkhan_case_returns_total{reason_code="DOC_BLUR"} 1', $content);
        $this->assertStringContainsString('pishkhan_sla_breaches_total{office_id="office-101"} 1', $content);
        $this->assertStringContainsString('pishkhan_reverb_connections_active 42', $content);
        $this->assertStringContainsString('pishkhan_ledger_balance_check 1', $content);
        $this->assertStringContainsString('pishkhan_audit_chain_status 1', $content);
    }
}
