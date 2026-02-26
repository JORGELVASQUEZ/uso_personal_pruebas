<?php
session_start();

// Helper: generate a problem based on max operand and difficulty
function generate_problem($max = 20, $difficulty = 'medio') {
    // Make difficulty changes more noticeable:
    // - 'facil': only + and - with small numbers
    // - 'medio': +, -, * with moderate numbers
    // - 'dificil': larger numbers, sometimes two-operator expressions including * and /
    if ($difficulty === 'facil') {
        $ops = ['+', '-'];
        $op = $ops[array_rand($ops)];
        $a = rand(0, max(5, intval($max/2)));
        $b = rand(0, max(5, intval($max/2)));
        if ($op == '+') $answer = $a + $b; else { $a = max($a,$b); $answer = $a - $b; }
        $expr = "$a $op $b";
    } elseif ($difficulty === 'dificil') {
        // with some probability create a two-operator expression
        if (rand(1,100) <= 50) {
            // two operators, ensure integers for division by construction
            $ops = ['+', '-', '*', '/'];
            $op1 = $ops[array_rand($ops)];
            $op2 = $ops[array_rand($ops)];
            $a = rand(1, max(2,$max));
            $b = rand(1, max(2,intval($max/2)));
            $c = rand(1, max(2,intval($max/3)));
            // ensure division produces integer by adjusting operands when needed
            if ($op1 == '/') {
                $q = rand(1, max(1,intval($max/3)));
                $a = $b * $q;
            }
            if ($op2 == '/') {
                $q = rand(1, max(1,intval($max/4)));
                $b = $c * $q;
            }
            $expr = "$a $op1 $b $op2 $c";
            // compute answer safely using eval after validation
            $safe = preg_replace('/[^0-9+\-\*\/\s]/', '', $expr);
            // eval the expression and return the value
            $answer_val = 0;
            try { $answer_val = @eval('return (' . $safe . ');'); } catch (Exception $e) { $answer_val = 0; }
            $answer = intval($answer_val);
        } else {
            // single op but larger numbers
            $ops = ['*','/','+','-'];
            $op = $ops[array_rand($ops)];
            if ($op == '*') {
                $a = rand(intval($max/2), $max);
                $b = rand(2, max(2,intval($max/3)));
                $answer = $a * $b;
            } elseif ($op == '/') {
                $b = rand(2, max(2,intval($max/3)));
                $q = rand(1, max(1,intval($max/2)));
                $a = $b * $q;
                $answer = $q;
            } else {
                $a = rand(0, $max);
                $b = rand(0, $max);
                $answer = $op == '+' ? $a + $b : max($a,$b) - min($a,$b);
            }
            $expr = "$a $op $b";
        }
    } else {
        // medio
        $ops = ['+','-','*'];
        $op = $ops[array_rand($ops)];
        if ($op == '*') {
            $a = rand(0, max(2,intval($max/2)));
            $b = rand(0, max(2,intval($max/2)));
            $answer = $a * $b;
        } else {
            $a = rand(0, $max);
            $b = rand(0, $max);
            $answer = $op == '+' ? $a + $b : max($a,$b) - min($a,$b);
        }
        $expr = "$a $op $b";
    }
    return ['expr' => $expr, 'answer' => intval($answer)];
}

// Initialize defaults and handle actions
if (!isset($_SESSION['rounds'])) {
    $_SESSION['rounds'] = 0;
    $_SESSION['correct'] = 0;
    $_SESSION['timed_out'] = 0;
}

$message = '';
$error = '';

// Save score to a simple JSON file (append entry)
function save_score_record($data) {
    $file = __DIR__ . '/scores.json';
    $existing = [];
    if (file_exists($file)) {
        $txt = @file_get_contents($file);
        $existing = $txt ? json_decode($txt, true) ?? [] : [];
    }
    $existing[] = $data;
    @file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT));
}

// Load recent scores (most recent last)
function load_recent_scores($limit = 5) {
    $file = __DIR__ . '/scores.json';
    if (!file_exists($file)) return [];
    $txt = @file_get_contents($file);
    $all = $txt ? json_decode($txt, true) ?? [] : [];
    return array_slice(array_reverse($all), 0, $limit);
}

// Handle start/new game form (includes difficulty)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start') {
    $difficulty = $_POST['difficulty'] ?? 'medio';
    $difficulty = in_array($difficulty, ['facil','medio','dificil']) ? $difficulty : 'medio';

    // map difficulty to defaults
    $map = [
        'facil' => ['max' => 10, 'time' => 15, 'attempts' => 3],
        'medio' => ['max' => 20, 'time' => 10, 'attempts' => 2],
        'dificil' => ['max' => 50, 'time' => 7, 'attempts' => 1],
    ];
    $defaults = $map[$difficulty];

    // Use user-provided values if present (non-empty), otherwise defaults by difficulty
    $time_raw = $_POST['time_limit'] ?? '';
    if (trim((string)$time_raw) === '') {
        $time_limit = $defaults['time'];
    } else {
        $time_limit = max(1, intval($time_raw));
    }

    $attempts_raw = $_POST['attempts'] ?? '';
    if (trim((string)$attempts_raw) === '') {
        $attempts = $defaults['attempts'];
    } else {
        $attempts = max(1, intval($attempts_raw));
    }

    $_SESSION['difficulty'] = $difficulty;
    $_SESSION['max_operand'] = $defaults['max'];
    $_SESSION['time_limit'] = $time_limit;
    $_SESSION['attempts_limit'] = $attempts;
    $_SESSION['rounds'] = 0;
    $_SESSION['correct'] = 0;
    $_SESSION['timed_out'] = 0;

    $p = generate_problem($_SESSION['max_operand'], $_SESSION['difficulty']);
    $_SESSION['expr'] = $p['expr'];
    $_SESSION['answer'] = $p['answer'];
    $_SESSION['attempts_left'] = $attempts;
    $_SESSION['start_time'] = time();
}

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'answer') {
    // ensure we have a problem
    if (!isset($_SESSION['expr'])) {
        $error = 'No hay pregunta activa. Inicia una nueva ronda.';
    } else {
        $now = time();
        $elapsed = $now - ($_SESSION['start_time'] ?? $now);
        $time_limit = $_SESSION['time_limit'] ?? 10;

        if ($elapsed > $time_limit) {
            $_SESSION['rounds']++;
            $_SESSION['timed_out']++;
            $message = 'Tiempo agotado. La respuesta correcta era: ' . $_SESSION['answer'];
            // mark as no active problem (user can click "Siguiente")
            unset($_SESSION['expr'], $_SESSION['answer'], $_SESSION['start_time']);
        } else {
            $user = trim($_POST['user_answer'] ?? '');
            if ($user === '') {
                $error = 'Introduce una respuesta.';
            } else {
                // numeric compare
                if (is_numeric($user)) {
                    $val = $user + 0;
                    if ($val == $_SESSION['answer']) {
                        $_SESSION['rounds']++;
                        $_SESSION['correct']++;
                        $message = '¡Correcto!';
                        unset($_SESSION['expr'], $_SESSION['answer'], $_SESSION['start_time']);
                    } else {
                        $_SESSION['attempts_left'] = max(0, ($_SESSION['attempts_left'] ?? 1) - 1);
                        if (($_SESSION['attempts_left'] ?? 0) <= 0) {
                            $_SESSION['rounds']++;
                            $message = 'Incorrecto. Agotaste los intentos. La respuesta era: ' . $_SESSION['answer'];
                            unset($_SESSION['expr'], $_SESSION['answer'], $_SESSION['start_time']);
                        } else {
                            $error = 'Incorrecto. Te quedan ' . $_SESSION['attempts_left'] . ' intento(s).';
                        }
                    }
                } else {
                    $error = 'Respuesta no válida. Usa números.';
                }
            }
        }
    }
}

// Handle next round
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'next') {
    $max = $_SESSION['max_operand'] ?? 20;
    $p = generate_problem($max, $_SESSION['difficulty'] ?? 'medio');
    $_SESSION['expr'] = $p['expr'];
    $_SESSION['answer'] = $p['answer'];
    $_SESSION['attempts_left'] = $_SESSION['attempts_limit'] ?? 2;
    $_SESSION['start_time'] = time();
}

// Handle quit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quit') {
    // clear session game variables
    // Before clearing, save final result to scores.json
    $record = [
        'timestamp' => time(),
        'rounds' => intval($_SESSION['rounds'] ?? 0),
        'correct' => intval($_SESSION['correct'] ?? 0),
        'timed_out' => intval($_SESSION['timed_out'] ?? 0),
        'difficulty' => $_SESSION['difficulty'] ?? 'medio',
    ];
    save_score_record($record);

    unset($_SESSION['expr'], $_SESSION['answer'], $_SESSION['start_time'], $_SESSION['attempts_left'], $_SESSION['time_limit'], $_SESSION['attempts_limit']);
    $message = 'Juego finalizado. Resultado guardado.';
}

// Prepare view variables
$expr = $_SESSION['expr'] ?? null;
$attempts_left = $_SESSION['attempts_left'] ?? ($_SESSION['attempts_limit'] ?? 0);
$time_limit = $_SESSION['time_limit'] ?? 10;
$start_time = $_SESSION['start_time'] ?? null;
// load recent scores for display
$recent_scores = load_recent_scores(5);

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Juego matemático</title>
    <link rel="stylesheet" href="game.css">
</head>
<body>
<div class="container">
    <h1>Juego matemático</h1>

    <div class="stats">
        <span>Rondas: <?php echo intval($_SESSION['rounds'] ?? 0); ?></span>
        <span>Correctas: <?php echo intval($_SESSION['correct'] ?? 0); ?></span>
        <span>Tiempo agotado: <?php echo intval($_SESSION['timed_out'] ?? 0); ?></span>
    </div>

    <?php if (!empty($recent_scores)): ?>
        <div class="recent">
            <h3>Últimos resultados</h3>
            <ul>
                <?php foreach($recent_scores as $r): ?>
                    <li><?php echo date('Y-m-d H:i', $r['timestamp']); ?> — dificultad: <?php echo htmlspecialchars($r['difficulty']); ?> — correctas: <?php echo intval($r['correct']); ?> / <?php echo intval($r['rounds']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$expr): ?>
        <form method="post">
            <input type="hidden" name="action" value="start">
            <label>Dificultad:
                <select name="difficulty">
                    <option value="facil" <?php echo ($_SESSION['difficulty'] ?? 'medio') === 'facil' ? 'selected' : ''; ?>>Fácil</option>
                    <option value="medio" <?php echo ($_SESSION['difficulty'] ?? 'medio') === 'medio' ? 'selected' : ''; ?>>Medio</option>
                    <option value="dificil" <?php echo ($_SESSION['difficulty'] ?? 'medio') === 'dificil' ? 'selected' : ''; ?>>Difícil</option>
                </select>
            </label>
            <label>Tiempo por pregunta (segundos, vacío = por defecto según dificultad): <input name="time_limit" type="number" value="" min="1" placeholder="por defecto"></label>
            <label>Intentos por pregunta (vacío = por defecto según dificultad): <input name="attempts" type="number" value="" min="1" placeholder="por defecto"></label>
            <div class="buttons">
                <button type="submit">Iniciar</button>
                <button type="submit" name="action" value="quit">Salir</button>
            </div>
        </form>
        <?php if ($message): ?><p class="message"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <?php else: ?>
        <div class="question">
            <h2>Resuelve: <span class="expr"><?php echo htmlspecialchars($expr); ?></span></h2>
            <p>Intentos restantes: <strong><?php echo intval($attempts_left); ?></strong></p>
            <p>Tiempo restante: <strong id="time">--</strong> s</p>

            <?php if ($error): ?><p class="error" id="serverError"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <?php if ($message): ?><p class="message server-message" id="serverMessage"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>

            <form method="post" id="answerForm">
                <input type="hidden" name="action" value="answer">
                <input type="text" name="user_answer" id="user_answer" autocomplete="off" autofocus>
                <div class="buttons">
                    <button type="submit">Enviar</button>
                    <button type="submit" name="action" value="quit">Salir</button>
                </div>
            </form>

        </div>
        <form method="post">
            <input type="hidden" name="action" value="next">
            <button type="submit">Siguiente</button>
        </form>

    <?php endif; ?>

    <footer>
        <p>Escribe <strong>q</strong> o pulsa "Salir" para terminar el juego en cualquier momento.</p>
    </footer>
</div>

<script>
// Countdown and auto-submit when time runs out (uses server start_time)
<?php if ($start_time): ?>
let serverStart = <?php echo intval($start_time); ?>;
let timeLimit = <?php echo intval($time_limit); ?>;
let endAt = serverStart + timeLimit;

function updateTimer(){
    let now = Math.floor(Date.now()/1000);
    let left = endAt - now;
    let el = document.getElementById('time');
    if (!el) return;
    if (left <= 0){
        el.textContent = '0';
        // auto-submit a blank answer to trigger server timeout handling
        let form = document.getElementById('answerForm');
        if (form){
            // create hidden field to indicate timeout submission
            let inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'user_answer'; inp.value = '';
            form.appendChild(inp);
            form.submit();
        }
    } else {
        el.textContent = left;
        setTimeout(updateTimer, 300);
    }
}
updateTimer();
<?php endif; ?>

// Sound helpers (WebAudio) and animations
function createAudioContext(){
    try{ return new (window.AudioContext || window.webkitAudioContext)(); } catch(e){ return null; }
}
const audioCtx = createAudioContext();
function playTone(freq, duration=0.15, type='sine', gain=0.07){
    if(!audioCtx) return;
    const o = audioCtx.createOscillator();
    const g = audioCtx.createGain();
    o.type = type; o.frequency.value = freq;
    g.gain.value = gain;
    o.connect(g); g.connect(audioCtx.destination);
    o.start();
    setTimeout(()=>{ o.stop(); }, duration*1000);
}
function playCorrect(){
    // celebratory melody sequence
    const seq = [440,660,880,1100];
    let t = 0;
    seq.forEach((f,i)=>{
        setTimeout(()=>playTone(f, 0.12, 'sine', 0.09), t*100);
        t += 1.2;
    });
    // layered twinkle
    setTimeout(()=>{ playTone(1320,0.08,'triangle',0.06); playTone(1760,0.08,'sine',0.05); }, 600);
    // larger confetti
    createConfetti(); createConfetti();
    // highlight server message visually
    const sm = document.getElementById('serverMessage');
    if(sm){ sm.classList.add('show'); sm.classList.add('correct'); setTimeout(()=>sm.classList.remove('show'),900); }
}
function playWrong(){ playTone(120,0.35,'sawtooth',0.14); }
function playTimeout(){ playTone(200,0.45,'sine',0.14); }

function playNext(){ playTone(520,0.12,'sine',0.06); playTone(720,0.08,'square',0.04); }

// Visual animations
function animateCorrect(){
    const c = document.querySelector('.container');
    if(!c) return;
    c.classList.add('pulse');
    setTimeout(()=>c.classList.remove('pulse'),700);
}
function animateWrong(){
    const q = document.querySelector('.question');
    if(!q) return;
    q.classList.add('shake');
    setTimeout(()=>q.classList.remove('shake'),700);
}

// Simple confetti
function createConfetti(){
    const colors = ['#ff5e5e','#ffd166','#6ee7b7','#60a5fa','#b794f4'];
    const count = 30;
    const container = document.body;
    for(let i=0;i<count;i++){
        const el = document.createElement('div');
        el.className = 'confetti';
        el.style.background = colors[Math.floor(Math.random()*colors.length)];
        el.style.left = Math.random()*100 + '%';
        el.style.top = '-10%';
        el.style.transform = 'rotate('+Math.random()*360+'deg)';
        el.style.opacity = '0.95';
        container.appendChild(el);
        setTimeout(()=> el.remove(), 2200);
    }
}

// React to server-side message/error
let serverMessage = <?php echo json_encode($message); ?>;
let serverError = <?php echo json_encode($error); ?>;
if(serverMessage){
    const smEl = document.getElementById('serverMessage');
    if(serverMessage.indexOf('Correcto') !== -1){ playCorrect(); animateCorrect(); if(smEl) smEl.classList.add('correct'); }
    else if(serverMessage.indexOf('Incorrecto') !== -1){ playWrong(); animateWrong(); if(smEl) smEl.classList.add('wrong'); }
    else if(serverMessage.indexOf('Tiempo agotado') !== -1){ playTimeout(); animateWrong(); if(smEl) smEl.classList.add('wrong'); }
}
if(serverError){ const errEl = document.getElementById('serverError'); if(errEl) { errEl.classList.add('wrong'); } animateWrong(); playWrong(); }

// Play a sound when clicking Siguiente
const nextBtn = document.querySelector('form input[value="next"] , form button');
// attach to the specific Next button (the form with hidden action=next)
const nextFormBtn = document.querySelector('form[action] button');
// simpler: attach to any button with text Content 'Siguiente'
Array.from(document.querySelectorAll('button')).forEach(b=>{
    if(b.textContent.trim().toLowerCase() === 'siguiente') b.addEventListener('click', ()=>{ playNext(); });
});
</script>
</body>
</html>
