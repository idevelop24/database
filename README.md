# PHP Database Operations

This project provides a simple and interactive interface to test and demonstrate various database operations using a PHP-based backend. It utilizes a custom `Db` class for handling database connections and queries with PDO.

## Table of Contents

- [Project Structure](#project-structure)
- [Setup and Installation](#setup-and-installation)
- [Usage Examples](#usage-examples)
  - [1. Database Connection](#1-database-connection)
  - [2. Insert a New Post](#2-insert-a-new-post)
  - [3. Select Posts](#3-select-posts)
  - [4. Update a Post](#4-update-a-post)
  - [5. Delete a Post](#5-delete-a-post)
  - [6. Transaction Tests](#6-transaction-tests)
  - [7. View Query Log](#7-view-query-log)
- [Stylish Elements](#stylish-elements)

## Project Structure

- `index.php`: The main file that provides the user interface for testing database operations. It handles user input and displays results.
- `db_actions.php`: This file contains the backend logic for database operations. It includes functions for connecting to the database, inserting, selecting, updating, and deleting records.
- `Library/Db.php`: A custom `Db` class that abstracts PDO operations, making it easier to work with the database.
- `css/style.css`: The stylesheet for the project, providing a clean and user-friendly interface.

## Setup and Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/php-database-operations.git
   ```

2. **Database Setup:**
   - Create a new database in your MySQL server.
   - Import the `database.sql` file (if provided) or create a table named `tbl_posts` with the following schema:
     ```sql
     CREATE TABLE `tbl_posts` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
       `title` varchar(255) NOT NULL,
       `content` text NOT NULL,
       `image` varchar(255) DEFAULT NULL,
       `posts_categories_id` int(11) NOT NULL,
       `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       `modify_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
       `is_archive` tinyint(1) NOT NULL DEFAULT '0',
       `status` tinyint(1) NOT NULL DEFAULT '1',
       PRIMARY KEY (`id`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
     ```

3. **Configuration:**
   - Open `db_actions.php` and update the database credentials:
     ```php
     $dbHost = 'localhost';
     $dbUser = 'your_db_user';
     $dbPass = 'your_db_password';
     $dbName = 'your_db_name';
     $dbPort = 3306;
     ```

4. **Run the project:**
   - Start your local web server (e.g., Apache, Nginx) and open `index.php` in your browser.

## Usage Examples

### 1. Database Connection

To check if the database connection is successful, click the **Ping Database** button.

> **Note:** This will call the `pingDatabase()` function in `db_actions.php`.

### 2. Insert a New Post

To insert a new post, fill in the form under the "Insert New Post" section and click the **Insert Post** button.

```php
// Example of inserting a new post
$postData = [
    'title' => 'New Post Title',
    'content' => 'This is the content of the new post.',
    'image' => 'post.jpg',
    'posts_categories_id' => 1,
    'is_archive' => 0,
    'status' => 1,
];
$result = insertNewPost($postData);
```

### 3. Select Posts

- **Select All Posts:** Click the **Select All Posts** button to retrieve the last 10 posts from the database.
- **Select Single Post:** Enter a post ID and click the **Select Single Post** button to retrieve a specific post.

```php
// Example of selecting all posts
$posts = selectAllPosts();

// Example of selecting a single post
$post = selectSinglePost(1);
```

### 4. Update a Post

To update a post, enter the post ID and the new title and content, then click the **Update Post** button.

```php
// Example of updating a post
$postId = 1;
$updateData = [
    'title' => 'Updated Post Title',
    'content' => 'This is the updated content.',
];
$result = updateExistingPost($postId, $updateData);
```

### 5. Delete a Post

To delete a post, enter the post ID and click the **Delete Post** button.

> **Warning:** This action is irreversible.

```php
// Example of deleting a post
$postId = 1;
$result = deleteExistingPost($postId);
```

### 6. Transaction Tests

- **Run Successful Transaction:** This will run a transaction that should succeed and commit the changes to the database.
- **Run Failed Transaction:** This will run a transaction that is expected to fail and be rolled back.

### 7. View Query Log

Click the **View Query Log** button to see a log of all the SQL queries executed during the session.

## Stylish Elements

This `README.md` uses various Markdown features to enhance readability:

- **Blockquotes:** Used for notes and warnings.
  > This is a blockquote.
- **Code Blocks:** Used for displaying code snippets with syntax highlighting.
  ```php
  echo "Hello, World!";
  ```
- **Tables:** Used to organize information.
  | Feature         | Description                               |
  |-----------------|-------------------------------------------|
  | **Responsive**  | The interface is designed to be responsive. |
  | **Interactive** | Provides a hands-on way to test the API.  |

