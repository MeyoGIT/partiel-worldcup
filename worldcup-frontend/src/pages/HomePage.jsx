import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Trophy, ArrowUpRight } from 'lucide-react';
import { getTeams, getStadiums } from '../services/api';
import LiveScoresBanner from '../components/matches/LiveScoresBanner';
import Loading from '../components/common/Loading';
import '../styles/home.css';

const HomePage = () => {
  const [stats, setStats] = useState({ teams: 0, stadiums: 0, matches: 0 });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [teamsRes, stadiumsRes] = await Promise.all([
          getTeams(),
          getStadiums(),
        ]);

        setStats({
          teams: teamsRes.data.meta.total,
          stadiums: stadiumsRes.data.meta.total,
          matches: 104,
        });
      } catch (error) {
        console.error('Error fetching data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  if (loading) return <Loading message="Chargement de la page d'accueil..." />;

  return (
    <>
      <section className="hero">
        <div className="hero-content">
          <div className="hero-icon">
            <Trophy size={80} strokeWidth={1.5} />
          </div>
          <h1 className="hero-title">
            FIFA World Cup <span className="gold">2026</span>
          </h1>
          <p className="hero-subtitle">
            Le plus grand événement sportif mondial
          </p>
          <div className="hero-hosts">
            <div className="hero-host">
              <img
                src="https://flagcdn.com/w80/us.png"
                alt="USA"
                className="hero-host-flag"
              />
              <span className="hero-host-name">États-Unis</span>
            </div>
            <div className="hero-host">
              <img
                src="https://flagcdn.com/w80/ca.png"
                alt="Canada"
                className="hero-host-flag"
              />
              <span className="hero-host-name">Canada</span>
            </div>
            <div className="hero-host">
              <img
                src="https://flagcdn.com/w80/mx.png"
                alt="Mexico"
                className="hero-host-flag"
              />
              <span className="hero-host-name">Mexique</span>
            </div>
          </div>
          <div className="hero-cta">
            <Link to="/matches" className="btn btn-gold">
              Voir les matchs
            </Link>
            <Link to="/standings" className="btn btn-outline">
              Classements
            </Link>
          </div>
        </div>
      </section>

      <div className="container">
        <LiveScoresBanner />

        <div className="quick-stats">
          <Link to="/teams" className="quick-stat">
            <ArrowUpRight className="quick-stat-arrow" size={18} />
            <div className="quick-stat-value">{stats.teams}</div>
            <div className="quick-stat-label">Équipes</div>
          </Link>
          <Link to="/standings" className="quick-stat">
            <ArrowUpRight className="quick-stat-arrow" size={18} />
            <div className="quick-stat-value">12</div>
            <div className="quick-stat-label">Groupes</div>
          </Link>
          <Link to="/stadiums" className="quick-stat">
            <ArrowUpRight className="quick-stat-arrow" size={18} />
            <div className="quick-stat-value">{stats.stadiums}</div>
            <div className="quick-stat-label">Stades</div>
          </Link>
          <Link to="/matches" className="quick-stat">
            <ArrowUpRight className="quick-stat-arrow" size={18} />
            <div className="quick-stat-value">{stats.matches}</div>
            <div className="quick-stat-label">Matchs</div>
          </Link>
        </div>
      </div>
    </>
  );
};

export default HomePage;
