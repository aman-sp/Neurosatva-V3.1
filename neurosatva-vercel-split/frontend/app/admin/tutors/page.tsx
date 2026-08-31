'use client';

import { useEffect, useState } from 'react';

import { deleteTutor, fetchTutors, updateTutor } from '@/lib/admin-api';

export default function TutorsPage() {
  const [tutors, setTutors] = useState<Array<Record<string, unknown>>>([]);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');
  const [editById, setEditById] = useState<Record<string, Record<string, string>>>({});

  async function loadTutors() {
    setLoading(true);
    const data = await fetchTutors(search, status);
    setTutors(data);
    setLoading(false);
  }

  useEffect(() => {
    void loadTutors();
  }, [status]);

  async function handleUpdate(id: number) {
    const draft = editById[String(id)] || {};
    const result = await updateTutor({
      id,
      name: draft.name || '',
      email: draft.email || '',
      phone: draft.phone || '',
      status: (draft.status as 'active' | 'deactivated') || 'active',
      password: draft.password || ''
    });

    if (result.success) {
      setMessage('Tutor updated');
      await loadTutors();
      return;
    }

    setMessage(result.error || 'Unable to update tutor');
  }

  async function handleDelete(id: number) {
    const result = await deleteTutor(id);
    if (result.success) {
      setMessage('Tutor deleted');
      await loadTutors();
      return;
    }

    setMessage(result.error || 'Unable to delete tutor');
  }

  return (
    <main className="shell">
      <section className="hero-card">
        <div className="hero-copy">
          <p className="eyebrow">Tutor management</p>
          <h1>Search, update, and remove tutors.</h1>
          <p className="lede">{message}</p>
          <div className="filters-row">
            <input className="mini-input" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search tutors" />
            <select className="mini-input" value={status} onChange={(event) => setStatus(event.target.value)}>
              <option value="">All statuses</option>
              <option value="active">Active</option>
              <option value="deactivated">Deactivated</option>
            </select>
            <button type="button" className="button primary" onClick={() => void loadTutors()}>
              Search
            </button>
          </div>
        </div>
        <aside className="info-panel">
          <h2>Management notes</h2>
          <ul>
            <li>Tutor list is still served by the PHP backend.</li>
            <li>Inline edits keep the first migration simple.</li>
            <li>You can split the form into a drawer later if you want.</li>
          </ul>
        </aside>
      </section>

      <section className="hero-card" style={{ marginTop: 24 }}>
        <div className="info-panel" style={{ gridColumn: '1 / -1' }}>
          {loading ? (
            <p className="lede">Loading tutors...</p>
          ) : tutors.length === 0 ? (
            <p className="lede">No tutors found.</p>
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
                  {tutors.map((tutor) => {
                    const id = Number(tutor.id);
                    const draft = editById[String(id)] || {
                      name: String(tutor.name || ''),
                      email: String(tutor.email || ''),
                      phone: String(tutor.phone || ''),
                      status: String(tutor.status || 'active'),
                      password: ''
                    };

                    return (
                      <tr key={id}>
                        <td>
                          <input
                            className="mini-input"
                            value={draft.name}
                            onChange={(event) =>
                              setEditById((current) => ({
                                ...current,
                                [String(id)]: { ...draft, name: event.target.value }
                              }))
                            }
                          />
                        </td>
                        <td>
                          <input
                            className="mini-input"
                            value={draft.email}
                            onChange={(event) =>
                              setEditById((current) => ({
                                ...current,
                                [String(id)]: { ...draft, email: event.target.value }
                              }))
                            }
                          />
                        </td>
                        <td>
                          <input
                            className="mini-input"
                            value={draft.phone}
                            onChange={(event) =>
                              setEditById((current) => ({
                                ...current,
                                [String(id)]: { ...draft, phone: event.target.value }
                              }))
                            }
                          />
                        </td>
                        <td>
                          <select
                            className="mini-input"
                            value={draft.status}
                            onChange={(event) =>
                              setEditById((current) => ({
                                ...current,
                                [String(id)]: { ...draft, status: event.target.value }
                              }))
                            }
                          >
                            <option value="active">Active</option>
                            <option value="deactivated">Deactivated</option>
                          </select>
                          <input
                            className="mini-input"
                            type="password"
                            value={draft.password}
                            onChange={(event) =>
                              setEditById((current) => ({
                                ...current,
                                [String(id)]: { ...draft, password: event.target.value }
                              }))
                            }
                            placeholder="New password"
                          />
                        </td>
                        <td>
                          <div className="action-stack">
                            <button type="button" className="button primary" onClick={() => handleUpdate(id)}>
                              Update
                            </button>
                            <button type="button" className="button" onClick={() => handleDelete(id)}>
                              Delete
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>
    </main>
  );
}
