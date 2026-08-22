import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../lib/api';

const EMPTY = {
  stock_number: '', brand: '', model: '', variant: '', year: new Date().getFullYear(),
  price: '', mileage: '', transmission: '', fuel_type: '', color: '', plate_region: '',
  condition_notes: '', status: 'ready',
};

const FIELD_CLASS =
  'w-full rounded-md border border-bay-200 bg-white px-3 py-2 text-sm focus:border-signal focus:outline-none focus:ring-1 focus:ring-signal';

export default function CarForm() {
  const { id } = useParams();
  const isEdit = id !== undefined && id !== 'new';
  const navigate = useNavigate();

  const [form, setForm] = useState(EMPTY);
  const [photos, setPhotos] = useState([]);
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (isEdit) {
      api.get(`/api/cars/${id}`).then(({ data }) => {
        setForm({ ...EMPTY, ...data.data });
        setPhotos(data.data.photos || []);
      });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    try {
      if (isEdit) {
        await api.put(`/api/cars/${id}`, form);
      } else {
        const { data } = await api.post('/api/cars', form);
        navigate(`/inventory/${data.data.id}`, { replace: true });
        return;
      }
      navigate('/inventory');
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors);
      }
    } finally {
      setSaving(false);
    }
  }

  async function handlePhotoUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    const body = new FormData();
    body.append('photo', file);
    const { data } = await api.post(`/api/cars/${id}/photos`, body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    setPhotos((p) => [...p, data]);
    e.target.value = '';
  }

  async function handlePhotoDelete(photoId) {
    await api.delete(`/api/cars/${id}/photos/${photoId}`);
    setPhotos((p) => p.filter((ph) => ph.id !== photoId));
  }

  return (
    <div className="mx-auto max-w-2xl p-8">
      <h1 className="mb-1 text-xl font-semibold text-bay-900">
        {isEdit ? `Edit ${form.brand} ${form.model}` : 'Add a car'}
      </h1>
      <p className="mb-6 text-sm text-bay-600">
        Everything here is what the bot will read from — write condition notes as if a customer will see them.
      </p>

      <form onSubmit={handleSubmit} className="space-y-5 rounded-lg border border-bay-200 bg-white p-6">
        <div className="grid grid-cols-2 gap-4">
          <Field label="Stock number" error={errors.stock_number}>
            <input className={FIELD_CLASS} value={form.stock_number} onChange={(e) => set('stock_number', e.target.value)} placeholder="STK-0231" required />
          </Field>
          <Field label="Status">
            <select className={FIELD_CLASS} value={form.status} onChange={(e) => set('status', e.target.value)}>
              <option value="ready">Ready</option>
              <option value="booked">Booked</option>
              <option value="sold">Sold</option>
            </select>
          </Field>
        </div>

        <div className="grid grid-cols-3 gap-4">
          <Field label="Brand" error={errors.brand}>
            <input className={FIELD_CLASS} value={form.brand} onChange={(e) => set('brand', e.target.value)} required />
          </Field>
          <Field label="Model" error={errors.model}>
            <input className={FIELD_CLASS} value={form.model} onChange={(e) => set('model', e.target.value)} required />
          </Field>
          <Field label="Variant">
            <input className={FIELD_CLASS} value={form.variant || ''} onChange={(e) => set('variant', e.target.value)} />
          </Field>
        </div>

        <div className="grid grid-cols-3 gap-4">
          <Field label="Year" error={errors.year}>
            <input type="number" className={FIELD_CLASS} value={form.year} onChange={(e) => set('year', e.target.value)} required />
          </Field>
          <Field label="Price (IDR)" error={errors.price}>
            <input type="number" className={FIELD_CLASS} value={form.price} onChange={(e) => set('price', e.target.value)} required />
          </Field>
          <Field label="Mileage (km)">
            <input type="number" className={FIELD_CLASS} value={form.mileage || ''} onChange={(e) => set('mileage', e.target.value)} />
          </Field>
        </div>

        <div className="grid grid-cols-3 gap-4">
          <Field label="Transmission">
            <select className={FIELD_CLASS} value={form.transmission || ''} onChange={(e) => set('transmission', e.target.value)}>
              <option value="">—</option>
              <option value="manual">Manual</option>
              <option value="automatic">Automatic</option>
              <option value="cvt">CVT</option>
            </select>
          </Field>
          <Field label="Fuel type">
            <select className={FIELD_CLASS} value={form.fuel_type || ''} onChange={(e) => set('fuel_type', e.target.value)}>
              <option value="">—</option>
              <option value="petrol">Petrol</option>
              <option value="diesel">Diesel</option>
              <option value="hybrid">Hybrid</option>
              <option value="electric">Electric</option>
            </select>
          </Field>
          <Field label="Color">
            <input className={FIELD_CLASS} value={form.color || ''} onChange={(e) => set('color', e.target.value)} />
          </Field>
        </div>

        <Field label="Condition notes" hint="This is what grounds the bot's answers — be specific and honest.">
          <textarea
            className={FIELD_CLASS}
            rows={4}
            value={form.condition_notes || ''}
            onChange={(e) => set('condition_notes', e.target.value)}
            placeholder="e.g. Minor scratch on rear bumper, service history complete, one owner, tires replaced 2025."
          />
        </Field>

        {isEdit && (
          <Field label="Photos">
            <div className="mb-2 flex flex-wrap gap-2">
              {photos.map((p) => (
                <div key={p.id} className="group relative h-20 w-20 overflow-hidden rounded-md border border-bay-200">
                  <img src={p.url} alt="" className="h-full w-full object-cover" />
                  <button
                    type="button"
                    onClick={() => handlePhotoDelete(p.id)}
                    className="absolute inset-0 hidden items-center justify-center bg-black/50 text-xs text-white group-hover:flex"
                  >
                    Remove
                  </button>
                </div>
              ))}
            </div>
            <input type="file" accept="image/*" onChange={handlePhotoUpload} className="text-sm" />
          </Field>
        )}

        <div className="flex justify-end gap-3 pt-2">
          <button type="button" onClick={() => navigate('/inventory')} className="px-4 py-2 text-sm text-bay-600 hover:text-bay-900">
            Cancel
          </button>
          <button type="submit" disabled={saving} className="rounded-md bg-signal px-4 py-2 text-sm font-semibold text-white hover:bg-signal-dim disabled:opacity-50">
            {saving ? 'Saving…' : 'Save car'}
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
