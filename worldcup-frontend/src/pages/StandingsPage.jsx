import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import StandingsTable from '../components/standings/StandingsTable';
import GroupSelector from '../components/standings/GroupSelector';
import '../styles/standings.css';

const StandingsPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const initialGroup = searchParams.get('group') || 'A';
  const [selectedGroup, setSelectedGroup] = useState(initialGroup);

  const handleGroupSelect = (group) => {
    setSelectedGroup(group);
    setSearchParams({ group });
  };

  return (
    <div className="page">
      <div className="container">
        <div className="section-title">
          <h2>Classements</h2>
        </div>

        <GroupSelector selectedGroup={selectedGroup} onSelect={handleGroupSelect} />

        <StandingsTable group={selectedGroup} />
      </div>
    </div>
  );
};

export default StandingsPage;
