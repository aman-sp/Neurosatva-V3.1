'use client';

import { FormEvent, useState } from 'react';
import { useRouter } from 'next/navigation';

const backendUrl = (process.env.NEXT_PUBLIC_API_BASE_URL || 'https://api.yourdomain.com').replace(/\/$/, '');

export default function AdminLoginPage() {
  const [message, setMessage] = useState('');
  const router = useRouter();

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage('Signing in...');

    const formData = new FormData(event.currentTarget);

    const response = await fetch(`${backendUrl}/api/auth/login`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
        email: String(formData.get('email') || ''),
        password: String(formData.get('password') || '')
      })
    });

    const result = await response.json();
    if (response.ok) {
      setMessage(`Signed in as ${result.user?.role}`);
      router.push('/admin/dashboard');
      return;
    }

    setMessage(result.error || 'Login failed');
  }

  return (
    <main className="shell">
      <section className="hero-card">
        <div className="hero-copy">
          <p className="eyebrow">Admin access</p>
          <h1>Sign in to the admin portal.</h1>
          <p className="lede">
            This page is the Vercel frontend shell. Connect the form action to the PHP
            backend login endpoint when the API contract is finalized.
          </p>

          <form
            className="login-form"
            onSubmit={handleSubmit}
          >
            <label>
              Email
              <input name="email" type="email" placeholder="admin@example.com" />
            </label>
            <label>
              Password
              <input name="password" type="password" placeholder="Your password" />
            </label>
            <button className="button primary" type="submit">
              Continue
            </button>
          </form>
          <p className="lede">{message}</p>
        </div>

        <aside className="info-panel">
          <h2>Backend target</h2>
          <ul>
            <li>{backendUrl}</li>
            <li>PHP sessions or token auth can be wired later.</li>
            <li>This route lives on Vercel, not the PHP host.</li>
          </ul>
        </aside>
      </section>
    </main>
  );
}
