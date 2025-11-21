<?php
// Защита от прямого доступа к файлу
if (!defined('APP')) {
    die('Access denied'); // если константа APP не определена, скрипт завершает выполнение
}

// Функция для отображения интерфейса удаления записей
function render_delete(PDO $db): string
{
    $msg = ''; // переменная для сообщений об успехе или ошибке

    // Обработка удаления записи, если передан GET-параметр delete_id
    if (isset($_GET['delete_id'])) {
        $id = intval($_GET['delete_id']); // получаем id записи и приводим к числу

        // Получаем фамилию удаляемой записи для формирования сообщения и подтверждения
        $stmt = $db->prepare("SELECT lastname FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $lastname = $row['lastname']; // фамилия записи
            // Подготавливаем и выполняем SQL-запрос на удаление
            $del = $db->prepare("DELETE FROM contacts WHERE id = ?");
            $del->execute([$id]);
            // Сообщение об успешном удалении
            $msg = '<div class="success">Запись удалена</div>';
        } else {
            // Сообщение, если запись не найдена
            $msg = '<div class="error">Запись не найдена.</div>';
        }
    }

    // Получаем список всех записей для отображения ссылок на удаление
    $list = $db->query("SELECT id, lastname, firstname, patronymic FROM contacts ORDER BY lastname ASC, firstname ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Если записей нет, выводим сообщение и прекращаем выполнение
    if (count($list) === 0) {
        return '<p>Нет записей для удаления.</p>';
    }

    // Начало формирования HTML-кода
    $html = $msg; // сначала выводим сообщение
    $html .= '<h2>Удаление записи</h2>';
    $html .= '<p>Выберите запись для удаления:</p>';

    // Формируем ссылки для удаления каждой записи
    foreach ($list as $r) {
        // Формируем инициалы имени и отчества по ТЗ
        $initials = mb_substr($r['firstname'], 0, 1) . '.';
        if (!empty($r['patronymic'])) {
            $initials .= mb_substr($r['patronymic'], 0, 1) . '.';
        }

        // Ссылка на удаление с подтверждением через JavaScript confirm()
        $html .= '<a class="record-link" href="?action=delete&delete_id=' . $r['id'] . '" ';
        $html .= 'onclick="return confirm(\'Удалить запись ' . htmlspecialchars($r['lastname'] . ' ' . $initials) . '?\')">';
        $html .= htmlspecialchars($r['lastname'] . ' ' . $initials);
        $html .= '</a>';
    }

    // Возвращаем сформированный HTML
    return $html;
}
