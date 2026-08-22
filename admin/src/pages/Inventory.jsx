import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { formatIDR, formatKm, CAR_STATUS_LABEL } from '../lib/format';
import StatusBadge from '../components/StatusBadge';

export default function Inventory() {
  const [cars, setCars] = useState([]);
  const [loading, setLoading] = useState(true);
  const [q, setQ] = useState('');
  const [status, setStatus] = useState('');

  useEffect(() => {
    const timeout = setTimeout(load, 250); // debounce search-as-you-type
    return () => clearTimeout(timeout);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, status]);

  async function load() {
    setLoading(true);
    const { data } = await api.get('/api/cars', { params: { q, status } });
    setCars(data.data);
    setLoading(false);
  }

  async function handleDelete(car) {
    if (!confirm(`Remove ${car.brand} ${car.model} (${car.stock_number}) from inventory?`)) return;
    await api.delete(`/api/cars/${car.id}`);
    load();
  }

  return (
    <div className="p-8">
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-bay-900">Inventory</h1>
          <p className="text-sm text-bay-600">Stock the bot is allowed to talk about.</p>
        </div>
        <Link
          to="/inventory/new"
          className="rounded-md bg-signal px-4 py-2 text-sm font-semibold text-white hover:bg-signal-dim"
        >
          + Add car
        </Link>
      </div>

      <div className="mb-4 flex gap-3">
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Search brand, model, or stock number…"
          className="w-72 rounded-md border border-bay-200 bg-white px-3 py-2 text-sm focus:border-signal focus:outline-none focus:ring-1 focus:ring-signal"
        />
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="rounded-md border border-bay-200 bg-white px-3 py-2 text-sm focus:border-signal focus:outline-none focus:ring-1 focus:ring-signal"
        >
          <option value="">All statuses</option>
          {Object.entries(CAR_STATUS_LABEL).map(([val, label]) => (
            <option key={val} value={val}>{label}</option>
          ))}
        </select>
      </div>

      <div className="overflow-hidden rounded-lg border border-bay-200 bg-white">
        <table className="w-full text-sm">
          <thead className="bg-bay-100 text-left text-xs uppercase tracking-wide text-bay-600">
            <tr>
              <th className="px-4 py-3">Stock #</th>
              <th className="px-4 py-3">Car</th>
              <th className="px-4 py-3">Year</th>
              <th className="px-4 py-3">Price</th>
              <th className="px-4 py-3">Mileage</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3" />
            </tr>
          </thead>
          <tbody className="divide-y divide-bay-100">
            {loading && (
              <tr><td colSpan={7} className="px-4 py-8 text-center text-bay-400">Loading…</td></tr>
            )}
            {!loading && cars.length === 0 && (
              <tr><td colSpan={7} className="px-4 py-8 text-center text-bay-400">No cars match. Add one to get started.</td></tr>
            )}
            {cars.map((car) => (
              <tr key={car.id} className="hover:bg-bay-50">
                <td className="px-4 py-3 font-mono text-xs text-bay-600">{car.stock_number}</td>
                <td className="px-4 py-3 font-medium text-bay-900">
                  {car.brand} {car.model} {car.variant && <span className="text-bay-400">{car.variant}</span>}
                </td>
                <td className="px-4 py-3 font-mono text-bay-700">{car.year}</td>
                <td className="px-4 py-3 font-mono text-bay-700">{formatIDR(car.price)}</td>
                <td className="px-4 py-3 font-mono text-bay-700">{formatKm(car.mileage)}</td>
                <td className="px-4 py-3">
                  <StatusBadge status={car.status} label={CAR_STATUS_LABEL[car.status]} />
                </td>
                <td className="px-4 py-3 text-right">
                  <Link to={`/inventory/${car.id}`} className="mr-3 text-signal hover:text-signal-dim">Edit</Link>
                  <button onClick={() => handleDelete(car)} className="text-bay-400 hover:text-signal">Remove</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
