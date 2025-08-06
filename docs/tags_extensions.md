# Extensii pentru Sistemul de Tags

## Actualizări Schema Bază de Date

### Tabela `tags`
Schema tabelei `tags` a fost extinsă cu următoarele câmpuri:

```sql
CREATE TABLE `tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `description` text,                    -- NOU: Descriere opțională pentru tag
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- NOU: Data creării
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,  -- NOU: Data actualizării
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
);
```

### Tabela `article_tags`
Schema tabelei `article_tags` a fost extinsă cu:

```sql
CREATE TABLE `article_tags` (
  `article_id` int NOT NULL,
  `tag_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- NOU: Când a fost adăugat tag-ul
  `assigned_by` int DEFAULT NULL,  -- NOU: Cine a adăugat tag-ul
  PRIMARY KEY (`article_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  KEY `idx_created_at` (`created_at`),  -- NOU: Index pentru performance
  KEY `idx_assigned_by` (`assigned_by`), -- NOU: Index pentru performance
  CONSTRAINT `article_tags_assigned_by_fk` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
```

## Scripts de Migrare

### Aplicarea Migrațiilor
```bash
php scripts/migrate.php
```

### Verificarea Structurii
```bash
php scripts/check_db_structure.php
```

### Rollback (dacă e necesar)
```sql
source sql/migrations/rollback_tags_extensions.sql
```

## Funcționalități Noi

### 1. Descrieri pentru Tags
- Câmpul opțional `description` permite explicarea scopului fiecărui tag
- Afișat în interfața de administrare
- Ajută la organizarea și înțelegerea tag-urilor

### 2. Tracking Temporal
- `created_at`: Când a fost creat tag-ul
- `updated_at`: Când a fost modificat ultima dată
- Permite sortarea și filtrarea după dată

### 3. Audit Trail pentru Relații
- `assigned_by`: Cine a adăugat tag-ul la articol
- `created_at` în `article_tags`: Când a fost adăugată relația
- Permite urmărirea istoricului modificărilor

## Interface de Administrare

### Modal Actualizat
Formularul pentru adăugare/editare tag-uri include acum:
- Câmpul `name` (obligatoriu)
- Câmpul `description` (opțional)
- Validări îmbunătățite
- Mesaje de feedback

### Funcționalități API

#### Endpoint: `/api/bkd_tags_management.php`

**GET Actions:**
- `list`: Returnează toate tag-urile cu statistici și noile câmpuri
- `stats`: Statistici detaliate incluzând info temporală
- `search`: Căutare tag-uri (compatibilitate backwards)

**POST Actions:**
- `create`: Creează tag cu `name` și `description`
- `update`: Actualizează tag (setează `updated_at` automat)
- `delete`: Șterge tag (doar dacă nefolosit)
- `merge`: Îmbină două tag-uri
- `clean_unused`: Șterge toate tag-urile nefolosite

### Exemple API

#### Crearea unui Tag
```javascript
const formData = new FormData();
formData.append('action', 'create');
formData.append('name', 'PHP');
formData.append('description', 'Articole despre limbajul PHP');
formData.append('csrf_token', CSRF_TOKEN);
```

#### Actualizarea unui Tag
```javascript
formData.append('action', 'update');
formData.append('id', 123);
formData.append('name', 'PHP Programming');
formData.append('description', 'Articole avansate despre PHP');
```

## Beneficii

1. **Organizare îmbunătățită**: Descrierile help la înțelegerea scopului tag-urilor
2. **Audit complet**: Tracking cine, când și ce modificări s-au făcut
3. **Performance**: Indexuri noi pentru căutări rapide
4. **Flexibilitate**: Câmpuri opționale pentru backwards compatibility
5. **Scalabilitate**: Structură pregătită pentru funcționalități viitoare

## Compatibilitate

- Toate funcționalitățile existente rămân funcționale
- API-ul menține backwards compatibility
- Noile câmpuri sunt opționale
- Migration scripts permit rollback în caz de probleme

## Maintenance

### Cleanup Periodic
Rularea periodică a `clean_unused_tags` pentru curățarea tag-urilor neutilizate.

### Monitorizare
Utilizarea câmpurilor `created_at` și `updated_at` pentru monitorizarea activității.

### Backup
Înainte de aplicarea migrațiilor, se recomandă backup-ul bazei de date.
