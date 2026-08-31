const apiBaseUrl = (process.env.NEXT_PUBLIC_API_BASE_URL || 'https://api.yourdomain.com').replace(/\/$/, '');

async function requestJson(path: string, options: RequestInit = {}) {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    credentials: 'include',
    ...options,
    headers: {
      ...(options.headers || {}),
      ...(options.body ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {})
    }
  });

  const data = await response.json();
  return { response, data };
}

export function getAdminApiBaseUrl() {
  return apiBaseUrl;
}

export async function fetchRegistrationRequests(status = '') {
  const query = status ? `?status=${encodeURIComponent(status)}` : '';
  const { data } = await requestJson(`/api/admin/registration-requests${query}`);
  return data as Array<Record<string, unknown>>;
}

export async function approveRegistrationRequest(id: number) {
  const { data } = await requestJson('/api/admin/registration-requests/approve', {
    method: 'POST',
    body: new URLSearchParams({ id: String(id) })
  });

  return data as { success?: boolean; tutor_id?: number; user_id?: string; initial_password?: string; error?: string };
}

export async function rejectRegistrationRequest(id: number, adminRemarks = '') {
  const { data } = await requestJson('/api/admin/registration-requests/reject', {
    method: 'POST',
    body: new URLSearchParams({ id: String(id), admin_remarks: adminRemarks })
  });

  return data as { success?: boolean; error?: string };
}

export async function fetchTutors(search = '', status = '') {
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  const query = params.toString() ? `?${params.toString()}` : '';
  const { data } = await requestJson(`/api/admin/tutors${query}`);
  return data as Array<Record<string, unknown>>;
}

export async function updateTutor(payload: {
  id: number;
  name: string;
  email: string;
  phone?: string;
  status: 'active' | 'deactivated';
  password?: string;
}) {
  const { data } = await requestJson('/api/admin/tutors/update', {
    method: 'POST',
    body: new URLSearchParams({
      id: String(payload.id),
      name: payload.name,
      email: payload.email,
      phone: payload.phone || '',
      status: payload.status,
      password: payload.password || ''
    })
  });

  return data as { success?: boolean; error?: string };
}

export async function deleteTutor(id: number) {
  const { data } = await requestJson('/api/admin/tutors/delete', {
    method: 'POST',
    body: new URLSearchParams({ id: String(id) })
  });

  return data as { success?: boolean; error?: string };
}
