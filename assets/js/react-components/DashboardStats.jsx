/**
 * Dashboard Statistics Component
 * Displays real-time statistics cards for the dashboard
 */

const DashboardStats = () => {
  const [stats, setStats] = React.useState({
    published: 0,
    drafts: 0,
    pending: 0,
    approved: 0,
    totalArticles: 0,
    totalUsers: 0,
    onlineUsers: 0
  });
  
  const [loading, setLoading] = React.useState(true);

  // Fetch stats from API
  React.useEffect(() => {
    fetch('api/bkd_dashboard_stats.php')
      .then(response => response.json())
      .then(data => {
        setStats(data);
        setLoading(false);
      })
      .catch(error => {
        console.error('Error fetching stats:', error);
        setLoading(false);
      });
  }, []);

  if (loading) {
    return (
      <div className="stats-loading">
        <p>Loading statistics...</p>
      </div>
    );
  }

  return (
    <div className="dashboard-stats-container">
      <h3>📊 Statistics Overview</h3>
      
      <div className="stats-grid">
        {/* Articles This Month Card */}
        <div className="stat-card articles-card">
          <div className="stat-icon">📚</div>
          <div className="stat-content">
            <h4>Articles This Month</h4>
            <div className="stat-details">
              <div className="stat-item">
                <span className="stat-label">Published:</span>
                <span className="stat-value">{stats.published}</span>
              </div>
              <div className="stat-item">
                <span className="stat-label">Drafts:</span>
                <span className="stat-value">{stats.drafts}</span>
              </div>
              <div className="stat-item">
                <span className="stat-label">Pending:</span>
                <span className="stat-value">{stats.pending}</span>
              </div>
              <div className="stat-item">
                <span className="stat-label">Approved:</span>
                <span className="stat-value">{stats.approved}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Total Articles Card */}
        <div className="stat-card total-card">
          <div className="stat-icon">📖</div>
          <div className="stat-content">
            <h4>Total Articles</h4>
            <div className="stat-number">{stats.totalArticles}</div>
            <p className="stat-subtitle">All time</p>
          </div>
        </div>

        {/* Users Card */}
        <div className="stat-card users-card">
          <div className="stat-icon">👥</div>
          <div className="stat-content">
            <h4>Users</h4>
            <div className="stat-number">{stats.totalUsers}</div>
            <div className="online-status">
              <span className="online-indicator">🟢</span>
              <span>{stats.onlineUsers} online</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

// Make it available globally
window.DashboardStats = DashboardStats;
