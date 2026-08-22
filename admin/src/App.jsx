import { useEffect, useState } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import api from './lib/api';
import Layout from './components/Layout';
import Login from './pages/Login';
import Inventory from './pages/Inventory';
import CarForm from './pages/CarForm';
import Leads from './pages/Leads';
import LeadDetail from './pages/LeadDetail';

export default function App() {
  const [user, setUser] = useState(undefined); // undefined = checking, null = signed out

  useEffect(() => {
    api.get('/api/me').then(({ data }) => setUser(data)).catch(() => setUser(null));
  }, []);

  if (user === undefined) {
    return <div className="flex min-h-screen items-center justify-center text-bay-400">Loading…</div>;
  }

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={user ? <Navigate to="/inventory" /> : <Login setUser={setUser} />} />
        <Route element={user ? <Layout user={user} setUser={setUser} /> : <Navigate to="/login" />}>
          <Route path="/inventory" element={<Inventory />} />
          <Route path="/inventory/new" element={<CarForm />} />
          <Route path="/inventory/:id" element={<CarForm />} />
          <Route path="/leads" element={<Leads />} />
          <Route path="/leads/:id" element={<LeadDetail />} />
          <Route path="/" element={<Navigate to="/inventory" />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
