<?php
// Защита от прямого доступа к файлу
if (!defined('APP')) {
    die('Access denied'); // если константа APP не определена, скрипт завершает выполнение
}

// Функция для отображения формы добавления новой записи
function render_add(PDO $db): string
{
    $msg = ''; // переменная для вывода сообщений об успехе или ошибке

    // Обработка отправки формы
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Получаем все поля из POST-запроса, обрезая лишние пробелы
        $lastname = trim($_POST['lastname'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $patronymic = trim($_POST['patronymic'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        // Проверка обязательных полей: фамилия и имя
        if ($lastname === '' || $firstname === '') {
            $msg = '<div class="error">Ошибка: фамилия и имя обязательны.</div>';
        } else {
            try {
                // Подготавливаем запрос на вставку новой записи
                $stmt = $db->prepare("INSERT INTO contacts (lastname, firstname, patronymic, gender, dob, phone, address, email, comment) VALUES (?,?,?,?,?,?,?,?,?)");
                // Выполняем запрос с передачей значений
                $stmt->execute([$lastname, $firstname, $patronymic, $gender, $dob, $phone, $address, $email, $comment]);
                // Сообщение об успешном добавлении
                $msg = '<div class="success">Запись добавлена</div>';
            } catch (Exception $e) {
                // Сообщение об ошибке при добавлении записи
                $msg = '<div class="error">Ошибка: запись не добавлена</div>';
            }
        }
    }

    // Начало формирования HTML-кода
    $html = $msg; // сначала выводим сообщение
    $html .= '<h2>Добавление записи</h2>';
    $html .= '<form method="post">'; // форма отправки методом POST

    // Поля формы
    $html .= '<div class="form-row"><label>Фамилия *</label><input type="text" name="lastname" required></div>';
    $html .= '<div class="form-row"><label>Имя *</label><input type="text" name="firstname" required></div>';
    $html .= '<div class="form-row"><label>Отчество</label><input type="text" name="patronymic"></div>';

    // Пол с выбором "муж" или "жен"
    $html .= '<div class="form-row"><label>Пол</label>
              <select name="gender">
                <option value="">-- Выберите --</option>
                <option value="муж">муж</option>
                <option value="жен">жен</option>
              </select></div>';

    // Дата рождения
    $html .= '<div class="form-row"><label>Дата рождения</label><input type="date" name="dob"></div>';
    // Телефон
    $html .= '<div class="form-row"><label>Телефон</label><input type="tel" name="phone"></div>';
    // Адрес
    $html .= '<div class="form-row"><label>Адрес</label><input type="text" name="address"></div>';
    // E-mail
    $html .= '<div class="form-row"><label>E-mail</label><input type="email" name="email"></div>';
    // Комментарий
    $html .= '<div class="form-row"><label>Комментарий</label><textarea name="comment" rows="3"></textarea></div>';

    // Кнопка отправки формы
    $html .= '<button type="submit">Добавить запись</button>';
    $html .= '</form>';

    // Возвращаем HTML-код формы
    return $html;
}
