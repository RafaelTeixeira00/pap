<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
start_app_session();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$classDefinitions = require __DIR__ . DIRECTORY_SEPARATOR . 'aulas-config.php';
$classes = [];
foreach ($classDefinitions as $key => $class) {
    $classes[$key] = [
        'title' => t($class['title']),
        'description' => t($class['description']),
        'schedule' => $class['schedule'],
    ];
}
$calendarData = [
    'classes' => $classes,
    'serverNow' => time(),
    'serverOffsetMinutes' => (int) (date('Z') / 60),
    'weekdays' => [
        t('Segunda'), t('Terça'), t('Quarta'), t('Quinta'),
        t('Sexta'), t('Sábado'), t('Domingo'),
    ],
    'timePlaceholder' => t('Seleciona uma hora'),
    'selectionPrompt' => t('Escolhe um dia disponível no calendário.'),
];
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090909">
    <link rel="icon" type="image/png" href="Icon.png">
    <title><?= e(t('Tipos de aulas')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #c4b8ba; --line: #392127; }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            background: radial-gradient(ellipse at 8% 10%, rgba(237,16,37,.2), transparent 34%), radial-gradient(ellipse at 92% 90%, rgba(237,16,37,.14), transparent 38%), var(--bg);
            color: var(--text);
            font: 16px/1.6 Arial, Helvetica, sans-serif;
        }
        .page { width: min(100%, 1000px); margin: 0 auto; padding: clamp(22px, 5vw, 42px); border: 1px solid #55202a; border-radius: 16px; background: linear-gradient(145deg, #191214, #101010); box-shadow: 0 24px 70px rgba(0,0,0,.45); }
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
        .brand-logo { display: block; width: 110px; height: 104px; object-fit: contain; }
        .language-switch { display: flex; align-items: center; gap: 6px; margin: 0; }
        .language-switch select, .language-switch button { min-height: 36px; border: 1px solid #54202a; border-radius: 7px; background: #120d0e; color: #fff; font: inherit; font-size: 13px; }
        .language-switch select { padding: 0 8px; }
        .language-switch button { padding: 0 10px; cursor: pointer; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
        .eyebrow { margin: 0 0 8px; color: var(--red); font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0; color: #fff; font-size: clamp(30px, 5vw, 40px); }
        .intro { max-width: 700px; margin: 8px 0 28px; color: var(--muted); }
        .class-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .class-card { min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; padding: 18px; border: 1px solid #4b2027; border-left: 4px solid var(--red); border-radius: 12px; background: linear-gradient(145deg, #1a1113, #0d0b0c); box-shadow: 0 8px 24px rgba(237,16,37,.12); color: inherit; font: inherit; text-align: center; cursor: pointer; transition: transform .2s, border-color .2s, box-shadow .2s; }
        .class-card:hover, .class-card:focus-visible { transform: translateY(-3px); border-color: #9c2534; box-shadow: 0 12px 30px rgba(237,16,37,.2); }
        .class-card-title { color: #ff5362; font-size: 20px; font-weight: 800; }
        .class-card-action { color: var(--muted); font-size: 13px; }
        .class-details { margin-top: 28px; padding: clamp(18px, 4vw, 28px); border: 1px solid #67222e; border-radius: 14px; background: linear-gradient(145deg, #171012, #0d0b0c); box-shadow: 0 10px 32px rgba(237,16,37,.15); scroll-margin-top: 20px; }
        .class-details[hidden], .booking-panel[hidden] { display: none; }
        .detail-grid { display: grid; grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr); gap: 22px; align-items: start; }
        .detail-copy h2 { margin: 0 0 8px; color: #ff5362; font-size: clamp(22px, 4vw, 30px); }
        .detail-copy > p { margin: 0 0 18px; color: #d0c4c6; }
        .schedule-title, .booking-panel h3 { margin: 0 0 10px; font-size: 17px; }
        .schedule-days { display: flex; flex-wrap: wrap; gap: 8px; margin: 0; padding: 0; list-style: none; }
        .schedule-days li { padding: 7px 10px; border: 1px solid #67222e; border-radius: 7px; background: #1b1012; color: #ffe8eb; font-size: 13px; }
        .calendar-card, .booking-panel { padding: 18px; border: 1px solid #392127; border-radius: 10px; background: #090909; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
        .calendar-header h3 { margin: 0; font-size: 18px; }
        .month-button { width: 38px; height: 38px; border: 1px solid #67222e; border-radius: 7px; background: #1b1012; color: #fff; font-size: 20px; cursor: pointer; }
        .month-button:hover { border-color: var(--red); background: #341016; }
        .calendar-weekdays, .calendar-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; text-align: center; }
        .calendar-weekdays { margin-bottom: 6px; color: var(--muted); font-size: 12px; font-weight: 700; }
        .calendar-day { min-height: 38px; border: 1px solid transparent; border-radius: 7px; background: #171717; color: #777; font: inherit; }
        button.calendar-day { cursor: pointer; }
        button.calendar-day.available { border-color: #67222e; background: #241014; color: #fff; }
        button.calendar-day.available:hover, button.calendar-day.available[aria-pressed="true"] { border-color: var(--red); background: var(--red); color: #fff; }
        .calendar-legend { margin: 12px 0 0; color: var(--muted); font-size: 12px; }
        .calendar-selection { min-height: 24px; margin: 10px 0 0; color: #ff7581; font-size: 14px; }
        .booking-panel { margin-top: 18px; }
        .booking-form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .booking-field { display: flex; flex-direction: column; gap: 5px; color: #ddd; font-size: 13px; font-weight: 700; }
        .booking-field input, .booking-field select { width: 100%; min-height: 42px; padding: 8px 10px; border: 1px solid #353535; border-radius: 7px; background: #111; color: #fff; font: inherit; }
        .booking-field input:focus, .booking-field select:focus { outline: 2px solid rgba(237,16,37,.55); border-color: var(--red); }
        .booking-field.full, .booking-form .privacy-consent, .booking-form .submit-booking { grid-column: 1 / -1; }
        .privacy-consent { display: flex; align-items: flex-start; gap: 8px; color: var(--muted); font-size: 13px; font-weight: 400; }
        .privacy-consent input { width: 17px; min-height: 17px; margin: 2px 0 0; accent-color: var(--red); }
        .privacy-consent a { color: #fff; }
        .submit-booking { min-height: 46px; padding: 0 18px; border: 0; border-radius: 8px; background: var(--red); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .submit-booking:hover { background: #ff263b; }
        .page-links { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 26px; }
        .page-links a { display: inline-flex; min-height: 44px; align-items: center; padding: 0 15px; border: 1px solid #6b2530; border-radius: 8px; background: #1b1012; color: #ffe8eb; text-decoration: none; font-weight: 700; }
        .page-links a:first-child { border-color: var(--red); background: var(--red); color: #fff; }
        .page-links a:hover { border-color: #ff5362; background: #92202d; color: #fff; }
        @media (max-width: 700px) {
            body { padding: 14px; }
            .class-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .class-card { min-height: 0; }
            .detail-grid { grid-template-columns: 1fr; }
            .brand-logo { width: 88px; height: 84px; }
        }
        @media (max-width: 480px) {
            .class-grid { grid-template-columns: 1fr; }
            .booking-form { grid-template-columns: 1fr; }
            .booking-field.full, .booking-form .privacy-consent, .booking-form .submit-booking { grid-column: auto; }
        }
    </style>
</head>
<body>
<main class="page">
    <header class="page-header">
        <a href="index.php" aria-label="<?= e(t('PowerLift Gym, início')) ?>">
            <?php include __DIR__ . DIRECTORY_SEPARATOR . 'Icon.php'; ?>
        </a>
        <?= language_selector() ?>
    </header>
    <p class="eyebrow">PowerLift Gym</p>
    <h1><?= e(t('Tipos de aulas')) ?></h1>
    <p class="intro"><?= e(t('Explora as modalidades de treino disponíveis no ginásio e escolhe a que melhor se adapta aos teus objetivos.')) ?></p>
    <p class="intro"><?= e(t('Os horários semanais são uma proposta e todos os pedidos ficam sujeitos a confirmação pela equipa.')) ?></p>
    <section class="class-grid" aria-label="<?= e(t('Tipos de aulas')) ?>">
    <?php foreach ($classes as $classKey => $class): ?>
        <button class="class-card" type="button" data-class-key="<?= e($classKey) ?>">
            <span class="class-card-title"><?= e($class['title']) ?></span>
            <span class="class-card-action"><?= e(t('Ver descrição e calendário')) ?></span>
        </button>
        <?php endforeach; ?>
    </section>
    <section class="class-details" id="class-details" hidden aria-labelledby="selected-class-title">
    <div class="detail-grid">
        <div class="detail-copy">
            <h2 id="selected-class-title"></h2>
            <p id="selected-class-description"></p>
            <h3 class="schedule-title"><?= e(t('Horário semanal proposto')) ?></h3>
            <ul class="schedule-days" id="schedule-days"></ul>
        </div>
        <div class="calendar-card">
            <div class="calendar-header">
                <button class="month-button" id="previous-month" type="button" aria-label="<?= e(t('Mês anterior')) ?>">‹</button>
                <h3 id="calendar-month"></h3>
                <button class="month-button" id="next-month" type="button" aria-label="<?= e(t('Mês seguinte')) ?>">›</button>
            </div>
            <div class="calendar-weekdays" id="calendar-weekdays"></div>
            <div class="calendar-days" id="calendar-days" role="group" aria-label="<?= e(t('Calendário de aulas')) ?>"></div>
            <p class="calendar-legend"><?= e(t('Os dias com aulas estão destacados.')) ?></p>
            <p class="calendar-selection" id="calendar-selection" aria-live="polite"><?= e(t('Escolhe um dia disponível no calendário.')) ?></p>
        </div>
    </div>
    <div class="booking-panel" id="booking-panel" hidden>
        <h3><?= e(t('Pedir marcação')) ?></h3>
        <form class="booking-form" method="post" action="horario.php">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="class_key" id="booking-class-key">
            <input type="hidden" name="date" id="booking-date" required>
            <label class="booking-field">
                <?= e(t('Hora')) ?>
                <select name="time" id="booking-time" required>
                    <option value=""><?= e(t('Seleciona uma hora')) ?></option>
                </select>
            </label>
            <label class="booking-field">
                <?= e(t('Nome')) ?>
                <input name="name" autocomplete="name" required minlength="2" maxlength="120">
            </label>
            <label class="booking-field">
                <?= e(t('Email')) ?>
                <input name="email" type="email" autocomplete="email" required maxlength="254">
            </label>
            <label class="booking-field full">
                <?= e(t('Telefone')) ?>
                <input name="phone" type="tel" autocomplete="tel" required maxlength="30">
            </label>
            <label class="privacy-consent">
                <input name="privacy_consent" type="checkbox" value="1" required>
                <span><?= e(t('Li e aceito a')) ?> <a href="privacidade.php" target="_blank" rel="noopener"><?= e(t('Política de Privacidade')) ?></a>.</span>
            </label>
            <button class="submit-booking" type="submit"><?= e(t('Pedir marcação')) ?> →</button>
        </form>
    </div>
    </section>
    <nav class="page-links" aria-label="<?= e(t('Navegação principal')) ?>">
        <a href="index.php#marcar"><?= e(t('Marcar Horário')) ?></a>
        <a href="index.php"><?= e(t('← Voltar à página inicial')) ?></a>
    </nav>
</main>
<script type="application/json" id="class-calendar-data"><?= json_encode($calendarData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
    const calendarData = JSON.parse(document.querySelector('#class-calendar-data').textContent);
    const classDetails = document.querySelector('#class-details');
    const pageStartTimestamp = Date.now();
    const serverNowTimestamp = calendarData.serverNow * 1000;
    const serverOffsetMilliseconds = calendarData.serverOffsetMinutes * 60 * 1000;
    const firstServerDate = serverLocalDateTime();
    const monthDate = new Date(firstServerDate.getUTCFullYear(), firstServerDate.getUTCMonth(), 1);
    let activeClassKey = null;
    let selectedCalendarDate = null;

    const calendarElement = document.querySelector('#calendar-days');
    const monthLabel = document.querySelector('#calendar-month');
    const dateInput = document.querySelector('#booking-date');
    const timeSelect = document.querySelector('#booking-time');
    const bookingPanel = document.querySelector('#booking-panel');
    const selectionLabel = document.querySelector('#calendar-selection');

    calendarData.weekdays.forEach((weekday) => {
        const label = document.createElement('span');
        label.textContent = weekday.slice(0, 3);
        document.querySelector('#calendar-weekdays').append(label);
    });

    function renderCalendar() {
        const year = monthDate.getFullYear();
        const month = monthDate.getMonth();
        const selectedClass = calendarData.classes[activeClassKey];
        const now = serverLocalDateTime();
        const today = new Date(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate());
        monthLabel.textContent = monthDate.toLocaleDateString(document.documentElement.lang, { month: 'long', year: 'numeric' });
        calendarElement.replaceChildren();

        const firstWeekdayOffset = (new Date(year, month, 1).getDay() + 6) % 7;
        for (let blank = 0; blank < firstWeekdayOffset; blank++) {
            const spacer = document.createElement('span');
            spacer.setAttribute('aria-hidden', 'true');
            calendarElement.append(spacer);
        }

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        for (let day = 1; day <= daysInMonth; day++) {
            const dayDate = new Date(year, month, day);
            const weekDay = dayDate.getDay() || 7;
            const scheduledTimes = availableTimesForDate(
                dayDate,
                selectedClass.schedule[String(weekDay)] || []
            );
            const dateButton = document.createElement('button');
            dateButton.type = 'button';
            dateButton.className = 'calendar-day';
            dateButton.textContent = String(day);
            dateButton.disabled = scheduledTimes.length === 0 || dayDate < today;
            dateButton.setAttribute('aria-label', `${day} ${monthLabel.textContent}`);
            if (scheduledTimes.length > 0) {
                dateButton.classList.add('available');
                dateButton.setAttribute('aria-pressed', selectedCalendarDate === formatDate(dayDate) ? 'true' : 'false');
            }
            if (!dateButton.disabled) {
                dateButton.addEventListener('click', () => selectDate(dayDate, scheduledTimes));
            }
            calendarElement.append(dateButton);
        }
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function availableTimesForDate(date, times) {
        const now = serverLocalDateTime();
        const todayDate = new Date(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate());
        if (date > todayDate) {
            return times;
        }

        return times.filter((time) => {
            const [hour, minute] = time.split(':').map(Number);
            const slotTimestamp = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), hour, minute);
            const nowTimestamp = Date.UTC(
                now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(),
                now.getUTCHours(), now.getUTCMinutes(), now.getUTCSeconds(), now.getUTCMilliseconds()
            );
            return slotTimestamp > nowTimestamp;
        });
    }

    function serverLocalDateTime() {
        return new Date(
            serverNowTimestamp +
            (Date.now() - pageStartTimestamp) +
            serverOffsetMilliseconds
        );
    }

    function selectDate(date, times) {
        times = availableTimesForDate(date, times);
        if (times.length === 0) {
            return;
        }
        selectedCalendarDate = formatDate(date);
        dateInput.value = selectedCalendarDate;
        selectionLabel.textContent = date.toLocaleDateString(document.documentElement.lang, {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
        timeSelect.replaceChildren(new Option(calendarData.timePlaceholder, ''));
        times.forEach((time) => timeSelect.add(new Option(time, time)));
        bookingPanel.hidden = false;
        renderCalendar();
    }

    function clearSelectedDate() {
        selectedCalendarDate = null;
        dateInput.value = '';
        selectionLabel.textContent = calendarData.selectionPrompt;
        timeSelect.replaceChildren(new Option(calendarData.timePlaceholder, ''));
        bookingPanel.hidden = true;
    }

    document.querySelectorAll('.class-card').forEach((button) => {
        button.addEventListener('click', () => {
            activeClassKey = button.dataset.classKey;
            clearSelectedDate();
            const selectedClass = calendarData.classes[activeClassKey];
            document.querySelector('#selected-class-title').textContent = selectedClass.title;
            document.querySelector('#selected-class-description').textContent = selectedClass.description;
            document.querySelector('#booking-class-key').value = activeClassKey;
            const scheduleList = document.querySelector('#schedule-days');
            scheduleList.replaceChildren();
            Object.keys(selectedClass.schedule).sort((a, b) => Number(a) - Number(b)).forEach((dayKey) => {
                const item = document.createElement('li');
                item.textContent = `${calendarData.weekdays[Number(dayKey) - 1]}: ${selectedClass.schedule[dayKey].join(', ')}`;
                scheduleList.append(item);
            });

            classDetails.hidden = false;
            renderCalendar();
            classDetails.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelector('#previous-month').addEventListener('click', () => {
        monthDate.setMonth(monthDate.getMonth() - 1);
        clearSelectedDate();
        renderCalendar();
    });
    document.querySelector('#next-month').addEventListener('click', () => {
        monthDate.setMonth(monthDate.getMonth() + 1);
        clearSelectedDate();
        renderCalendar();
    });
</script>
</body>
</html>
