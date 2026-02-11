import { useState, useEffect } from 'react';
import { Settings, RefreshCw } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { adminGetMatches } from '../services/api';
import AdminMatchCard from '../components/admin/AdminMatchCard';
import Loading from '../components/common/Loading';
import '../styles/admin.css';

const STATUSES = [
  { value: '', label: 'Tous les statuts' },
  { value: 'scheduled', label: 'Programmés' },
  { value: 'live', label: 'En cours' },
  { value: 'finished', label: 'Terminés' },
];

const AdminPage = () => {
  const { user, logout } = useAuth();
  const [matches, setMatches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({ total: 0, pages: 1 });

  const fetchMatches = async () => {
    setLoading(true);
    try {
      const params = { page, limit: 10 };
      if (statusFilter) params.status = statusFilter;

      const response = await adminGetMatches(params);
      setMatches(response.data.data);
      setMeta(response.data.meta);
    } catch (error) {
      console.error('Error fetching matches:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMatches();
  }, [statusFilter, page]);

  const handleMatchUpdate = () => {
    fetchMatches();
  };

  return (
    <div className="page admin-dashboard">
      <div className="container">
        <div className="admin-header">
          <div className="admin-title">
            <Settings className="icon" size={28} />
            <h1>Administration</h1>
          </div>
          <div className="admin-user">
            <span>Connecté en tant que {user?.email}</span>
            <button className="btn btn-outline btn-sm" onClick={logout}>
              Déconnexion
            </button>
          </div>
        </div>

        <div className="admin-filters">
          <select
            className="form-select"
            value={statusFilter}
            onChange={(e) => {
              setStatusFilter(e.target.value);
              setPage(1);
            }}
          >
            {STATUSES.map((status) => (
              <option key={status.value} value={status.value}>
                {status.label}
              </option>
            ))}
          </select>

          <button className="btn btn-primary btn-sm" onClick={fetchMatches}>
            <RefreshCw size={16} />
            Actualiser
          </button>
        </div>

        {loading ? (
          <Loading message="Chargement des matchs..." />
        ) : matches.length === 0 ? (
          <div className="empty-state">
            <h3>Aucun match trouvé</h3>
            <p>Modifiez vos filtres pour voir plus de résultats.</p>
          </div>
        ) : (
          <>
            {matches.map((match) => (
              <AdminMatchCard
                key={match.id}
                match={match}
                onUpdate={handleMatchUpdate}
              />
            ))}

            {meta.pages > 1 && (
              <div style={{ display: 'flex', justifyContent: 'center', gap: 'var(--space-md)', marginTop: 'var(--space-xl)' }}>
                <button
                  className="btn btn-outline btn-sm"
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  disabled={page === 1}
                >
                  Précédent
                </button>
                <span style={{ display: 'flex', alignItems: 'center', color: 'var(--color-gray)' }}>
                  Page {page} / {meta.pages}
                </span>
                <button
                  className="btn btn-outline btn-sm"
                  onClick={() => setPage((p) => Math.min(meta.pages, p + 1))}
                  disabled={page === meta.pages}
                >
                  Suivant
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default AdminPage;
