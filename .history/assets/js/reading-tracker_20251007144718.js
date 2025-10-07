/**
 * Reading Time Tracker - Integrează cu sistemul existent de views și likes
 */
class ReadingTimeTracker {
    constructor(articleId) {
        this.articleId = articleId;
        this.startTime = Date.now();
        this.lastActiveTime = Date.now();
        this.totalActiveTime = 0;
        this.isPageVisible = true;
        this.isReading = false;
        this.scrollPercentage = 0;
        this.maxScrollPercentage = 0;
        this.readingStartTime = null;
        this.totalReadingTime = 0;
        this.inactivityTimeout = null;
        this.saveTimeout = null;
        
        // Configurări
        this.INACTIVITY_THRESHOLD = 5000; // 5 secunde
        this.MIN_READING_TIME = 3000; // 3 secunde minimum
        this.SAVE_INTERVAL = 30000; // Salvează la 30 secunde
        this.SCROLL_THRESHOLD = 5; // Minimum 5% scroll pentru a considera că citește
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.startReading();
        this.scheduleSave();
        
        console.log('📚 Reading Time Tracker inițializat pentru articolul', this.articleId);
    }
    
    bindEvents() {
        // Detectează vizibilitatea paginii
        document.addEventListener('visibilitychange', () => {
            this.handleVisibilityChange();
        });
        
        // Detectează scroll
        window.addEventListener('scroll', () => {
            this.handleScroll();
        });
        
        // Detectează activitatea utilizatorului
        ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => {
                this.handleUserActivity();
            });
        });
        
        // Salvează datele înainte de a părăsi pagina
        window.addEventListener('beforeunload', () => {
            this.saveReadingTime(true);
        });
        
        // Salvează datele când pagina este ascunsă
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.saveReadingTime(true);
            }
        });
    }
    
    handleVisibilityChange() {
        const now = Date.now();
        
        if (document.hidden) {
            if (this.isPageVisible) {
                this.totalActiveTime += now - this.lastActiveTime;
                this.stopReading();
                this.isPageVisible = false;
            }
        } else {
            this.lastActiveTime = now;
            this.isPageVisible = true;
            this.startReading();
        }
    }
    
    handleScroll() {
        this.updateScrollPercentage();
        this.handleUserActivity();
        
        if (this.scrollPercentage >= this.SCROLL_THRESHOLD && !this.isReading) {
            this.startReading();
        }
    }
    
    handleUserActivity() {
        const now = Date.now();
        this.lastActiveTime = now;
        
        if (this.inactivityTimeout) {
            clearTimeout(this.inactivityTimeout);
        }
        
        if (!this.isReading && this.isPageVisible) {
            this.startReading();
        }
        
        this.inactivityTimeout = setTimeout(() => {
            this.stopReading();
        }, this.INACTIVITY_THRESHOLD);
    }
    
    startReading() {
        if (!this.isReading && this.isPageVisible) {
            this.isReading = true;
            this.readingStartTime = Date.now();
        }
    }
    
    stopReading() {
        if (this.isReading && this.readingStartTime) {
            const readingSession = Date.now() - this.readingStartTime;
            this.totalReadingTime += readingSession;
            this.isReading = false;
            this.readingStartTime = null;
        }
    }
    
    updateScrollPercentage() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        
        if (scrollHeight > 0) {
            this.scrollPercentage = Math.round((scrollTop / scrollHeight) * 100);
            this.maxScrollPercentage = Math.max(this.maxScrollPercentage, this.scrollPercentage);
        }
    }
    
    getCurrentReadingTime() {
        let currentTotal = this.totalReadingTime;
        
        if (this.isReading && this.readingStartTime) {
            currentTotal += Date.now() - this.readingStartTime;
        }
        
        return currentTotal;
    }
    
    getCurrentActiveTime() {
        let currentTotal = this.totalActiveTime;
        
        if (this.isPageVisible) {
            currentTotal += Date.now() - this.lastActiveTime;
        }
        
        return currentTotal;
    }
    
    scheduleSave() {
        this.saveTimeout = setTimeout(() => {
            this.saveReadingTime();
            this.scheduleSave();
        }, this.SAVE_INTERVAL);
    }
    
    async saveReadingTime(isFinal = false) {
        const readingTime = this.getCurrentReadingTime();
        const activeTime = this.getCurrentActiveTime();
        
        if (isFinal && readingTime < this.MIN_READING_TIME) {
            console.log('⚠️ Timp de citire prea scurt:', this.formatTime(readingTime));
            return;
        }
        
        const data = {
            article_id: this.articleId,
            reading_time: Math.round(readingTime),
            scroll_percentage: this.maxScrollPercentage,
            page_visibility_time: Math.round(activeTime),
            total_time_on_page: Math.round(Date.now() - this.startTime)
        };
        
        try {
            const response = await fetch('/mykdb/public/api/reading_analytics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save_reading_time',
                    ...data
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('💾 Reading time salvat:', {
                    citire: this.formatTime(readingTime),
                    scroll: `${this.maxScrollPercentage}%`,
                    activ: this.formatTime(activeTime)
                });
            } else {
                console.warn('⚠️ Eroare la salvarea reading time:', result.message || result.error);
            }
        } catch (error) {
            console.error('❌ Eroare la trimiterea reading time:', error);
        }
    }
    
    formatTime(milliseconds) {
        const seconds = Math.floor(milliseconds / 1000);
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        
        if (minutes > 0) {
            return `${minutes}m ${remainingSeconds}s`;
        }
        return `${remainingSeconds}s`;
    }
    
    getStats() {
        return {
            totalReadingTime: this.getCurrentReadingTime(),
            totalActiveTime: this.getCurrentActiveTime(),
            maxScrollPercentage: this.maxScrollPercentage,
            currentScrollPercentage: this.scrollPercentage,
            isReading: this.isReading,
            isPageVisible: this.isPageVisible
        };
    }
    
    destroy() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        if (this.inactivityTimeout) {
            clearTimeout(this.inactivityTimeout);
        }
        
        this.saveReadingTime(true);
        console.log('🛑 Reading Time Tracker oprit');
    }
}

// Exportă pentru utilizare globală
window.ReadingTimeTracker = ReadingTimeTracker;