<?php
// Защита от прямого доступа к файлу
if (!defined('APP')) {
    die('Access denied'); // если константа APP не определена, скрипт завершает выполнение
}

// Функция для отображения интерфейса редактирования записи
function render_edit(PDO $db): string
{
    // Получаем список всех записей с минимальной информацией (id, фамилия, имя) для списка ссылок
    $all = $db->query("SELECT id, lastname, firstname FROM contacts ORDER BY lastname ASC, firstname ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Если записей нет, выводим сообщение и прекращаем выполнение
    if (count($all) === 0) {
        return '<p>Нет записей для редактирования.</p>';
    }

    // Определяем текущую запись для редактирования
    // Если edit_id в GET не задан, берём первую запись из списка
    $currentId = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : $all[0]['id'];

    $msg = ''; // переменная для вывода сообщений (успех/ошибка)

    // Обработка отправки формы редактирования
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $id = intval($_POST['id']); // id редактируемой записи
        $lastname = trim($_POST['lastname'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $patronymic = trim($_POST['patronymic'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        // Проверка обязательных полей
        if ($lastname === '' || $firstname === '') {
            $msg = '<div class="error">Ошибка: фамилия и имя обязательны.</div>';
        } else {
            // Подготавливаем запрос на обновление записи
            $stmt = $db->prepare("UPDATE contacts SET lastname=?, firstname=?, patronymic=?, gender=?, dob=?, phone=?, address=?, email=?, comment=? WHERE id=?");
            $ok = $stmt->execute([$lastname, $firstname, $patronymic, $gender, $dob, $phone, $address, $email, $comment, $id]);
            
            // Сообщение об успехе или ошибке
            $msg = $ok ? '<div class="success">Запись обновлена</div>' : '<div class="error">Ошибка: запись не обновлена</div>';
        }

        // После обработки формы оставляем редактируемую запись текущей
        $currentId = $id;
    }

    // Загружаем данные текущей записи из базы
    $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$currentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Если запись не найдена, выводим сообщение об ошибке
    if (!$row) {
        return '<div class="error">Выбранная запись не найдена.</div>';
    }

    $html = '<h2>Редактирование записи</h2>';

    // Формируем список ссылок на все записи для быстрого выбора
    $html .= '<div style="margin-bottom: 20px;">';
    foreach ($all as $a) {
        $activeClass = ($a['id'] == $currentId) ? ' active' : ''; // подсветка текущей записи
        $html .= '<a class="record-link' . $activeClass . '" href="?action=edit&edit_id=' . $a['id'] . '">' .
                 htmlspecialchars($a['lastname'] . ' ' . $a['firstname']) . '</a>';
    }
    $html .= '</div>';

    // Вывод сообщения об успехе или ошибке после отправки формы
    $html .= $msg;

    // Формируем значение для поля input type="date"
    $dobValue = '';
    if (!empty($row['dob'])) {
        // Преобразуем дату из формата БД (YYYY-MM-DD) в формат для input date
        $dobValue = htmlspecialchars($row['dob']);
    }

    // Начало формы редактирования
    $html .= '<form method="post">';
    $html .= '<input type="hidden" name="id" value="' . $currentId . '">'; // скрытое поле с id записи

    // Поля формы с предзаполненными значениями
    $html .= '<div class="form-row"><label>Фамилия *</label><input type="text" name="lastname" value="' . htmlspecialchars($row['lastname']) . '" required></div>';
    $html .= '<div class="form-row"><label>Имя *</label><input type="text" name="firstname" value="' . htmlspecialchars($row['firstname']) . '" required></div>';
    $html .= '<div class="form-row"><label>Отчество</label><input type="text" name="patronymic" value="' . htmlspecialchars($row['patronymic']) . '"></div>';

    // Пол с выбором "муж" или "жен"
    $selM = $row['gender'] === 'муж' ? 'selected' : '';
    $selF = $row['gender'] === 'жен' ? 'selected' : '';
    $html .= '<div class="form-row"><label>Пол</label>
              <select name="gender">
                <option value="">-- Выберите --</option>
                <option value="муж" ' . $selM . '>муж</option>
                <option value="жен" ' . $selF . '>жен</option>
              </select></div>';

    // Поле даты рождения
    $html .= '<div class="form-row"><label>Дата рождения</label><input type="date" name="dob" value="' . $dobValue . '"></div>';

    $html .= '<div class="form-row"><label>Телефон</label><input type="tel" name="phone" value="' . htmlspecialchars($row['phone']) . '"></div>';
    $html .= '<div class="form-row"><label>Адрес</label><input type="text" name="address" value="' . htmlspecialchars($row['address']) . '"></div>';
    $html .= '<div class="form-row"><label>E-mail</label><input type="email" name="email" value="' . htmlspecialchars($row['email']) . '"></div>';
    $html .= '<div class="form-row"><label>Комментарий</label><textarea name="comment" rows="3">' . htmlspecialchars($row['comment']) . '</textarea></div>';

    // Кнопка отправки формы
    $html .= '<button type="submit">Сохранить изменения</button>';
    $html .= '</form>';

    // Возвращаем сформированный HTML
    return $html;
}
