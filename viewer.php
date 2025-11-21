<?php
// Защита от прямого доступа к файлу
if (!defined('APP')) {
    die('Access denied'); // если константа APP не определена, скрипт завершает выполнение
}

// Функция для отображения списка записей с пагинацией и сортировкой
function render_viewer(PDO $db, string $sort, int $page): string
{
    $perPage = 10; // количество записей на одной странице
    $offset = ($page - 1) * $perPage; // смещение для SQL-запроса, чтобы получить нужную страницу

    // Определяем порядок сортировки в зависимости от параметра $sort
    switch ($sort) {
        case 'lastname':
            $order = "lastname ASC, firstname ASC"; // сортировка по фамилии и имени
            break;
        case 'dob':
            $order = "dob ASC"; // сортировка по дате рождения
            break;
        case 'created':
        default:
            $order = "created_at ASC"; // сортировка по дате добавления записи
            break;
    }

    // Получаем общее количество записей в таблице contacts
    $totalStmt = $db->query("SELECT COUNT(*) FROM contacts");
    $total = (int) $totalStmt->fetchColumn(); // преобразуем результат в число
    $totalPages = max(1, ceil($total / $perPage)); // вычисляем общее количество страниц для пагинации

    // Получаем записи для текущей страницы с учетом сортировки и смещения
    $stmt = $db->prepare("SELECT * FROM contacts ORDER BY $order LIMIT :lim OFFSET :off");
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT); // лимит записей на странице
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);   // смещение
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC); // получаем массив записей

    $html = '<h2>Просмотр записей</h2>'; // заголовок страницы

    // Если записей нет, выводим сообщение
    if ($total === 0) {
        $html .= '<p>Записей пока нет. Вы можете добавить новую запись через пункт меню «Добавление записи».</p>';
        return $html; // возвращаем только сообщение
    }

    // Формируем HTML-таблицу со всеми полями
    $html .= '<table>';
    $html .= '<tr>
                <th>Фамилия</th>
                <th>Имя</th>
                <th>Отчество</th>
                <th>Пол</th>
                <th>Дата рождения</th>
                <th>Телефон</th>
                <th>Адрес</th>
                <th>E-mail</th>
                <th>Комментарий</th>
              </tr>';

    // Проходим по всем записям и формируем строки таблицы
    foreach ($rows as $r) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($r['lastname']) . '</td>';    // фамилия
        $html .= '<td>' . htmlspecialchars($r['firstname']) . '</td>';   // имя
        $html .= '<td>' . htmlspecialchars($r['patronymic']) . '</td>';  // отчество
        $html .= '<td>' . htmlspecialchars($r['gender']) . '</td>';      // пол
        // Форматируем дату из YYYY-MM-DD в DD.MM.YYYY
        if (!empty($r['dob'])) {
            $date = DateTime::createFromFormat('Y-m-d', $r['dob']);
            $dobFormatted = $date ? $date->format('d.m.Y') : '';
        } else {
            $dobFormatted = '';
        }
        $html .= '<td>' . htmlspecialchars($dobFormatted) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['dob']) . '</td>';         // дата рождения
        $html .= '<td>' . htmlspecialchars($r['phone']) . '</td>';       // телефон
        $html .= '<td>' . htmlspecialchars($r['address']) . '</td>';     // адрес
        $html .= '<td>' . htmlspecialchars($r['email']) . '</td>';       // email
        $html .= '<td>' . htmlspecialchars($r['comment']) . '</td>';     // комментарий
        $html .= '</tr>';
    }

    $html .= '</table>'; // закрываем таблицу

    // Формируем пагинацию, если страниц больше одной
    if ($totalPages > 1) {
        $html .= '<div class="pagination">';
        for ($p = 1; $p <= $totalPages; $p++) {
            $activeClass = ($p == $page) ? ' active' : ''; // подсветка текущей страницы
            $html .= '<a class="page-link' . $activeClass . '" href="?action=view&sort=' . $sort . '&page=' . $p . '">' . $p . '</a>';
        }
        $html .= '</div>';
    }

    // Возвращаем готовый HTML код для отображения
    return $html;
}
