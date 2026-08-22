import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { logout } from '../lib/api';

const NAV = [
  { to: '/inventory', label: 'Inventory' },
  { to: '/leads', label: 'Leads' },
];

export default function Layout({ user, setUser }) {
  const navigate = useNavigate();

  async function handleLogout() {
    await logout();
    setUser(null);
    navigate('/login');
  }

  return (
    <div className="flex min-h-screen bg-bay-50">
      <aside className="flex w-56 flex-col justify-between bg-bay-900 text-bay-100">
        <div>
          <div className="border-b border-bay-800 px-5 py-5">
            <div className="text-xs uppercase tracking-widest text-bay-400">Dealership</div>
            <div className="font-mono text-lg font-semibold text-white">Console</div>
          </div>
          <nav className="mt-2 flex flex-col gap-1 px-3">
            {NAV.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) =>
                  `rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                    isActive
                      ? 'bg-signal/15 text-signal-light'
                      : 'text-bay-200 hover:bg-bay-800 hover:text-white'
                  }`
                }
              >
                {item.label}
              </NavLink>
            ))}
          </nav>
        </div>
        <div className="border-t border-bay-800 px-5 py-4">
          <div className="text-sm text-bay-200">{user?.name}</div>
          <div className="mb-3 text-xs text-bay-400">{user?.role}</div>
          <button
            onClick={handleLogout}
            className="text-xs font-medium text-bay-400 hover:text-signal-light"
          >
            Sign out
          </button>
        </div>
      </aside>
      <main className="flex-1 overflow-y-auto">
        <Outlet />
      </main>
    </div>
  );
}
