import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../lib/api';

const FIELD_CLASS =
  'w-full rounded-md border border-bay-200 bg-white px-3 py-2 text-sm focus:border-signal focus:outline-none focus:ring-1 focus:ring-signal';

export default function LeadForm() {
  const navigate = useNavigate();
  const [cars, setCars] = useState([]);
  const [form, setForm] = useState({ name: '', phone: '', car_id: '', interest_summary: '' });
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    // Only offer cars actually available to sell, so this stays consistent
    // with what the bot itself would be able to talk about.
    api.get('/api/cars', { params: { status: 'ready', per_page: 100 } }).then(({ data }) => {
      setCars(data.data);
    });
  }, []);

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    try {
      const { data } = await api.post('/api/leads', {
        ...form,
        car_id: form.car_id || null,
      });
      navigate(`/leads/${data.data.id}`);
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors);
      }
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="mx-auto max-w-lg p-8">
      <h1 className="mb-1 text-xl font-semibold text-bay-900">Add a lead manually</h1>
      <p className="mb-6 text-sm text-bay-600">
        For walk-ins, phone calls, or anyone who never went through WhatsApp. This assigns the lead to you and marks it as staff-handled.
      </p>

      <form onSubmit={handleSubmit} className="space-y-5 rounded-lg border border-bay-200 bg-white p-6">
        <Field label="Name" error={errors.name}>
          <input className={FIELD_CLASS} value={form.name} onChange={(e) => set('name', e.target.value)} required />
        </Field>

        <Field label="Phone number" error={errors.phone} hint="Include country code if you plan to WhatsApp them later, e.g. 62812xxxxxxx">
          <input className={FIELD_CLASS} value={form.phone} onChange={(e) => set('phone', e.target.value)} required />
        </Field>

        <Field label="Interested in (optional)">
          <select className={FIELD_CLASS} value={form.car_id} onChange={(e) => set('car_id', e.target.value)}>
            <option value="">— No specific car yet —</option>
            {cars.map((car) => (
              <option key={car.id} value={car.id}>
                {car.brand} {car.model} {car.year} · {car.stock_number}
              </option>
            ))}
          </select>
        </Field>

        <Field label="Notes (optional)">
          <textarea
            className={FIELD_CLASS}
            rows={3}
            value={form.interest_summary}
            onChange={(e) => set('interest_summary', e.target.value)}
            placeholder="What are they looking for, budget, timeline, anything worth remembering."
          />
        </Field>

        <div className="flex justify-end gap-3 pt-2">
          <button type="button" onClick={() => navigate('/leads')} className="px-4 py-2 text-sm text-bay-600 hover:text-bay-900">
            Cancel
          </button>
          <button type="submit" disabled={saving} className="rounded-md bg-signal px-4 py-2 text-sm font-semibold text-white hover:bg-signal-dim disabled:opacity-50">
            {saving ? 'Adding…' : 'Add lead'}
          </button>
        </div>
      </form>
    </div>
  );
}

function Field({ label, error, hint, children }) {
  return (
    <div>
      <label className="mb-1 block text-xs font-medium text-bay-600">{label}</label>
      {children}
      {hint && <p className="mt-1 text-xs text-bay-400">{hint}</p>}
      {error && <p className="mt-1 text-xs text-signal">{error[0]}</p>}
    </div>
  );
}
