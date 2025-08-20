# Sistema de Badges și Gamification - MyKDB

## 📋 **Cuprins**

1. [Prezentare generală](#prezentare-generală)
2. [Arhitectura sistemului](#arhitectura-sistemului)
3. [Structura bazei de date](#structura-bazei-de-date)
4. [Clasa Gamification](#clasa-gamification)
5. [Funcții helper](#funcții-helper)
6. [Acțiuni cu puncte](#acțiuni-cu-puncte)
7. [Sistem de badge-uri](#sistem-de-badge-uri)
8. [Interfața utilizator](#interfața-utilizator)
9. [API-uri și integrare](#api-uri-și-integrare)
10. [Configurare și implementare](#configurare-și-implementare)
11. [Troubleshooting](#troubleshooting)

---

## 🎯 **Prezentare generală**

Sistemul de badges și gamification din MyKDB este conceput pentru a încuraja participarea utilizatorilor și a îmbunătăți experiența în platforma de knowledge base. Sistemul acordă puncte pentru diverse acțiuni și oferă badge-uri pentru realizări specifice.

### **Obiective principale:**
- Încurajarea creării de conținut de calitate
- Stimularea interacțiunii în comunitate
- Recompensarea utilizatorilor activi
- Oferirea unei experiențe engaging

### **Componente principale:**
- **Sistem de puncte (XP)** - pentru acțiuni zilnice
- **Badge-uri** - pentru realizări specifice
- **Niveluri utilizator** - progresie pe baza punctelor
- **Leaderboard** - clasament utilizatori
- **Notificări** - feedback instant

---

## 🏗️ **Arhitectura sistemului**

```
┌─────────────────────────────────────────────────────────────┐
│                    GAMIFICATION SYSTEM                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐    ┌─────────────────┐                │
│  │   User Actions  │────│ Points System   │                │
│  │   - Articles    │    │ - Add Points    │                │
│  │   - Comments    │    │ - Calculate XP  │                │
│  │   - Reading     │    │ - Update Level  │                │
│  │   - Login       │    └─────────────────┘                │
│  └─────────────────┘             │                         │
│           │                      │                         │
│           │     ┌─────────────────┐                        │
│           └─────│ Badge System    │                        │
│                 │ - Check Rules   │                        │
│                 │ - Award Badges  │                        │
│                 │ - Notifications │                        │
│                 └─────────────────┘                        │
│                          │                                 │
│                 ┌─────────────────┐                        │
│                 │   Database      │                        │
│                 │ - user_points   │                        │
│                 │ - badges        │                        │
│                 │ - user_badges   │                        │
│                 │ - points_history│                        │
│                 └─────────────────┘                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 🗄️ **Structura bazei de date**

### **Tabelul `badges`**
Stochează toate badge-urile disponibile în sistem.

```sql
CREATE TABLE badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    category ENUM('content', 'engagement', 'consumption', 'special') DEFAULT 'content',
    points_reward INT DEFAULT 0,
    conditions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Câmpuri importante:**
- `name` - Numele badge-ului (ex: "First Steps")
- `conditions` - Condițiile pentru obținerea badge-ului în format JSON
- `points_reward` - Punctele bonus acordate la obținerea badge-ului
- `category` - Categoria badge-ului pentru organizare

### **Tabelul `user_points`**
Stochează punctele totale și nivelul pentru fiecare utilizator.

```sql
CREATE TABLE user_points (
    user_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    level VARCHAR(50) DEFAULT 'Rookie',
    streak_days INT DEFAULT 0,
    last_activity_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### **Tabelul `user_badges`**
Asociază badge-urile cu utilizatorii care le-au obținut.

```sql
CREATE TABLE user_badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    badge_id INT,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress JSON,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (user_id, badge_id)
);
```

### **Tabelul `points_history`**
Înregistrează istoricul tuturor punctelor acordate.

```sql
CREATE TABLE points_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    points INT,
    description TEXT,
    related_id INT,
    related_type ENUM('article', 'comment', 'user', 'system') DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 🔧 **Clasa Gamification**

Locație: `classes/gamification.php`

### **Metode principale:**

#### `addPoints($userId, $points, $action, $description, $relatedId, $relatedType)`
Adaugă puncte pentru un utilizator și verifică badge-urile.

```php
/**
 * Adaugă puncte pentru un utilizator
 * @param int $userId - ID-ul utilizatorului
 * @param int $points - Numărul de puncte de adăugat
 * @param string $action - Tipul acțiunii (ex: 'article_published')
 * @param string $description - Descrierea acțiunii
 * @param int $relatedId - ID-ul obiectului asociat (opțional)
 * @param string $relatedType - Tipul obiectului asociat
 * @return bool - Succes/eșec
 */
```

#### `calculateLevel($points)`
Calculează nivelul utilizatorului pe baza punctelor.

**Niveluri disponibile:**
- `Rookie` (0-99 puncte)
- `Explorer` (100-299 puncte)
- `Contributor` (300-699 puncte)
- `Expert` (700-1499 puncte)
- `Master` (1500-2999 puncte)
- `Legend` (3000+ puncte)

#### `checkAndAwardBadges($userId)`
Verifică automat toate badge-urile disponibile și le acordă utilizatorului dacă îndeplinește condițiile.

#### `getUserPoints($userId)`
Returnează informațiile despre punctele utilizatorului.

#### `getUserBadges($userId)`
Returnează toate badge-urile obținute de utilizator.

---

## 🛠️ **Funcții helper**

Locație: `includes/gamification_helpers.php`

### **Funcții de bază:**

```php
// Funcție generală pentru acordarea punctelor
awardPoints($userId, $points, $action, $description, $relatedId, $relatedType)

// Funcții specifice pentru acțiuni
awardArticlePublished($userId, $articleId, $title)    // +50 puncte
awardCommentAdded($userId, $commentId, $articleTitle) // +5 puncte
awardArticleRead($userId, $articleId, $title)         // +1 punct
awardProfileCompleted($userId)                        // +25 puncte
awardDailyLogin($userId)                              // +1 punct

// Funcție pentru statistici
getUserGameStats($userId)
```

### **Exemplu de utilizare:**
```php
// Când se publică un articol
if ($articlePublished) {
    awardArticlePublished($_SESSION['user']['id'], $articleId, $articleTitle);
}

// Când se adaugă un comentariu
if ($commentSaved) {
    awardCommentAdded($_SESSION['user']['id'], $commentId, $articleTitle);
}
```

---

## 🎯 **Acțiuni cu puncte**

### **Tabel cu punctaje:**

| Acțiune | Puncte | Descriere |
|---------|--------|-----------|
| Article Published | +50 | Publicarea unui articol |
| Comment Added | +5 | Adăugarea unui comentariu |
| Article Read | +1 | Citirea completă a unui articol |
| Daily Login | +1 | Login zilnic (o dată pe zi) |
| Profile Completed | +25 | Completarea profilului |
| Badge Earned | +variabil | Puncte bonus pentru badge-uri |

### **Implementări specifice - Tracking complet:**

#### **1. Puncte pentru articole publicate (aprobate)**
**Locație:** `public/approve_article.php`

```php
// La aprobarea articolului de către admin
if ($action === 'approve') {
    $db->query("UPDATE articles SET status = 'published' WHERE id = ?", [$articleId]);
    
    // Award points for article publication
    $article = $db->fetchSingle("SELECT user_id, title FROM articles WHERE id = ?", [$articleId]);
    if ($article) {
        awardArticlePublished($article['user_id'], $articleId, $article['title']);
    }
    
    sendNotification($article['user_id'], 'success', 'Your article has been approved and published');
    logActivity($_SESSION['user']['id'], 'approve_article', 'Article approved: ' . $article['title']);
    echo json_encode(['success' => true]);
}
```

**Tracking detaliat:**
- **Trigger:** Când admin-ul aprobă un articol
- **Puncte:** +50 pentru autor
- **Verificare:** Punctele se acordă o singură dată per articol
- **Notificare:** Utilizatorul primește notificare la aprobare

#### **2. Puncte pentru comentarii (la aprobare)**
**Locație:** `public/api/bkd_comments.php`

```php
// La aprobarea comentariului de către admin
if ($action === 'approve') {
    if ($comment['status'] === 'approved') {
        echo json_encode(['error' => 'Comentariul este deja aprobat']);
        exit;
    }
    
    $db->query("UPDATE article_comments SET status = 'approved' WHERE id = ?", [$commentId]);

    // Get article title for context
    $articleData = $db->fetchSingle("SELECT title FROM articles WHERE id = ?", [$comment['article_id']]);
    $articleTitle = $articleData ? $articleData['title'] : 'Unknown Article';
    
    // Award points for comment approval
    awardCommentAdded($comment['user_id'], $commentId, $articleTitle);
    
    sendNotification($comment['user_id'], 'info', 'Your comment has been approved');
    logActivity($_SESSION['user']['id'], 'approve_comment', 'Comment approved');
    echo json_encode(['success' => true]);
}
```

**Tracking detaliat:**
- **Trigger:** Când admin-ul aprobă un comentariu
- **Puncte:** +5 pentru autorul comentariului
- **Context:** Include titlul articolului pentru referință
- **Verificare:** Doar comentarii aprobate primesc puncte

#### **3. Puncte pentru citirea articolelor**
**Locație:** `public/view_article.php` + `public/api/bkd_award_reading_points.php`

**În view_article.php (tracking JavaScript):**
```php
<?php
// JavaScript pentru tracking citirea articolului
if (isset($_SESSION['user']['id'])) {
    echo '<script>
    // Tracking pentru citirea completă a articolului
    let readStartTime = Date.now();
    let hasAwarded = false;
    
    // Metodă 1: Verifică dacă utilizatorul a citit cel puțin 30 de secunde
    setTimeout(function() {
        if (!hasAwarded) {
            fetch("api/bkd_award_reading_points.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "article_id=' . $articleId . '&csrf_token=' . $_SESSION['csrf_token'] . '"
            });
            hasAwarded = true;
        }
    }, 30000); // 30 secunde
    
    // Metodă 2: Când utilizatorul ajunge la sfârșitul articolului
    window.addEventListener("scroll", function() {
        if (!hasAwarded && (window.innerHeight + window.scrollY) >= document.body.offsetHeight - 100) {
            fetch("api/bkd_award_reading_points.php", {
                method: "POST", 
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "article_id=' . $articleId . '&csrf_token=' . $_SESSION['csrf_token'] . '"
            });
            hasAwarded = true;
        }
    });
    </script>';
}
?>
```

**API bkd_award_reading_points.php:**
```php
<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

// Verifică autentificarea
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Verifică CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token invalid']);
    exit;
}

$userId = $_SESSION['user']['id'];
$articleId = (int)($_POST['article_id'] ?? 0);

if (!$articleId) {
    http_response_code(400);
    echo json_encode(['error' => 'Article ID required']);
    exit;
}

$db = new Database();

// Verifică dacă utilizatorul a primit deja puncte pentru acest articol
$alreadyAwarded = $db->fetchSingle(
    "SELECT id FROM points_history WHERE user_id = ? AND action = 'article_read' AND related_id = ?",
    [$userId, $articleId]
);

if ($alreadyAwarded) {
    echo json_encode(['success' => true, 'message' => 'Points already awarded']);
    exit;
}

// Verifică dacă articolul există și este publicat
$article = $db->fetchSingle(
    "SELECT title FROM articles WHERE id = ? AND status = 'published'",
    [$articleId]
);

if (!$article) {
    http_response_code(404);
    echo json_encode(['error' => 'Article not found']);
    exit;
}

// Acordă puncte pentru citire
awardArticleRead($userId, $articleId, $article['title']);

echo json_encode(['success' => true, 'message' => 'Points awarded']);
?>
```

**Tracking detaliat:**
- **Trigger:** Citirea completă (30 secunde SAU scroll la final)
- **Puncte:** +1 pentru cititor
- **Verificare:** O singură acordare per utilizator per articol
- **Condiții:** Doar pentru articole publicate
- **Securitate:** CSRF protection și validare autentificare

---

## 📊 **Sistemul de tracking puncte**

### **Monitorizarea acordării punctelor**

Sistemul de gamification include un tracking complet pentru toate acțiunile care acordă puncte:

#### **Tabelul de tracking - points_history**
Toate punctele acordate sunt înregistrate în detaliu:

```sql
SELECT 
    ph.id,
    u.username,
    ph.action,
    ph.points,
    ph.description,
    ph.related_id,
    ph.related_type,
    ph.created_at
FROM points_history ph
JOIN users u ON u.id = ph.user_id
ORDER BY ph.created_at DESC;
```

#### **Statistici puncte per utilizator**

```sql
-- Puncte totale per utilizator
SELECT 
    u.username,
    up.total_points,
    up.level,
    COUNT(ph.id) as total_actions
FROM users u
LEFT JOIN user_points up ON up.user_id = u.id
LEFT JOIN points_history ph ON ph.user_id = u.id
GROUP BY u.id
ORDER BY up.total_points DESC;

-- Breakdown per tip de acțiune
SELECT 
    ph.action,
    COUNT(*) as count,
    SUM(ph.points) as total_points,
    AVG(ph.points) as avg_points
FROM points_history ph
GROUP BY ph.action
ORDER BY total_points DESC;
```

#### **Verificări de integritate**

```sql
-- Verifică consistența punctelor
SELECT 
    u.id,
    u.username,
    up.total_points as stored_points,
    COALESCE(SUM(ph.points), 0) as calculated_points,
    (up.total_points - COALESCE(SUM(ph.points), 0)) as difference
FROM users u
LEFT JOIN user_points up ON up.user_id = u.id
LEFT JOIN points_history ph ON ph.user_id = u.id
GROUP BY u.id, up.total_points
HAVING difference != 0;
```

### **Locații complete de acordare puncte:**

| Locație | Acțiune | Puncte | Trigger | Frecvență |
|---------|---------|--------|---------|-----------|
| `approve_article.php` | Article Published | +50 | Admin aprobă articol | O dată per articol |
| `bkd_comments.php` | Comment Added | +5 | Admin aprobă comentariu | O dată per comentariu |
| `bkd_award_reading_points.php` | Article Read | +1 | 30sec SAU scroll final | O dată per utilizator per articol |
| `bkd_login.php` | Daily Login | +1 | Login zilnic | O dată pe zi |
| `profile.php` | Profile Completed | +25 | Completare profil | O dată per utilizator |

### **Fluxul de acordare puncte:**

```
1. Acțiune utilizator
   ↓
2. Verificare condiții (autentificare, validare)
   ↓
3. Verificare dublură (evită acordarea multiplă)
   ↓
4. Apelare funcție helper (ex: awardArticleRead)
   ↓
5. Gamification::addPoints()
   ↓
6. Înregistrare în points_history
   ↓
7. CALCULARE AUTOMATĂ total_points:
   - Se face SUM pe points_history pentru utilizator
   - Se actualizează user_points.total_points
   - Se actualizează users.total_points (pentru acces rapid)
   ↓
8. Calculare și actualizare nivel (level)
   ↓
9. Verificare și acordare badge-uri
   ↓
10. Notificare utilizator (dacă aplicabil)
```

### **📊 Calcularea total_points:**

**Locația:** `classes/gamification.php` - metoda `updateUserTotalPoints()`

```php
private function updateUserTotalPoints($userId) {
    // Calculează totalul punctelor din istoricul complet
    $totalPoints = $this->db->fetchSingle(
        "SELECT COALESCE(SUM(points), 0) as total FROM points_history WHERE user_id = ?",
        [$userId]
    )['total'] ?? 0;
    
    // Determină nivelul pe baza punctelor
    $level = $this->calculateLevel($totalPoints);
    
    // Actualizează în user_points (tabelul principal de gamification)
    $this->db->query(
        "INSERT INTO user_points (user_id, total_points, level, last_activity_date) 
         VALUES (?, ?, ?, CURDATE()) 
         ON DUPLICATE KEY UPDATE 
         total_points = ?, level = ?, last_activity_date = CURDATE()",
        [$userId, $totalPoints, $level, $totalPoints, $level]
    );
    
    // Actualizează și în tabelul users pentru acces rapid
    $this->db->query(
        "UPDATE users SET total_points = ?, user_level = ? WHERE id = ?",
        [$totalPoints, $level, $userId]
    );
}
```

**Principiul de funcționare:**
- **Source of Truth:** Tabelul `points_history` (toate punctele acordate)
- **Calculare dinamică:** `SUM(points)` din `points_history` pentru utilizator
- **Dublă stocare:** Rezultatul se salvează în `user_points.total_points` ȘI `users.total_points`
- **Consistență:** La fiecare acordare de puncte se recalculează totalul complet
- **Performance:** `users.total_points` oferă acces rapid fără SUM query

### **🔄 Sincronizarea datelor:**

**Redundanța controlată:**
- `points_history` = Sursa primară de adevăr (toate punctele acordate)
- `user_points.total_points` = Cache calculat pentru rapoarte și analize
- `users.total_points` = Cache calculat pentru afișare rapidă în interfață

**Avantaje:**
- **Integritatea datelor:** Totalul se recalculează complet de fiecare dată
- **Audit trail:** Istoricul complet al punctelor în `points_history`
- **Performance:** Acces rapid la totaluri fără query-uri complexe
- **Flexibilitate:** Poți modifica punctajele și se recalculează automat

### **Debugging și monitorizare:**

#### **Log-uri pentru tracking:**
```php
// În funcțiile helper
error_log("GAMIFICATION: User {$userId} awarded {$points} points for {$action}");

// În clasa Gamification
error_log("GAMIFICATION: Total points for user {$userId}: {$newTotal}");
```

#### **Verificare rapidă puncte utilizator:**
```sql
-- Ultimele 10 acțiuni pentru un utilizator
SELECT * FROM points_history 
WHERE user_id = ? 
ORDER BY created_at DESC 
LIMIT 10;

-- Puncte pe zi pentru ultimele 7 zile
SELECT 
    DATE(created_at) as date,
    COUNT(*) as actions,
    SUM(points) as daily_points
FROM points_history 
WHERE user_id = ? 
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

---

## 🏆 **Sistem de badge-uri**

### **Badge-uri predefinite:**

```sql
INSERT INTO badges (name, description, icon, category, points_reward, conditions) VALUES
('First Steps', 'Published your first article successfully', 'icon-create-article.svg', 'content', 50, '{"articles_published": 1}'),
('Helpful Commenter', 'Added your first comment', 'icon-add.svg', 'engagement', 10, '{"comments_made": 1}'),
('Curious Reader', 'Read your first 10 articles', 'icon-view.svg', 'consumption', 25, '{"articles_read": 10}'),
('Rising Writer', 'Published 5 articles', 'icon-docs.svg', 'content', 100, '{"articles_published": 5}'),
('Social Butterfly', 'Added 10 comments', 'icon-bell.svg', 'engagement', 75, '{"comments_made": 10}'),
('Knowledge Seeker', 'Read 25 articles', 'icon-search.svg', 'consumption', 50, '{"articles_read": 25}');
```

### **Categorii de badge-uri:**

#### **Content (Conținut)**
- Badge-uri pentru crearea de articole
- Focalizate pe calitatea și cantitatea conținutului

#### **Engagement (Implicare)**
- Badge-uri pentru interacțiuni sociale
- Comentarii, like-uri, participare la discuții

#### **Consumption (Consum)**
- Badge-uri pentru citirea și explorarea conținutului
- Încurajează consumul de knowledge

#### **Special (Speciale)**
- Badge-uri pentru realizări unice
- Evenimente speciale, milestone-uri importante

### **Condițiile badge-urilor**

Badge-urile folosesc un sistem JSON pentru definirea condițiilor:

```json
{
    "articles_published": 5,
    "comments_made": 10,
    "articles_read": 25
}
```

**Tipuri de condiții suportate:**
- `articles_published` - Numărul de articole publicate
- `comments_made` - Numărul de comentarii adăugate
- `articles_read` - Numărul de articole citite
- (Se pot adăuga altele în viitor)

---

## 🎨 **Interfața utilizator**

### **Display badge-uri în profil:**

```html
<div class="user-badges">
    <h3>Badges Earned (<?= count($badges) ?>/<?= $totalBadges ?>)</h3>
    <div class="badge-grid">
        <?php foreach ($badges as $badge): ?>
        <div class="badge earned">
            <img src="<?= APP_URL ?>assets/icons/<?= $badge['icon'] ?>" alt="<?= $badge['name'] ?>">
            <span><?= $badge['name'] ?></span>
            <div class="badge-description"><?= $badge['description'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
```

### **Progress bar pentru nivel:**

```html
<div class="user-level">
    <div class="level-info">
        <span class="level-name"><?= $userPoints['level'] ?></span>
        <span class="xp"><?= $userPoints['total_points'] ?> XP</span>
    </div>
    <div class="progress-bar">
        <div class="progress" style="width: <?= $progressPercent ?>%"></div>
    </div>
    <span class="next-level"><?= $pointsToNext ?> XP to next level</span>
</div>
```

### **Notificări pentru badge-uri noi:**

```javascript
function showBadgeUnlock(badge) {
    const notification = `
        <div class="badge-unlock-modal">
            <div class="badge-animation">
                <img src="${APP_URL}assets/icons/${badge.icon}" alt="${badge.name}">
            </div>
            <h2>Badge Unlocked!</h2>
            <h3>${badge.name}</h3>
            <p>${badge.description}</p>
            <div class="xp-reward">+${badge.points_reward} XP</div>
        </div>
    `;
    showModal(notification);
}
```

---

## 🔌 **API-uri și integrare**

### **API pentru punctele de citire**

**Endpoint:** `api/award_reading_points.php`

**Metoda:** POST

**Parametri:**
- `article_id` - ID-ul articolului
- `csrf_token` - Token CSRF pentru securitate

**Răspuns:**
```json
{
    "success": true,
    "message": "Points awarded"
}
```

### **Integrare în aplicație**

#### **1. În config/bootstrap.php:**
```php
require_once APP_ROOT . 'classes/gamification.php';
require_once APP_ROOT . 'includes/gamification_helpers.php';
```

#### **2. În fișierele care gestionează acțiuni:**
```php
// La publicarea unui articol
awardArticlePublished($userId, $articleId, $title);

// La adăugarea unui comentariu
awardCommentAdded($userId, $commentId, $articleTitle);

// La login
if (!$todayLogin) {
    awardDailyLogin($userId);
}
```

### **Securitate și validări**

- **CSRF Protection** - Toate API-urile verifică token-ul CSRF
- **Validare utilizator** - Verifică dacă utilizatorul este autentificat
- **Prevenirea dublării** - Verifică dacă punctele au fost deja acordate
- **Rate limiting** - O acțiune pe utilizator pe zi pentru unele tipuri

---

## ⚙️ **Configurare și implementare**

### **Pasul 1: Crearea tabelelor**

Execută scripturile SQL pentru crearea tabelelor:

```sql
-- Rulează scripturile din secțiunea "Structura bazei de date"
```

### **Pasul 2: Adăugarea coloanelor în tabelul users**

```sql
ALTER TABLE users ADD COLUMN total_points INT DEFAULT 0 AFTER email;
ALTER TABLE users ADD COLUMN user_level VARCHAR(50) DEFAULT 'Rookie' AFTER total_points;
```

### **Pasul 3: Inserarea badge-urilor**

```sql
-- Rulează scriptul de inserare badge-uri
```

### **Pasul 4: Adăugarea clasei și helper-ilor**

1. Creează `classes/gamification.php`
2. Creează `includes/gamification_helpers.php`
3. Adaugă include-urile în `config/bootstrap.php`

### **Pasul 5: Integrarea în acțiuni - IMPLEMENTAT**

Punctele au fost integrate în următoarele locații:

#### **A. Aprobarea articolelor (`public/approve_article.php`):**
```php
// La aprobarea articolului de către admin
if ($action === 'approve') {
    $db->query("UPDATE articles SET status = 'published' WHERE id = ?", [$articleId]);
    
    // Award points for article publication
    $article = $db->fetchSingle("SELECT user_id, title FROM articles WHERE id = ?", [$articleId]);
    if ($article) {
        awardArticlePublished($article['user_id'], $articleId, $article['title']);
    }
}
```

#### **B. Aprobarea comentariilor (`public/api/bkd_comments.php`):**
```php
// La aprobarea comentariului
if ($action === 'approve') {
    $db->query("UPDATE article_comments SET status = 'approved' WHERE id = ?", [$commentId]);
    
    $articleData = $db->fetchSingle("SELECT title FROM articles WHERE id = ?", [$comment['article_id']]);
    $articleTitle = $articleData ? $articleData['title'] : 'Unknown Article';
    
    awardCommentAdded($comment['user_id'], $commentId, $articleTitle);
}
```

#### **C. Citirea articolelor (`public/view_article.php` + API):**
```php
// JavaScript tracking în view_article.php
if (isset($_SESSION['user']['id'])) {
    echo '<script>
    setTimeout(function() {
        if (!hasAwarded) {
            fetch("api/bkd_award_reading_points.php", {
                method: "POST",
                body: "article_id=' . $articleId . '&csrf_token=' . $_SESSION['csrf_token'] . '"
            });
            hasAwarded = true;
        }
    }, 30000);
    </script>';
}
```

#### **D. API pentru puncte citire (`public/api/bkd_award_reading_points.php`):**
- Verifică autentificarea și CSRF token
- Previne acordarea multiplă de puncte pentru același articol
- Acordă +1 punct pentru citirea completă

#### **E. Login zilnic - IMPLEMENTAT (`public/api/bkd_login.php`):**
```php
// După autentificare cu succes
// add points for login
$todayLogin = $db->fetchSingle("SELECT id FROM points_history WHERE user_id = ? AND action = 'daily_login' AND DATE(created_at) = CURDATE()",[$userId]);   
if (!$todayLogin) {
    awardDailyLogin($userId);
}
```

**Tracking detaliat:**
- **Trigger:** La fiecare login cu succes
- **Verificare:** Doar dacă nu a primit puncte astăzi
- **Puncte:** +1 pentru login zilnic
- **Frecvență:** Maxim o dată pe zi per utilizator

#### **4. Puncte pentru profilul complet - IMPLEMENTAT**
**Locație:** `public/profile.php` + `includes/gamification_helpers.php`

**Funcții helper adăugate:**
```php
/**
 * Verifică dacă profilul utilizatorului este complet
 */
function isProfileComplete($userId) {
    $db = new Database();
    $user = $db->fetchSingle("SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ?", [$userId]);
    
    if (!$user) return false;
    
    // Profilul este considerat complet dacă are:
    // - first_name (nu gol)
    // - last_name (nu gol) 
    // - email (nu gol)
    // - profile_picture (nu null)
    
    return !empty(trim($user['first_name'])) &&
           !empty(trim($user['last_name'])) &&
           !empty(trim($user['email'])) &&
           !empty($user['profile_picture']);
}

/**
 * Verifică și acordă puncte pentru profilul complet
 */
function checkAndAwardProfileCompletion($userId) {
    // Verifică dacă a primit deja puncte pentru profilul complet
    $db = new Database();
    $alreadyAwarded = $db->fetchSingle(
        "SELECT id FROM points_history WHERE user_id = ? AND action = 'profile_completed'",
        [$userId]
    );
    
    // Dacă nu a primit puncte și profilul este complet, acordă puncte
    if (!$alreadyAwarded && isProfileComplete($userId)) {
        awardProfileCompleted($userId);
        return true;
    }
    
    return false;
}
```

**Implementare în profile.php:**
```php
// După actualizarea profilului cu poză
$db->query("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_picture = ? WHERE id = ?", [
    $firstName, $lastName, $email, $filename, $userId]);

// Verifică și acordă puncte pentru profilul complet
checkAndAwardProfileCompletion($userId);

// După actualizarea profilului fără poză
$db->query("UPDATE users SET first_name = ?, last_name = ?, profile_picture = ?, email = ? WHERE id = ?", [
    $firstName, $lastName, $picture, $email, $userId]);

// Verifică și acordă puncte pentru profilul complet
checkAndAwardProfileCompletion($userId);
```

**Tracking detaliat:**
- **Trigger:** După actualizarea profilului
- **Condiții pentru profil complet:** first_name + last_name + email + profile_picture (toate completate)
- **Verificare:** Doar dacă nu a primit puncte anterior
- **Puncte:** +25 pentru completarea profilului
- **Frecvență:** O singură dată per utilizator

### **Pasul 6: Adăugarea interfeței**

Integrează afișarea badge-urilor și punctelor în:
- Profilul utilizatorului
- Dashboard-ul principal
- Header-ul aplicației

---

## 🔍 **Troubleshooting**

### **Probleme comune:**

#### **1. Funcțiile gamification nu sunt recunoscute**
**Cauza:** Helper-ii nu sunt incluși în bootstrap.php
**Soluția:** 
```php
// Adaugă în config/bootstrap.php
require_once APP_ROOT . 'includes/gamification_helpers.php';
```

#### **2. Badge-urile nu se acordă automat**
**Cauza:** Condițiile JSON nu sunt corecte sau funcția checkBadgeConditions nu funcționează
**Soluția:** Verifică formatul JSON și implementarea funcției getUserStatistic()

#### **3. Punctele nu se actualizează**
**Cauza:** Erori în baza de date sau probleme cu transacțiile
**Soluția:** Verifică log-urile pentru erori SQL și asigură-te că tabelele sunt create corect

#### **4. Erori JSON în API-uri**
**Cauza:** Output HTML înainte de JSON sau erori PHP
**Soluția:** 
- Verifică că nu există echo/print înainte de header JSON
- Activează error_reporting pentru debugging
- Verifică log-urile PHP

### **Debugging:**

#### **Activează logging pentru gamification:**
```php
// În clasa Gamification, adaugă:
error_log("Gamification: Adding $points points for user $userId, action: $action");
```

#### **Verifică istoricul punctelor:**
```sql
SELECT * FROM points_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 10;
```

#### **Verifică badge-urile utilizatorului:**
```sql
SELECT b.name, ub.earned_at 
FROM user_badges ub 
JOIN badges b ON ub.badge_id = b.id 
WHERE ub.user_id = ?;
```

---

## 📈 **Extensii viitoare**

### **Funcționalități planificate:**

1. **Leaderboard săptămânal/lunar**
2. **Badge-uri sezoniere și evenimente**
3. **Sistem de achievements cu progres**
4. **Team badges pentru colaborări**
5. **Streak bonuses pentru activitate consecutivă**
6. **Marketplace pentru schimbul de puncte**
7. **Badge-uri personalizate pentru admins**

### **Optimizări tehnice:**

1. **Caching pentru badge-uri și puncte**
2. **Queue system pentru procesarea badge-urilor**
3. **Analytics dashboard pentru admins**
4. **API REST complet pentru gamification**

---

## 📝 **Concluzie**

Sistemul de badges și gamification din MyKDB oferă o bază solidă pentru încurajarea participării utilizatorilor. Arhitectura modulară permite extinderea facilă cu noi tipuri de badge-uri și acțiuni.

**Beneficii principale:**
- Creșterea engagement-ului utilizatorilor
- Încurajarea creării de conținut de calitate
- Feedback pozitiv pentru contribuții
- Experiență mai interactivă și fun

**Următorii pași:**
1. ✅ **IMPLEMENTAT:** Sistemul de tracking pentru puncte și badge-uri
2. ✅ **IMPLEMENTAT:** Acordarea punctelor pentru articole, comentarii și citire
3. ✅ **IMPLEMENTAT:** API complet pentru gamification cu securitate CSRF
4. 🔄 **ÎN PROGRES:** Integrarea afișării punctelor în interfața utilizator
5. ✅ **IMPLEMENTAT:** Implementarea daily login points
6. ✅ **IMPLEMENTAT:** Implementarea profile completion points
7. 📋 **PLANIFICAT:** Dashboard pentru monitorizarea gamification
8. 📋 **PLANIFICAT:** Extensii avansate (leaderboard, achievements)

**Status curent:** 
- ✅ **Core System:** 100% implementat și funcțional
- ✅ **Database:** Toate tabelele create și populate
- ✅ **Tracking:** Complet implementat pentru toate 5 acțiunile principale
- ✅ **API Security:** CSRF protection și validări complete
- ✅ **Documentation:** Completă și actualizată

---

**Autor:** Sistem dezvoltat pentru MyKDB Knowledge Base  
**Data:** August 2025  
**Versiune:** 1.1  
**Status:** Core implementat și funcțional - Tracking activ
