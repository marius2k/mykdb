<?php

/**
 * Translator Class
 * Handles automatic translation of articles using LibreTranslate
 */
class Translator {
    private $apiUrl;
    
    public function __construct($apiUrl = 'http://translator:5000') {
        $this->apiUrl = $apiUrl;
    }
    
    /**
     * Translate text from source language to target language
     * 
     * @param string $text Text to translate
     * @param string $targetLang Target language code (e.g., 'ro', 'en')
     * @param string $sourceLang Source language code (default: 'auto' for auto-detection)
     * @param string $format Format of the text ('text' or 'html')
     * @return string|false Translated text or false on error
     */
    public function translate($text, $targetLang, $sourceLang = 'auto', $format = 'html') {
        if (empty($text)) {
            return $text;
        }
        
        // Prepare request data
        $data = [
            'q' => $text,
            'source' => $sourceLang,
            'target' => $targetLang,
            'format' => $format
        ];
        
        try {
            $ch = curl_init($this->apiUrl . '/translate');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("Translation API error: HTTP $httpCode - Response: $response");
                return false;
            }
            
            if ($curlError) {
                error_log("Translation cURL error: $curlError");
                return false;
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['translatedText'])) {
                return $result['translatedText'];
            }
            
            error_log("Translation failed: " . print_r($result, true));
            return false;
            
        } catch (Exception $e) {
            error_log("Translation exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Translate an entire article (title and content)
     * 
     * @param array $article Article array with 'title' and 'content' keys
     * @param string $targetLang Target language code
     * @param string $sourceLang Source language code
     * @return array|false Array with translated title and content, or false on error
     */
    public function translateArticle($article, $targetLang, $sourceLang = 'auto') {
        if (!isset($article['title']) || !isset($article['content'])) {
            return false;
        }
        
        // Translate title (plain text)
        $translatedTitle = $this->translate($article['title'], $targetLang, $sourceLang, 'text');
        
        if ($translatedTitle === false) {
            return false;
        }
        
        // Translate content (HTML)
        $translatedContent = $this->translate($article['content'], $targetLang, $sourceLang, 'html');
        
        if ($translatedContent === false) {
            return false;
        }
        
        return [
            'title' => $translatedTitle,
            'content' => $translatedContent,
            'original_title' => $article['title'],
            'original_content' => $article['content'],
            'target_lang' => $targetLang,
            'source_lang' => $sourceLang
        ];
    }
    
    /**
     * Get list of supported languages
     * 
     * @return array|false Array of supported languages or false on error
     */
    public function getSupportedLanguages() {
        try {
            $ch = curl_init($this->apiUrl . '/languages');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                return json_decode($response, true);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Failed to get supported languages: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if LibreTranslate service is available
     * 
     * @return bool True if service is available, false otherwise
     */
    public function isAvailable() {
        try {
            $ch = curl_init($this->apiUrl . '/languages');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
