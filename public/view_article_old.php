<?php
require_once '../config/bootstrap.php';


//chech if the logged user is allowed to perform this operation (view_article)

$ops=['view_article','add_comment'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}



if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

$db = new Database();


// increment view counter for the article;

$db->query("UPDATE articles SET views = views + 1 WHERE id = ?", [$id]);



// Fetch articolul
$stmt = $db->prepare("
    SELECT a.*, u.username, c.name AS category, c.icon
    FROM articles a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$article = $stmt->fetch();

$userId = $_SESSION['user']['id'];

logActivity($_SESSION['user']['id'], 'article_viewed', 'User ' .$_SESSION['user']['username'] .' viewed the article:'.$article['title']);

if (!$article) {
    echo "Articol inexistent sau neaprobat.";
    exit;
}








?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div style="max-width:80%; margin:20px auto;">
    <h2 class="article-title">
        
            <?php if (!empty($article['icon'])): ?>
                <?php if (str_starts_with($article['icon'], 'http') || str_ends_with($article['icon'], '.png') || str_ends_with($article['icon'], '.svg')): ?>
                <img src="<?=APP_URL?>assets/icons/categories/<?= $article['icon'] ?>" alt="icon" class="me-1" style="width: 45px; vertical-align: middle;">
            <?php else: ?>
                <span class="me-1"><?= htmlspecialchars($article['icon']) ?></span>
                <?php endif; ?>
            <?php endif; ?>

            <?= htmlspecialchars($article['title']) ?>
    
            
    </h2>
    <p style="color=#"><em>Autor: <?= escape($article['username']) ?> | Categorie: <?= escape($article['category']) ?> | Publicat: <?= formatDate($article['created_at']) ?> | Actualizat: <?= $article['updated_at']?></em></p>

    

    <div class="article-view">
        <div>
            <?= $article['content'] ?>
            <br>
        </div>
        <div class="vote-buttons" style="align:right">
                                                
                <?php
                     $votes = getArticleLikesDislikes($article['id']);
                     $currentVote = $userId ? getUserVote($article['id'], $userId) : null;
                     //echo "crt vote:".$currentVote;
                ?>
                <!-- LIKE -->
                <a href="#" onclick="voteArticle(<?= $article['id'] ?>, 'like', this); return false;">
                <img src="<?=APP_URL?>assets/images/icon-like.png" class="vote-icon <?= $currentVote === 'like' ? 'active' : '' ?>" width="20" high="auto" title="<?= lang('lang_article_like') ?>">  
                </a>
                <span class="like-count"><?= $votes['like'] ?></span>
                <!-- DISLIKE -->
                <a href="#" onclick="voteArticle(<?= $article['id'] ?>, 'dislike', this); return false;">
                <img src="<?=APP_URL?>assets/images/icon-dlike.png" class="vote-icon <?= $currentVote === 'dislike' ? 'active' : '' ?>" width="20" height="auto" title="<?= lang('lang_article_dislike') ?>">    
                </a>
                <span class="dislike-count"><?= $votes['dislike'] ?></span>
        </div>



    </div>

    <div class="article-view">
            <h4 id="comments">💬 Comentarii</h4>

            <?php

            $comments = $db->fetchAll("
                    SELECT c.id, c.content, c.created_at, u.username,
                        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id AND vote_type = 'like') AS likes,
                        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id AND vote_type = 'dislike') AS dislikes
                    FROM article_comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.article_id = ? AND c.status = 'approved'
                    ORDER BY c.created_at DESC
                ", [$article['id']]);
            

            $commentIds = array_column($comments, 'id');
            $userVotes = [];

            if (!empty($_SESSION['user']['id']) && count($commentIds)) {
                $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
                $voteResults = $db->fetchAll("
                    SELECT comment_id, vote_type 
                    FROM comment_likes 
                    WHERE user_id = ? AND comment_id IN ($placeholders)
                ", array_merge([$_SESSION['user']['id']], $commentIds));
                
                //$likes = $db->fetchAll("SELECT COUNT(*) AS count FROM comment_likes WHERE comment_id = ? AND vote_type = 'like'", [$commentId]);
                //$dislikes = $db->fetchAll("SELECT COUNT(*) AS count FROM comment_likes WHERE comment_id = ? AND vote_type = 'dislike'", [$commentId]);

                
                
                foreach ($voteResults as $vote) {
                    $userVotes[$vote['comment_id']] = $vote['vote_type'];
                }
            }



            if ($comments):
            foreach ($comments as $c):
                $hasVoted = $userVotes[$c['id']] ?? null;

            ?>
            <div id="comment-<?= $c['id'] ?>" class="comment border rounded p-2 mb-2">
                <strong><?= htmlspecialchars($c['username']) ?></strong>
                <small class="text-muted"><?= date('Y-m-d H:i', strtotime($c['created_at'])) ?></small>
                <p class="mb-0" style="padding: 20px;"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                <div class="comment-footer" style="display: flex; justyfy-content: space-between; align-items: center; width: 100%;">
                    <div>
                        <!-- like comment -->
                       <a href="#" onclick="voteComment(<?= $c['id'] ?>, 'like'); return false;">
                        <img width="20" high="auto" src="<?= APP_URL ?>assets/images/icon-like.png" 
                            id="like-icon-<?= $c['id'] ?>" 
                            class="vote-icon <?= $hasVoted === 'like' ? 'voted' : '' ?>" 
                            title="<?= $hasVoted ? 'Ai votat deja' : 'Like' ?>">
                        <span class="dislike-count" id="like-count-<?= $c['id'] ?>"><?= $c['likes'] ?? 0 ?></span>
                        </a>
                    
                        <!-- disalike comment -->
                        <a href="#" onclick="voteComment(<?= $c['id'] ?>, 'dislike'); return false;">
                        <img width="20" high="auto" src="<?= APP_URL ?>assets/images/icon-dlike.png" 
                            id="dislike-icon-<?= $c['id'] ?>" 
                            class="vote-icon <?= $hasVoted === 'dislike' ? 'voted' : '' ?>" 
                            title="<?= $hasVoted ? 'Ai votat deja' : 'Dislike' ?>">
                        <span class="dislike-count" id="dislike-count-<?= $c['id'] ?>"><?= $c['dislikes'] ?? 0 ?></span>
                        </a>
                    </div>

                    <div style="margin-left: auto;">

                        <!-- Disable comment -->
                        <?php if (hasPermission($_SESSION['user']['id'], ['reject_comment'])): ?>
                                <a href="#" onclick="disableComment(<?= $c['id'] ?>, 'like'); return false;">
                                <img width="20" high="auto" src="<?= APP_URL ?>assets/icons/icon-disable-comm.svg" class="vote-icon" title="Disable Comment">
                                </a>
                                <span>&nbsp;</span>
                        <?php endif; ?>

                        <!-- Delete comment -->
                        
                        <?php if (hasPermission($_SESSION['user']['id'], ['delete_comment'])): ?>

                                <a href="#" onclick="deleteComment(<?= $c['id'] ?>, 'like'); return false;">
                                <img width="20" high="auto" src="<?= APP_URL ?>assets/icons/icon-delete-comm.svg" class="vote-icon" title="Delete Comment">
                                </a>
                                <span>&nbsp;</span>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
            <?php endforeach; else: ?>
            <p>Nu există comentarii aprobate.</p>
            <?php endif; ?>




    </div>
    
</div>


<div class="article-view" style="max-width:80%;margin:20px auto;">
    <form id="comment-form" class="mb-4" onsubmit="return submitComment();">
    <input type="hidden" name="article_id" value="<?= $article['id'] ?>" id="article_id">
    <textarea name="content" id="comment-content" class="form-control" required rows="3" placeholder="Scrie un comentariu..."></textarea>
    <button type="submit" class="btn btn-primary mt-2">Trimite</button>
    <div id="comment-feedback" class="mt-2 text-success d-none">Comentariul a fost trimis!</div>
    </form>
</div>

<script>




    document.addEventListener('DOMContentLoaded', function() {
    updateArticleMeta(<?= $article['id'] ?>);
    });

</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
