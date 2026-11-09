import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { StatusBadge } from '@/components/StatusBadge';
import { EMPTY_VALUE, formatNumber, formatRupiah } from '@/lib/format';

describe('format helpers & StatusBadge', () => {
  it('returns the canonical empty placeholder instead of fabricated values', () => {
    expect(formatRupiah(null)).toBe(EMPTY_VALUE);
    expect(formatRupiah('')).toBe(EMPTY_VALUE);
    expect(formatNumber(undefined)).toBe(EMPTY_VALUE);
  });

  it('formats decimal strings without floating-point coercion issues', () => {
    expect(formatNumber('1250.5000')).toContain('1.250');
    expect(formatRupiah('150000.00')).toContain('150.000');
  });

  it('renders a consistent text label on status badges (not color alone)', () => {
    render(<StatusBadge status="SUBMITTED" />);
    expect(screen.getByText('Menunggu Persetujuan')).toBeInTheDocument();
  });
});
