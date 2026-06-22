<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('admin');
$page_title = 'Масовни увоз ученика';

$report = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $raw  = trim($_POST['data'] ?? '');
    $pass = $_POST['password'] ?? '';
    $added = 0; $skipped = 0; $errors = [];

    if (strlen($pass) < 6) {
        $errors[] = 'Почетна лозинка мора имати најмање 6 карактера.';
    } elseif ($raw === '') {
        $errors[] = 'Поље са подацима је празно.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $ins  = db()->prepare("INSERT INTO users (name,username,maticni_broj,password,role,grade_class,must_change_password)
                               VALUES (?,?,?,?, 'student', ?, 1)");
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $rowNo = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $rowNo++;
            // delimiter: tab (Excel paste) > semicolon > comma
            if (strpos($line, "\t") !== false)      $p = explode("\t", $line);
            elseif (strpos($line, ';') !== false)    $p = explode(';', $line);
            else                                     $p = explode(',', $line);
            $p = array_map('trim', $p);
            $name    = $p[0] ?? '';
            $username= $p[1] ?? '';
            $maticni = $p[2] ?? '';
            $grade   = $p[3] ?? '';
            if ($username === '') $username = $maticni; // default login = matični broj
            if ($name === '' || $username === '') { $skipped++; $errors[] = "Ред $rowNo: недостаје име или корисничко име."; continue; }
            try {
                $ins->execute([$name, $username, $maticni !== '' ? $maticni : null, $hash, $grade !== '' ? $grade : null]);
                $added++;
            } catch (PDOException $ex) {
                $skipped++;
                $errors[] = "Ред $rowNo ($username): дупликат корисничког имена или матичног броја.";
            }
        }
    }
    $report = ['added'=>$added, 'skipped'=>$skipped, 'errors'=>$errors];
    if ($added > 0 && $skipped === 0 && empty($errors)) {
        flash("Успешно додато ученика: $added.");
        redirect('users.php');
    }
}

include __DIR__ . '/includes/header.php';
?>
<h1>Масовни увоз ученика</h1>
<p class="sub"><a href="users.php">&larr; Назад на кориснике</a></p>

<?php if ($report): ?>
  <div class="card">
    <strong>Резултат увоза:</strong> додато <?= (int)$report['added'] ?>, прескочено <?= (int)$report['skipped'] ?>.
    <?php if (!empty($report['errors'])): ?>
      <ul style="margin:10px 0 0;padding-left:20px">
        <?php foreach (array_slice($report['errors'], 0, 50) as $er): ?><li class="muted" style="font-size:13px"><?= e($er) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card" style="max-width:720px">
  <p>Налепите ученике, <strong>један по реду</strong>. Колоне раздвојите табом (нпр. налепљено из Excel-а/табеле), тачка-зарезом или зарезом:</p>
  <p class="muted" style="font-size:13px">
    <code>Име и презиме ; корисничко име ; матични број ; разред</code><br>
    Само су <strong>име</strong> и <strong>корисничко име</strong> обавезни. Ако изоставите корисничко име,
    користи се матични број. Пример:
  </p>
  <pre style="background:var(--bg);border:1px solid var(--line);border-radius:8px;padding:10px;font-size:13px;overflow:auto">Ана Новак;anovak;2024001;10-А
Бранко Костић;bkostic;2024002;10-А
Клара Ђурић;;2024003;10-Б</pre>

  <form method="post">
    <?= csrf_field() ?>
    <label>Подаци о ученицима</label>
    <textarea name="data" rows="12" required placeholder="Име и презиме ; корисничко име ; матични број ; разред"><?= e($_POST['data'] ?? '') ?></textarea>
    <label>Почетна лозинка за све (мин. 6 карактера)</label>
    <input type="text" name="password" value="promeni123" required>
    <p class="muted" style="font-size:13px;margin-top:8px">Сви увезени ученици добијају ову лозинку и морају да је промене при првом пријављивању.</p>
    <div style="margin-top:8px"><button class="btn">Увези ученике</button></div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
