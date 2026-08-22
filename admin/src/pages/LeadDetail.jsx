import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../lib/api';
import { formatIDR, LEAD_STATUS_LABEL } from '../lib/format';
import StatusBadge from '../components/StatusBadge';

export default function LeadDetail() {
  const { id } = useParams();
  const [lead, setLead] = useState(null);
  const [reply, setReply] = useState('');
  const [sending, setSending] = useState(false);

  useEffect(() => { load(); }, [id]); // eslint-disable-line react-hooks/exhaustive-deps

  async function load() {
    const { data } = await api.get(`/api/leads/${id}`);
    setLead(data.data);
  }

  async function handleTakeover() {
    await api.put(`/api/leads/${id}`, { status: 'human_handling' });
    load();
  }

  async function handleStatusChange(status) {
    await api.put(`/api/leads/${id}`, { status });
    load();
  }

  async function handleSend(e) {
    e.preventDefault();
    if (!reply.trim()) return;
    setSending(true);
    await api.post(`/api/leads/${id}/conversations`, { message: reply });
    setReply('');
    await load();
    setSending(false);
  }

  if (!lead) return <div className="p-8 text-bay-400">Loading…</div>;

  return (
    <div className="mx-auto flex max-w-4xl gap-6 p-8">
      <div className="flex-1">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold text-bay-900">{lead.name || lead.phone}</h1>
            <div className="font-mono text-xs text-bay-400">{lead.phone}</div>
          </div>
          <StatusBadge status={lead.status} label={LEAD_STATUS_LABEL[lead.status]} />
        </div>

        <div className="mb-4 h-[420px] overflow-y-auto rounded-lg border border-bay-200 bg-white p-4">
          {lead.conversations?.length === 0 && (
            <p className="text-sm text-bay-400">No messages logged yet.</p>
          )}
          {lead.conversations?.map((c) => (
            <div key={c.id} className={`mb-3 flex ${c.sender === 'customer' ? 'justify-start' : 'justify-end'}`}>
              <div
                className={`max-w-[75%] rounded-lg px-3 py-2 text-sm ${
                  c.sender === 'customer'
                    ? 'bg-bay-100 text-bay-900'
                    : c.sender === 'bot'
                    ? 'bg-confirm/10 text-bay-900'
                    : 'bg-signal/15 text-bay-900'
                }`}
              >
                <div className="mb-0.5 text-[10px] uppercase tracking-wide text-bay-400">
                  {c.sender === 'human' && c.user ? c.user.name : c.sender}
                </div>
                {c.message}
              </div>
            </div>
          ))}
        </div>

        <form onSubmit={handleSend} className="flex gap-2">
          <input
            value={reply}
            onChange={(e) => setReply(e.target.value)}
            placeholder="Reply as staff (sends once the WhatsApp integration is wired up)…"
            className="flex-1 rounded-md border border-bay-200 bg-white px-3 py-2 text-sm focus:border-signal focus:outline-none focus:ring-1 focus:ring-signal"
          />
          <button
            type="submit"
            disabled={sending}
            className="rounded-md bg-signal px-4 py-2 text-sm font-semibold text-white hover:bg-signal-dim disabled:opacity-50"
          >
            Send
          </button>
        </form>
      </div>

      <aside className="w-64 shrink-0">
        <div className="mb-4 rounded-lg border border-bay-200 bg-white p-4">
          <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-bay-500">Interested in</h2>
          {lead.car ? (
            <div>
              <div className="font-medium text-bay-900">{lead.car.brand} {lead.car.model} {lead.car.year}</div>
              <div className="font-mono text-sm text-bay-600">{formatIDR(lead.car.price)}</div>
            </div>
          ) : (
            <p className="text-sm text-bay-400">Not yet linked to a car.</p>
          )}
        </div>

        <div className="mb-4 rounded-lg border border-bay-200 bg-white p-4">
          <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-bay-500">Summary</h2>
          <p className="text-sm text-bay-700">{lead.interest_summary || 'No summary yet.'}</p>
        </div>

        <div className="rounded-lg border border-bay-200 bg-white p-4">
          <h2 className="mb-3 text-xs font-semibold uppercase tracking-wide text-bay-500">Actions</h2>
          {lead.status === 'needs_handoff' && (
            <button
              onClick={handleTakeover}
              className="mb-2 w-full rounded-md bg-signal px-3 py-2 text-sm font-semibold text-white hover:bg-signal-dim"
            >
              Take over conversation
            </button>
          )}
          <select
            value={lead.status}
            onChange={(e) => handleStatusChange(e.target.value)}
            className="w-full rounded-md border border-bay-200 bg-white px-3 py-2 text-sm"
          >
            {Object.entries(LEAD_STATUS_LABEL).map(([val, label]) => (
              <option key={val} value={val}>{label}</option>
            ))}
          </select>
        </div>
      </aside>
    </div>
  );
}
