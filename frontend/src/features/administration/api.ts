import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { http } from '@/api/client';
import type { ListParams, PaginatedResponse, ResourceResponse } from '@/api/types';
import type { AuditLog, Permission, Role, User } from '@/types/models';

export interface UserPayload {
  name: string;
  email: string;
  password?: string;
  is_active: boolean;
  role_ids: number[];
}

export interface RolePayload {
  code?: string;
  name: string;
  description?: string | null;
  permission_codes: string[];
}

const keys = {
  users: (params: ListParams) => ['administration', 'users', params] as const,
  roles: (params: ListParams) => ['administration', 'roles', params] as const,
  permissions: ['administration', 'permissions'] as const,
  auditLogs: (params: ListParams) => ['administration', 'audit-logs', params] as const,
};

export function useUsers(params: ListParams) {
  return useQuery({
    queryKey: keys.users(params),
    queryFn: async () => (await http.get<PaginatedResponse<User>>('/users', { params })).data,
    placeholderData: keepPreviousData,
  });
}

export function useSaveUser() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, payload }: { id?: number; payload: UserPayload }) => {
      const res = id
        ? await http.put<ResourceResponse<User>>(`/users/${id}`, payload)
        : await http.post<ResourceResponse<User>>('/users', payload);
      return res.data.data;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ['administration', 'users'] }),
  });
}

export function useRoles(params: ListParams = { per_page: 100 }) {
  return useQuery({
    queryKey: keys.roles(params),
    queryFn: async () => (await http.get<PaginatedResponse<Role>>('/roles', { params })).data,
    placeholderData: keepPreviousData,
  });
}

export function useSaveRole() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, payload }: { id?: number; payload: RolePayload }) => {
      const res = id
        ? await http.put<ResourceResponse<Role>>(`/roles/${id}`, payload)
        : await http.post<ResourceResponse<Role>>('/roles', payload);
      return res.data.data;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ['administration', 'roles'] }),
  });
}

export function usePermissions() {
  return useQuery({
    queryKey: keys.permissions,
    queryFn: async () => (await http.get<ResourceResponse<Permission[]>>('/permissions')).data.data,
    staleTime: 5 * 60_000,
  });
}

export function useAuditLogs(params: ListParams) {
  return useQuery({
    queryKey: keys.auditLogs(params),
    queryFn: async () => (await http.get<PaginatedResponse<AuditLog>>('/audit-logs', { params })).data,
    placeholderData: keepPreviousData,
  });
}
