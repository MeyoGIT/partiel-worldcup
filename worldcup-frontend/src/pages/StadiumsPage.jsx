import { useState, useEffect } from 'react';
import { getStadiums } from '../services/api';
import StadiumCard from '../components/stadiums/StadiumCard';
import Loading from '../components/common/Loading';
import '../styles/stadiums.css';

const COUNTRIES = [
  { value: '', label: 'Tous les pays' },
  { value: 'USA', label: 'États-Unis' },
  { value: 'Canada', label: 'Canada' },
  { value: 'Mexique', label: 'Mexique' },
];

const StadiumsPage = () => {
  const [stadiums, setStadiums] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedCountry, setSelectedCountry] = useState('');

  useEffect(() => {
    const fetchStadiums = async () => {
      try {
        const response = await getStadiums();
        setStadiums(response.data.data);
      } catch (error) {
        console.error('Error fetching stadiums:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchStadiums();
  }, []);

  const filteredStadiums = selectedCountry
    ? stadiums.filter((stadium) => stadium.country === selectedCountry)
    : stadiums;

  const getCountryCount = (country) => {
    if (!country) return stadiums.length;
    return stadiums.filter((s) => s.country === country).length;
  };

  if (loading) return <Loading message="Chargement des stades..." />;

  return (
    <div className="page">
      <div className="container">
        <div className="section-title">
          <h2>Stades</h2>
        </div>

        <div className="stadium-filters">
          {COUNTRIES.map((country) => (
            <button
              key={country.value}
              className={`country-filter-btn ${selectedCountry === country.value ? 'active' : ''}`}
              onClick={() => setSelectedCountry(country.value)}
            >
              {country.label}
              <span className="count">{getCountryCount(country.value)}</span>
            </button>
          ))}
        </div>

        <div className="stadiums-grid">
          {filteredStadiums.map((stadium) => (
            <StadiumCard key={stadium.id} stadium={stadium} />
          ))}
        </div>

        {filteredStadiums.length === 0 && (
          <div className="empty-state">
            <h3>Aucun stade trouvé</h3>
          </div>
        )}
      </div>
    </div>
  );
};

export default StadiumsPage;
