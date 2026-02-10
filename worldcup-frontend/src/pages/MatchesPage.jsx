import { useState, useEffect, useRef, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { getMatches } from '../services/api';
import MatchCard from '../components/matches/MatchCard';
import MatchFilters from '../components/matches/MatchFilters';
import LiveScoresBanner from '../components/matches/LiveScoresBanner';
import Loading from '../components/common/Loading';
import '../styles/matches.css';

const POLLING_INTERVAL = 5000; // 5 secondes

const MatchesPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [matches, setMatches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [meta, setMeta] = useState({ total: 0, page: 1, pages: 1 });
  const intervalRef = useRef(null);

  const [filters, setFilters] = useState({
    phase: searchParams.get('phase') || '',
    group: searchParams.get('group') || '',
    status: searchParams.get('status') || '',
    date: searchParams.get('date') || '',
  });

  const [page, setPage] = useState(1);

  const fetchMatches = useCallback(async (showLoading = false) => {
    if (showLoading) setLoading(true);
    try {
      const params = { page, limit: 12 };
      if (filters.phase) params.phase = filters.phase;
      if (filters.group) params.group = filters.group;
      if (filters.status) params.status = filters.status;
      if (filters.date) params.date = filters.date;

      const response = await getMatches(params);
      setMatches(response.data.data);
      setMeta(response.data.meta);
    } catch (error) {
      console.error('Error fetching matches:', error);
    } finally {
      setLoading(false);
    }
  }, [filters, page]);

  useEffect(() => {
    fetchMatches(true);
    intervalRef.current = setInterval(() => fetchMatches(false), POLLING_INTERVAL);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [fetchMatches]);

  const handleFilterChange = (newFilters) => {
    setFilters(newFilters);
    setPage(1);

    // Update URL params
    const params = new URLSearchParams();
    if (newFilters.phase) params.set('phase', newFilters.phase);
    if (newFilters.group) params.set('group', newFilters.group);
    if (newFilters.status) params.set('status', newFilters.status);
    if (newFilters.date) params.set('date', newFilters.date);
    setSearchParams(params);
  };

  return (
    <div className="page">
      <div className="container">
        <div className="section-title">
          <h2>Matchs</h2>
        </div>

        <LiveScoresBanner />

        <MatchFilters filters={filters} onFilterChange={handleFilterChange} />

        {loading ? (
          <Loading message="Chargement des matchs..." />
        ) : matches.length === 0 ? (
          <div className="empty-state">
            <h3>Aucun match trouvé</h3>
            <p>Modifiez vos filtres pour voir plus de résultats.</p>
          </div>
        ) : (
          <>
            <div className="matches-grid">
              {matches.map((match) => (
                <MatchCard key={match.id} match={match} />
              ))}
            </div>

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

export default MatchesPage;
