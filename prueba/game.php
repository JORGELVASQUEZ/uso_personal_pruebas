<?php
session_start();

function generate_problem($max = 20, $difficulty = 'medio') {
    if ($difficulty === 'facil') {
        $ops = ['+', '-'];
        $op = $ops[array_rand($ops)];
        $a = rand(0, max(5, intval($max/2)));
        $b = rand(0, max(5, intval($max/2)));
        $expr = "$a $op $b";
        // Evaluar la expresión tal cual para permitir resultados negativos (p.ej. 9 - 13 = -4)
        $safe = preg_replace('/[^0-9+\-\*\/\s]/', '', $expr);
        $answer_val = 0;
        try { $answer_val = @eval('return (' . $safe . ');'); } catch (Exception $e) { $answer_val = 0; }
        $answer = intval($answer_val);
    } elseif ($difficulty === 'dificil') {
        if (rand(1,100) <= 50) {
            $ops = ['+', '-', '*', '/'];
            $op1 = $ops[array_rand($ops)];
            $op2 = $ops[array_rand($ops)];
            $a = rand(1, max(2,$max));
            $b = rand(1, max(2,intval($max/2)));
            $c = rand(1, max(2,intval($max/3)));
            if ($op1 == '/') {
                $q = rand(1, max(1,intval($max/3)));
                $a = $b * $q;
            }
            if ($op2 == '/') {
                $q = rand(1, max(1,intval($max/4)));
                $b = $c * $q;
            }
            $expr = "$a $op1 $b $op2 $c";
            $safe = preg_replace('/[^0-9+\-\*\/\s]/', '', $expr);
            $answer_val = 0;
            try { $answer_val = @eval('return (' . $safe . ');'); } catch (Exception $e) { $answer_val = 0; }
            $answer = intval($answer_val);
            } else {
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
                $expr = "$a $op $b";
                $safe = preg_replace('/[^0-9+\-\*\/\s]/', '', $expr);
                $answer_val = 0;
                try { $answer_val = @eval('return (' . $safe . ');'); } catch (Exception $e) { $answer_val = 0; }
                $answer = intval($answer_val);
            }
            $expr = "$a $op $b";
        }
    } else {
        $ops = ['+','-','*'];
        $op = $ops[array_rand($ops)];
        if ($op == '*') {
            $a = rand(0, max(2,intval($max/2)));
            $b = rand(0, max(2,intval($max/2)));
            $answer = $a * $b;
        } else {
            $a = rand(0, $max);
            $b = rand(0, $max);
            $expr = "$a $op $b";
            $safe = preg_replace('/[^0-9+\-\*\/\s]/', '', $expr);
            $answer_val = 0;
            try { $answer_val = @eval('return (' . $safe . ');'); } catch (Exception $e) { $answer_val = 0; }
            $answer = intval($answer_val);
        }
        if (!isset($expr)) $expr = "$a $op $b";
    }
    return ['expr' => $expr, 'answer' => intval($answer)];
}

if (!isset($_SESSION['rounds'])) {
    $_SESSION['rounds'] = 0;
    $_SESSION['correct'] = 0;
    $_SESSION['timed_out'] = 0;
    $_SESSION['incorrect'] = 0;
}

$message = '';
$error = '';

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

function load_recent_scores($limit = 5) {
    $file = __DIR__ . '/scores.json';
    if (!file_exists($file)) return [];
    $txt = @file_get_contents($file);
    $all = $txt ? json_decode($txt, true) ?? [] : [];
    return array_slice(array_reverse($all), 0, $limit);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start') {
    $difficulty = $_POST['difficulty'] ?? 'medio';
    $difficulty = in_array($difficulty, ['facil','medio','dificil']) ? $difficulty : 'medio';

    $map = [
        'facil' => ['max' => 10, 'time' => 15, 'attempts' => 3],
        'medio' => ['max' => 20, 'time' => 10, 'attempts' => 2],
        'dificil' => ['max' => 50, 'time' => 7, 'attempts' => 1],
    ];
    $defaults = $map[$difficulty];

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
    $_SESSION['incorrect'] = 0;

    $p = generate_problem($_SESSION['max_operand'], $_SESSION['difficulty']);
    $_SESSION['expr'] = $p['expr'];
    $_SESSION['answer'] = $p['answer'];
    $_SESSION['attempts_left'] = $attempts;
    $_SESSION['start_time'] = time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'answer') {
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

            // Generar nueva pregunta automáticamente (el juego continúa hasta que el usuario pulse Salir)
            // Contar los timeouts también como incorrectas
            $_SESSION['incorrect'] = ($_SESSION['incorrect'] ?? 0) + 1;

            $max = $_SESSION['max_operand'] ?? 20;
            $p = generate_problem($max, $_SESSION['difficulty'] ?? 'medio');
            $_SESSION['expr'] = $p['expr'];
            $_SESSION['answer'] = $p['answer'];
            $_SESSION['attempts_left'] = $_SESSION['attempts_limit'] ?? 2;
            $_SESSION['start_time'] = time();
        } else {
            $userRaw = trim($_POST['user_answer'] ?? '');
            if ($userRaw === '') {
                $error = 'Introduce una respuesta.';
            } else {
                // Normalizar guiones/minus unicode y espacios comunes del input
                $userNorm = str_replace(array('−', '—', '–'), '-', $userRaw);
                // Eliminar espacios entre signo y número (ej: '- 5' -> '-5')
                $userNorm = preg_replace('/\s+/', '', $userNorm);
                // Aceptar coma decimal como punto
                $userNorm = str_replace(',', '.', $userNorm);

                if (is_numeric($userNorm)) {
                    // Forzar conversión numérica segura
                    $val = $userNorm + 0;
                    if ($val == $_SESSION['answer']) {
                        $_SESSION['rounds']++;
                        $_SESSION['correct']++;
                        $message = '¡Correcto!';

                        // Generar siguiente pregunta automáticamente
                        $max = $_SESSION['max_operand'] ?? 20;
                        $p = generate_problem($max, $_SESSION['difficulty'] ?? 'medio');
                        $_SESSION['expr'] = $p['expr'];
                        $_SESSION['answer'] = $p['answer'];
                        $_SESSION['attempts_left'] = $_SESSION['attempts_limit'] ?? 2;
                        $_SESSION['start_time'] = time();
                    } else {
                        $_SESSION['attempts_left'] = max(0, ($_SESSION['attempts_left'] ?? 1) - 1);
                        if (($_SESSION['attempts_left'] ?? 0) <= 0) {
                            $_SESSION['rounds']++;
                            $_SESSION['incorrect'] = ($_SESSION['incorrect'] ?? 0) + 1;
                            $message = 'Incorrecto. Agotaste los intentos. La respuesta era: ' . $_SESSION['answer'];

                            // Generar nueva pregunta automáticamente
                            $max = $_SESSION['max_operand'] ?? 20;
                            $p = generate_problem($max, $_SESSION['difficulty'] ?? 'medio');
                            $_SESSION['expr'] = $p['expr'];
                            $_SESSION['answer'] = $p['answer'];
                            $_SESSION['attempts_left'] = $_SESSION['attempts_limit'] ?? 2;
                            $_SESSION['start_time'] = time();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'next') {
    $max = $_SESSION['max_operand'] ?? 20;
    $p = generate_problem($max, $_SESSION['difficulty'] ?? 'medio');
    $_SESSION['expr'] = $p['expr'];
    $_SESSION['answer'] = $p['answer'];
    $_SESSION['attempts_left'] = $_SESSION['attempts_limit'] ?? 2;
    $_SESSION['start_time'] = time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quit') {
    $record = [
        'timestamp' => time(),
        'rounds' => intval($_SESSION['rounds'] ?? 0),
        'correct' => intval($_SESSION['correct'] ?? 0),
        'timed_out' => intval($_SESSION['timed_out'] ?? 0),
        'incorrect' => intval($_SESSION['incorrect'] ?? 0),
        'difficulty' => $_SESSION['difficulty'] ?? 'medio',
    ];
    save_score_record($record);

    unset($_SESSION['expr'], $_SESSION['answer'], $_SESSION['start_time'], $_SESSION['attempts_left'], $_SESSION['time_limit'], $_SESSION['attempts_limit']);
    $r = intval($_SESSION['rounds'] ?? 0);
    $c = intval($_SESSION['correct'] ?? 0);
    $i = intval($_SESSION['incorrect'] ?? 0);
    $message = 'Juego finalizado. Rondas: ' . $r . ' — Correctas: ' . $c . ' — Incorrectas: ' . $i . '. Resultado guardado.';
}

$expr = $_SESSION['expr'] ?? null;
$attempts_left = $_SESSION['attempts_left'] ?? ($_SESSION['attempts_limit'] ?? 0);
$time_limit = $_SESSION['time_limit'] ?? 10;
$start_time = $_SESSION['start_time'] ?? null;
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
        <span>Incorrectas: <?php echo intval($_SESSION['incorrect'] ?? 0); ?></span>
    </div>

    <?php if (!empty($recent_scores)): ?>
        <div class="recent">
            <h3>Últimos resultados</h3>
            <ul>
                <?php foreach($recent_scores as $r): ?>
                    <li><?php echo date('Y-m-d H:i', $r['timestamp']); ?> — dificultad: <?php echo htmlspecialchars($r['difficulty']); ?> — correctas: <?php echo intval($r['correct']); ?> / <?php echo intval($r['rounds']); ?> — incorrectas: <?php echo intval($r['incorrect'] ?? 0); ?></li>
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
            
            <div class="input-group">
                <label>Tiempo por pregunta (segundos):</label>
                <div class="input-control">
                    <button type="button" class="btn-control" id="timeDecrement">−</button>
                    <input name="time_limit" type="number" value="" min="1" placeholder="por defecto" id="timeInput" class="input-small">
                    <button type="button" class="btn-control" id="timeIncrement">+</button>
                </div>
            </div>
            
            <div class="input-group">
                <label>Intentos por pregunta:</label>
                <div class="input-control">
                    <button type="button" class="btn-control" id="attemptsDecrement">−</button>
                    <input name="attempts" type="number" value="" min="1" placeholder="por defecto" id="attemptsInput" class="input-small">
                    <button type="button" class="btn-control" id="attemptsIncrement">+</button>
                </div>
            </div>
            
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

    <?php endif; ?>

    <footer>
        <p>Escribe <strong>q</strong> o pulsa "Salir" para terminar el juego en cualquier momento.</p>
    </footer>
</div>

<script>
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
        let form = document.getElementById('answerForm');
        if (form){
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
    const seq = [440,660,880,1100];
    let t = 0;
    seq.forEach((f,i)=>{
        setTimeout(()=>playTone(f, 0.12, 'sine', 0.09), t*100);
        t += 1.2;
    });

    setTimeout(()=>{ playTone(1320,0.08,'triangle',0.06); playTone(1760,0.08,'sine',0.05); }, 600);

    createConfetti(); createConfetti();

    const sm = document.getElementById('serverMessage');
    if(sm){ sm.classList.add('show'); sm.classList.add('correct'); setTimeout(()=>sm.classList.remove('show'),900); }
}
function playWrong(){ playTone(120,0.35,'sawtooth',0.14); }
function playTimeout(){ playTone(200,0.45,'sine',0.14); }

function playNext(){ playTone(520,0.12,'sine',0.06); playTone(720,0.08,'square',0.04); }

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

let serverMessage = <?php echo json_encode($message); ?>;
let serverError = <?php echo json_encode($error); ?>;
if(serverMessage){
    const smEl = document.getElementById('serverMessage');
    if(serverMessage.indexOf('Correcto') !== -1){ playCorrect(); animateCorrect(); if(smEl) smEl.classList.add('correct'); }
    else if(serverMessage.indexOf('Incorrecto') !== -1){ playWrong(); animateWrong(); if(smEl) smEl.classList.add('wrong'); }
    else if(serverMessage.indexOf('Tiempo agotado') !== -1){ playTimeout(); animateWrong(); if(smEl) smEl.classList.add('wrong'); }
}
if(serverError){ const errEl = document.getElementById('serverError'); if(errEl) { errEl.classList.add('wrong'); } animateWrong(); playWrong(); }

// Mostrar un overlay breve con el mensaje del servidor para dar pausa entre preguntas
if (serverMessage) {
    try {
        const overlay = document.createElement('div');
        overlay.id = 'pauseOverlay';
        overlay.style.position = 'fixed';
        overlay.style.left = '0';
        overlay.style.top = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.background = 'rgba(0,0,0,0.45)';
        overlay.style.zIndex = '9999';
        overlay.style.color = '#fff';
        overlay.style.fontSize = '1.4rem';
        overlay.style.textAlign = 'center';
        overlay.style.padding = '1rem';
        overlay.style.boxSizing = 'border-box';
        overlay.innerText = serverMessage;
        document.body.appendChild(overlay);
    // Duración de la pausa (ms)
    const PAUSE_MS = 1200;
        setTimeout(() => {
            overlay.remove();
            const ans = document.getElementById('user_answer');
            if (ans) { ans.focus(); }
        }, PAUSE_MS);
    } catch (e) {
        // no-op
    }
}

const nextBtn = document.querySelector('form input[value="next"] , form button');
const nextFormBtn = document.querySelector('form[action] button');
Array.from(document.querySelectorAll('button')).forEach(b=>{
    if(b.textContent.trim().toLowerCase() === 'siguiente') b.addEventListener('click', ()=>{ playNext(); });
});

/* ============================================
   ANIMACIONES PARA CAMPOS DE TIEMPO E INTENTOS
   ============================================ */

// Animar campos de entrada al enfocarse
const timeInput = document.querySelector('input[name="time_limit"]');
const attemptsInput = document.querySelector('input[name="attempts"]');
const selects = document.querySelectorAll('select');

// Botones de control para incremento/decremento
const timeDecrement = document.getElementById('timeDecrement');
const timeIncrement = document.getElementById('timeIncrement');
const attemptsDecrement = document.getElementById('attemptsDecrement');
const attemptsIncrement = document.getElementById('attemptsIncrement');
const timeInputSmall = document.getElementById('timeInput');
const attemptsInputSmall = document.getElementById('attemptsInput');

function setupControlButtons(decrementBtn, incrementBtn, inputEl) {
  if (!decrementBtn || !incrementBtn || !inputEl) return;
  
  function updateValue(delta) {
    let val = parseInt(inputEl.value) || 1;
    val = Math.max(1, val + delta);
    inputEl.value = val;
    
    // Animar el input
    inputEl.style.transform = 'scale(1.08)';
    inputEl.style.borderColor = 'var(--primary)';
    
    // Reproducir sonido
    playTone(delta > 0 ? 600 : 500, 0.1, 'sine', 0.05);
    
    setTimeout(() => {
      inputEl.style.transform = 'scale(1)';
      inputEl.style.borderColor = 'var(--border)';
    }, 150);
    
    // Disparar evento change
    inputEl.dispatchEvent(new Event('change', { bubbles: true }));
  }
  
  decrementBtn.addEventListener('click', (e) => {
    e.preventDefault();
    updateValue(-1);
  });
  
  incrementBtn.addEventListener('click', (e) => {
    e.preventDefault();
    updateValue(1);
  });
  
  // Asegurar que sea un número válido al cambiar
  inputEl.addEventListener('blur', function() {
    if (this.value && parseInt(this.value) < 1) {
      this.value = 1;
    }
  });
}

function addInputAnimations(input) {
  if (!input) return;
  
  // Evento de enfoque
  input.addEventListener('focus', function() {
    this.style.transform = 'scale(1.02)';
    this.style.boxShadow = '0 0 0 3px rgba(5, 150, 105, 0.15), inset 0 0 8px rgba(5, 150, 105, 0.08)';
    playTone(880, 0.1, 'sine', 0.04);
  });
  
  // Evento de desenfoque
  input.addEventListener('blur', function() {
    this.style.transform = 'scale(1)';
  });
  
  // Evento de cambio de valor con retroalimentación audible
  input.addEventListener('change', function() {
    if (this.value && this.value > 0) {
      playTone(550, 0.08, 'sine', 0.05);
      playTone(750, 0.08, 'sine', 0.04);
      
      // Animación de confirmación
      this.classList.add('input-validated');
      this.style.background = 'rgba(5, 150, 105, 0.08)';
      setTimeout(() => {
        this.classList.remove('input-validated');
        this.style.background = 'var(--bg-white)';
      }, 400);
    }
  });
  
  // Efecto de entrada de valor (contador animado)
  input.addEventListener('input', function() {
    if (this.value && this.value > 0) {
      this.style.borderColor = 'var(--primary)';
      this.style.boxShadow = '0 0 0 2px rgba(5, 150, 105, 0.1), 0 2px 8px rgba(5, 150, 105, 0.1)';
      
      // Efecto de pulso suave
      this.style.animation = 'none';
      setTimeout(() => {
        this.style.animation = 'inputPulse 0.6s ease';
      }, 10);
    } else {
      this.style.borderColor = 'var(--border)';
      this.style.boxShadow = 'none';
      this.style.animation = 'none';
    }
  });
}

// Animar selectores
function addSelectAnimations(select) {
  if (!select) return;
  
  select.addEventListener('focus', function() {
    this.style.transform = 'scale(1.02)';
    playTone(820, 0.1, 'sine', 0.04);
  });
  
  select.addEventListener('blur', function() {
    this.style.transform = 'scale(1)';
  });
  
  select.addEventListener('change', function() {
    playTone(650, 0.12, 'sine', 0.05);
    playTone(880, 0.12, 'sine', 0.04);
    
    // Efecto visual de cambio
    this.style.background = 'rgba(5, 150, 105, 0.1)';
    setTimeout(() => {
      this.style.background = 'var(--bg-white)';
    }, 300);
  });
}

// Aplicar animaciones
if (timeInput) addInputAnimations(timeInput);
if (attemptsInput) addInputAnimations(attemptsInput);
selects.forEach(select => addSelectAnimations(select));

// Configurar botones de control
setupControlButtons(timeDecrement, timeIncrement, timeInputSmall);
setupControlButtons(attemptsDecrement, attemptsIncrement, attemptsInputSmall);

// Efecto hover en inputs grandes (answer form)
const userAnswer = document.getElementById('user_answer');
if (userAnswer) {
  userAnswer.addEventListener('mouseenter', function() {
    this.style.borderColor = 'rgba(5, 150, 105, 0.4)';
  });
  userAnswer.addEventListener('mouseleave', function() {
    if (document.activeElement !== this) {
      this.style.borderColor = 'var(--border)';
    }
  });
}
</script>
</body>
</html>
