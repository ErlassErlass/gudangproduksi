const TIMEZONE = 'Asia/Jakarta';

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
  timeZone: TIMEZONE,
});

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  timeZone: TIMEZONE,
});

const longDateFormatter = new Intl.DateTimeFormat('id-ID', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
  timeZone: TIMEZONE,
});

const rupiahFormatter = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
});

const numberFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 });

/** Placeholder rendered for values that are unavailable (design brief §11.3). */
export const EMPTY_VALUE = '—';

function toDate(value: string | Date | null | undefined): Date | null {
  if (!value) return null;
  const date = value instanceof Date ? value : new Date(value);
  return Number.isNaN(date.getTime()) ? null : date;
}

export function formatDate(value: string | Date | null | undefined): string {
  const date = toDate(value);
  return date ? dateFormatter.format(date) : EMPTY_VALUE;
}

export function formatDateTime(value: string | Date | null | undefined): string {
  const date = toDate(value);
  return date ? dateTimeFormatter.format(date) : EMPTY_VALUE;
}

export function formatLongDate(value: string | Date = new Date()): string {
  const date = toDate(value);
  return date ? longDateFormatter.format(date) : EMPTY_VALUE;
}

/** Money values arrive from the API as decimal strings; never coerce through float for arithmetic. */
export function formatRupiah(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') return EMPTY_VALUE;
  const num = typeof value === 'number' ? value : Number(value);
  return Number.isFinite(num) ? rupiahFormatter.format(num) : EMPTY_VALUE;
}

export function formatNumber(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') return EMPTY_VALUE;
  const num = typeof value === 'number' ? value : Number(value);
  return Number.isFinite(num) ? numberFormatter.format(num) : EMPTY_VALUE;
}

export function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}
