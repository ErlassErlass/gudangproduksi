import { http } from '@/api/client';
import type { ResourceResponse } from '@/api/types';
import type { AuthUser } from '@/types/models';

export interface LoginPayload {
  email: string;
  password: string;
  device_name?: string;
}

export interface UpdateProfilePayload {
  email?: string;
  name?: string;
  current_password?: string;
  password?: string;
  password_confirmation?: string;
}

export interface LoginResult {
  token: string;
  token_type: 'Bearer';
  user: AuthUser;
}

export const authApi = {
  async login(payload: LoginPayload): Promise<LoginResult> {
    const { data } = await http.post<ResourceResponse<LoginResult>>('/auth/login', {
      device_name: 'web',
      ...payload,
    });
    return data.data;
  },

  async me(): Promise<AuthUser> {
    const { data } = await http.get<ResourceResponse<AuthUser>>('/auth/me');
    return data.data;
  },

  async updateProfile(payload: UpdateProfilePayload): Promise<AuthUser> {
    const { data } = await http.patch<ResourceResponse<AuthUser>>('/auth/profile', payload);
    return data.data;
  },

  async logout(): Promise<void> {
    await http.post('/auth/logout');
  },
};
