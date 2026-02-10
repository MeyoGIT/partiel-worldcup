import { useState, useEffect, useRef, useCallback } from 'react';
import { getStandings } from '../../services/api';
import Loading from '../common/Loading';
import '../../styles/standings.css';

const POLLING_INTERVAL = 5000; // 5 secondes

const StandingsTable = ({ group }) => {
  const [standings, setStandings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const intervalRef = useRef(null);

  const fetchStandings = useCallback(async (showLoading = false) => {
    if (showLoading) {
      setLoading(true);
      setError(null);
    }
    try {
      const response = await getStandings(group);
      setStandings(response.data.data);
    } catch (err) {
      setError('Erreur lors du chargement du classement');
    } finally {
      setLoading(false);
    }
  }, [group]);

  useEffect(() => {
    if (!group) return;

    fetchStandings(true);
    intervalRef.current = setInterval(() => fetchStandings(false), POLLING_INTERVAL);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [fetchStandings, group]);

  if (loading) return <Loading message="Chargement du classement..." />;
  if (error) return <div className="empty-state">{error}</div>;
  if (!standings.length) return <div className="empty-state">Aucune donnée disponible</div>;

  const getPositionClass = (position) => {
    if (position <= 2) return 'qualified';
    if (position === 3) return 'playoff';
    return '';
  };

  const formatGD = (gd) => {
    if (gd > 0) return `+${gd}`;
    return gd.toString();
  };

  return (
    <div className="standings-container">
      <div className="standings-header">
        <h3 className="standings-title">Groupe {group}</h3>
      </div>

      <table className="standings-table">
        <thead>
          <tr>
            <th>Pos</th>
            <th>Équipe</th>
            <th>J</th>
            <th className="hide-mobile">G</th>
            <th className="hide-mobile">N</th>
            <th className="hide-mobile">P</th>
            <th className="hide-mobile">BP</th>
            <th className="hide-mobile">BC</th>
            <th>Diff</th>
            <th>Pts</th>
          </tr>
        </thead>
        <tbody>
          {standings.map((row) => (
            <tr key={row.team.id}>
              <td>
                <span className={`standings-position ${getPositionClass(row.position)}`}>
                  {row.position}
                </span>
              </td>
              <td>
                <div className="standings-team">
                  <img
                    src={row.team.flag}
                    alt={row.team.code}
                    className="standings-team-flag"
                  />
                  <span className="standings-team-name">{row.team.name}</span>
                </div>
              </td>
              <td>{row.played}</td>
              <td className="hide-mobile">{row.won}</td>
              <td className="hide-mobile">{row.drawn}</td>
              <td className="hide-mobile">{row.lost}</td>
              <td className="hide-mobile">{row.goalsFor}</td>
              <td className="hide-mobile">{row.goalsAgainst}</td>
              <td>
                <span className={`standings-gd ${row.goalDifference > 0 ? 'positive' : row.goalDifference < 0 ? 'negative' : ''}`}>
                  {formatGD(row.goalDifference)}
                </span>
              </td>
              <td>
                <span className="standings-points">{row.points}</span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <div className="standings-legend">
        <div className="legend-item">
          <span className="legend-dot qualified"></span>
          <span>Qualifié</span>
        </div>
        <div className="legend-item">
          <span className="legend-dot playoff"></span>
          <span>Barrage</span>
        </div>
      </div>
    </div>
  );
};

export default StandingsTable;
