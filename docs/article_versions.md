# Managementul Versiunilor de Articol (VoA) - Documentație Completă

## Concept
Sistemul "Versions of Article" (VoA) permite păstrarea unui istoric complet al tuturor modificărilor unui articol, oferind control, audit și restaurare pentru orice versiune. Implementarea include funcționalități avansate de role-based management, approval workflow și publication scheduling.

## Structura tabelelor

### 1. `articles`
- Conține DOAR versiunea aprobată/publicată a fiecărui articol care este disponibila online, afișată pe prima pagină
- Este sursa pentru afișarea pe site/public, listări, etc.
- Câmpuri relevante: `id`, `title`, `content`, `category_id`, `user_id`, `status`, `version`, `publish_at`, `created_at`, `updated_at`

### 2. `article_versions`
- Conține TOATE versiunile unui articol (draft, pending, approved).
- Orice operație de editare, salvare ca draft, trimitere spre aprobare, restaurare, etc. se face aici.
- **Câmpuri:** 
  - `id` - Primary key
  - `article_id` - Foreign key către `articles.id`
  - `version_number` - Numărul versiunii (incrementat automat)
  - `title` - Titlul versiunii
  - `content` - Conținutul versiunii
  - `category_id` - Categoria versiunii (poate fi diferită între versiuni)
  - `author_id` - ID-ul autorului versiunii
  - `status` - ENUM('draft','pending','approved') 
  - `is_online` - TINYINT(1) - indică dacă versiunea este publicată (1) sau nu (0)
  - `change_note` - Nota explicativă pentru modificare
  - `created_at`, `updated_at` - Timestamps

## Reguli Oficiale de Versioning

### Regula 1: Editare și salvare ca Draft/Pending
**NU se generează versiune nouă** la editarea și salvarea unui articol ca draft sau pending.

**EXCEPȚIA UNICĂ:** Dacă articolul modificat (editat) este cel online (cel din tabela `articles`), cel afișat pe prima pagină, atunci se va genera o versiune nouă în tabela `article_versions`.

### Regula 2: Generarea versiunilor noi
Versiunea nouă a unui articol se generează **DOAR în momentul aprobării (approve)**.

**Excepție:** Vezi Regula 5 pentru editarea articolului online.

### Regula 3: Publicarea (publish) articolelor
Publicarea unui articol este o **acțiune separată** și constă în copierea articolului aprobat în tabela `articles`:
- **Peste versiunea deja existentă** (dacă articolul a fost publicat anterior)
- **Se creează o înregistrare nouă** dacă articolul nu a fost publicat (articol nou)

### Regula 4: Editarea versiunilor aprobate
Editarea unei versiuni de articol deja aprobată va:
- **Scoate versiunea din statusul Approved**
- **Trece automat în Draft sau Pending** (în funcție de butonul apăsat de user în Edit Form)
- **Se va relua ciclul de aprobare** pentru acea versiune

### Regula 5: Editarea articolului online
Dacă articolul care se editează este cel online (cel din tabela `articles`, cel afișat pe prima pagină), atunci **se va genera o noua versiune în tabela `article_versions`**.

## Implementarea Regulilor în Cod

### 1. **Logica de Editare (edit_article)**

```php
if ($action === 'edit_article') {
    $articleId = (int)($_POST['article_id'] ?? 0);
    $baseVersion = (int)($_POST['base_version'] ?? 1);
    $isEditingOnlineVersion = (int)($_POST['is_editing_online_version'] ?? 0);
    
    // Preluare date din form
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $status = $_POST['submit_type'] === 'draft' ? 'draft' : 'pending';
    $user_id = $_SESSION['user']['id'];
    $change_note = trim($_POST['change_note'] ?? '');

    if ($isEditingOnlineVersion === 1) {
        // REGULA 5: Editarea articolului online → generează versiune nouă
        
        $lastVersion = $db->fetchSingle("SELECT MAX(version_number) as v FROM article_versions WHERE article_id = ?", [$articleId]);
        $nextVersion = ($lastVersion && $lastVersion['v']) ? $lastVersion['v'] + 1 : 1;
        
        // Creează noua versiune în article_versions (NU este online)
        $db->query("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, is_online, created_at, updated_at, change_note) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)",
            [$articleId, $nextVersion, $title, $clean_content, $category_id, $user_id, $status, $edit_note]);

        // NU actualizează tabelul articles - versiunea online rămâne neschimbată
        
    } else {
        // REGULA 1: Editarea unei versiuni care NU este online
        // REGULA 4: Editarea unei versiuni aprobate o scoate din statusul Approved
        
        // Actualizează versiunea existentă în article_versions
        $db->query("UPDATE article_versions SET title = ?, content = ?, category_id = ?, status = ?, updated_at = NOW(), change_note = ? WHERE article_id = ? AND version_number = ?",
            [$title, $clean_content, $category_id, $status, $change_note, $articleId, $baseVersion]);
    }
}
```

### 2. **Logica de Aprobare (approve)**

```php
if ($action === 'approve') {
    // REGULA 2: Versiunea nouă se generează DOAR la aprobare
    
    $articleId = (int)($_POST['article_id'] ?? 0);
    $version = (int)($_POST['version'] ?? 1);
    $publishAt = $_POST['publish_at'] ?? null;
    
    // Obține datele versiunii care se aprobă
    $versionData = $db->fetchSingle("SELECT * FROM article_versions WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
    
    // Marchează versiunea ca approved în article_versions
    $db->query("UPDATE article_versions SET status = 'approved', updated_at = NOW() WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
    
    // REGULA 3: Publicarea = copierea în tabelul articles
    
    // Marchează vechea versiune online ca non-online
    $db->query("UPDATE article_versions SET is_online = 0 WHERE article_id = ? AND is_online = 1", [$articleId]);
    
    // Marchează noua versiune ca online
    $db->query("UPDATE article_versions SET is_online = 1 WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
    
    // Actualizează tabelul articles cu datele versiunii aprobate (REGULA 3)
    $db->query("UPDATE articles SET title = ?, content = ?, category_id = ?, status = 'approved', version = ?, publish_at = ?, updated_at = NOW() WHERE id = ?", 
        [$versionData['title'], $versionData['content'], $versionData['category_id'], $version, $publishAt, $articleId]);
}
```

### 3. **Logica de Creare Articol Nou**

```php
if ($action === 'add_article') {
    // Inserează articolul în tabelul articles
    $stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at, created_at, updated_at, version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at, $created_at, $created_at]);
    $aid = $db->lastInsertId();

    // Salvează versiunea inițială în article_versions (v1) - nu este online încă
    $db->query("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, is_online, created_at, updated_at, change_note) VALUES (?, 1, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)",
        [$aid, $title, $clean_content, $category_id, $user_id, $status, $initial_note]);
}
```

## Scenarii de Utilizare Detaliați

### Scenariul 1: Editare articol non-online (Regula 1)
**Situația:** Un utilizator editează versiunea 3 care NU este online (`is_online = 0`)
- **Status inițial:** Draft/Pending/Approved
- **Acțiune:** Editare + salvare ca Draft/Pending
- **Rezultat:** Versiunea 3 este actualizată direct
- **Reguli aplicate:** Regula 1 + eventual Regula 4 (dacă era Approved)

### Scenariul 2: Editare articol online (Regula 5)
**Situația:** Un utilizator editează versiunea 2 care ESTE online (`is_online = 1`)
- **Status inițial:** Approved (forțat, pentru că este online)
- **Acțiune:** Editare + salvare ca Draft/Pending
- **Rezultat:** Se creează versiunea 4 cu statusul ales, versiunea 2 rămâne online
- **Reguli aplicate:** Regula 5

### Scenariul 3: Aprobare versiune (Regula 2 + 3)
**Situația:** Un moderator aprobă versiunea 4 care este în Pending
- **Status inițial:** Pending
- **Acțiune:** Approve cu dată de publicare
- **Rezultat:** 
  - Versiunea 4 devine Approved și `is_online = 1`
  - Versiunea 2 devine `is_online = 0`
  - Tabelul `articles` este actualizat cu datele versiunii 4
- **Reguli aplicate:** Regula 2 + Regula 3

### Scenariul 4: Editare versiune aprobată non-online (Regula 4)
**Situația:** Un utilizator editează versiunea 3 care este Approved dar `is_online = 0`
- **Status inițial:** Approved
- **Acțiune:** Editare + salvare ca Draft
- **Rezultat:** Versiunea 3 devine Draft și este actualizată
- **Reguli aplicate:** Regula 1 + Regula 4

## Fluxuri de Lucru Tipice

### Flux 1: Articol nou → Publicare
1. **Creare:** Se creează versiunea 1 în ambele tabele (`is_online = 0`)
2. **Editare:** Se editează versiunea 1 de mai multe ori (Regula 1)
3. **Aprobare:** Se aprobă versiunea 1 → devine online (Regula 2 + 3)

### Flux 2: Actualizare articol publicat
1. **Editare online:** Se editează versiunea online → se creează versiunea 2 (Regula 5)
2. **Dezvoltare:** Se editează versiunea 2 de mai multe ori (Regula 1)
3. **Aprobare:** Se aprobă versiunea 2 → devine online (Regula 2 + 3)
4. **Versiunea anterioară:** Versiunea 1 rămâne în istoric ca non-online

### Flux 3: Revenire la versiune anterioară
1. **Editare approve:** Se editează o versiune approved non-online (Regula 4)
2. **Dezvoltare:** Se editează versiunea de mai multe ori (Regula 1)
3. **Aprobare:** Se aprobă versiunea → devine online (Regula 2 + 3)

## Avantajele Acestui Sistem

### 1. **Stabilitate versiunii online**
- Versiunea afișată pe site rămâne stabilă în timpul dezvoltării
- Utilizatorii nu văd conținut incomplet sau în testare

### 2. **Flexibilitate în dezvoltare**
- Dezvoltatorii pot lucra pe versiuni separate fără să afecteze versiunea live
- Pot fi testate multiple versiuni în paralel

### 3. **Control editorial complet**
- Editorialii controlează exact ce se publică și când
- Istoricul complet al modificărilor este păstrat

### 4. **Workflow simplu**
- Regulile sunt clare și consistente
- Fiecare acțiune are un rezultat predictibil

## Frontend JavaScript Implications

### Determinarea tipului de editare:
```javascript
// În openEditArticleModal
fetch(`../api/bkd_articles.php?action=get_version&id=${articleId}&version=${targetVersion}`)
    .then(res => res.json())
    .then(data => {
        // Folosește direct câmpul is_online pentru a determina tipul editării
        form.setAttribute('data-is-online-version', data.is_online ? 1 : 0);
        
        console.log(data.is_online ? 
            'Editare versiune ONLINE → va crea versiune nouă (Regula 5)' : 
            'Editare versiune NON-ONLINE → va actualiza versiunea existentă (Regula 1)');
    });
```

### Feedback pentru utilizator:
```javascript
// În submitArticle2
.then(data => {
    if (data.success) {
        if (data.was_editing_online_version) {
            alert(`A fost creată versiunea ${data.new_version} bazată pe versiunea online ${data.base_version}`);
        } else {
            alert(`Versiunea ${data.base_version} a fost actualizată`);
        }
    }
});
```

## Debugging și Verificări

### Verificare aplicare corectă a regulilor:
```sql
-- Verifică că nu există mai mult de o versiune online per articol
SELECT article_id, COUNT(*) as online_count 
FROM article_versions 
WHERE is_online = 1 
GROUP BY article_id 
HAVING online_count > 1;

-- Verifică consistența între articles și article_versions
SELECT a.id, a.version as published_version, 
       av.version_number, av.is_online, av.status
FROM articles a 
LEFT JOIN article_versions av ON a.id = av.article_id AND av.is_online = 1
WHERE a.version != av.version_number OR av.version_number IS NULL;

-- Verifică că versiunile online sunt întotdeauna approved
SELECT * FROM article_versions 
WHERE is_online = 1 AND status != 'approved';
```

## Restricții și Validări

### 1. **Validări Backend**
- Versiunea online trebuie să fie întotdeauna `approved`
- Exact o versiune online per articol
- Versiunile sunt incrementale și consecutive

### 2. **Validări Frontend**
- Afișarea corectă a indicatorilor pentru versiunea online
- Mesaje clare despre tipul de editare care se va face
- Restricții UI bazate pe regulile de versioning

### 3. **Integritate Referențială**
- Toate versiunile aparțin unui articol valid
- Tagurile sunt asociate la nivel de articol, nu versiune
- Autorizațiile se verifică la nivel de versiune



1. Editarea și Salvarea unui articol
Cazul general: Când un utilizator editează un articol și îl salvează ca "Draft" sau "Pending", nu se creează o nouă versiune. Modificările sunt aplicate direct pe înregistrarea existentă.

Excepția: Dacă articolul editat este cel online (cel din tabela articles), se generează automat o nouă versiune în tabela article_versions. Aceasta asigură că versiunea publicată nu este suprascrisă accidental.

2. Aprobarea (Approve)
Punctul de generare a versiunii: O nouă versiune a articolului este generată doar în momentul în care un articol este aprobat.

Acțiunea: Când un articol este aprobat, sistemul ia conținutul actual și creează o înregistrare separată în tabela article_versions, marcând-o ca fiind aprobată.

3. Publicarea (Publish)
Acțiune separată: Publicarea este o acțiune distinctă de aprobare.

Copierea conținutului: Articolul aprobat este copiat din tabela article_versions în tabela articles, care stochează conținutul publicat, vizibil pe prima pagină a site-ului.

Logică:

Dacă articolul există deja în articles, acesta va fi suprascris cu noua versiune aprobată.

Dacă este un articol nou, se va crea o înregistrare nouă în articles.

4. Editarea unei versiuni aprobate
Schimbarea stării: Când un utilizator editează o versiune deja aprobată, acea versiune își pierde statutul de "Approved".

Noua stare: Articolul intră automat în starea "Draft" sau "Pending", în funcție de butonul apăsat de utilizator în formularul de editare.

Reluarea ciclului: După editare, versiunea modificată trebuie să parcurgă din nou ciclul de aprobare pentru a putea fi publicată.

---


**Ultima actualizare:** August 28, 2025  
**Status:** Documentație completă conform regulilor oficiale de versioning  
**Versiune:** 4.0 - Official Versioning Rules Implementation