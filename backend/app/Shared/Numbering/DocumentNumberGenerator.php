<?php

namespace App\Shared\Numbering;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Atomic, row-locked document number generator (agent_docs/02_SCOPE_AND_ASSUMPTIONS.md,
 * 05_DATABASE_SCHEMA.md §3). Formatting is driven by `config/pwms.php`, not hardcoded in callers.
 */
final class DocumentNumberGenerator
{
    /**
     * @param  array<string, string>  $context  Placeholder values such as `['product_code' => '1001']`.
     */
    public function next(string $documentType, ?CarbonInterface $at = null, array $context = []): string
    {
        $definition = config("pwms.numbering.{$documentType}");
        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown document numbering type [{$documentType}].");
        }

        $at = ($at ?? now())->copy()->timezone(config('pwms.display_timezone', 'Asia/Jakarta'));
        $prefix = (string) ($definition['prefix'] ?? '');
        $dateFormat = $definition['date_format'] ?? 'Ymd';
        $resetPeriod = (string) ($definition['reset_period'] ?? 'DAILY');
        $padding = (int) ($definition['padding'] ?? 4);
        $pattern = (string) ($definition['pattern'] ?? '{prefix}-{date}-{seq}');

        $dateToken = $dateFormat ? $at->format($dateFormat) : '';
        $scopeKey = $this->buildScopeKey($resetPeriod, $at, $context);

        $sequence = DB::transaction(function () use ($documentType, $scopeKey, $prefix, $dateFormat, $resetPeriod): int {
            $row = $this->lockOrCreateRow($documentType, $scopeKey, $prefix, $dateFormat, $resetPeriod);
            $next = ((int) $row->last_number) + 1;

            DB::table('document_sequences')
                ->where('id', $row->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        });

        $replacements = [
            '{prefix}' => $prefix,
            '{date}' => $dateToken,
            '{seq}' => str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT),
        ];
        foreach ($context as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
        }

        return strtr($pattern, $replacements);
    }

    /**
     * @param  array<string, string>  $context
     */
    private function buildScopeKey(string $resetPeriod, CarbonInterface $at, array $context): string
    {
        $periodPart = match ($resetPeriod) {
            'DAILY' => $at->format('Ymd'),
            'MONTHLY' => $at->format('Ym'),
            'YEARLY' => $at->format('Y'),
            'NEVER' => 'ALL',
            default => throw new InvalidArgumentException("Unsupported reset period [{$resetPeriod}]."),
        };

        if ($context === []) {
            return $periodPart;
        }

        ksort($context);

        return implode('|', [...array_values($context), $periodPart]);
    }

    private function lockOrCreateRow(
        string $documentType,
        string $scopeKey,
        string $prefix,
        ?string $dateFormat,
        string $resetPeriod,
    ): object {
        $row = DB::table('document_sequences')
            ->where('document_type', $documentType)
            ->where('scope_key', $scopeKey)
            ->lockForUpdate()
            ->first();

        if ($row !== null) {
            return $row;
        }

        try {
            DB::table('document_sequences')->insert([
                'document_type' => $documentType,
                'scope_key' => $scopeKey,
                'prefix' => $prefix,
                'date_format' => $dateFormat,
                'reset_period' => $resetPeriod,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another transaction inserted the same scope concurrently; fall through to lock it.
        }

        return (object) DB::table('document_sequences')
            ->where('document_type', $documentType)
            ->where('scope_key', $scopeKey)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
