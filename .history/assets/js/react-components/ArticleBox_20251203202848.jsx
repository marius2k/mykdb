const ArticleBox = ({ 
    article,
    onBookmarkToggle,
    onVote,
    isBookmarked = false,
    currentVote = null,
    appUrl = '/mykdb/',
    lang = {
        author: 'Author',
        category: 'Category',
        published: 'Published',
        updated: 'Updated'
    }
}) => {
    // Helper to check if icon is a file path
    const isIconFile = (icon) => {
        return icon && (icon.startsWith('http') || icon.endsWith('.png') || icon.endsWith('.svg'));
    };

    return (
        <div className="article-card">
            {/* HEADER: Icon + Title + Bookmark */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                <h3 className="article-title">
                    {article.icon && (
                        <>
                            {isIconFile(article.icon) ? (
                                <a href={`index.php?fcategory=${article.catid}`} title={article.category}>
                                    <img 
                                        src={`${appUrl}assets/icons/categories/${article.icon}`} 
                                        alt="icon" 
                                        className="me-1" 
                                        style={{ width: '35px', verticalAlign: 'middle' }}
                                    />
                                </a>
                            ) : (
                                <span className="me-1">{article.icon}</span>
                            )}
                        </>
                    )}
                    {article.title}
                </h3>
                
                <div style={{ paddingTop: '15px', paddingRight: '10px' }}>
                    <img 
                        src={`${appUrl}assets/icons/icon-bookmark-${isBookmarked ? 'full' : 'empty'}.svg`}
                        alt="Bookmark" 
                        className="bookmark-icon"
                        style={{ cursor: 'pointer', width: '30px', height: 'auto' }}
                        onClick={() => onBookmarkToggle(article.id)}
                        title={isBookmarked ? 'Remove from favorites' : 'Add to favorites'}
                    />
                </div>
            </div>

            {/* TAGS */}
            {article.tags && article.tags.length > 0 && (
                <div className="article-tags" style={{ margin: '6px 10px', paddingBottom: '10px' }}>
                    {article.tags.map((tag, index) => (
                        <span key={index} className="tag-badge-1">
                            <a href={`articles_by_tag.php?tag=${encodeURIComponent(tag)}`}>
                                {tag}
                            </a>
                        </span>
                    ))}
                </div>
            )}

            {/* BODY: Article Content */}
            <div className="article-body" id={`article${article.id}`}>
                <p>
                    {article.shortText.split('\n').map((line, i) => (
                        <span key={i}>
                            {line}
                            {i < article.shortText.split('\n').length - 1 && <br />}
                        </span>
                    ))}
                    <a href={`view_article.php?id=${article.id}`}>
                        <img 
                            width="24" 
                            height="auto" 
                            src={`${appUrl}assets/icons/icon-read-more.svg`}
                            title="Read more"
                        />
                    </a>
                </p>
            </div>

            {/* FOOTER: Meta Info + Actions */}
            <div className="article-footer">
                <div className="article-meta-section">
                    <span className="article-meta">
                        Author: {article.username} | 
                        Category: {article.category} | 
                        Published: {article.publishedAt} | 
                        Updated: {article.updatedAt}
                    </span>
                </div>

                <div className="vote-buttons-container">
                    {/* Views and Comments */}
                    <div className="vote-buttons" id={`meta-${article.id}`}>
                        <a href={`${appUrl}public/view_article.php?id=${article.id}&version=${article.version}&isonline=1`}>
                            <img 
                                src={`${appUrl}assets/images/icon-view.png`}
                                title="Views" 
                                className="vote-icon"
                            />
                        </a>
                        <span className="view-count">{article.viewsCount || 0}</span>

                        <a href={`${appUrl}public/view_article.php?id=${article.id}#comments`}>
                            <img 
                                src={`${appUrl}assets/images/icon-comm.png`}
                                title="Comments" 
                                className="vote-icon"
                            />
                        </a>
                        <span className="comments-count">{article.commentsCount || 0}</span>
                    </div>

                    {/* Likes and Dislikes */}
                    <div className="vote-buttons">
                        <a 
                            href="#" 
                            onClick={(e) => {
                                e.preventDefault();
                                onVote(article.id, 'like');
                            }}
                        >
                            <img 
                                src={`${appUrl}assets/images/icon-like.png`}
                                className={`vote-icon ${currentVote === 'like' ? 'active' : ''}`}
                                width="20" 
                                height="auto" 
                                title="Like"
                            />
                        </a>
                        <span className="like-count">{article.likes || 0}</span>

                        <a 
                            href="#" 
                            onClick={(e) => {
                                e.preventDefault();
                                onVote(article.id, 'dislike');
                            }}
                        >
                            <img 
                                src={`${appUrl}assets/images/icon-dlike.png`}
                                className={`vote-icon ${currentVote === 'dislike' ? 'active' : ''}`}
                                width="20" 
                                height="auto" 
                                title="Dislike"
                            />
                        </a>
                        <span className="dislike-count">{article.dislikes || 0}</span>
                    </div>
                </div>
            </div>
        </div>
    );
};

window.ArticleBox = ArticleBox;
