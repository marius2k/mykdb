<?php
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Curăță logurile vechi.
 *
 * @param int $days Număr de zile vechime
 * @param string $mode 'archive' sau 'delete'
 * 
 * 
 * 
 * Implementation in cron job:
 * crontab -e
 * 0 3 * * * /opt/lampp/bin/php /opt/lampp/htdocs/mykdb/scripts/clean_logs.php >> /opt/lampp/htdocs/mykdb/logs/clean.log 2>&1
 * 
 */
function cleanOldLogs(int $days = 30, string $mode = 'archive'): int {
    $db = new Database();

    if ($mode === 'archive') {
        $sql = "UPDATE activity_log SET archived = 1 WHERE created_at < NOW() - INTERVAL :days DAY AND archived = 0";
    } elseif ($mode === 'delete') {
        $sql = "DELETE FROM activity_log WHERE created_at < NOW() - INTERVAL :days DAY";
    } else {
        throw new InvalidArgumentException("Invalid mode: $mode");
    }

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->rowCount();
}

/**
 * Scrie mesajul într-un fișier de log dedicat.
 */
function logToFile(string $message): void {
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/cleanup_activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}

// CLI ONLY
if (php_sapi_name() !== 'cli') {
    echo "⚠️ Acest script trebuie rulat din linia de comandă.\n";
    exit(1);
}

// -----------------------------
// Config
$days = 30;
$mode = 'archive'; // archive sau delete
// -----------------------------

try {
    $affected = cleanOldLogs($days, $mode);
    $message = "✔ $affected loguri " . ($mode === 'archive' ? 'arhivate' : 'șterse') . " mai vechi de $days zile.";
    echo $message . PHP_EOL;
    
    sendNotificationToRole('admin','info', $message);
    sendNotificationToRole('superadmin','info', $message);

    logToFile($message);
} catch (Exception $e) {
    $error = "❌ Eroare cleanup: " . $e->getMessage();
    echo $error . PHP_EOL;
    logToFile($error);
    exit(1);
}
