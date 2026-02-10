import { usePolling } from '../../hooks/usePolling';
import { getLiveMatches } from '../../services/api';
import { Link } from 'react-router-dom';
import { Radio } from 'lucide-react';
import '../../styles/matches.css';

const LiveScoresBanner = () => {
  const { data, loading } = usePolling(getLiveMatches);

  if (loading) return null;
  if (!data?.data?.length) return null;

  return (
    <div className="live-scores-banner">
      <div className="live-scores-title">
        <Radio size={18} />
        <span>Matchs en direct</span>
      </div>

      <div className="live-scores-list">
        {data.data.map((match) => (
          <Link to={`/matches/${match.id}`} key={match.id} className="live-score-item">
            <div className="live-score-team">
              <img
                src={match.homeTeam.flag}
                alt={match.homeTeam.code}
                className="live-score-flag"
              />
              <span className="live-score-name">{match.homeTeam.code}</span>
            </div>
            <span className="live-score-result">
              {match.homeScore} - {match.awayScore}
            </span>
            <div className="live-score-team">
              <span className="live-score-name">{match.awayTeam.code}</span>
              <img
                src={match.awayTeam.flag}
                alt={match.awayTeam.code}
                className="live-score-flag"
              />
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
};

export default LiveScoresBanner;
