import { useState, useEffect, useRef, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { ArrowLeft, Calendar, MapPin } from 'lucide-react';
import { getMatch } from '../services/api';
import Loading from '../components/common/Loading';
import '../styles/matches.css';

const POLLING_INTERVAL = 5000; // 5 secondes

const MatchDetailPage = () => {
  const { id } = useParams();
  const [match, setMatch] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const intervalRef = useRef(null);

  const fetchMatch = useCallback(async (showLoading = false) => {
    if (showLoading) setLoading(true);
    try {
      const response = await getMatch(id);
      setMatch(response.data.data);
    } catch (err) {
      setError('Match non trouvé');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchMatch(true);
    intervalRef.current = setInterval(() => fetchMatch(false), POLLING_INTERVAL);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [fetchMatch]);

  if (loading) return <Loading message="Chargement du match..." />;
  if (error) return <div className="page"><div className="container"><div className="empty-state">{error}</div></div></div>;
  if (!match) return null;

  const isLive = match.status === 'live';
  const isScheduled = match.status === 'scheduled';

  const formatMatchDate = (dateString) => {
    const date = new Date(dateString);
    return format(date, "EEEE dd MMMM yyyy 'à' HH:mm", { locale: fr });
  };

  return (
    <div className="page">
      <div className="container">
        <Link to="/matches" className="btn btn-outline btn-sm" style={{ marginBottom: 'var(--space-xl)', display: 'inline-flex' }}>
          <ArrowLeft size={16} />
          Retour aux matchs
        </Link>

        <div className={`match-card ${isLive ? 'is-live' : ''}`} style={{ maxWidth: '800px', margin: '0 auto' }}>
          <div className="match-card-header" style={{ padding: 'var(--space-lg)' }}>
            <span className="match-card-phase">{match.phase.name}</span>
            {match.groupName && <span className="badge badge-group">Groupe {match.groupName}</span>}
            {isLive && <span className="badge badge-live">En direct</span>}
          </div>

          <div className="match-card-body" style={{ padding: 'var(--space-2xl)' }}>
            <div className="match-card-teams">
              <div className="match-card-team home" style={{ gap: 'var(--space-lg)' }}>
                <img
                  src={match.homeTeam.flag}
                  alt={match.homeTeam.code}
                  className="match-card-flag"
                  style={{ width: '80px', height: '60px' }}
                />
                <div>
                  <div className="match-card-team-name" style={{ fontSize: 'var(--text-2xl)' }}>
                    {match.homeTeam.name}
                  </div>
                  <div className="match-card-team-code">{match.homeTeam.code}</div>
                </div>
              </div>

              <div className="match-card-score" style={{ minWidth: '150px' }}>
                {isScheduled ? (
                  <>
                    <div className="match-card-time" style={{ fontSize: 'var(--text-4xl)' }}>
                      {format(new Date(match.matchDate), 'HH:mm')}
                    </div>
                    <div className="match-card-status">Programmé</div>
                  </>
                ) : (
                  <>
                    <div className="match-card-score-display" style={{ fontSize: 'var(--text-5xl)' }}>
                      <span>{match.homeScore}</span>
                      <span className="match-card-score-separator">-</span>
                      <span>{match.awayScore}</span>
                    </div>
                    <div className={`match-card-status ${isLive ? 'live' : ''}`}>
                      {isLive ? 'En cours' : 'Terminé'}
                    </div>
                  </>
                )}
              </div>

              <div className="match-card-team away" style={{ gap: 'var(--space-lg)' }}>
                <div>
                  <div className="match-card-team-name" style={{ fontSize: 'var(--text-2xl)' }}>
                    {match.awayTeam.name}
                  </div>
                  <div className="match-card-team-code">{match.awayTeam.code}</div>
                </div>
                <img
                  src={match.awayTeam.flag}
                  alt={match.awayTeam.code}
                  className="match-card-flag"
                  style={{ width: '80px', height: '60px' }}
                />
              </div>
            </div>
          </div>

          <div className="match-card-footer" style={{ padding: 'var(--space-lg)', flexDirection: 'column', gap: 'var(--space-md)' }}>
            <div style={{ display: 'flex', justifyContent: 'center', gap: 'var(--space-xl)' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--space-sm)' }}>
                <Calendar size={16} />
                <span>{formatMatchDate(match.matchDate)}</span>
              </div>
            </div>
            <div style={{ display: 'flex', justifyContent: 'center', gap: 'var(--space-sm)' }}>
              <MapPin size={16} />
              <span>{match.stadium.name}, {match.stadium.city}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default MatchDetailPage;
