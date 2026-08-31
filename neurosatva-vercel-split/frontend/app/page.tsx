const backendUrl = process.env.NEXT_PUBLIC_API_BASE_URL || 'https://api.yourdomain.com';

const highlights = [
  'Admin and tutor login screens first',
  'API-first frontend with a PHP backend',
  'Ready for Vercel deployment'
];

const routes = [
  { label: 'Admin Login', href: '/admin/login' },
  { label: 'Tutor Login', href: '/tutor/login' },
  { label: 'Registration Approvals', href: '/admin/registration-requests' },
  { label: 'Tutor Management', href: '/admin/tutors' }
];

export default function HomePage() {
  return (
    <main className="shell">
      <section className="hero-card">
        <div className="hero-copy">
          <p className="eyebrow">Neurosatva split architecture</p>
          <h1>Frontend on Vercel. Backend stays on PHP.</h1>
          <p className="lede">
            This starter is the Vercel side of the deployment. It is designed to talk to
            the existing PHP/MySQL backend through a base API URL.
          </p>

          <div className="button-row">
            {routes.map((route) => (
              <a key={route.label} className="button primary" href={route.href}>
                {route.label}
              </a>
            ))}
          </div>

          <div className="status-panel">
            <span>Backend API</span>
            <strong>{backendUrl}</strong>
          </div>
        </div>

        <aside className="info-panel">
          <h2>What moves here first</h2>
          <ul>
            {highlights.map((item) => (
              <li key={item}>{item}</li>
            ))}
          </ul>
        </aside>
      </section>
    </main>
  );
}
