import { useCallback, useState } from 'react';
import type { ListParams } from '@/api/types';

/** Shared list state (search, sort, page, filters) for server-driven tables. */
export function useListParams(initial: ListParams = {}) {
  const [params, setParams] = useState<ListParams>({ page: 1, per_page: 15, ...initial });

  const setSearch = useCallback((search: string) => setParams((p) => ({ ...p, search, page: 1 })), []);
  const setSort = useCallback((sort: string) => setParams((p) => ({ ...p, sort, page: 1 })), []);
  const setPage = useCallback((page: number) => setParams((p) => ({ ...p, page })), []);
  const setFilter = useCallback(
    (key: string, value: string | number | boolean | undefined) =>
      setParams((p) => ({ ...p, [key]: value === '' ? undefined : value, page: 1 })),
    [],
  );

  return { params, setSearch, setSort, setPage, setFilter };
}
