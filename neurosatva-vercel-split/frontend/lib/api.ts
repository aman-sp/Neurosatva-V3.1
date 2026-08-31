const apiBaseUrl = (process.env.NEXT_PUBLIC_API_BASE_URL || 'https://api.yourdomain.com').replace(/\/$/, '');

export async function apiLogin(email: string, password: string) {
  const response = await fetch(`${apiBaseUrl}/api/auth/login`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({ email, password })
  });

  return response.json();
}

export async function apiMe() {
  const response = await fetch(`${apiBaseUrl}/api/auth/me`, {
    credentials: 'include'
  });

  return response.json();
}