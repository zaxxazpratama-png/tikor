<?php
/**
 * MySQL-based PHP Session Handler
 * Dibutuhkan untuk lingkungan serverless (Vercel) di mana filesystem tidak persisten.
 * Session disimpan di tabel `php_sessions` di database.
 */

class MysqlSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private int $lifetime;

    public function __construct(PDO $pdo, int $lifetime = 0)
    {
        $this->pdo      = $pdo;
        $this->lifetime = $lifetime ?: (int) ini_get('session.gc_maxlifetime');
    }

    public function open(string $path, string $name): bool
    {
        // Pastikan tabel sessions ada
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `php_sessions` (
                `id`         VARCHAR(128)  NOT NULL PRIMARY KEY,
                `data`       MEDIUMTEXT    NOT NULL DEFAULT '',
                `expires_at` DATETIME      NOT NULL,
                INDEX idx_expires (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $this->gc(-1); // lazy GC
        $stmt = $this->pdo->prepare(
            "SELECT `data` FROM `php_sessions` WHERE `id` = ? AND `expires_at` > NOW() LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $expires = date('Y-m-d H:i:s', time() + $this->lifetime);
        $stmt = $this->pdo->prepare("
            INSERT INTO `php_sessions` (`id`, `data`, `expires_at`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `expires_at` = VALUES(`expires_at`)
        ");
        return $stmt->execute([$id, $data, $expires]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `php_sessions` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare("DELETE FROM `php_sessions` WHERE `expires_at` < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
}

/**
 * Inisialisasi session dengan MySQL handler.
 * Panggil fungsi ini SEBELUM session_start() di mana saja.
 */
function initMysqlSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return; // session sudah aktif
    }

    // Gunakan MySQL session handler hanya jika berjalan di Vercel / serverless
    // Di XAMPP lokal, session filesystem tetap digunakan (lebih cepat)
    $isServerless = (bool) getenv('VERCEL');

    if ($isServerless) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_PORT') ?: '3306',
                getenv('DB_NAME') ?: 'support_map_db'
            );
            $pdo = new PDO($dsn, getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            $handler = new MysqlSessionHandler($pdo);
            session_set_save_handler($handler, true);

            // Cookie settings yang aman
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } catch (PDOException $e) {
            // Fallback ke filesystem jika DB tidak tersedia
            error_log('[Session] MySQL session handler error: ' . $e->getMessage());
        }
    }
}
