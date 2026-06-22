<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_role('admin');
$page_title = 'Масовни увоз ученика';

$created = []; $skipped = []; $formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $raw  = trim($_POST['data'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (strlen($pass) < 6) {
        $formError = 'Почетна лозинка мора имати најмање 6 карактера.';
    } elseif ($raw === '') {
        $formError = 'Поље са подацима је празно.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $ins  = db()->prepare("INSERT INTO users (name,username,maticni_broj,password,role,grade_class,must_change_password)
                               VALUES (?,?,?,?, 'student', ?, 1)");
        $chkU = db()->prepare("SELECT 1 FROM users WHERE username=?");
        $batchUsernames = [];
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
            $maticni = $p[1] ?? '';
            $grade   = $p[2] ?? '';
            if ($name === '' || $maticni === '') {
                $skipped[] = "Ред $rowNo: недостаје име или матични број.";
                continue;
            }
            // auto-generate username; ensure uniqueness (DB + within this batch)
            $base = build_username($name, $maticni);
            if ($base === '') { $skipped[] = "Ред $rowNo ($name): не могу да направим корисничко име."; continue; }
            $username = $base; $n = 1;
            while (isset($batchUsernames[$username]) || (function() use ($chkU,$username){ $chkU->execute([$username]); return (bool)$chkU->fetchColumn(); })()) {
                $n++; $username = $base . $n;
            }
            try {
                $ins->execute([$name, $username, $maticni, $hash, $grade !== '' ? $grade : null]);
                $batchUsernames[$username] = true;
                $created[] = ['name'=>$name, 'username'=>$username, 'maticni'=>$maticni, 'grade'=>$grade];
            } catch (PDOException $ex) {
                $skipped[] = "Ред $rowNo ($name): дупликат матичног броја.";
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<h1>Масовни увоз ученика</h1>
<p class="sub"><a href="users.php">&larr; Назад на кориснике</a></p>

<?php if ($formError): ?>
  <div class="err" style="max-width:720px"><?= e($formError) ?></div>
<?php endif; ?>

<?php if ($created || $skipped): ?>
  <div class="card">
    <strong>Резултат увоза:</strong> додато <?= count($created) ?>, прескочено <?= count($skipped) ?>.
    <?php if ($skipped): ?>
      <ul style="margin:10px 0 0;padding-left:20px">
        <?php foreach (array_slice($skipped, 0, 60) as $er): ?><li class="muted" style="font-size:13px"><?= e($er) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <?php if ($created): ?>
  <h2>Направљени налози (подели ученицима њихово корисничко име)</h2>
  <table>
    <tr><th>Име и презиме</th><th>Корисничко име</th><th>Матични број</th><th>Разред</th></tr>
    <?php foreach ($created as $c): ?>
      <tr>
        <td><?= e($c['name']) ?></td>
        <td><strong><?= e($c['username']) ?></strong></td>
        <td class="muted"><?= e($c['maticni']) ?></td>
        <td class="muted"><?= e($c['grade'] ?: '—') ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <p class="muted" style="font-size:13px">Сви добијају почетну лозинку коју сте унели и мораће да је промене при првом пријављивању.</p>
  <?php endif; ?>
<?php endif; ?>

<div class="card" style="max-width:720px">
  <p>Налепите ученике, <strong>један по реду</strong>. Колоне раздвојите табом (нпр. налепљено из Excel-а/табеле), тачка-зарезом или зарезом:</p>
  <p class="muted" style="font-size:13px">
    <code>Име и презиме ; матични број ; разред</code><br>
    Обавезни су <strong>име</strong> и <strong>матични број</strong>; разред је опционо.
    Корисничко име се прави аутоматски: <strong>прво слово имена + презиме + последње 4 цифре матичног броја</strong>
    (нпр. „Ана Новак“ + „…7118“ → <code>anovak7118</code>).
  </p>
  <pre style="background:var(--bg);border:1px solid var(--line);border-radius:8px;padding:10px;font-size:13px;overflow:auto">Ана Новак;1077004097118;10-А
Бранко Костић;1060831350511;10-А
Клара Ђурић;1009573232084;10-Б</pre>

  <form method="post">
    <?= csrf_field() ?>
    <label>Подаци о ученицима</label>
    <textarea name="data" rows="12" required placeholder="Име и презиме ; матични број ; разред"><?= e($_POST['data'] ?? '') ?></textarea>
    <label>Почетна лозинка за све (мин. 6 карактера)</label>
    <input type="text" name="password" value="promeni123" required>
    <p class="muted" style="font-size:13px;margin-top:8px">Сви увезени ученици добијају ову лозинку и морају да је промене при првом пријављивању.</p>
    <div style="margin-top:8px"><button class="btn">Увези ученике</button></div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
