# Translation Feature Documentation

## Overview

The MYKDB application now supports on-the-fly translation of articles using LibreTranslate, a free and open-source translation API. This feature allows users to translate articles to their preferred language without storing translated versions in the database.

## Architecture

### Components

1. **LibreTranslate Service** (Docker container)
   - Image: `libretranslate/libretranslate:latest`
   - Port: 5000
   - Configuration: Web UI enabled, auto-update models
   - Network: `mykdb-network`

2. **Translator Class** (`classes/translator.php`)
   - PHP wrapper for LibreTranslate API
   - Methods:
     - `translate($text, $targetLang, $sourceLang = 'auto', $format = 'html')` - Translate text
     - `translateArticle($article, $targetLang, $sourceLang = 'auto')` - Translate article title and content
     - `getLanguages()` - Get list of supported languages
     - `detectLanguage($text)` - Auto-detect text language
     - `isAvailable()` - Check if translation service is available

3. **Translation API Endpoint** (`public/api/translate_article.php`)
   - REST endpoint for frontend translation requests
   - Method: POST
   - Input: `{article_id: number, target_lang: string, source_lang: string (optional)}`
   - Output: `{success: boolean, title: string, content: string, source_lang: string, target_lang: string}`
   - Features:
     - User authentication check
     - Input validation
     - Error handling (404, 400, 500, 503)
     - Service availability check

4. **Frontend UI** (`public/view_article.php`)
   - Translation button with globe icon (🌍)
   - JavaScript functions:
     - `toggleTranslation()` - Toggle between original and translated content
     - `checkTranslationAvailability()` - Show/hide translate button based on user language
   - State management:
     - `showingOriginal` - Current view state
     - `translationCache` - Cached translation to avoid repeated API calls
     - `originalTitle` / `originalContent` - Original content for restoration

## User Flow

1. **Article Loading**
   - Article loads in its original language
   - System checks user's preferred language (`$_SESSION['settings']['lang']`)
   - If user language differs from assumed article language, translation button appears

2. **Translation Request**
   - User clicks "🌍 Translate to [LANGUAGE]" button
   - Button shows "Translating..." loading state
   - Frontend sends POST request to `/api/translate_article.php`
   - Backend:
     - Fetches article from database
     - Calls LibreTranslate API via Translator class
     - Returns translated title and content (HTML format preserved)

3. **Display Translation**
   - Frontend caches the translation result
   - Original title and content are saved
   - UI updates to show translated content
   - Button changes to "Show Original"

4. **Restore Original**
   - User clicks "Show Original"
   - Frontend restores cached original content
   - Button changes back to "🌍 Translate to [LANGUAGE]"
   - No API call needed (using cached original)

## Supported Languages

LibreTranslate supports many languages. Current UI mapping includes:

| Code | Language   |
|------|------------|
| en   | English    |
| ro   | Romanian   |
| es   | Spanish    |
| fr   | French     |
| de   | German     |
| it   | Italian    |
| pt   | Portuguese |
| ru   | Russian    |
| zh   | Chinese    |
| ja   | Japanese   |
| ko   | Korean     |

## Technical Details

### API Request Format

```javascript
POST /public/api/translate_article.php
Content-Type: application/json

{
  "article_id": 123,
  "target_lang": "ro",
  "source_lang": "auto"  // optional, defaults to "auto"
}
```

### API Response Format

**Success:**
```json
{
  "success": true,
  "title": "Titlu tradus",
  "content": "<p>Conținut HTML tradus...</p>",
  "source_lang": "en",
  "target_lang": "ro"
}
```

**Error:**
```json
{
  "success": false,
  "error": "Translation service unavailable"
}
```

### Translation Service Configuration

**docker-compose.yml:**
```yaml
translator:
  image: libretranslate/libretranslate:latest
  container_name: mykdb-translator
  restart: unless-stopped
  ports:
    - "5000:5000"
  environment:
    LT_DISABLE_WEB_UI: "false"
    LT_UPDATE_MODELS: "true"
  networks:
    - mykdb-network
```

### HTML Format Preservation

The translation system uses LibreTranslate's HTML format support to preserve:
- HTML tags (e.g., `<strong>`, `<em>`, `<a>`)
- Link structure (`href` attributes)
- Image references
- Lists and formatting
- Embedded media (figures, attachments)

**Example:**
```html
<!-- Original -->
<p>This is <strong>important</strong> text with a <a href="/link">link</a>.</p>

<!-- Translated to Romanian -->
<p>Acesta este text <strong>important</strong> cu un <a href="/link">link</a>.</p>
```

## Performance Considerations

### Caching Strategy

- **On-the-fly translation**: No database storage, translations happen in real-time
- **Frontend caching**: Translation result cached in JavaScript during session
- **Benefits**:
  - No database bloat
  - Always uses latest translation models
  - Easy to clear/update translations
- **Trade-offs**:
  - Slight delay on first translation (~2-5 seconds)
  - Network dependent
  - Translation service must be running

### Optimization Tips

1. **Pre-warm models**: First translation may be slower (model loading)
2. **Network latency**: LibreTranslate runs on same Docker network (fast)
3. **Content size**: Large articles (>5000 words) may take longer
4. **Concurrent requests**: LibreTranslate can handle multiple translations

## Error Handling

### Frontend Errors

```javascript
try {
  // Translation request
} catch (error) {
  console.error('Translation error:', error);
  alert('Translation failed. Please try again later.');
}
```

### Backend Errors

1. **401 Unauthorized**: User not logged in
2. **400 Bad Request**: Invalid article_id or target_lang
3. **404 Not Found**: Article doesn't exist
4. **503 Service Unavailable**: LibreTranslate service down
5. **500 Internal Server Error**: Translation failed

## Testing

### Manual Testing

1. **Start services:**
   ```bash
   docker-compose up -d
   ```

2. **Check LibreTranslate status:**
   ```bash
   docker ps | grep mykdb-translator
   curl http://localhost:5000/languages
   ```

3. **Test translation in browser:**
   - Log in to MYKDB
   - Change user language to Romanian (Settings)
   - Open an English article
   - Click "🌍 Translate to Romanian" button
   - Verify translated content appears
   - Click "Show Original" to restore

### API Testing

```bash
# Test translation endpoint
curl -X POST http://localhost:8080/public/api/translate_article.php \
  -H "Content-Type: application/json" \
  -d '{
    "article_id": 1,
    "target_lang": "ro"
  }'
```

## Future Enhancements

### Potential Improvements

1. **Proper language detection**
   - Add `language` column to `articles` table
   - Auto-detect and store article language on creation
   - Only show translate button if article language ≠ user language

2. **Language selector dropdown**
   - Allow users to translate to any supported language
   - Not just their preferred language

3. **Translation quality indicator**
   - Show confidence score
   - Mark auto-detected source language

4. **Persistent caching**
   - Optional: Cache translations in database/Redis
   - Faster subsequent translations
   - Reduce API load

5. **Offline support**
   - Download translation models for offline use
   - No external API dependency

6. **Translation history**
   - Track which articles users translate
   - Analytics on most translated content

## Troubleshooting

### Translation button doesn't appear

**Cause**: User language is English (default)
**Solution**: Change user language in Settings to Romanian, Spanish, etc.

### "Translation failed" error

**Possible causes:**
1. LibreTranslate service not running
   ```bash
   docker-compose restart translator
   ```

2. Network connectivity issue
   ```bash
   docker network inspect mykdb_mykdb-network
   ```

3. LibreTranslate models not downloaded
   - First translation takes longer (auto-downloads models)
   - Check logs: `docker logs mykdb-translator`

### Translated content looks broken

**Cause**: HTML tags were escaped instead of preserved
**Solution**: Verify `format: 'html'` is set in `Translator::translate()` call

### Translation is slow

**Possible causes:**
1. First-time model download (one-time delay)
2. Large article content (>10,000 words)
3. LibreTranslate container resource limits

**Solutions:**
- Pre-warm models by translating a test article
- Increase Docker container memory/CPU
- Consider splitting very large articles

## Configuration Files

### Modified Files

1. **docker-compose.yml**
   - Added `translator` service definition

2. **classes/translator.php**
   - Created LibreTranslate wrapper class

3. **public/api/translate_article.php**
   - Created translation REST endpoint

4. **public/view_article.php**
   - Added translation UI button
   - Added JavaScript translation functions
   - Added language detection logic

## API Reference

### Translator Class Methods

```php
// Translate text
$translator = new Translator();
$translated = $translator->translate(
    'Hello world',    // text to translate
    'ro',            // target language
    'auto',          // source language (auto-detect)
    'html'           // format (text or html)
);

// Translate article
$article = [
    'title' => 'My Article',
    'content' => '<p>Article content...</p>'
];
$result = $translator->translateArticle($article, 'ro', 'en');
// Returns: ['title' => '...', 'content' => '...', 'source_lang' => 'en', 'target_lang' => 'ro']

// Get supported languages
$languages = $translator->getLanguages();
// Returns: [['code' => 'en', 'name' => 'English'], ...]

// Detect language
$detectedLang = $translator->detectLanguage('Bonjour le monde');
// Returns: 'fr'

// Check service availability
if ($translator->isAvailable()) {
    // Translation service is ready
}
```

## Security Considerations

1. **Authentication**: All translation requests require user login
2. **Input validation**: Article ID and language codes are validated
3. **XSS protection**: Translated HTML is still rendered (same as original content)
4. **Rate limiting**: Consider adding rate limits to prevent abuse
5. **Access control**: Users can only translate articles they have permission to view

## Deployment Checklist

- [ ] LibreTranslate service running in Docker
- [ ] Translator class exists in `classes/translator.php`
- [ ] Translation API endpoint created
- [ ] Frontend UI updated with translation button
- [ ] JavaScript translation functions implemented
- [ ] User language setting available in session
- [ ] Error handling in place
- [ ] Docker network connectivity verified
- [ ] Translation tested end-to-end

## Maintenance

### Regular Tasks

1. **Update LibreTranslate models**
   - LibreTranslate auto-updates when `LT_UPDATE_MODELS=true`
   - Or manually: `docker exec mykdb-translator libretranslate --update-models`

2. **Monitor translation service**
   ```bash
   docker logs mykdb-translator --tail 100 -f
   ```

3. **Clean up cache**
   - Frontend cache clears on page reload
   - No database cleanup needed (on-the-fly approach)

### Version Updates

To update LibreTranslate:
```bash
docker-compose pull translator
docker-compose up -d translator
```

## Resources

- **LibreTranslate Documentation**: https://libretranslate.com/docs
- **LibreTranslate GitHub**: https://github.com/LibreTranslate/LibreTranslate
- **Supported Languages**: https://libretranslate.com/languages

## License

LibreTranslate is licensed under AGPLv3.
