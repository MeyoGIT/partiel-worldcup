import { useState, useEffect } from 'react';
import { getPhases } from '../../services/api';
import '../../styles/matches.css';

const GROUPS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
const STATUSES = [
  { value: '', label: 'Tous les statuts' },
  { value: 'scheduled', label: 'Programmés' },
  { value: 'live', label: 'En cours' },
  { value: 'finished', label: 'Terminés' },
];

const MatchFilters = ({ filters, onFilterChange }) => {
  const [phases, setPhases] = useState([]);

  useEffect(() => {
    const fetchPhases = async () => {
      try {
        const response = await getPhases();
        setPhases(response.data.data);
      } catch (error) {
        console.error('Error fetching phases:', error);
      }
    };
    fetchPhases();
  }, []);

  const handleChange = (key, value) => {
    onFilterChange({ ...filters, [key]: value });
  };

  return (
    <div className="match-filters">
      <select
        className="form-select"
        value={filters.phase || ''}
        onChange={(e) => handleChange('phase', e.target.value)}
      >
        <option value="">Toutes les phases</option>
        {phases.map((phase) => (
          <option key={phase.id} value={phase.code}>
            {phase.name}
          </option>
        ))}
      </select>

      <select
        className="form-select"
        value={filters.group || ''}
        onChange={(e) => handleChange('group', e.target.value)}
      >
        <option value="">Tous les groupes</option>
        {GROUPS.map((group) => (
          <option key={group} value={group}>
            Groupe {group}
          </option>
        ))}
      </select>

      <select
        className="form-select"
        value={filters.status || ''}
        onChange={(e) => handleChange('status', e.target.value)}
      >
        {STATUSES.map((status) => (
          <option key={status.value} value={status.value}>
            {status.label}
          </option>
        ))}
      </select>

      <input
        type="date"
        className="form-input"
        value={filters.date || ''}
        onChange={(e) => handleChange('date', e.target.value)}
      />

      {(filters.phase || filters.group || filters.status || filters.date) && (
        <button
          className="btn btn-outline btn-sm"
          onClick={() => onFilterChange({ phase: '', group: '', status: '', date: '' })}
        >
          Réinitialiser
        </button>
      )}
    </div>
  );
};

export default MatchFilters;
