<?php

// --- Database Actions Backend ---
// This file contains functions that perform database operations using the Db class.
// It's included by index.php to handle the logic for user requests.
//
// Security Note for Production:
// - Ensure DB credentials are not hardcoded here but loaded from a secure configuration.
// - Implement proper authorization checks if this were part of a larger application.
// - Expand input validation beyond basic filtering if complex data types are expected.
// - Consider rate limiting or other abuse prevention if exposed publicly.
// ---

// Include the Db class file
require_once __DIR__ . '/Library/Db.php';

// Use the namespace for the Db class
use Framework\Library\Db;
use Framework\Library\Db as DbConnection; // Alias for clarity if needed elsewhere

// --- Database Configuration ---
// **IMPORTANT**: Replace these with your actual database credentials.
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = ''; // Replace with your database password
$dbName = 'vanilla'; // Replace with your database name
$dbPort = 3306;

// Global variable for the Db instance
$db = null;

/**
 * Establishes a database connection or returns existing one.
 * @return DbConnection|null Returns the Db instance or null on failure.
 */
function getDbConnection(): ?DbConnection {
    global $db, $dbHost, $dbUser, $dbPass, $dbName, $dbPort;
    if ($db === null) {
        try {
            $db = DbConnection::getInstance($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
        } catch (\RuntimeException $e) {
            // Log error or handle as appropriate for web context
            error_log("DB Connection Runtime Error: " . $e->getMessage());
            return null;
        } catch (\PDOException $e) {
            error_log("DB Connection PDO Error: " . $e->getMessage());
            return null;
        }
    }
    return $db;
}

/**
 * Pings the database.
 * @return array ['status' => bool, 'message' => string]
 */
function pingDatabase(): array {
    $db = getDbConnection();
    if (!$db) {
        return ['status' => false, 'message' => 'Failed to establish database connection for ping. Check server logs and credentials.'];
    }
    try {
        if ($db->ping()) {
            return ['status' => true, 'message' => 'Database ping successful. Connection is active.'];
        } else {
            return ['status' => false, 'message' => 'Database ping failed.'];
        }
    } catch (\PDOException $e) {
        return ['status' => false, 'message' => 'PDOException during ping: ' . $e->getMessage()];
    }
}

/**
 * Inserts a new post.
 * @param array $data Post data (title, content, image, posts_categories_id, is_archive, status)
 * @return array ['success' => bool, 'message' => string, 'post_id' => int|null]
 */
function insertNewPost(array $data): array {
    $db = getDbConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection not available for inserting post.'];
    }
    try {
        $insertSql = "INSERT INTO `tbl_posts` (`title`, `content`, `image`, `posts_categories_id`, `is_archive`, `status`) 
                      VALUES (:title, :content, :image, :posts_categories_id, :is_archive, :status)";
        
        // Basic validation/sanitization should be done before calling this function in a real app
        $postData = [
            'title' => $data['title'] ?? 'Default Title',
            'content' => $data['content'] ?? 'Default Content',
            'image' => $data['image'] ?? 'default.jpg',
            'posts_categories_id' => (int)($data['posts_categories_id'] ?? 1),
            'is_archive' => (int)($data['is_archive'] ?? 0),
            'status' => (int)($data['status'] ?? 1)
        ];
        
        $insertResult = $db->query($insertSql, $postData);
        
        if ($insertResult->num_rows > 0) {
            $lastId = $db->lastInsertId();
            return ['success' => true, 'message' => "Post inserted successfully. New Post ID: " . $lastId, 'post_id' => $lastId];
        } else {
            return ['success' => false, 'message' => "Post insertion failed or no rows affected."];
        }
    } catch (\PDOException $e) {
        return ['success' => false, 'message' => "Error inserting post: " . $e->getMessage()];
    }
}

/**
 * Selects all posts (with a limit).
 * @return array ['success' => bool, 'message' => string, 'posts' => array]
 */
function selectAllPosts(): array {
    $db = getDbConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection not available for selecting posts.', 'posts' => []];
    }
    try {
        // Use prepared statement for fetching multiple rows
        $stmt = $db->prepared("SELECT `id`, `title`, `content`, `image`, `posts_categories_id`, `created_at`, `modify_at`, `is_archive`, `status` FROM `tbl_posts` ORDER BY `created_at` DESC LIMIT 10");
        $allPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($allPosts) {
            return ['success' => true, 'message' => "Found " . count($allPosts) . " posts.", 'posts' => $allPosts];
        } else {
            return ['success' => true, 'message' => "No posts found.", 'posts' => []];
        }
    } catch (\PDOException $e) {
        return ['success' => false, 'message' => "Error selecting all posts: " . $e->getMessage(), 'posts' => []];
    }
}

/**
 * Selects a single post by ID.
 * @param int $postId
 * @return array ['success' => bool, 'message' => string, 'post' => array|null]
 */
function selectSinglePost(int $postId): array {
    $db = getDbConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection not available.', 'post' => null];
    }
    try {
        $selectOneSql = "SELECT `id`, `title`, `content`, `image`, `posts_categories_id`, `created_at`, `modify_at`, `is_archive`, `status` FROM `tbl_posts` WHERE `id` = :id";
        $onePostResult = $db->query($selectOneSql, ['id' => $postId]);
        
        if (!empty($onePostResult->row)) {
            return ['success' => true, 'message' => "Post found.", 'post' => $onePostResult->row];
        } else {
            return ['success' => false, 'message' => "No post found with ID: " . $postId, 'post' => null];
        }
    } catch (\PDOException $e) {
        return ['success' => false, 'message' => "Error selecting single post: " . $e->getMessage(), 'post' => null];
    }
}

/**
 * Updates an existing post.
 * @param int $postId
 * @param array $data (title, content)
 * @return array ['success' => bool, 'message' => string]
 */
function updateExistingPost(int $postId, array $data): array {
    $db = getDbConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection not available for update.'];
    }
    try {
        $updateSql = "UPDATE `tbl_posts` 
                      SET `title` = :title, `content` = :content, `modify_at` = CURRENT_TIMESTAMP 
                      WHERE `id` = :id";
        $updatedData = [
            'id' => $postId,
            'title' => $data['title'] ?? 'Default Updated Title',
            'content' => $data['content'] ?? 'Default Updated Content'
        ];
        
        $updateResult = $db->query($updateSql, $updatedData);
        
        if ($updateResult->num_rows > 0) {
            return ['success' => true, 'message' => "Post with ID: " . $postId . " updated successfully. Rows affected: " . $updateResult->num_rows];
        } else {
            // This can also mean the data was the same and no actual update occurred, or ID not found.
            $checkPost = selectSinglePost($postId); // Check if the post exists
            if (!$checkPost['post']) {
                 return ['success' => false, 'message' => "Post update failed. Post with ID: " . $postId . " not found."];
            }
            return ['success' => false, 'message' => "Post with ID: " . $postId . " was not updated. Data might be the same, or post not found. Rows affected: " . $updateResult->num_rows];
        }
    } catch (\PDOException $e) {
        return ['success' => false, 'message' => "Error updating post: " . $e->getMessage()];
    }
}

/**
 * Deletes a post by ID.
 * @param int $postId
 * @return array ['success' => bool, 'message' => string]
 */
function deleteExistingPost(int $postId): array {
    $db = getDbConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection not available for deletion.'];
    }
    try {
        // First, verify if the post exists
        $checkResult = $db->query("SELECT `id` FROM `tbl_posts` WHERE `id` = :id", ['id' => $postId]);

        if (empty($checkResult->row)) {
            return ['success' => false, 'message' => "Cannot delete. Post with ID: " . $postId . " not found."];
        }

        $deleteSql = "DELETE FROM `tbl_posts` WHERE `id` = :id";
        $deleteResult = $db->query($deleteSql, ['id' => $postId]);
        
        if ($deleteResult->num_rows > 0) {
            return ['success' => true, 'message' => "Post with ID: " . $postId . " deleted successfully. Rows affected: " . $deleteResult->num_rows];
        } else {
            return ['success' => false, 'message' => "Post deletion failed or no rows affected for ID: " . $postId . ". It might have been deleted by another process or did not exist (though checked)."];
        }
    } catch (\PDOException $e) {
        return ['success' => false, 'message' => "Error deleting post: " . $e->getMessage()];
    }
}

/**
 * Runs a successful transaction example.
 * @return array ['success' => bool, 'message' => string, 'data' => array]
 */
function runSuccessfulTransaction(): array {
    $db = getDbConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection not available for transaction.','data'=>[]];
    }
    $messages = [];
    $txPost1Id = null;
    $txPost2Id = null;

    try {
        $db->beginTransaction();
        $messages[] = "Transaction started.";

        $post1Data = ['title' => 'TX Commit Post 1 - Web', 'content' => 'Content TX1', 'image' => 'txc1.jpg', 'posts_categories_id' => 1, 'is_archive' => 0, 'status' => 1];
        $db->query("INSERT INTO `tbl_posts` (`title`, `content`, `image`, `posts_categories_id`, `is_archive`, `status`) VALUES (:title, :content, :image, :posts_categories_id, :is_archive, :status)", $post1Data);
        $txPost1Id = $db->lastInsertId();
        $messages[] = "First post (ID: $txPost1Id) inserted within transaction.";

        $post2Data = ['title' => 'TX Commit Post 2 - Web', 'content' => 'Content TX2', 'image' => 'txc2.jpg', 'posts_categories_id' => 1, 'is_archive' => 0, 'status' => 1];
        $db->query("INSERT INTO `tbl_posts` (`title`, `content`, `image`, `posts_categories_id`, `is_archive`, `status`) VALUES (:title, :content, :image, :posts_categories_id, :is_archive, :status)", $post2Data);
        $txPost2Id = $db->lastInsertId();
        $messages[] = "Second post (ID: $txPost2Id) inserted within transaction.";
        
        $db->commit();
        $messages[] = "Transaction committed successfully. Both posts (IDs: $txPost1Id, $txPost2Id) should be saved.";
        return ['success' => true, 'message' => implode("\n", $messages), 'data' => ['post1_id' => $txPost1Id, 'post2_id' => $txPost2Id]];

    } catch (\PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
            $messages[] = "Error during transaction: " . $e->getMessage() . ". Transaction rolled back.";
        } else {
            $messages[] = "Error during transaction (not in transaction state): " . $e->getMessage();
        }
        return ['success' => false, 'message' => implode("\n", $messages), 'data'=>[]];
    }
}

/**
 * Runs a failed transaction example (rollback).
 * @return array ['success' => bool, 'message' => string, 'data' => array]
 */
function runFailedTransaction(): array {
    $db = getDbConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection not available for transaction.', 'data'=>[]];
    }
    $messages = [];
    $txPost3IdAttempt = null;

    try {
        $db->beginTransaction();
        $messages[] = "Transaction started for rollback scenario.";

        $post3Data = ['title' => 'TX Rollback Post 3 - Web', 'content' => 'Content TX3 (should rollback)', 'image' => 'txr3.jpg', 'posts_categories_id' => 1, 'is_archive' => 0, 'status' => 1];
        $db->query("INSERT INTO `tbl_posts` (`title`, `content`, `image`, `posts_categories_id`, `is_archive`, `status`) VALUES (:title, :content, :image, :posts_categories_id, :is_archive, :status)", $post3Data);
        $txPost3IdAttempt = $db->lastInsertId();
        $messages[] = "First post (Potential ID: $txPost3IdAttempt) inserted (will attempt rollback).";
        
        $messages[] = "Simulating an error by throwing an exception...";
        throw new \PDOException("Manually triggered PDOException to test rollback!");

        // This part should not be reached
        $db->commit();
        $messages[] = "Transaction committed (UNEXPECTED for rollback scenario!).";
        return ['success' => false, 'message' => implode("\n", $messages) . "\nRollback FAILED if commit occurred.", 'data'=>[]];

    } catch (\PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
            $messages[] = "Caught PDOException: " . $e->getMessage() . ". Transaction rolled back as expected.";
            // Verify post 3 was rolled back
            if ($txPost3IdAttempt) {
                $checkRollback = $db->query("SELECT id FROM tbl_posts WHERE id = :id", ['id' => $txPost3IdAttempt]);
                if (empty($checkRollback->row)) {
                    $messages[] = "Verified: Post with (attempted) ID $txPost3IdAttempt was rolled back.";
                } else {
                    $messages[] = "VERIFICATION FAILED: Post with (attempted) ID $txPost3IdAttempt was NOT rolled back.";
                }
            }
            return ['success' => true, 'message' => implode("\n", $messages), 'data' => ['rolled_back_attempted_id' => $txPost3IdAttempt]];
        } else {
            $messages[] = "PDOException, but transaction was already handled: " . $e->getMessage();
            return ['success' => false, 'message' => implode("\n", $messages), 'data'=>[]];
        }
    }
}

/**
 * Retrieves the query log.
 * @return array Query log data.
 */
function getQueryLogData(): array {
    $db = getDbConnection();
    if (!$db) {
        return [['error' => 'Database connection not available to retrieve query log.']];
    }
    return $db->getQueryLog();
}

/**
 * Closes the database connection.
 * @return string Message about closing action.
 */
function closeDbConnection(): string {
    global $db;
    if ($db && method_exists($db, 'closeConnection')) {
        $db->closeConnection();
        $db = null; // Ensure it's reset for potential re-connection on next request.
        return "Database connection closed explicitly.";
    }
    return "No active database connection to close or 'closeConnection' method not found.";
}

// Note: The old CLI output logic has been removed.
// Functions are designed to be called by index.php and return results.

?>
