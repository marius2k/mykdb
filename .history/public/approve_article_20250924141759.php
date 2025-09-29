<?php

// Include fișierele necesare pentru conexiunea la baza de date
require_once '../config/bootstrap.php';

// Verifică dacă ID-ul articolului a fost trimis
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $articleId = intval($_GET['id']);
    $versionNumber = intval($_GET['version']);

    // Verifică permisiunile utilizatorului
    if (!isset($_SESSION['user']['id'])) {
        die('Neautentificat');
    }
    
    $ops = ['approve_article'];
    if (!hasPermission($_SESSION['user']['id'], $ops)) {
        die('Nu ai permisiunea să aprobi articole');
    }

    // Creează o instanță a clasei Database
    $db = new Database();

    try {
        // Pregătește interogarea SQL pentru a actualiza statusul articolului
        $sql = "UPDATE article_versions SET status = 'approved' WHERE article_id = :id AND version_number = :version";
        $stmt = $db->getPdo()->prepare($sql);

        // Leagă parametrul și execută interogarea
        $stmt->bindParam(':id', $articleId, PDO::PARAM_INT);
        $stmt->bindParam(':version', $versionNumber, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Log activitatea
            logActivity($_SESSION['user']['id'], 'article_approved', 'User ' . $_SESSION['user']['username'] . ' approved an article');
            
            // Obține datele articolului pentru notificare
            $article = $db->fetchSingle("SELECT author_id, title FROM article_versions WHERE article_id = ? AND version_number = ?", [$articleId, $versionNumber]);
            if ($article) {
                // Trimite notificare către autorul articolului
                sendNotificationToUser($article['author_id'], 'Article Approved', 'Your article <a href="view_article.php?id=' . $articleId . '&version=' . $versionNumber . '">' . truncateText($article['title'], 30) . '</a> has been approved.', 'info');

                // add points for article approval
                awardArticlePublished($article['author_id'], $articleId, $article['title']);
            }
            
            // Aprobarea a avut succes
            $_SESSION['message'] = 'Articolul a fost aprobat cu succes.';
        } else {
            // A apărut o eroare la aprobarea articolului
            $_SESSION['message'] = 'Eroare la aprobarea articolului. Te rugăm să încerci din nou.';
        }
    } catch (PDOException $e) {
        // Capturarea erorilor de bază de date
        $_SESSION['message'] = 'Eroare de bază de date: ' . htmlspecialchars($e->getMessage());
    }
} else {
    // ID-ul articolului nu este valid
    $_SESSION['message'] = 'ID-ul articolului nu este valid.';
}

// Redirecționează utilizatorul înapoi la pagina anterioară
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>