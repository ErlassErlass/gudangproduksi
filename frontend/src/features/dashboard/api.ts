import { useQuery } from '@tanstack/react-query';
import { http } from '@/api/client';
import type { ResourceResponse } from '@/api/types';

export interface DashboardSummary {
  generated_at: string;
  metrics: {
    key: string;
    label: string;
    value: number | null;
    hint: string | null;
  }[];
  recent_activities: {
    id: number;
    action: string;
    module: string;
    user: string | null;
    created_at: string;
  }[];
}

export function useDashboardSummary() {
  return useQuery({
    queryKey: ['dashboard', 'summary'],
    queryFn: async () => {
      const { data } = await http.get<ResourceResponse<DashboardSummary>>('/dashboard/summary');
      return data.data;
    },
    refetchInterval: 60_000,
  });
}
