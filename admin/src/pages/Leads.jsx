import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { LEAD_STATUS_LABEL } from '../lib/format';
import StatusBadge from '../components/StatusBadge';

export default function Leads() {
  const [leads, setLeads] = useState([]);
  const [status, setStatus] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => { load(); }, [status]); // eslint-disable-line react-hooks/exhaustive-deps

  async function load() {
    setLoading(true);
    const { data } = await api.get('/api/leads', { params: { status } });
    setLeads(data.data);
    setLoading(false);
  }

  return (
    <div className="p-8">
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-bay-900">Leads</h1>
          <p className="text-sm text-bay-600">Conversations needing a human float to the top.</p>
        </div>
        <Link
          to="/leads/new"
          className="rounded-md bg-signal px-4 py-2 text-sm font-semibold text-white hover:bg-signal-dim"
        >
          + Add lead
        </Link>
      </div>

      <div className="mb-4">
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="rounded-md border border-bay-200 bg-white px-3 py-2 text-sm focus:border-signal focus:outline-none focus:ring-1 focus:ring-signal"
        >
          <option value="">All statuses</option>
          {Object.entries(LEAD_STATUS_LABEL).map(([val, label]) => (
            <option key={val} value={val}>{label}</option>
          ))}
        </select>
      </div>

      <div className="overflow-hidden rounded-lg border border-bay-200 bg-white">
        <table className="w-full text-sm">
          <thead className="bg-bay-100 text-left text-xs uppercase tracking-wide text-bay-600">
            <tr>
              <th className="px-4 py-3">Customer</th>
              <th className="px-4 py-3">Interested in</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Assigned</th>
              <th className="px-4 py-3">Last message</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-bay-100">
            {loading && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-bay-400">Loading…</td></tr>
            )}
            {!loading && leads.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-bay-400">No leads yet — they'll appear once the bot is live.</td></tr>
            )}
            {leads.map((lead) => (
              <tr key={lead.id} className="hover:bg-bay-50">
                <td className="px-4 py-3">
                  <Link to={`/leads/${lead.id}`} className="font-medium text-bay-900 hover:text-signal">
                    {lead.name || lead.phone}
                  </Link>
                  <div className="font-mono text-xs text-bay-400">
                    {lead.phone}
                    {lead.source === 'manual' && (
                      <span className="ml-2 rounded bg-bay-100 px-1.5 py-0.5 text-bay-500">manual</span>
                    )}
                  </div>
                </td>
                <td className="px-4 py-3 text-bay-700">
                  {lead.car ? `${lead.car.brand} ${lead.car.model} ${lead.car.year}` : '—'}
                </td>
                <td className="px-4 py-3">
                  <StatusBadge status={lead.status} label={LEAD_STATUS_LABEL[lead.status]} />
                </td>
                <td className="px-4 py-3 text-bay-700">{lead.assignee?.name || '—'}</td>
                <td className="px-4 py-3 text-bay-500">
                  {lead.last_message_at ? new Date(lead.last_message_at).toLocaleString('id-ID') : '—'}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
