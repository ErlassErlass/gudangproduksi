export interface Role {
  id: number;
  code: string;
  name: string;
  description: string | null;
  is_system: boolean;
  users_count?: number;
  permissions?: Permission[];
  permission_codes?: string[];
}

export interface Permission {
  id: number;
  code: string;
  name: string;
  module: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  is_active: boolean;
  last_login_at: string | null;
  created_at: string | null;
  roles: Pick<Role, 'id' | 'code' | 'name'>[];
}

export interface AuthUser extends User {
  permissions: string[];
}

export interface AuditLog {
  id: number;
  action: string;
  entity_type: string | null;
  entity_id: number | null;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  ip_address: string | null;
  user: { id: number; name: string } | null;
  created_at: string;
}
