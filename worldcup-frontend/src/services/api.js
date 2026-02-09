import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      switch (error.response.status) {
        case 401:
          console.error('Non authentifié');
          break;
        case 403:
          console.error('Accès interdit');
          break;
        case 404:
          console.error('Ressource non trouvée');
          break;
        case 500:
          console.error('Erreur serveur');
          break;
        default:
          console.error('Erreur API:', error.response.data);
      }
    } else if (error.request) {
      console.error('Pas de réponse du serveur');
    } else {
      console.error('Erreur:', error.message);
    }
    return Promise.reject(error);
  }
);

// Teams
export const getTeams = () => api.get('/teams');
export const getTeam = (id) => api.get(`/teams/${id}`);
export const getTeamsByGroup = (group) => api.get(`/teams/group/${group}`);

// Stadiums
export const getStadiums = () => api.get('/stadiums');
export const getStadium = (id) => api.get(`/stadiums/${id}`);

// Phases
export const getPhases = () => api.get('/phases');

// Matches
export const getMatches = (params = {}) => api.get('/matches', { params });
export const getMatch = (id) => api.get(`/matches/${id}`);
export const getMatchesByPhase = (phaseCode) => api.get(`/matches/phase/${phaseCode}`);
export const getLiveMatches = () => api.get('/matches/live');
export const getTodayMatches = () => api.get('/matches/today');

// Standings
export const getStandings = (group) => api.get(`/standings/${group}`);

// Auth
export const login = (email, password) => api.post('/login', { email, password });
export const logout = () => api.post('/logout');
export const getMe = () => api.get('/me');

// Admin
export const adminGetMatches = (params = {}) => api.get('/admin/matches', { params });
export const adminStartMatch = (id) => api.post(`/admin/matches/${id}/start`);
export const adminUpdateScore = (id, homeScore, awayScore) =>
  api.patch(`/admin/matches/${id}/score`, { homeScore, awayScore });
export const adminFinishMatch = (id, homeScore, awayScore) =>
  api.post(`/admin/matches/${id}/finish`, { homeScore, awayScore });

export default api;
