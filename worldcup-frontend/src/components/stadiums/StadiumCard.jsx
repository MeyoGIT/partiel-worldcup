import { MapPin, Users, Building } from 'lucide-react';
import '../../styles/stadiums.css';

const StadiumCard = ({ stadium }) => {
  const getCountryClass = () => {
    switch (stadium.country) {
      case 'USA':
        return 'usa';
      case 'Canada':
        return 'canada';
      case 'Mexique':
        return 'mexico';
      default:
        return '';
    }
  };

  const formatCapacity = (capacity) => {
    return new Intl.NumberFormat('fr-FR').format(capacity);
  };

  return (
    <div className="stadium-card">
      <span className={`stadium-country-badge ${getCountryClass()}`}>
        {stadium.country}
      </span>

      <div className="stadium-card-image">
        <Building size={64} strokeWidth={1} />
      </div>

      <div className="stadium-card-body">
        <h3 className="stadium-card-name">{stadium.name}</h3>
        <div className="stadium-card-location">
          <MapPin size={14} />
          <span>{stadium.city}</span>
        </div>
        <div className="stadium-card-capacity">
          <Users size={16} />
          <span className="stadium-card-capacity-value">{formatCapacity(stadium.capacity)}</span>
          <span className="stadium-card-capacity-label">places</span>
        </div>
      </div>
    </div>
  );
};

export default StadiumCard;
