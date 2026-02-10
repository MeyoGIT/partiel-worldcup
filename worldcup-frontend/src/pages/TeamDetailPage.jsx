import { useState, useEffect, useRef, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { getTeam, getMatches } from '../services/api';
import MatchCard from '../components/matches/MatchCard';
import Loading from '../components/common/Loading';
import '../styles/teams.css';

const POLLING_INTERVAL = 5000; // 5 secondes

const TeamDetailPage = () => {
  const { id } = useParams();
  const [team, setTeam] = useState(null);
  const [matches, setMatches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const intervalRef = useRef(null);

  const fetchData = useCallback(async (showLoading = false) => {
    if (showLoading) setLoading(true);
    try {
      const [teamRes, matchesRes] = await Promise.all([
        getTeam(id),
        getMatches({ limit: 50 }),
      ]);

      const teamData = teamRes.data.data;
      setTeam(teamData);

      const teamMatches = matchesRes.data.data.filter(
        (match) =>
          match.homeTeam.id === parseInt(id) || match.awayTeam.id === parseInt(id)
      );
      setMatches(teamMatches);
    } catch (err) {
      setError('Équipe non trouvée');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchData(true);
    intervalRef.current = setInterval(() => fetchData(false), POLLING_INTERVAL);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [fetchData]);

  if (loading) return <Loading message="Chargement de l'équipe..." />;
  if (error) return <div className="page"><div className="container"><div className="empty-state">{error}</div></div></div>;
  if (!team) return null;

  const stats = matches.reduce(
    (acc, match) => {
      if (match.status !== 'finished') return acc;

      const isHome = match.homeTeam.id === team.id;
      const goalsFor = isHome ? match.homeScore : match.awayScore;
      const goalsAgainst = isHome ? match.awayScore : match.homeScore;

      acc.played++;
      acc.goalsFor += goalsFor;
      acc.goalsAgainst += goalsAgainst;

      if (goalsFor > goalsAgainst) acc.won++;
      else if (goalsFor < goalsAgainst) acc.lost++;
      else acc.drawn++;

      return acc;
    },
    { played: 0, won: 0, drawn: 0, lost: 0, goalsFor: 0, goalsAgainst: 0 }
  );

  return (
    <div className="page">
      <div className="container">
        <Link to="/teams" className="btn btn-outline btn-sm" style={{ marginBottom: 'var(--space-xl)', display: 'inline-flex' }}>
          <ArrowLeft size={16} />
          Retour aux équipes
        </Link>

        <div className="team-detail">
          <div className="team-detail-header">
            <img
              src={team.flag}
              alt={team.code}
              className="team-detail-flag"
            />
            <div className="team-detail-info">
              <h1>{team.name}</h1>
              <div className="team-detail-code">{team.code}</div>
              <span className="badge badge-group" style={{ marginTop: 'var(--space-md)' }}>
                Groupe {team.groupName}
              </span>
            </div>
          </div>

          <div className="team-detail-body">
            <div className="team-detail-stats">
              <div className="team-stat">
                <div className="team-stat-value">{stats.played}</div>
                <div className="team-stat-label">Matchs joués</div>
              </div>
              <div className="team-stat">
                <div className="team-stat-value">{stats.won}</div>
                <div className="team-stat-label">Victoires</div>
              </div>
              <div className="team-stat">
                <div className="team-stat-value">{stats.drawn}</div>
                <div className="team-stat-label">Nuls</div>
              </div>
              <div className="team-stat">
                <div className="team-stat-value">{stats.lost}</div>
                <div className="team-stat-label">Défaites</div>
              </div>
            </div>

            {matches.length > 0 && (
              <>
                <div className="section-title" style={{ marginTop: 'var(--space-xl)' }}>
                  <h2>Matchs</h2>
                </div>
                <div className="matches-grid">
                  {matches.map((match) => (
                    <MatchCard key={match.id} match={match} />
                  ))}
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default TeamDetailPage;
