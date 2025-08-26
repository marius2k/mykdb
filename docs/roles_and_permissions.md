# Roluri și Permisiuni - Sistemul de Articole

## Prezentare Generală

Această documentație detaliază sistemul de roluri și permisiuni pentru gestionarea articolelor în aplicația MyKDB. Sistemul definește 6 operații principale care pot fi executate asupra articolelor, cu permisiuni specifice bazate pe rolul utilizatorului și statusul articolului.

## Operații Disponibile

Sistemul suportă următoarele 6 operații asupra articolelor:

1. **View** - Vizualizarea conținutului articolului
2. **Edit** - Editarea și modificarea articolului  
3. **History** - Accesarea istoricului versiunilor articolului
4. **Approve** - Aprobarea articolelor din stadiul Pending
5. **Disable** - Dezactivarea articolelor aprobate
6. **Delete** - Ștergerea definitivă a articolelor

## Roluri de Utilizator

Sistemul definește 5 tipuri de roluri cu nivele diferite de acces:

### 1. **Contributor** (Nivel de bază)
- Rol pentru utilizatori care contribuie cu conținut
- Acces limitat doar la propriile articole (în draft/pending)
- Nu poate aproba sau dezactiva articole

### 2. **Editor** 
- Rol pentru editarea și moderarea conținutului
- Poate edita articolele altor utilizatori în anumite stadii
- Poate aproba și dezactiva articole

### 3. **Moderator**
- Similar cu Editor, cu responsabilități de moderare
- Poate gestiona articolele în diverse stadii
- Acces extins pentru aprobare și dezactivare

### 4. **Admin**
- Acces administrativ extins
- Poate edita toate articolele indiferent de status
- Control complet asupra operațiilor de aprobare/dezactivare

### 5. **Superadmin** (Nivel maxim)
- Acces nelimitat la toate funcționalitățile
- Poate executa orice operație asupra oricărui articol
- Control complet asupra sistemului

## Statusuri Articole

Articolele pot avea unul din următoarele 3 statusuri:

- **Draft** - Articol în lucru, necomplet sau nepublicat
- **Pending** - Articol trimis pentru aprobare
- **Approved** - Articol aprobat și publicat

## Matricea de Permisiuni

### Legenda
- **(toate)** = Operația este permisă pentru toate articolele cu acel status
- **(propriu)** = Operația este permisă doar pentru articolele create de utilizatorul curent
- **N/A** = Operația nu este disponibilă pentru acel rol

---

## View (Vizualizare)

| Rol | Draft | Pending | Approved |
|-----|--------|---------|----------|
| **Contributor** | (propriu) | (propriu) | (toate) |
| **Editor** | (toate) | (toate) | (toate) |
| **Moderator** | (toate) | (toate) | (toate) |
| **Admin** | (toate) | (toate) | (toate) |
| **Superadmin** | (toate) | (toate) | (toate) |

**Explicații:**
- Contributorii pot vedea doar propriile articole draft/pending și toate cele aprobate
- Rolurile superioare (Editor+) pot vedea toate articolele indiferent de status

---

## Edit (Editare)

| Rol | Draft | Pending | Approved |
|-----|--------|---------|----------|
| **Contributor** | (propriu) | N/A | N/A |
| **Editor** | (toate) | (toate) | N/A |
| **Moderator** | (toate) | (toate) | N/A |
| **Admin** | (toate) | (toate) | (toate) |
| **Superadmin** | (toate) | (toate) | (toate) |

**Explicații:**
- Contributorii pot edita doar propriile draft-uri
- Editorii și moderatorii pot edita articolele draft și pending ale oricui
- Doar adminii și superadminii pot edita articolele aprobate

---

## History (Istoric Versiuni)

| Rol | Draft | Pending | Approved |
|-----|--------|---------|----------|
| **Contributor** | (propriu) | (propriu) | (toate) |
| **Editor** | (toate) | (toate) | (toate) |
| **Moderator** | (toate) | (toate) | (toate) |
| **Admin** | (toate) | (toate) | (toate) |
| **Superadmin** | (toate) | (toate) | (toate) |

**Explicații:**
- Logica similară cu View - contributorii la propriile draft/pending și toate aprobate
- Rolurile superioare au acces la istoricul tuturor articolelor

---

## Approve (Aprobare)

| Rol | Draft | Pending | Approved |
|-----|--------|---------|----------|
| **Contributor** | N/A | N/A | N/A |
| **Editor** | N/A | (toate) | N/A |
| **Moderator** | N/A | (toate) | N/A |
| **Admin** | N/A | (toate) | N/A |
| **Superadmin** | N/A | (toate) | N/A |

**Explicații:**
- Doar editorii, moderatorii, adminii și superadminii pot aproba articole
- Aprobarea se aplică exclusiv articolelor în stadiul Pending
- Contributorii nu au drepturi de aprobare

---

## Disable (Dezactivare)

| Rol | Draft | Pending | Approved |
|-----|--------|---------|----------|
| **Contributor** | N/A | N/A | N/A |
| **Editor** | N/A | N/A | (toate) |
| **Moderator** | N/A | N/A | (toate) |
| **Admin** | N/A | N/A | (toate) |
| **Superadmin** | N/A | N/A | (toate) |

**Explicații:**
- Dezactivarea se aplică doar articolelor aprobate
- Operația este disponibilă pentru editoare, moderatori, admini și superadmini
- Contributorii nu pot dezactiva articole

---

## Delete (Ștergere)

| Rol | Draft | Pending | Approved |
|-----|--------|---------|----------|
| **Contributor** | (propriu) | N/A | N/A |
| **Editor** | (toate) | (toate) | N/A |
| **Moderator** | (toate) | (toate) | N/A |
| **Admin** | (toate) | (toate) | (toate) |
| **Superadmin** | (toate) | (toate) | (toate) |

**Explicații:**
- Contributorii pot șterge doar propriile draft-uri
- Editorii și moderatorii pot șterge articolele draft și pending ale oricui
- Doar adminii și superadminii pot șterge articolele aprobate

---

## Implementare Tehnică

### Locație Cod
Logica de permisiuni este implementată în:
- **Fișier:** `/public/admin/articles.php`
- **Funcția principală:** `isActionEnabled(actionName, userRole, articleStatus, authorId, currentUserId)`

### Algoritmul de Verificare
1. **Verificare rol utilizator** - Se identifică rolul din sesiune
2. **Verificare status articol** - Se normalizează statusul (lowercase)
3. **Verificare proprietate** - Se compară ID-ul autorului cu utilizatorul curent
4. **Aplicare matrice** - Se returnează permisiunea bazată pe matricea de mai sus

### Icon-uri UI
Fiecare operație are un icon dedicat în interfața de administrare:
- **View:** `icon-view.svg`
- **Edit:** `icon-edit.svg` 
- **History:** `icon-history.svg`
- **Approve:** `icon-approve.svg`
- **Disable:** `icon-disable.svg`
- **Delete:** `icon-delete.svg`

Icon-urile disabled sunt afișate cu:
- `filter: grayscale(100%) brightness(0.7)`
- `opacity: 0.6`
- `cursor: not-allowed`

---

## Exemple de Utilizare

### Scenariul 1: Contributor cu Draft
Un utilizator cu rol **Contributor** are un articol în status **Draft**:
- ✅ **View** - Poate vizualiza (este propriu)
- ✅ **Edit** - Poate edita (este propriu și draft)
- ✅ **History** - Poate vedea istoricul (este propriu)
- ❌ **Approve** - Nu poate aproba (rol insuficient)
- ❌ **Disable** - Nu poate dezactiva (rol insuficient)
- ✅ **Delete** - Poate șterge (este propriu și draft)

### Scenariul 2: Editor cu Articol Pending
Un utilizator cu rol **Editor** accesează un articol în status **Pending** (nu al lui):
- ✅ **View** - Poate vizualiza (editor poate vedea toate)
- ✅ **Edit** - Poate edita (editor poate edita pending)
- ✅ **History** - Poate vedea istoricul (editor poate vedea toate)
- ✅ **Approve** - Poate aproba (editor poate aproba pending)
- ❌ **Disable** - Nu poate dezactiva (nu este approved)
- ✅ **Delete** - Poate șterge (editor poate șterge pending)

### Scenariul 3: Admin cu Articol Approved
Un utilizator cu rol **Admin** accesează un articol în status **Approved**:
- ✅ **View** - Poate vizualiza (admin poate vedea toate)
- ✅ **Edit** - Poate edita (admin poate edita approved)
- ✅ **History** - Poate vedea istoricul (admin poate vedea toate)
- ❌ **Approve** - Nu poate aproba (deja approved)
- ✅ **Disable** - Poate dezactiva (admin poate dezactiva approved)
- ✅ **Delete** - Poate șterge (admin poate șterge toate)

---

## Note Importante

1. **Securitate:** Toate verificările sunt efectuate atât pe frontend (UI) cât și pe backend (API)

2. **Consistență:** Matricea de permisiuni este aplicată uniform în toată aplicația

3. **Debugging:** Sistemul include logging pentru depanarea permisiunilor în modul dezvoltare

4. **Flexibilitate:** Sistemul permite adăugarea ușoară de noi roluri sau operații prin extinderea matricei

5. **Performanță:** Verificările de permisiuni sunt optimizate pentru execuție rapidă

---

**Data ultimei actualizări:** 26 August 2025  
**Versiune:** 1.0  
**Autor:** MyKDB Development Team
