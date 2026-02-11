import { useState } from 'react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { adminStartMatch, adminUpdateScore, adminFinishMatch } from '../../services/api';
import '../../styles/admin.css';

const AdminMatchCard = ({ match, onUpdate }) => {
  const [homeScore, setHomeScore] = useState(match.homeScore ?? 0);
  const [awayScore, setAwayScore] = useState(match.awayScore ?? 0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const isScheduled = match.status === 'scheduled';
  const isLive = match.status === 'live';
  const isFinished = match.status === 'finished';

  const formatMatchDate = (dateString) => {
    const date = new Date(dateString);
    return format(date, "dd MMM yyyy 'à' HH:mm", { locale: fr });
  };

  const handleStartMatch = async () => {
    setLoading(true);
    setError(null);
    try {
      await adminStartMatch(match.id);
      onUpdate();
    } catch (err) {
      setError(err.response?.data?.error || 'Erreur lors du démarrage');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdateScore = async () => {
    setLoading(true);
    setError(null);
    try {
      await adminUpdateScore(match.id, homeScore, awayScore);
      onUpdate();
    } catch (err) {
      setError(err.response?.data?.error || 'Erreur lors de la mise à jour');
    } finally {
      setLoading(false);
    }
  };

  const handleFinishMatch = async () => {
    setLoading(true);
    setError(null);
    try {
      await adminFinishMatch(match.id, homeScore, awayScore);
      onUpdate();
    } catch (err) {
      setError(err.response?.data?.error || 'Erreur lors de la finalisation');
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = () => {
    if (isLive) return <span className="admin-status-badge live">En Direct</span>;
    if (isFinished) return <span className="admin-status-badge finished">Terminé</span>;
    return <span className="admin-status-badge scheduled">Programmé</span>;
  };

  return (
    <div className="admin-match-card">
      <div className="admin-match-header">
        <div className="admin-match-info">
          <span>{match.phase.name}</span>
          {match.groupName && <span>Groupe {match.groupName}</span>}
          <span>{formatMatchDate(match.matchDate)}</span>
        </div>
        {getStatusBadge()}
      </div>

      <div className="admin-match-body">
        <div className="admin-match-teams">
          <div className="admin-match-team home">
            <img
              src={match.homeTeam.flag}
              alt={match.homeTeam.code}
              className="admin-match-flag"
            />
            <div className="admin-match-team-info">
              <span className="admin-match-team-name">{match.homeTeam.name}</span>
              <span className="admin-match-team-code">{match.homeTeam.code}</span>
            </div>
          </div>

          <div className="admin-score-editor">
            {isLive ? (
              <>
                <div className="admin-score-control">
                  <button
                    type="button"
                    className="admin-score-btn"
                    onClick={() => setHomeScore(Math.max(0, homeScore - 1))}
                    disabled={homeScore <= 0}
                  >
                    −
                  </button>
                  <span className="admin-score-value">{homeScore}</span>
                  <button
                    type="button"
                    className="admin-score-btn"
                    onClick={() => setHomeScore(homeScore + 1)}
                  >
                    +
                  </button>
                </div>
                <span className="admin-score-separator">-</span>
                <div className="admin-score-control">
                  <button
                    type="button"
                    className="admin-score-btn"
                    onClick={() => setAwayScore(Math.max(0, awayScore - 1))}
                    disabled={awayScore <= 0}
                  >
                    −
                  </button>
                  <span className="admin-score-value">{awayScore}</span>
                  <button
                    type="button"
                    className="admin-score-btn"
                    onClick={() => setAwayScore(awayScore + 1)}
                  >
                    +
                  </button>
                </div>
              </>
            ) : (
              <div className="match-card-score-display">
                <span>{match.homeScore ?? '-'}</span>
                <span className="match-card-score-separator">-</span>
                <span>{match.awayScore ?? '-'}</span>
              </div>
            )}
          </div>

          <div className="admin-match-team away">
            <img
              src={match.awayTeam.flag}
              alt={match.awayTeam.code}
              className="admin-match-flag"
            />
            <div className="admin-match-team-info">
              <span className="admin-match-team-name">{match.awayTeam.name}</span>
              <span className="admin-match-team-code">{match.awayTeam.code}</span>
            </div>
          </div>
        </div>

        {error && <div className="login-error">{error}</div>}

        <div className="admin-match-actions">
          {isScheduled && (
            <button
              className="btn btn-primary"
              onClick={handleStartMatch}
              disabled={loading}
            >
              {loading ? 'Chargement...' : 'Démarrer le match'}
            </button>
          )}

          {isLive && (
            <>
              <button
                className="btn btn-gold"
                onClick={handleUpdateScore}
                disabled={loading}
              >
                {loading ? 'Chargement...' : 'Mettre à jour'}
              </button>
              <button
                className="btn btn-danger"
                onClick={handleFinishMatch}
                disabled={loading}
              >
                {loading ? 'Chargement...' : 'Terminer'}
              </button>
            </>
          )}

          {isFinished && (
            <span style={{ color: 'var(--color-gray)' }}>Match terminé</span>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminMatchCard;
