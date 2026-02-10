import { Link } from 'react-router-dom';
import '../../styles/teams.css';

const TeamCard = ({ team }) => {
  return (
    <Link to={`/teams/${team.id}`} className="team-card">
      <div className="team-card-header">
        <img
          src={team.flag}
          alt={team.code}
          className="team-card-flag"
        />
      </div>
      <div className="team-card-body">
        <h3 className="team-card-name">{team.name}</h3>
        <div className="team-card-code">{team.code}</div>
        <div className="team-card-group">
          <span className="badge badge-group">Groupe {team.groupName}</span>
        </div>
      </div>
    </Link>
  );
};

export default TeamCard;
