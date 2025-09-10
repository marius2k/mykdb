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





        $sql = "SELECT a.title, a.content, a.created_at, u.username, c.name AS category
                FROM articles a
                JOIN users u ON a.user_id = u.id
                JOIN categories c ON a.category_id = c.id
                WHERE a.status = 'approved'
                AND a.status <> 'disabled'
                AND (a.publish_at IS NULL OR a.publish_at <= NOW())
                AND c.is_active = 1";

        $params = [];


        if (!empty($q)) {
            $sql .= " AND (LOWER(a.content) LIKE :q1 OR LOWER(a.title) LIKE :q2)";
            $params[':q1'] = '%' . strtolower($q) . '%';
            $params[':q2'] = '%' . strtolower($q) . '%';
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

        // Process articles pentru display
    foreach ($results as &$article) {
        $article['content'] = strip_tags($article['content']);
        $article['content'] = substr($article['content'], 0, 300);
        if (strlen($article['content']) >= 300) {
            $article['content'] .= '...';
        }
    }

        echo json_encode($results);

    } catch (Exception $e) {
            // Log error dar returnează JSON valid
            error_log("Search API Error: " . $e->getMessage());
            echo json_encode([]);
    }

?>