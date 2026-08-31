'use client';

import { useEffect, useMemo, useState } from 'react';

import {
  approveRegistrationRequest,
  fetchRegistrationRequests,
  rejectRegistrationRequest
} from '@/lib/admin-api';

export default function RegistrationRequestsPage() {
  const [requests, setRequests] = useState<Array<Record<string, unknown>>>([]);
  const [status, setStatus] = useState('Pending');
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');
  const [remarksById, setRemarksById] = useState<Record<string, string>>({});

  async function loadRequests() {
    setLoading(true);
    const data = await fetchRegistrationRequests(status);
    setRequests(data);
    setLoading(false);
  }

  useEffect(() => {
    void loadRequests();
  }, [status]);

  const pendingCount = useMemo(() => requests.filter((request) => request.status === 'Pending').length, [requests]);

  async function handleApprove(id: number) {
    setMessage('Approving...');
    const result = await approveRegistrationRequest(id);
    if (result.success) {
      setMessage(`Approved. Tutor ID: ${result.user_id}`);
      await loadRequests();
      return;
    }

    setMessage(result.error || 'Unable to approve request');
  }

  async function handleReject(id: number) {
    setMessage('Rejecting...');
    const result = await rejectRegistrationRequest(id, remarksById[String(id)] || '');
    if (result.success) {
      setMessage('Request rejected');
      await loadRequests();
      return;
    }

    setMessage(result.error || 'Unable to reject request');
  }

  return (
    <main className="shell">
      <section className="hero-card">
        <div className="hero-copy">
          <p className="eyebrow">Registration approvals</p>
          <h1>Review tutor requests.</h1>
          <p className="lede">Pending requests: {pendingCount}</p>
          <div className="button-row">
            {['Pending', 'Approved', 'Rejected', ''].map((value) => (
              <button
                key={value || 'All'}
                type="button"
                className="button primary"
                onClick={() => setStatus(value)}
              >
                {value || 'All'}
              </button>
            ))}
          </div>
          <p className="lede">{message}</p>
        </div>

        <aside className="info-panel">
          <h2>Approval flow</h2>
          <ul>
            <li>Approve to create the tutor account and assign a user ID.</li>
            <li>Reject with remarks when the request should not proceed.</li>
            <li>The backend remains the source of truth.</li>
          </ul>
        </aside>
      </section>

      <section className="hero-card" style={{ marginTop: 24 }}>
        <div className="info-panel" style={{ gridColumn: '1 / -1' }}>
          {loading ? (
            <p className="lede">Loading requests...</p>
          ) : requests.length === 0 ? (
            <p className="lede">No requests found.</p>
          ) : (
            <div className="table-wrap">
              <table className="admin-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {requests.map((request) => (
                    <tr key={String(request.id)}>
                      <td>{String(request.full_name || '')}</td>
                      <td>{String(request.email || '')}</td>
                      <td>{String(request.phone || '')}</td>
                      <td>{String(request.status || '')}</td>
                      <td>
                        <div className="action-stack">
                          <button type="button" className="button primary" onClick={() => handleApprove(Number(request.id))}>
                            Approve
                          </button>
                          <input
                            className="mini-input"
                            placeholder="Rejection remarks"
                            value={remarksById[String(request.id)] || ''}
                            onChange={(event) =>
                              setRemarksById((current) => ({ ...current, [String(request.id)]: event.target.value }))
                            }
                          />
                          <button type="button" className="button" onClick={() => handleReject(Number(request.id))}>
                            Reject
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>
    </main>
  );
}
