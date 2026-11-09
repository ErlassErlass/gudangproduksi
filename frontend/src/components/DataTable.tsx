import type { ReactNode } from 'react';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight } from 'lucide-react';
import type { PaginatedResponse } from '@/api/types';
import { formatNumber } from '@/lib/format';
import { Button } from './Button';
import { EmptyState, ErrorState, LoadingState } from './States';

export interface Column<T> {
  key: string;
  header: string;
  render: (row: T) => ReactNode;
  /** Server-side sort field. When set the header becomes a sort toggle. */
  sortField?: string;
  width?: string;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  rowKey: (row: T) => string | number;
  data: PaginatedResponse<T> | undefined;
  isLoading: boolean;
  error: unknown;
  onRetry?: () => void;
  sort?: string;
  onSortChange?: (sort: string) => void;
  onPageChange?: (page: number) => void;
  emptyTitle?: string;
  emptyDescription?: string;
}

/**
 * Server-driven table: pagination, sorting and filtering are always delegated to the API
 * (agent_docs/AGENT_INSTRUCTIONS.md §9). `sort` uses the `field` / `-field` convention.
 */
export function DataTable<T>({
  columns,
  rowKey,
  data,
  isLoading,
  error,
  onRetry,
  sort,
  onSortChange,
  onPageChange,
  emptyTitle,
  emptyDescription,
}: DataTableProps<T>) {
  if (isLoading && !data) return <LoadingState />;
  if (error) return <ErrorState error={error} onRetry={onRetry} />;
  if (!data || data.data.length === 0) {
    return <EmptyState title={emptyTitle} description={emptyDescription} />;
  }

  const toggleSort = (field: string) => {
    if (!onSortChange) return;
    onSortChange(sort === field ? `-${field}` : field);
  };

  const sortIcon = (field: string) => {
    if (sort === field) return <ArrowUp size={12} />;
    if (sort === `-${field}`) return <ArrowDown size={12} />;
    return <ArrowUpDown size={12} opacity={0.5} />;
  };

  const ariaSort = (field?: string): 'ascending' | 'descending' | 'none' | undefined => {
    if (!field) return undefined;
    if (sort === field) return 'ascending';
    if (sort === `-${field}`) return 'descending';
    return 'none';
  };

  const { meta } = data;

  return (
    <>
      <div className="table-wrap">
        <table className="table">
          <thead>
            <tr>
              {columns.map((col) => (
                <th key={col.key} style={{ width: col.width }} aria-sort={ariaSort(col.sortField)}>
                  {col.sortField && onSortChange ? (
                    <button type="button" onClick={() => toggleSort(col.sortField!)}>
                      {col.header} {sortIcon(col.sortField)}
                    </button>
                  ) : (
                    col.header
                  )}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {data.data.map((row) => (
              <tr key={rowKey(row)}>
                {columns.map((col) => (
                  <td key={col.key}>{col.render(row)}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="pagination">
        <span>
          Menampilkan {formatNumber(meta.from ?? 0)}–{formatNumber(meta.to ?? 0)} dari {formatNumber(meta.total)} data
        </span>
        <div className="pagination__controls">
          <Button
            iconOnly
            aria-label="Halaman sebelumnya"
            icon={<ChevronLeft size={16} />}
            disabled={meta.current_page <= 1}
            onClick={() => onPageChange?.(meta.current_page - 1)}
          />
          <span style={{ alignSelf: 'center', padding: '0 8px' }}>
            {meta.current_page} / {meta.last_page}
          </span>
          <Button
            iconOnly
            aria-label="Halaman berikutnya"
            icon={<ChevronRight size={16} />}
            disabled={meta.current_page >= meta.last_page}
            onClick={() => onPageChange?.(meta.current_page + 1)}
          />
        </div>
      </div>
    </>
  );
}
