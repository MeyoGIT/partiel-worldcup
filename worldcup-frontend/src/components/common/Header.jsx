import { NavLink, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useState, useEffect } from 'react';
import { getLiveMatches } from '../../services/api';
import { Trophy, Menu } from 'lucide-react';
import '../../styles/header.css';

const Header = () => {
  const { user, isAdmin, logout } = useAuth();
  const [hasLiveMatches, setHasLiveMatches] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  useEffect(() => {
    const checkLiveMatches = async () => {
      try {
        const response = await getLiveMatches();
        setHasLiveMatches(response.data?.data?.length > 0);
      } catch (error) {
        // Ignore errors
      }
    };

    checkLiveMatches();
    const interval = setInterval(checkLiveMatches, 5000);
    return () => clearInterval(interval);
  }, []);

  const handleLogout = async () => {
    await logout();
  };

  return (
    <header className="header">
      <div className="header-main">
        <div className="container header-content">
          <Link to="/" className="logo">
            <div className="logo-icon">
              <Trophy size={24} />
            </div>
            <div className="logo-text">
              <span className="logo-title">World Cup</span>
              <span className="logo-subtitle">2026</span>
            </div>
          </Link>

          <nav className="nav">
            <NavLink to="/" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              Accueil
            </NavLink>
            <NavLink
              to="/matches"
              className={({ isActive }) => `nav-link ${hasLiveMatches ? 'has-live' : ''} ${isActive ? 'active' : ''}`}
            >
              Matchs
              {hasLiveMatches && <span className="live-dot" />}
            </NavLink>
            <NavLink to="/standings" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              Classements
            </NavLink>
            <NavLink to="/teams" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              Équipes
            </NavLink>
            <NavLink to="/stadiums" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              Stades
            </NavLink>
            {isAdmin() && (
              <NavLink to="/admin" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
                Admin
              </NavLink>
            )}
          </nav>

          <div className="header-actions">
            {user ? (
              <button onClick={handleLogout} className="btn btn-outline btn-sm">
                Déconnexion
              </button>
            ) : (
              <Link to="/login" className="btn btn-gold btn-sm">
                Admin
              </Link>
            )}
          </div>

          <button
            className="mobile-menu-btn"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          >
            <Menu size={24} />
          </button>
        </div>
      </div>
    </header>
  );
};

export default Header;
