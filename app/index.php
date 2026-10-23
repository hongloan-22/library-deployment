<?php
declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$books = [];
$error = null;

try {
    $host = getenv('DB_HOST') ?: 'db';
    $database = getenv('DB_NAME') ?: 'library_db';
    $username = getenv('DB_USER') ?: 'library_user';
    $password = getenv('DB_PASSWORD') ?: '';

    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS books (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL UNIQUE,
            author VARCHAR(150) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $count = (int) $pdo
        ->query('SELECT COUNT(*) FROM books')
        ->fetchColumn();

    if ($count === 0) {
        $seed = $pdo->prepare(
            'INSERT INTO books (title, author) VALUES (?, ?)'
        );

        $sampleBooks = [
            ['Docker cơ bản', 'Nhóm quản trị hệ thống'],
            ['Lập trình PHP', 'Tác giả minh họa'],
            ['Cơ sở dữ liệu MySQL', 'Tác giả minh họa'],
        ];

        foreach ($sampleBooks as $book) {
            $seed->execute($book);
        }
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $author = trim((string) ($_POST['author'] ?? ''));

            if ($title === '' || $author === '') {
                throw new RuntimeException(
                    'Vui lòng nhập đầy đủ tên sách và tác giả.'
                );
            }

            $statement = $pdo->prepare(
                'INSERT INTO books (title, author) VALUES (?, ?)'
            );

            $statement->execute([$title, $author]);

            header('Location: /');
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);

            if ($id > 0) {
                $statement = $pdo->prepare(
                    'DELETE FROM books WHERE id = ?'
                );

                $statement->execute([$id]);
            }

            header('Location: /');
            exit;
        }
    }

    $books = $pdo
        ->query(
            'SELECT id, title, author, created_at
             FROM books ORDER BY id DESC'
        )
        ->fetchAll();

} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thư viện Docker</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 32px 16px;
            background: #f3f6fb;
            color: #1f2937;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 950px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 8px;
            color: #174ea6;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 28px;
        }

        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
        }

        .status {
            color: #166534;
            background: #dcfce7;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .error {
            color: #991b1b;
            background: #fee2e2;
            padding: 12px;
            border-radius: 8px;
            overflow-wrap: anywhere;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
        }

        input {
            min-width: 0;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }

        button {
            padding: 10px 14px;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            background: #1d4ed8;
        }

        button:hover {
            opacity: 0.9;
        }

        .delete-button {
            background: #b91c1c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #f8fafc;
        }

        .empty {
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 650px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 14px;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
<main>
    <h1>Thư viện Docker</h1>

    <p class="subtitle">
        Ứng dụng PHP và MySQL chạy bằng Docker Compose
    </p>

    <?php if ($error !== null): ?>
        <div class="panel error">
            <strong>Không thể hoàn thành thao tác:</strong>
            <?= h($error) ?>
        </div>
    <?php else: ?>
        <div class="status">
            Kết nối MySQL thành công.
            Có <?= count($books) ?> cuốn sách trong hệ thống.
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>Thêm sách mới</h2>

        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add">

            <input
                type="text"
                name="title"
                maxlength="200"
                placeholder="Tên sách"
                required
            >

            <input
                type="text"
                name="author"
                maxlength="150"
                placeholder="Tên tác giả"
                required
            >

            <button type="submit">Thêm sách</button>
        </form>
    </section>

    <section class="panel">
        <h2>Danh sách sách</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên sách</th>
                    <th>Tác giả</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($books) === 0): ?>
                <tr>
                    <td colspan="5" class="empty">
                        Chưa có dữ liệu.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= h($book['id']) ?></td>
                        <td><?= h($book['title']) ?></td>
                        <td><?= h($book['author']) ?></td>
                        <td><?= h($book['created_at']) ?></td>
                        <td>
                            <form method="post">
                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= h($book['id']) ?>"
                                >

                                <button
                                    type="submit"
                                    class="delete-button"
                                    onclick="return confirm('Bạn có chắc muốn xóa sách này?')"
                                >
                                    Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>