<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');


// Suppress PHP errors pentru a nu corupe JSON-ul
error_reporting(0);
ini_set('display_errors', 0);

try {
        //$search = strtolower(trim($_GET['q'] ?? ''));
        $q = trim($_GET['q'] ?? '');
        $author = trim($_GET['author'] ?? '');
        $category = trim($_GET['category'] ?? ''); //name
        $fcategory = trim($_GET['fcategory'] ?? ''); //id

        // Helper function to remove base64 images from content
        function removeBase64Images($content) {
            // Remove data:image base64 strings
            return preg_replace('/data:image\/[^;]+;base64,[A-Za-z0-9+\/=]+/', '', $content);
        }


        $sql = "SELECT a.title, a.id, a.version, a.content, a.publish_at, u.username, c.name AS category, c.icon as icon
                FROM articles a
                JOIN users u ON a.user_id = u.id
                JOIN categories c ON a.category_id = c.id
                WHERE a.status = 'published'
                AND a.status <> 'disabled'
                AND (a.publish_at IS NULL OR a.publish_at <= NOW())
                AND c.is_active = 1";

        $params = [];


        if (!empty($q)) {
            // Note: We'll filter base64 images after fetching, not in SQL
            // because SQL REGEXP can be slow on large content
            $sql .= " AND (LOWER(a.content) LIKE :q1 OR LOWER(a.title) LIKE :q2)";
            $params[':q1'] = '%' . strtolower($q) . '%';
            $params[':q2'] = '%' . strtolower($q) . '%';
            
            // Debug: Log the search query
            error_log("Searching for: " . $q);
        }

        if (!empty($author)) {
            $sql .= " AND LOWER(u.username) LIKE :author";
            $params[':author'] = '%' . strtolower($author) . '%';
        }

        if (!empty($fcategory)) {
            $sql .= " AND a.category_id = :fcategory";
            $params[':fcategory'] = $fcategory;
        } elseif (!empty($category)) {
            $sql .= " AND LOWER(c.name) = :category";
            $params[':category'] = strtolower($category);
        }


        $sql .= " ORDER BY a.created_at DESC LIMIT 20";

        //echo "DEBUG SQL: " . $sql . "\n";

        $db = new Database();

        $stmt = $db->prepare($sql);
        //$stmt->execute(['%' . $search . '%']);
        /*
        file_put_contents('debug.log', json_encode([
            'fcategory' => $_GET['fcategory'] ?? null
        ]));
        */

        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Debug: Check if article content contains the search term
        if (!empty($q)) {
            foreach ($results as $result) {
                $pos_content = stripos($result['content'], $q);
                $pos_title = stripos($result['title'], $q);
                error_log("Article ID {$result['id']}: Title match at position {$pos_title}, Content match at position {$pos_content}");
                if ($pos_content !== false) {
                    $context_start = max(0, $pos_content - 50);
                    $context = substr($result['content'], $context_start, 150);
                    error_log("Context around match: " . $context);
                }
            }
        }

        // Process articles pentru display
        foreach ($results as &$article) {
            $article['content'] = strip_tags($article['content']);
            $article['content'] = substr($article['content'], 0, 200);
            if (strlen($article['content']) >= 200) {
                $article['content'] .= '...';
            }
        }

        // Pentru fiecare articol, obțin toate tagurile
        /*
        foreach ($results as &$article) {
            $tags = $db->fetchAll("
                SELECT t.name 
                FROM tags t
                JOIN article_tags at ON t.id = at.tag_id 
                WHERE at.article_id = ?
            ", [$article['id']]);
            
            $article['tags'] = array_column($tags, 'name');
        }
        */

        echo json_encode($results);

    } catch (Exception $e) {
            // Log error dar returnează JSON valid
            error_log("Search API Error: " . $e->getMessage());
            echo json_encode([]);
    }

?>