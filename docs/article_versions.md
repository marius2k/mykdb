# Managementul Versiunilor de Articol (VoA) - Documentație Completă

## Concept
Sistemul "Versions of Article" (VoA) permite păstrarea unui istoric complet al tuturor modificărilor unui articol, oferind control, audit și restaurare pentru orice versiune. Implementarea include funcționalități avansate de role-based management, approval workflow și publication scheduling.

## Structura tabelelor

### 1. `articles`
- Conține DOAR versiune aprobată/publicată a fiecărui articol care este disponibila online, afisata pe prima pagina
- Este sursa pentru afișarea pe site/public, listări, etc.
- Câmpuri relevante: `id`, `title`, `content`, `category_id`, `user_id`, `status`, `version`, `publish_at`, `created_at`, `updated_at`

### 2. `article_versions`
- Conține TOATE versiunile unui articol (draft, pending, approved, rejected).
- Orice operație de editare, salvare ca draft, trimitere spre aprobare, restaurare, etc. se face aici.
- **Câmpuri:** 
  - `id` - Primary key
  - `article_id` - Foreign key către `articles.id`
  - `version_number` - Numărul versiunii (incrementat automat)
  - `title` - Titlul versiunii
  - `content` - Conținutul versiunii
  - `category_id` - **NOU**: Categoria versiunii (poate fi diferită între versiuni)
  - `author_id` - ID-ul autorului versiunii
  - `status` - ENUM('draft','pending','approved') 
  - `change_note` - Nota explicativă pentru modificare
  - `created_at`, `updated_at` - Timestamps

## Funcționalități Implementate

### 1. **Interface Admin cu VoA**
- **Dropdown selector de versiuni** pentru fiecare articol în DataTable
- **Actualizare dinamică** a coloanelor (Title, Status, Publication Date, Actions) la schimbarea versiunii
- **Role-based Actions Column** - butonul Approve apare doar pentru moderatori/admini când versiunea este pending
- **Role-based Publication Date** - câmp editabil pentru moderatori când versiunea este pending

### 2. **Backend API Endpoints**

#### GET Endpoints:
- `?action=list` - Lista articolelor cu versiunile disponibile
- `?action=get_version&id=X&version=Y` - Datele unei versiuni specifice
- `?action=get_versions&id=X` - Toate versiunile unui articol  
- `?action=get_version_history&id=X` - Istoricul versiunilor cu autori

#### POST Endpoints:
- `action=create_article` - Creare articol nou + versiunea inițială
- `action=edit_article` - Editare (creează versiune nouă)
- `action=approve&version=X` - Aprobare versiune specifică
- `action=publish_at` - Modificare dată publicare pentru articole pending

### 3. **Fluxul de Aprobare (Advanced)**

#### Logica de Approve:
```php
// 1. Primește ID articol + versiunea specifică din frontend
$versionToApprove = $_POST['version'];

// 2. Găsește versiunea specifică din article_versions
$versionData = $db->fetchSingle("SELECT version_number, title, content, category_id, author_id 
    FROM article_versions WHERE article_id = ? AND version_number = ? AND status = 'pending'");

// 3. Verifică dacă articolul există în tabela articles
$existingArticle = $db->fetchSingle("SELECT id FROM articles WHERE id = ?", [$articleId]);

if ($existingArticle) {
    // EXISTĂ: UPDATE cu datele din versiunea aprobată
    $db->query("UPDATE articles SET title = ?, content = ?, category_id = ?, version = ?, 
        status = 'approved', updated_at = NOW() WHERE id = ?");
} else {
    // NU EXISTĂ: INSERT în tabela articles cu datele din versiune
    $db->query("INSERT INTO articles (id, title, content, category_id, user_id, version, 
        status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())");
}

// 4. Marchează versiunea ca aprobată în article_versions
$db->query("UPDATE article_versions SET status = 'approved', updated_at = NOW() 
    WHERE article_id = ? AND version_number = ?");
```

### 4. **Publication Date Management**
- **Auto-populate** cu data/ora curentă pentru versiuni pending
- **Role-based editing** - doar moderatori/admini pot modifica
- **Dynamic update** în interface la schimbarea versiunii
- **Future scheduling** - articolele pot fi programate pentru publicare în viitor

### 5. **Frontend JavaScript Functions**

#### Core Functions:
- `generateActionsForArticle(row)` - Generează actions bazate pe rol și status versiune
- `generatePublishAtForArticle(row)` - Generează Publication Date bazat pe rol și status
- `buildActionsHtml(articleId, status, publishAt, version)` - Construiește HTML pentru actions
- `buildPublishAtHtml(articleId, status, publishAt)` - Construiește HTML pentru Publication Date
- `getSelectedVersion(articleId)` - Obține versiunea selectată din dropdown
- `getVersionDataFromRow(row, versionNumber)` - Extrage datele versiunii din DataTables

#### Event Handlers:
- Change pe dropdown versiune → actualizează Title, Status, Publication Date, Actions
- Click pe Approve → trimite articleId + version specifică la backend
- Change pe Publication Date → salvează data în backend

## Flux operațional complet

### 1. **Creare articol nou**
```php
// INSERT în articles (dacă status = 'published')
$db->query("INSERT INTO articles (title, content, category_id, user_id, status, publish_at) 
    VALUES (?, ?, ?, ?, ?, ?)");

// INSERT versiunea inițială în article_versions  
$db->query("INSERT INTO article_versions (article_id, version_number, title, content, 
    category_id, author_id, status, change_note) VALUES (?, 1, ?, ?, ?, ?, ?, ?)");
```

### 2. **Editare articol existent**
```php
// UPDATE în articles doar dacă status = 'published'
if ($status === 'published') {
    $db->query("UPDATE articles SET title=?, content=?, category_id=?, publish_at=?, 
        status=?, updated_at=? WHERE id=?");
}

// INSERT nouă versiune în article_versions
$nextVersion = $currentMaxVersion + 1;
$db->query("INSERT INTO article_versions (article_id, version_number, title, content, 
    category_id, author_id, status, change_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
```

### 3. **Aprobare versiune**
- Se selectează versiunea specifică din interface
- Backend-ul copiază/actualizează datele în `articles`
- Se marchează versiunea ca `approved` în `article_versions`
- Se trimit notificări și se loggează activitatea

### 4. **Programare publicare**
- Moderatorii pot seta Publication Date în viitor pentru versiuni pending
- La aprobare, articolul va fi publicat automat la data setată
- Interface-ul arată data cu culoare diferită pentru articolele programate

## Schimbări și Migrări

### Migration 003: Add category_id to article_versions
```sql
-- Adaugă coloana category_id în article_versions
ALTER TABLE article_versions 
ADD COLUMN category_id INT DEFAULT NULL AFTER content;

-- Adaugă foreign key constraint
ALTER TABLE article_versions 
ADD CONSTRAINT fk_article_versions_category 
FOREIGN KEY (category_id) REFERENCES categories(id);

-- Actualizează înregistrările existente cu categoria din articles
UPDATE article_versions av 
JOIN articles a ON av.article_id = a.id 
SET av.category_id = a.category_id 
WHERE av.category_id IS NULL;
```

## Avantaje implementării

### 1. **Separare clară**
- Versiunea publicată vs. istoricul modificărilor
- Fiecare versiune poate avea categoria ei specifică

### 2. **Role-based Security**
- Doar moderatori/admini pot aproba articole
- Interface adaptiv în funcție de rolul utilizatorului

### 3. **Audit complet**
- Istoric complet al tuturor modificărilor
- Change notes pentru fiecare versiune
- Tracking autor pentru fiecare modificare

### 4. **Flexibility**
- Restaurare la orice versiune anterioară
- Aprobare versiune specifică (nu doar ultima)
- Programare publicare în viitor

### 5. **Performance**
- Tabelul `articles` conține doar versiuni publicate (optimizat pentru queries publice)
- Tabelul `article_versions` pentru management admin

## API Examples

### Obținere versiuni articol:
```javascript
fetch(`../api/bkd_articles.php?action=get_versions&id=${articleId}`)
    .then(res => res.json())
    .then(data => console.log(data.versions));
```

### Aprobare versiune:
```javascript
fetch('../api/bkd_articles.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=approve&article_id=${articleId}&version=${versionNumber}&csrf_token=${token}`
});
```

### Modificare Publication Date:
```javascript
fetch('../api/bkd_articles.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=publish_at&article_id=${articleId}&publish_at=${dateTime}&csrf_token=${token}`
});
```

## Rollback și Maintenance

Pentru rollback migration 003:
```bash
mysql < sql/migrations/rollback_003_add_category_id_to_article_versions.sql
```

Pentru debugging versiuni:
```sql
-- Toate versiunile unui articol
SELECT av.*, u.username as author, c.name as category 
FROM article_versions av 
LEFT JOIN users u ON av.author_id = u.id 
LEFT JOIN categories c ON av.category_id = c.id 
WHERE av.article_id = ? ORDER BY av.version_number DESC;

-- Verificare sincronizare articles vs article_versions
SELECT a.id, a.title as published_title, a.version as published_version,
       av.title as version_title, av.version_number, av.status
FROM articles a 
JOIN article_versions av ON a.id = av.article_id AND a.version = av.version_number;
```

---
**Ultima actualizare:** August 25, 2025  
**Status:** Implementare completă cu toate funcționalitățile VoA  
**Versiune:** 2.0 - Full VoA System with Role-based Management
