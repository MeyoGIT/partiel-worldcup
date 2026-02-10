import { Link } from 'react-router-dom';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { MapPin } from 'lucide-react';
import '../../styles/matches.css';

const MatchCard = ({ match }) => {
  const isLive = match.status === 'live';
  const isScheduled = match.status === 'scheduled';

  const formatMatchDate = (dateString) => {
    const date = new Date(dateString);
    return format(date, 'dd MMM yyyy', { locale: fr });
  };

  const formatMatchTime = (dateString) => {
    const date = new Date(dateString);
    return format(date, 'HH:mm', { locale: fr });
  };

  const getStatusLabel = () => {
    if (isLive) return 'En direct';
    return 'Terminé';
  };

  return (
    <Link to={`/matches/${match.id}`} className={`match-card ${isLive ? 'is-live' : ''}`}>
      <div className="match-card-header">
        <span className="match-card-phase">{match.phase.name}</span>
        {match.groupName && <span className="badge badge-group">Groupe {match.groupName}</span>}
        {isLive && <span className="badge badge-live">Live</span>}
      </div>

      <div className="match-card-body">
        <div className="match-card-teams">
          <div className="match-card-team home">
            <img
              src={match.homeTeam.flag}
              alt={match.homeTeam.code}
              className="match-card-flag"
            />
            <div>
              <div className="match-card-team-name">{match.homeTeam.name}</div>
              <div className="match-card-team-code">{match.homeTeam.code}</div>
            </div>
          </div>

          <div className="match-card-score">
            {isScheduled ? (
              <>
                <div className="match-card-time">{formatMatchTime(match.matchDate)}</div>
                <div className="match-card-status">Programmé</div>
              </>
            ) : (
              <>
                <div className="match-card-score-display">
                  <span>{match.homeScore}</span>
                  <span className="match-card-score-separator">-</span>
                  <span>{match.awayScore}</span>
                </div>
                <div className={`match-card-status ${isLive ? 'live' : ''}`}>
                  {getStatusLabel()}
                </div>
              </>
            )}
          </div>

          <div className="match-card-team away">
            <div>
              <div className="match-card-team-name">{match.awayTeam.name}</div>
              <div className="match-card-team-code">{match.awayTeam.code}</div>
            </div>
            <img
              src={match.awayTeam.flag}
              alt={match.awayTeam.code}
              className="match-card-flag"
            />
          </div>
        </div>
      </div>

      <div className="match-card-footer">
        <div className="match-card-stadium">
          <MapPin size={14} />
          <span>{match.stadium.name}, {match.stadium.city}</span>
        </div>
        <span>{formatMatchDate(match.matchDate)}</span>
      </div>
    </Link>
  );
};

export default MatchCard;
