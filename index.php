<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    // --- DB Operations Test Interface ---
    // Main file for handling user interaction and displaying results from db_actions.php.
    // For a production application, consider:
    // 1. More robust input validation and sanitization.
    // 2. CSRF protection on all POST forms.
    // 3. User authentication and authorization.
    // 4. More sophisticated error handling and logging.
    // 5. Environment-specific configurations (DB credentials should not be hardcoded directly).
    // 6. Using a templating engine for separating logic and presentation.
    // ---

    // Start session to store messages if needed (e.g., for redirect messages, though not heavily used here yet)
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/db_actions.php';

    $userMessage = ''; // General message to display (e.g. results of an action)
    $messageType = 'info'; // Can be 'info', 'success', 'error', 'warning'
    $actionData = null; // To store data returned by actions, like selected posts

    // Handle actions
    $action = $_REQUEST['action'] ?? null; // Use $_REQUEST to catch both GET and POST

    if ($action) {
        // Ensure DB connection is attempted for most actions
        // For ping, it's handled within the function. For others, good to have $db global available.
        $db = getDbConnection(); 
        
        if (!$db && !in_array($action, ['ping_db'])) { // If DB connection failed and it's not a ping action
            $userMessage = "Database connection failed. Please check credentials in db_actions.php and ensure the database server is running.";
            $messageType = 'error';
        } else {
            switch ($action) {
                case 'ping_db':
                    $result = pingDatabase();
                    $userMessage = $result['message'];
                    $messageType = $result['status'] ? 'success' : 'error';
                    break;

                case 'insert_post':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // Basic sanitation, more robust needed for production
                        $postData = [
                            'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
                            'content' => filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING),
                            'image' => filter_input(INPUT_POST, 'image', FILTER_SANITIZE_STRING),
                            'posts_categories_id' => filter_input(INPUT_POST, 'posts_categories_id', FILTER_VALIDATE_INT),
                            'is_archive' => filter_input(INPUT_POST, 'is_archive', FILTER_VALIDATE_INT),
                            'status' => filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT),
                        ];
                        $result = insertNewPost($postData);
                        $userMessage = $result['message'];
                        $messageType = $result['success'] ? 'success' : 'error';
                        if ($result['success']) {
                             // Potentially clear form or redirect, for now just message
                        }
                    }
                    break;

                case 'select_all_posts':
                    $result = selectAllPosts();
                    $userMessage = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'info'; // info, as "no posts" isn't an error
                    if ($result['success'] && !empty($result['posts'])) {
                        $actionData = $result['posts'];
                    }
                    break;

                case 'select_single_post':
                    $postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
                    if ($postId) {
                        $result = selectSinglePost($postId);
                        $userMessage = $result['message'];
                        $messageType = $result['success'] ? 'success' : 'error';
                        if ($result['success'] && !empty($result['post'])) {
                            $actionData = $result['post'];
                        }
                    } else {
                        $userMessage = "Invalid Post ID provided for selection.";
                        $messageType = 'error';
                    }
                    break;

                case 'update_post':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
                        $updateData = [
                            'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
                            'content' => filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING),
                        ];
                        if ($postId) {
                            $result = updateExistingPost($postId, $updateData);
                            $userMessage = $result['message'];
                            $messageType = $result['success'] ? 'success' : 'error';
                        } else {
                            $userMessage = "Invalid Post ID provided for update.";
                            $messageType = 'error';
                        }
                    }
                    break;

                case 'delete_post':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
                        if ($postId) {
                            $result = deleteExistingPost($postId);
                            $userMessage = $result['message'];
                            $messageType = $result['success'] ? 'success' : 'error';
                        } else {
                            $userMessage = "Invalid Post ID provided for deletion.";
                            $messageType = 'error';
                        }
                    }
                    break;

                case 'transaction_commit':
                    $result = runSuccessfulTransaction();
                    $userMessage = "Successful Transaction Test Results:\n" . $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                    $actionData = $result['data'] ?? null;
                    break;

                case 'transaction_rollback':
                    $result = runFailedTransaction();
                    $userMessage = "Failed Transaction Test Results (Rollback):\n" . $result['message'];
                    // This is a "successful" test of a failure, so message type can be 'info' or 'success' if rollback occurred as expected
                    $messageType = $result['success'] ? 'success' : 'error'; // If success is true, it means rollback worked
                    $actionData = $result['data'] ?? null;
                    break;

                case 'view_query_log':
                    $logData = getQueryLogData();
                    $userMessage = "Query Log Retrieved.";
                    $messageType = 'info';
                    $actionData = $logData; // Store log data for display
                    // Query log display will be handled in the HTML part
                    break;
                
                default:
                    $userMessage = "Unknown action: " . htmlspecialchars($action);
                    $messageType = 'warning';
            }
        }
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Operations Test Interface</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Database Operations Test Interface</h1>

    <div class="results-area">
        <h2>Results / Messages</h2>
        <div id="results-output" class="message-output <?php echo $messageType; ?>">
            <?php
            if (!empty($userMessage)) {
                echo nl2br(htmlspecialchars($userMessage)); // nl2br to respect newlines, htmlspecialchars for security
            } else {
                echo "Perform an action to see results here.";
            }

            // Display additional data if available (e.g., selected posts, query log)
            if ($action === 'select_all_posts' && !empty($actionData)) {
                echo "<h3>Selected Posts:</h3>";
                echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Content (Excerpt)</th><th>Image</th><th>Category ID</th><th>Created At</th><th>Status</th></tr>";
                foreach ($actionData as $post) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($post['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($post['title']) . "</td>";
                    echo "<td>" . htmlspecialchars(substr($post['content'], 0, 100)) . "...</td>";
                    echo "<td>" . htmlspecialchars($post['image']) . "</td>";
                    echo "<td>" . htmlspecialchars($post['posts_categories_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($post['created_at']) . "</td>";
                    echo "<td>" . htmlspecialchars($post['status']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } elseif ($action === 'select_single_post' && !empty($actionData)) {
                echo "<h3>Selected Post Details:</h3>";
                echo "<pre>" . htmlspecialchars(print_r($actionData, true)) . "</pre>";
            } elseif ($action === 'view_query_log' && isset($actionData)) {
                 echo "<h3>Query Log:</h3>";
                if (empty($actionData)) {
                    echo "<p>Query log is empty or not available.</p>";
                } else {
                    echo "<pre>";
                    foreach ($actionData as $logEntry) {
                        echo "SQL: " . htmlspecialchars($logEntry['query'] ?? '') . "\n";
                        if (!empty($logEntry['params'])) {
                            echo "Params: " . htmlspecialchars(json_encode($logEntry['params'])) . "\n";
                        }
                        if (isset($logEntry['time'])) {
                             echo "Time: " . htmlspecialchars(round($logEntry['time'] * 1000, 2)) . " ms\n";
                        }
                        if (isset($logEntry['rows'])) {
                            echo "Rows: " . htmlspecialchars($logEntry['rows']) . "\n";
                        }
                        if (!empty($logEntry['error'])) {
                            echo "Error: " . htmlspecialchars($logEntry['error']) . "\n";
                        }
                        echo "-----------------------------\n";
                    }
                    echo "</pre>";
                }
            } elseif (($action === 'transaction_commit' || $action === 'transaction_rollback') && !empty($actionData) ) {
                 echo "<h3>Transaction Data:</h3>";
                 echo "<pre>" . htmlspecialchars(print_r($actionData, true)) . "</pre>";
            }
            ?>
        </div>
    </div>

    <div class="actions-container">
        <!-- DB Connection Section -->
        <section id="db-connection" class="action-section">
            <h2>1. Database Connection</h2>
            <form action="index.php" method="GET">
                <input type="hidden" name="action" value="ping_db">
                <button type="submit" class="info">Ping Database</button>
            </form>
        </section>

        <!-- Insert Post Section -->
        <section id="insert-post" class="action-section">
            <h2>2. Insert New Post</h2>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="insert_post">
                <div><label for="title">Title:</label><input type="text" id="title" name="title" value="Test Post Title" required></div>
                <div><label for="content">Content:</label><textarea id="content" name="content" required>Some test content.</textarea></div>
                <div><label for="image">Image:</label><input type="text" id="image" name="image" value="test.jpg"></div>
                <div><label for="posts_categories_id">Category ID:</label><input type="number" id="posts_categories_id" name="posts_categories_id" value="1" required></div>
                <div><label for="is_archive">Is Archive (0 or 1):</label><input type="number" id="is_archive" name="is_archive" value="0" required></div>
                <div><label for="status">Status (e.g., 0 or 1):</label><input type="number" id="status" name="status" value="1" required></div>
                <button type="submit">Insert Post</button> {/* Default green for create */}
            </form>
        </section>

        <!-- Select Posts Section -->
        <section id="select-posts" class="action-section">
            <h2>3. Select Posts</h2>
            <form action="index.php" method="GET" style="display: inline-block; margin-right: 10px;">
                <input type="hidden" name="action" value="select_all_posts">
                <button type="submit" class="info">Select All Posts (Limit 10)</button>
            </form>
            <hr>
            <form action="index.php" method="GET" style="margin-top:10px;">
                <input type="hidden" name="action" value="select_single_post">
                <div><label for="select_post_id">Post ID to Select:</label><input type="number" id="select_post_id" name="post_id" value="1" required></div>
                <button type="submit" class="info">Select Single Post</button>
            </form>
        </section>

        <!-- Update Post Section -->
        <section id="update-post" class="action-section">
            <h2>4. Update Post</h2>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="update_post">
                <div><label for="update_post_id">Post ID to Update:</label><input type="number" id="update_post_id" name="post_id" value="1" required></div>
                <div><label for="update_title">New Title:</label><input type="text" id="update_title" name="title" value="Updated Test Post Title" required></div>
                <div><label for="update_content">New Content:</label><textarea id="update_content" name="content" required>Updated test content.</textarea></div>
                <button type="submit" style="background-color: #f0ad4e; border-color: #eea236;">Update Post</button> {/* Orange for update */}
            </form>
        </section>

        <!-- Delete Post Section -->
        <section id="delete-post" class="action-section">
            <h2>5. Delete Post</h2>
            <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                <input type="hidden" name="action" value="delete_post">
                <div><label for="delete_post_id">Post ID to Delete:</label><input type="number" id="delete_post_id" name="post_id" value="1" required></div>
                <button type="submit" class="danger">Delete Post</button> {/* class 'danger' already styled in css */}
            </form>
        </section>

        <!-- Transactions Section -->
        <section id="transactions" class="action-section">
            <h2>6. Transaction Tests</h2>
            <form action="index.php" method="GET" style="display: inline-block; margin-right: 10px;">
                <input type="hidden" name="action" value="transaction_commit">
                <button type="submit" class="info">Run Successful Transaction (Commit)</button>
            </form>
            <form action="index.php" method="GET" style="display: inline-block;">
                <input type="hidden" name="action" value="transaction_rollback">
                <button type="submit" class="info">Run Failed Transaction (Rollback)</button>
            </form>
        </section>

        <!-- Query Log Section -->
        <section id="query-log" class="action-section">
            <h2>7. View Query Log</h2>
            <form action="index.php" method="GET">
                <input type="hidden" name="action" value="view_query_log">
                <button type="submit" class="info">View Query Log</button>
            </form>
        </section>
    </div>

</body>
</html>
