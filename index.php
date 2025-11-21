<?php
// Определяем константу для защиты от прямого доступа к файлам
define('APP', true);

// Запускаем сессию, чтобы можно было использовать $_SESSION
session_start();

// Определяем путь к файлу базы данных SQLite
define('DB_FILE', __DIR__ . '/data/contacts.db');

// Функция для подключения к базе данных и создания таблицы, если её нет
function getDb(): PDO
{
    // Проверяем, существует ли файл базы данных
    $needCreate = !file_exists(DB_FILE);

    // Если папки для базы данных нет, создаем её
    if (!is_dir(dirname(DB_FILE))) {
        mkdir(dirname(DB_FILE), 0777, true); // создаём рекурсивно с правами 777
    }

    // Подключаемся к SQLite через PDO
    $pdo = new PDO('sqlite:' . DB_FILE);

    // Настраиваем PDO на выброс исключений при ошибках
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Если базы данных ещё нет, создаём таблицу contacts
    if ($needCreate) {
        $sql = "CREATE TABLE contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,  -- уникальный идентификатор
            lastname TEXT NOT NULL,               -- фамилия
            firstname TEXT NOT NULL,              -- имя
            patronymic TEXT,                      -- отчество
            gender TEXT,                          -- пол
            dob TEXT,                             -- дата рождения
            phone TEXT,                           -- телефон
            address TEXT,                         -- адрес
            email TEXT,                           -- email
            comment TEXT,                         -- комментарий
            created_at TEXT DEFAULT (datetime('now'))  -- дата создания записи
        )";
        $pdo->exec($sql); // выполняем SQL-запрос
    }

    // Возвращаем объект PDO для работы с базой
    return $pdo;
}

// Подключаем отдельные файлы с функционалом приложения
require_once __DIR__ . '/menu.php';     // навигационное меню
require_once __DIR__ . '/viewer.php';   // просмотр записей
require_once __DIR__ . '/add.php';      // добавление записей
require_once __DIR__ . '/edit.php';     // редактирование записей
require_once __DIR__ . '/delete.php';   // удаление записей

// Определяем действие пользователя (view, add, edit, delete) через GET-параметр
$action = $_GET['action'] ?? 'view';

// Список допустимых сортировок для таблицы
$allowed_sorts = ['created', 'lastname', 'dob'];

// Определяем текущую сортировку (если передана в GET и разрешена)
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'created';

// Определяем текущую страницу для пагинации (по умолчанию 1)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Переменная для контента, который будет отображён на странице
$content = '';

try {
    // Получаем объект базы данных
    $db = getDb();

    // Выполняем действие в зависимости от $action
    switch ($action) {
        case 'add':
            // Генерируем форму для добавления новой записи
            $content = render_add($db);
            break;
        case 'edit':
            // Генерируем форму редактирования существующей записи
            $content = render_edit($db);
            break;
        case 'delete':
            // Генерируем интерфейс для удаления записи
            $content = render_delete($db);
            break;
        case 'view':
        default:
            // Показываем список записей с пагинацией и сортировкой
            $content = render_viewer($db, $sort, $page);
            break;
    }
} catch (Exception $e) {
    // Если произошла ошибка, выводим сообщение
    $content = "<div class='error'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Записная книжка</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #fff5f5, #fff0f5);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Шапка сайта */
        .site-header {
            background: linear-gradient(135deg, #ffb6c1, #ff91a4);
            color: white;
            padding: 30px 0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(255, 182, 193, 0.3);
        }

        .site-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .site-header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .menu-container {
            display: inline-block;
            text-align: center;
        }

        /* Основная навигация */
        .main-nav {
            background: #fff5f5;
            padding: 25px;
            border-bottom: 2px solid #ffd1dc;
            text-align: center;
        }

        /* Кнопки меню */
        .menu-btn {
            display: inline-block;
            padding: 14px 28px;
            margin: 0 12px 12px 0;
            background: #ffb6c1;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(255, 182, 193, 0.4);
        }

        .menu-btn:hover {
            background: #ff91a4;
            /* Темнее при наведении */
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 182, 193, 0.5);
        }

        .menu-btn.active {
            background: #ff6b8b;
            /* Самый темный для активной кнопки */
            transform: scale(1.05);
        }

        /* Подменю */
        .submenu {
            margin-top: 15px;
        }

        .sub-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px 10px 0;
            background: #ffc8d3;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(255, 182, 193, 0.3);
        }

        .sub-btn:hover {
            background: #ffb6c1;
            transform: translateY(-1px);
        }

        .sub-btn.active {
            background: #ff6b8b;
        }

        /* Основной контент */
        .main-content {
            flex: 1;
            padding: 40px;
            margin: 0 auto;
            width: 100%;
        }

        /* Подвал сайта */
        .site-footer {
            background: linear-gradient(135deg, #ffd1dc, #ffb6c1);
            text-align: center;
            padding: 25px;
            color: #8b5a5a;
            border-top: 2px solid #ff91a4;
            margin-top: auto;
        }

        .site-footer p {
            font-size: 1em;
            font-weight: 500;
        }

        /* Таблица */
        table {
            width: auto;
            min-width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(255, 182, 193, 0.2);
            margin: 25px 0;
        }

        th:nth-child(1),
        td:nth-child(1) {
            min-width: 120px;
        }

        /* Фамилия */
        th:nth-child(2),
        td:nth-child(2) {
            min-width: 100px;
        }

        /* Имя */
        th:nth-child(3),
        td:nth-child(3) {
            min-width: 120px;
        }

        /* Отчество */
        th:nth-child(5),
        td:nth-child(5) {
            min-width: 110px;
        }

        /* Дата рождения */
        th:nth-child(6),
        td:nth-child(6) {
            min-width: 130px;
        }

        /* Телефон */

        th,
        td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #ffeaf1;
        }

        th {
            background: linear-gradient(135deg, #fff5f5, #ffeffe);
            color: #ff6b8b;
            font-weight: bold;
            font-size: 15px;
        }

        tr:hover {
            background: #fffaf0;
        }

        /* Формы */
        form {
            max-width: 700px;
            margin: 0 auto;
        }

        .form-row {
            margin-bottom: 20px;
        }

        .form-row label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #ff6b8b;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 14px;
            border: 2px solid #ffd1dc;
            border-radius: 10px;
            font-size: 15px;
            background: #fff;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #ffb6c1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 182, 193, 0.2);
        }

        button {
            background: linear-gradient(135deg, #ffb6c1, #ff91a4);
            color: white;
            padding: 15px 35px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 182, 193, 0.4);
        }

        button:hover {
            background: linear-gradient(135deg, #ff91a4, #ff6b8b);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(255, 182, 193, 0.5);
        }

        /* Сообщения */
        .success {
            background: linear-gradient(135deg, #e8f5e8, #d4ffd4);
            color: #2e8b57;
            padding: 18px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #2e8b57;
            box-shadow: 0 3px 10px rgba(46, 139, 87, 0.2);
        }

        .error {
            background: linear-gradient(135deg, #ffe8e8, #ffd4d4);
            color: #dc143c;
            padding: 18px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #dc143c;
            box-shadow: 0 3px 10px rgba(220, 20, 60, 0.2);
        }

        /* Ссылки записей */
        .record-link {
            display: inline-block;
            padding: 12px 20px;
            margin: 8px 12px 8px 0;
            background: #ffc8d3;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 182, 193, 0.3);
        }

        .record-link:hover {
            background: #ffb6c1;
            transform: translateY(-2px);
        }

        .record-link.active {
            background: #ff6b8b;
            transform: scale(1.05);
        }

        /* Пагинация */
        .pagination {
            text-align: center;
            margin: 30px 0;
        }

        .page-link {
            display: inline-block;
            padding: 12px 18px;
            margin: 0 6px;
            background: #ffc8d3;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .page-link:hover {
            border: 2px solid #ff6b8b;
            background: #ffb6c1;
        }

        .page-link.active {
            background: #ff6b8b;
            transform: scale(1.1);
        }

        /* Заголовки */
        h2 {
            color: #ff6b8b;
            margin-bottom: 25px;
            border-bottom: 3px solid #ffd1dc;
            padding-bottom: 12px;
            font-size: 1.8em;
        }

        /* Адаптивность */
        @media (max-width: 768px) {

            .menu-btn,
            .sub-btn {
                display: block;
                margin: 8px 0;
                text-align: center;
            }

            .main-content {
                padding: 20px;
            }

            table {
                font-size: 14px;
            }

            th,
            td {
                padding: 10px 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Шапка сайта -->
    <header class="site-header">
        <h1><i class="fas fa-book" style="margin-right: 15px"></i>Дорогой дневник...</h1>
    </header>

    <!-- Основная навигация -->
    <nav class="main-nav">
        <?php echo menu(); ?>
    </nav>

    <!-- Основной контент -->
    <main class="main-content">
        <?php echo $content; ?>
    </main>

    <!-- Подвал сайта -->
    <footer class="site-footer">
        <p>© 2025 Записная книжка • Сделано с 🤍</p>
    </footer>
</body>

</html>