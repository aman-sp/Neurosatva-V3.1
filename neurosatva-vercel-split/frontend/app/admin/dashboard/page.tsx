'use client';

import { useEffect, useState } from 'react';

import { apiMe } from '@/lib/api';

export default function AdminDashboardPage() {
  const [status, setStatus] = useState('Loading session...');
  const [user, setUser] = useState<Record<string, unknown> | null>(null);

  useEffect(() => {
    let mounted = true;

    apiMe().then((response) => {
      if (!mounted) {
        return;
      }

      if (response.authenticated) {
        setUser(response.user ?? null);
        setStatus('Authenticated');
        return;
      }

      setStatus('Not signed in');
    });

    return () => {
      mounted = false;
    };
  }, []);

  return (
    <main className="shell">
      <section className="hero-card">
        <div className="hero-copy">
          <p className="eyebrow">Admin dashboard</p>
          <h1>Frontend shell connected to backend session state.</h1>
          <p className="lede">{status}</p>
          <pre className="status-panel">{JSON.stringify(user, null, 2)}</pre>
        </div>
        <aside className="info-panel">
          <h2>Next migration step</h2>
          <ul>
            <li>Replace PHP-rendered tables with API-driven data.</li>
            <li>Move registration approvals here first.</li>
            <li>Keep the backend as the source of truth.</li>
          </ul>
        </aside>
      </section>
    </main>
  );
}
