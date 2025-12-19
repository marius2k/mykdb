/**
 * DashboardStatsBar Component
 * 
 * Displays quick stats cards and activity timeline feed
 * @param {Object} props
 * @param {Object} props.stats - Statistics data
 * @param {Array} props.activities - Recent activities
 */

const DashboardStatsBar = ({
  stats,
  activities
}) => {
  const [showAllActivities, setShowAllActivities] = React.useState(false);
  const displayActivities = showAllActivities ? activities : activities.slice(0, 5);
  return React.createElement('div', {
    className: 'dashboard-stats-bar'
  }, // Quick Stats Cards
  React.createElement('div', {
    className: 'quick-stats-container'
  }, // Total Articles Card
  React.createElement('div', {
    className: 'stat-card stat-card-blue'
  }, React.createElement('div', {
    className: 'stat-icon'
  }, '📊'), React.createElement('div', {
    className: 'stat-content'
  }, React.createElement('div', {
    className: 'stat-label'
  }, 'Total Articles'), React.createElement('div', {
    className: 'stat-value'
  }, stats.totalArticles), stats.articlesTrend && React.createElement('div', {
    className: `stat-trend ${stats.articlesTrend > 0 ? 'trend-up' : 'trend-down'}`
  }, stats.articlesTrend > 0 ? '↑' : '↓', ' ', Math.abs(stats.articlesTrend), '% this week'))), // Active Users Card
  React.createElement('div', {
    className: 'stat-card stat-card-green'
  }, React.createElement('div', {
    className: 'stat-icon'
  }, '👥'), React.createElement('div', {
    className: 'stat-content'
  }, React.createElement('div', {
    className: 'stat-label'
  }, 'Active Users'), React.createElement('div', {
    className: 'stat-value'
  }, stats.activeUsers), React.createElement('div', {
    className: 'stat-sublabel'
  }, 'last 7 days'))), // Comments This Week Card
  React.createElement('div', {
    className: 'stat-card stat-card-orange'
  }, React.createElement('div', {
    className: 'stat-icon'
  }, '💬'), React.createElement('div', {
    className: 'stat-content'
  }, React.createElement('div', {
    className: 'stat-label'
  }, 'Comments'), React.createElement('div', {
    className: 'stat-value'
  }, stats.commentsThisWeek), stats.commentsComparison && React.createElement('div', {
    className: 'stat-sublabel'
  }, stats.commentsComparison > 0 ? '+' : '', stats.commentsComparison, ' vs last week'))), // Views This Week Card
  React.createElement('div', {
    className: 'stat-card stat-card-purple'
  }, React.createElement('div', {
    className: 'stat-icon'
  }, '👁️'), React.createElement('div', {
    className: 'stat-content'
  }, React.createElement('div', {
    className: 'stat-label'
  }, 'Article Views'), React.createElement('div', {
    className: 'stat-value'
  }, stats.viewsThisWeek), React.createElement('div', {
    className: 'stat-sublabel'
  }, 'this week')))), // Activity Timeline Feed
  React.createElement('div', {
    className: 'activity-feed-container'
  }, React.createElement('div', {
    className: 'activity-feed-header'
  }, React.createElement('h3', {
    className: 'activity-feed-title'
  }, React.createElement('span', {
    className: 'activity-icon'
  }, '📰'), ' Recent Activity'), activities.length > 5 && React.createElement('button', {
    className: 'toggle-activities-btn',
    onClick: () => setShowAllActivities(!showAllActivities)
  }, showAllActivities ? 'Show Less' : `Show All (${activities.length})`)), React.createElement('div', {
    className: 'activity-feed-list'
  }, displayActivities.length > 0 ? displayActivities.map((activity, index) => React.createElement('div', {
    key: index,
    className: 'activity-item'
  }, React.createElement('div', {
    className: 'activity-icon-wrapper'
  }, React.createElement('span', {
    className: 'activity-type-icon'
  }, activity.icon)), React.createElement('div', {
    className: 'activity-content'
  }, React.createElement('div', {
    className: 'activity-text'
  }, React.createElement('strong', null, activity.username), ' ', activity.message), React.createElement('div', {
    className: 'activity-time'
  }, activity.timeAgo)))) : React.createElement('div', {
    className: 'no-activity'
  }, '📭 No recent activity'))));
};

// Export for use in other files
if (typeof window !== 'undefined') {
  window.DashboardStatsBar = DashboardStatsBar;
}
