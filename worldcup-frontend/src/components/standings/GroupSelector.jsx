import '../../styles/standings.css';

const GROUPS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];

const GroupSelector = ({ selectedGroup, onSelect }) => {
  return (
    <div className="group-selector">
      {GROUPS.map((group) => (
        <button
          key={group}
          className={`group-btn ${selectedGroup === group ? 'active' : ''}`}
          onClick={() => onSelect(group)}
        >
          {group}
        </button>
      ))}
    </div>
  );
};

export default GroupSelector;
