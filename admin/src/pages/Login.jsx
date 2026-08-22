import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api, { login } from '../lib/api';

export default function Login({ setUser }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await login(email, password);
      const { data } = await api.get('/api/me');
      setUser(data);
      navigate('/inventory');
    } catch (err) {
      setError('Wrong email or password.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-bay-900 px-4">
      <div className="w-full max-w-sm">
        <div className="mb-8 text-center">
          <div className="text-xs uppercase tracking-widest text-bay-400">Dealership</div>
          <div className="font-mono text-2xl font-semibold text-white">Console</div>
        </div>
        <form onSubmit={handleSubmit} className="rounded-lg bg-bay-800 p-6 shadow-xl">
          <label className="mb-1 block text-xs font-medium text-bay-200">Email</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="mb-4 w-full rounded-md border border-bay-700 bg-bay-900 px-3 py-2 text-sm text-white focus:border-signal focus:outline-none focus:ring-1 focus:ring-signal"
          />
          <label className="mb-1 block text-xs font-medium text-bay-200">Password</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            className="mb-5 w-full rounded-md border border-bay-700 bg-bay-900 px-3 py-2 text-sm text-white focus:border-signal focus:outline-none focus:ring-1 focus:ring-signal"
          />
          {error && <p className="mb-4 text-sm text-signal-light">{error}</p>}
          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-md bg-signal py-2 text-sm font-semibold text-white transition-colors hover:bg-signal-dim disabled:opacity-50"
          >
            {loading ? 'Signing in…' : 'Sign in'}
          </button>
        </form>
      </div>
    </div>
  );
}
