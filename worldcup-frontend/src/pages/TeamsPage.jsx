import { useState, useEffect } from 'react';
import { getTeams } from '../services/api';
import TeamCard from '../components/teams/TeamCard';
import Loading from '../components/common/Loading';
import '../styles/teams.css';

const GROUPS = ['', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];

const TeamsPage = () => {
  const [teams, setTeams] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedGroup, setSelectedGroup] = useState('');

  useEffect(() => {
    const fetchTeams = async () => {
      try {
        const response = await getTeams();
        setTeams(response.data.data);
      } catch (error) {
        console.error('Error fetching teams:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchTeams();
  }, []);

  const filteredTeams = selectedGroup
    ? teams.filter((team) => team.groupName === selectedGroup)
    : teams;

  if (loading) return <Loading message="Chargement des équipes..." />;

  return (
    <div className="page">
      <div className="container">
        <div className="section-title">
          <h2>Équipes</h2>
        </div>

        <div className="teams-filter">
          <span className="teams-filter-label">Filtrer par groupe :</span>
          <select
            className="form-select"
            value={selectedGroup}
            onChange={(e) => setSelectedGroup(e.target.value)}
            style={{ width: 'auto', minWidth: '220px' }}
          >
            <option value="">Toutes les équipes</option>
            {GROUPS.filter(Boolean).map((group) => (
              <option key={group} value={group}>
                Groupe {group}
              </option>
            ))}
          </select>
        </div>

        <div className="teams-grid">
          {filteredTeams.map((team) => (
            <TeamCard key={team.id} team={team} />
          ))}
        </div>

        {filteredTeams.length === 0 && (
          <div className="empty-state">
            <h3>Aucune équipe trouvée</h3>
          </div>
        )}
      </div>
    </div>
  );
};

export default TeamsPage;
