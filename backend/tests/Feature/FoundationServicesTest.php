<?php

namespace Tests\Feature;

use App\Shared\Audit\AuditLog;
use App\Shared\Idempotency\IdempotencyService;
use App\Shared\Numbering\DocumentNumberGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class FoundationServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('idempotent')->post('/api/v1/_test/idempotent-post', function () {
            $gen = app(DocumentNumberGenerator::class);

            return response()->json([
                'data' => [
                    'document_no' => $gen->next('INVENTORY_TRANSFER', CarbonImmutable::parse('2026-11-09 10:00:00', 'Asia/Jakarta')),
                    'status' => 'POSTED',
                ],
            ], 201);
        })->name('test.idempotent');
    }

    public function test_document_number_generator_is_sequential_and_respects_patterns(): void
    {
        $gen = app(DocumentNumberGenerator::class);
        $date = CarbonImmutable::parse('2026-11-07 09:00:00', 'Asia/Jakarta');

        $this->assertSame('TRF-20261107-0001', $gen->next('INVENTORY_TRANSFER', $date));
        $this->assertSame('TRF-20261107-0002', $gen->next('INVENTORY_TRANSFER', $date));

        // Next day resets the DAILY sequence.
        $nextDay = $date->addDay();
        $this->assertSame('TRF-20261108-0001', $gen->next('INVENTORY_TRANSFER', $nextDay));

        // Production execution code pattern from 02_SCOPE_AND_ASSUMPTIONS.md: {product_code}-{YYMMDD}-{sequence}
        $this->assertSame('1001-261107-01', $gen->next('PRODUCTION_ORDER', $date, ['product_code' => '1001']));
        $this->assertSame('1001-261107-02', $gen->next('PRODUCTION_ORDER', $date, ['product_code' => '1001']));
        $this->assertSame('1002-261107-01', $gen->next('PRODUCTION_ORDER', $date, ['product_code' => '1002']));

        // Rental pattern: PP-{YYMMDD}-{sequence}
        $this->assertSame('PP-261107-01', $gen->next('RENTAL_ORDER', $date));
    }

    public function test_idempotent_endpoint_does_not_double_post_on_retry(): void
    {
        $headers = [IdempotencyService::HEADER => 'client-key-001'];
        $payload = ['source_location_id' => 1, 'destination_location_id' => 2];

        $first = $this->postJson('/api/v1/_test/idempotent-post', $payload, $headers);
        $first->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'false')
            ->assertJsonPath('data.document_no', 'TRF-20261109-0001');

        // Retrying the exact same request with the same Idempotency-Key replays the original response
        // without advancing the document sequence.
        $second = $this->postJson('/api/v1/_test/idempotent-post', $payload, $headers);
        $second->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.document_no', 'TRF-20261109-0001');

        // Reusing the same key for a different payload is rejected with HTTP 409.
        $conflict = $this->postJson('/api/v1/_test/idempotent-post', ['source_location_id' => 99], $headers);
        $conflict->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED_WITH_DIFFERENT_PAYLOAD');
    }

    public function test_idempotent_endpoint_rejects_missing_header(): void
    {
        $this->postJson('/api/v1/_test/idempotent-post', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');
    }

    public function test_audit_logger_redacts_secrets(): void
    {
        $log = app(\App\Shared\Audit\AuditLogger::class)->record(
            'test.action',
            null,
            ['password' => 'secret123', 'nested' => ['token' => 'abc']],
            ['name' => 'Visible'],
        );

        $fresh = AuditLog::query()->findOrFail($log->id);
        $this->assertSame('[REDACTED]', $fresh->before_json['password']);
        $this->assertSame('[REDACTED]', $fresh->before_json['nested']['token']);
        $this->assertSame('Visible', $fresh->after_json['name']);
    }
}
