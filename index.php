<?php
declare(strict_types=1);

/* UPGRADER — учебный симулятор кейсов и апгрейдера. Виртуальная валюта, без реальных денег. PHP 7.4+ */

const RAR = [
    'c' => ['Ширпотреб', '#b0c3d9', 40],
    'i' => ['Промышленное', '#5e98d9', 30],
    'm' => ['Армейское', '#4b69ff', 18],
    'r' => ['Запрещённое', '#8847ff', 8],
    'k' => ['Засекреченное', '#d32ce6', 3],
    't' => ['Тайное', '#eb4b4b', 0.8],
    'g' => ['Редкое', '#e4ae39', 0.2],
];
// id => [название, редкость, цена, цвет A, цвет B]
const SKINS = [
    1  => ['P250 | Sand Dune', 'c', 0.1, '#c9b37a', '#7a6636'],
    2  => ['MP9 | Storm', 'c', 0.2, '#8a9aa8', '#3d4a56'],
    3  => ['Glock-18 | Candy Apple', 'i', 0.8, '#e0413a', '#8c1b17'],
    4  => ['MAC-10 | Heat', 'i', 1.1, '#f08a2c', '#a03a0b'],
    5  => ['Five-SeveN | Case Hardened', 'm', 4, '#4a7bd6', '#1b2f66'],
    6  => ['USP-S | Cortex', 'm', 6, '#2fb7a3', '#12594f'],
    7  => ['AK-47 | Redline', 'r', 18, '#d6212b', '#2a0b0e'],
    8  => ['M4A4 | Asiimov', 'r', 35, '#f2f2f2', '#e8742a'],
    9  => ['AWP | Asiimov', 'k', 65, '#f4f4f4', '#2a6fdb'],
    10 => ['M4A1-S | Printstream', 'k', 90, '#f0f0f0', '#555b66'],
    11 => ['Desert Eagle | Blaze', 'k', 120, '#ffb02e', '#d3200f'],
    12 => ['AK-47 | Fire Serpent', 't', 420, '#e4572e', '#5c1a0a'],
    13 => ['AWP | Dragon Lore', 't', 2500, '#6fae4f', '#233d17'],
    14 => ['★ Karambit | Doppler', 'g', 900, '#c04bd6', '#2f6bff'],
    15 => ['★ Butterfly Knife | Fade', 'g', 1800, '#ffd34e', '#ff2f8a'],
];
// id => [название, [id скинов]]
const CASES = [
    1 => ['Стартовый', [1, 2, 3, 4, 5, 6]],
    2 => ['Боевой', [3, 4, 5, 6, 7, 8, 9]],
    3 => ['Золотой', [7, 8, 9, 10, 11, 12, 13, 14, 15]],
];

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float $v): string
{
    return '$' . number_format($v, 2, '.', ' ');
}

function skinParts(int $id): array
{
    $p = explode(' | ', SKINS[$id][0], 2);
    return [$p[0], isset($p[1]) ? $p[1] : ''];
}

function caseWeights(int $cid): array
{
    $ids = CASES[$cid][1];
    $cnt = [];
    foreach ($ids as $i) {
        $r = SKINS[$i][1];
        $cnt[$r] = (isset($cnt[$r]) ? $cnt[$r] : 0) + 1;
    }
    $w = [];
    foreach ($ids as $i) {
        $r = SKINS[$i][1];
        $w[$i] = (int) round(RAR[$r][2] * 1000 / $cnt[$r]);
    }
    return $w;
}

function casePrice(int $cid): float
{
    $w = caseWeights($cid);
    $t = array_sum($w);
    $ev = 0.0;
    foreach ($w as $i => $x) {
        $ev += SKINS[$i][2] * $x / $t;
    }
    return max(0.5, round($ev * 1.12, 2));
}

function rollCase(int $cid): int
{
    $w = caseWeights($cid);
    $n = random_int(1, (int) array_sum($w));
    $acc = 0;
    foreach ($w as $i => $x) {
        $acc += $x;
        if ($n <= $acc) {
            return $i;
        }
    }
    return (int) array_key_first($w);
}

/* Генерируемое изображение скина (SVG) — работает всегда, без внешних файлов */
function skinSvg(int $id): string
{
    $s = SKINS[$id];
    $rc = RAR[$s[1]][1];
    [$weapon, $name] = skinParts($id);
    $shape = $s[1] === 'g'
        ? '<path d="M60 60Q150 8 270 55L278 68Q160 88 60 80Z"/><rect x="8" y="58" width="58" height="26" rx="9"/>'
        : '<path d="M0 22h46l8-10h120l10 8h44v16h-40l-10 10h-30l-8 36h-28l6-36h-60l-20 14h-26z"/>';
    $tr = $s[1] === 'g' ? 'translate(0 36)' : 'translate(36 60)';
    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 200" width="300" height="200">'
        . '<defs><radialGradient id="g" cx="50%" cy="45%" r="60%"><stop offset="0" stop-color="' . $rc . '" stop-opacity=".5"/><stop offset="1" stop-color="' . $rc . '" stop-opacity="0"/></radialGradient>'
        . '<linearGradient id="s" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $s[3] . '"/><stop offset="1" stop-color="' . $s[4] . '"/></linearGradient>'
        . '<pattern id="p" width="9" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(35)"><rect width="3" height="9" fill="#000" fill-opacity=".22"/></pattern></defs>'
        . '<rect width="300" height="200" fill="#1d1e24"/><rect width="300" height="200" fill="url(#g)"/>'
        . '<g transform="' . $tr . '" stroke="#000" stroke-opacity=".5" stroke-width="2" stroke-linejoin="round">'
        . '<g fill="url(#s)">' . $shape . '</g><g fill="url(#p)">' . $shape . '</g></g>'
        . '<rect x="0" y="194" width="300" height="6" fill="' . $rc . '"/>'
        . '<text x="150" y="178" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" fill="#fff" fill-opacity=".85">' . h($weapon) . '</text>'
        . '</svg>';
}

if (isset($_GET['img'])) {
    $iid = (int) $_GET['img'];
    if (!array_key_exists($iid, SKINS)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo skinSvg($iid);
    exit;
}

session_start();
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
if (!isset($_SESSION['u'])) {
    $_SESSION['u'] = ['name' => 'Player' . random_int(1000, 9999), 'bal' => 25.0, 'inv' => [], 'hist' => [], 'op' => 0, 'up' => 0, 'win' => 0, 'bonus' => 0];
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = [$type, $msg];
}

function go(string $p): void
{
    header('Location: ?p=' . $p);
    exit;
}

function addHist(string $text): void
{
    array_unshift($_SESSION['u']['hist'], date('H:i') . '  ' . $text);
    $_SESSION['u']['hist'] = array_slice($_SESSION['u']['hist'], 0, 12);
}

function newUid(): string
{
    return bin2hex(random_bytes(4));
}

/* ---------- Обработка действий ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    if (!hash_equals($_SESSION['csrf'], $token)) {
        flash('err', 'Сессия устарела. Обновите страницу и повторите.');
        go('cases');
    }
    $a = isset($_POST['a']) ? (string) $_POST['a'] : '';
    $u = &$_SESSION['u'];

    if ($a === 'open') {
        $cid = (int) (isset($_POST['case']) ? $_POST['case'] : 0);
        if (!array_key_exists($cid, CASES)) {
            go('cases');
        }
        $price = casePrice($cid);
        if ($u['bal'] + 0.000001 < $price) {
            flash('err', 'Недостаточно средств. Заберите бонус в профиле.');
            go('cases');
        }
        $u['bal'] = round($u['bal'] - $price, 2);
        $win = rollCase($cid);
        $u['inv'][] = ['uid' => newUid(), 'id' => $win];
        $u['op']++;
        addHist('Кейс «' . CASES[$cid][0] . '»: ' . SKINS[$win][0]);
        $_SESSION['spin'] = ['case' => $cid, 'win' => $win];
        go('cases');
    }

    if ($a === 'sell') {
        $uid = isset($_POST['uid']) ? (string) $_POST['uid'] : '';
        foreach ($u['inv'] as $k => $it) {
            if ($it['uid'] === $uid) {
                $u['bal'] = round($u['bal'] + SKINS[$it['id']][2], 2);
                addHist('Продано: ' . SKINS[$it['id']][0]);
                unset($u['inv'][$k]);
                $u['inv'] = array_values($u['inv']);
                flash('ok', 'Предмет продан за ' . money((float) SKINS[$it['id']][2]) . '.');
                break;
            }
        }
        go('profile');
    }

    if ($a === 'up') {
        $sel = isset($_POST['sel']) && is_array($_POST['sel']) ? array_map('strval', $_POST['sel']) : [];
        $t = (int) (isset($_POST['target']) ? $_POST['target'] : 0);
        if (!$sel || !array_key_exists($t, SKINS)) {
            flash('err', 'Выберите свои предметы и цель.');
            go('upgrader');
        }
        $sum = 0.0;
        $used = [];
        $keep = [];
        foreach ($u['inv'] as $it) {
            if (in_array($it['uid'], $sel, true)) {
                $sum += SKINS[$it['id']][2];
                $used[] = $it;
            } else {
                $keep[] = $it;
            }
        }
        if (!$used) {
            flash('err', 'Выбранных предметов нет в инвентаре.');
            go('upgrader');
        }
        if ($sum >= SKINS[$t][2]) {
            flash('err', 'Цель должна быть дороже выбранных предметов.');
            go('upgrader');
        }
        $chance = min(75.0, max(1.0, $sum / SKINS[$t][2] * 95));
        $won = random_int(1, 10000) <= (int) round($chance * 100);
        $u['inv'] = $keep;
        if ($won) {
            $u['inv'][] = ['uid' => newUid(), 'id' => $t];
            $u['win']++;
        }
        $u['up']++;
        addHist('Апгрейд до ' . SKINS[$t][0] . ($won ? ': успех' : ': неудача'));
        $_SESSION['upres'] = ['win' => $won, 'id' => $t, 'chance' => $chance];
        go('upgrader');
    }

    if ($a === 'bonus') {
        if (time() - (int) $u['bonus'] >= 60) {
            $u['bal'] = round($u['bal'] + 10, 2);
            $u['bonus'] = time();
            flash('ok', 'Бонус $10.00 зачислен.');
        } else {
            flash('err', 'Бонус пока недоступен.');
        }
        go('profile');
    }

    if ($a === 'reset') {
        unset($_SESSION['u']);
        flash('ok', 'Профиль сброшен.');
        go('profile');
    }
    go('cases');
}

/* ---------- Вывод ---------- */
$u = $_SESSION['u'];
$page = isset($_GET['p']) ? (string) $_GET['p'] : 'cases';
if (!in_array($page, ['cases', 'upgrader', 'profile'], true)) {
    $page = 'cases';
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function card(int $id, string $extra = ''): string
{
    $s = SKINS[$id];
    [$w, $n] = skinParts($id);
    return '<div class="sk" style="--rc:' . RAR[$s[1]][1] . '">'
        . '<img src="?img=' . $id . '" alt="' . h($s[0]) . '" width="300" height="200">'
        . '<b>' . h($n !== '' ? $n : $w) . '</b><small>' . h($w) . '</small><i>' . money((float) $s[2]) . '</i>' . $extra . '</div>';
}

$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#17181c">
<title>UPGRADER — кейсы и апгрейдер скинов</title>
<style>
:root{--bg:#17181c;--panel:#1f2026;--line:#2c2e36;--tx:#eceef2;--mut:#8b8f9b;--ac:#7c5cff;--ok:#3ddc84;--bad:#ff5a5f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--tx);font:15px/1.45 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
a{color:inherit;text-decoration:none}
header{position:sticky;top:0;z-index:5;background:rgba(23,24,28,.92);backdrop-filter:blur(8px);border-bottom:1px solid var(--line)}
.bar{max-width:1180px;margin:auto;display:flex;align-items:center;gap:20px;padding:12px 16px;flex-wrap:wrap}
.logo{font-weight:800;font-size:20px;letter-spacing:.5px}.logo span{color:var(--ac)}
nav{display:flex;gap:6px;flex:1}
nav a{padding:8px 14px;border-radius:10px;color:var(--mut);font-weight:600}
nav a.on,nav a:hover{background:var(--panel);color:var(--tx)}
.bal{background:var(--panel);border:1px solid var(--line);padding:8px 14px;border-radius:10px;font-weight:700}
main{max-width:1180px;margin:auto;padding:24px 16px 60px}
h1{font-size:26px;margin:0 0 4px}.sub{color:var(--mut);margin:0 0 22px}
h2{font-size:18px;margin:28px 0 12px}
.msg{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-weight:600}
.msg.ok{background:rgba(61,220,132,.12);color:var(--ok)}.msg.err{background:rgba(255,90,95,.12);color:var(--bad)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px}
.sk{position:relative;background:var(--panel);border:1px solid var(--line);border-bottom:3px solid var(--rc);border-radius:12px;padding:8px 10px 10px;display:flex;flex-direction:column;gap:1px;min-width:0}
.sk img{width:100%;height:auto;border-radius:8px;display:block;background:#1d1e24}
.sk b{font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:6px}
.sk small{color:var(--mut)}.sk i{font-style:normal;font-weight:700;color:var(--ok)}
.btn{border:0;background:var(--ac);color:#fff;font:inherit;font-weight:700;padding:10px 16px;border-radius:10px;cursor:pointer}
.btn:hover{filter:brightness(1.12)}.btn:focus-visible,nav a:focus-visible,summary:focus-visible{outline:2px solid #fff;outline-offset:2px}
.btn.alt{background:var(--line)}.btn[disabled]{opacity:.4;cursor:not-allowed}
.case{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:16px}
.case .pv{display:flex;gap:6px;margin-bottom:10px}.case .pv img{width:33%;border-radius:8px;background:#1d1e24}
.case h3{margin:0}.case .row{display:flex;justify-content:space-between;align-items:center;margin-top:10px}
details{margin-top:12px;color:var(--mut)}summary{cursor:pointer}
details .grid{margin-top:10px;grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}
.ch{position:absolute;top:12px;right:14px;font-size:12px;background:rgba(0,0,0,.55);padding:1px 6px;border-radius:6px}
.roul{position:relative;overflow:hidden;background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:14px 0;margin-bottom:20px}
.roul::after{content:"";position:absolute;left:50%;top:0;bottom:0;width:3px;background:var(--ac);transform:translateX(-50%)}
#strip{display:flex;gap:8px;width:max-content;transition:transform 5.5s cubic-bezier(.12,.7,.12,1)}
#strip .sk{width:170px;flex:none}
.won{text-align:center;margin-bottom:24px}.won .sk{max-width:260px;margin:10px auto 0}
.up{display:grid;grid-template-columns:1fr 220px 1fr;gap:18px;align-items:start}
.up .box{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:14px;max-height:620px;overflow:auto}
.up .grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr))}
.pick{position:relative;cursor:pointer;display:block}.pick input{position:absolute;opacity:0;pointer-events:none}
.pick .sk{border-color:var(--line);border-bottom-color:var(--rc)}
.pick input:checked+.sk{outline:2px solid var(--ac);background:#272435}
.pick input:focus-visible+.sk{outline:2px dashed #fff}
.mid{text-align:center;position:sticky;top:80px}
.ring{--v:0;width:180px;height:180px;margin:0 auto 14px;border-radius:50%;background:conic-gradient(var(--ac) calc(var(--v)*1%),var(--line) 0);display:grid;place-items:center}
.ring div{width:148px;height:148px;border-radius:50%;background:var(--bg);display:grid;place-items:center;font-size:30px;font-weight:800}
.ring small{display:block;font-size:12px;color:var(--mut);font-weight:500}
.res{padding:14px;border-radius:14px;margin-bottom:20px;text-align:center;font-weight:700}
.res.w{background:rgba(61,220,132,.12);color:var(--ok)}.res.l{background:rgba(255,90,95,.12);color:var(--bad)}
.res .sk{max-width:240px;margin:10px auto 0;color:var(--tx)}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:16px 0}
.stat{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:14px}.stat b{display:block;font-size:22px}.stat span{color:var(--mut)}
.me{display:flex;align-items:center;gap:14px}.av{width:56px;height:56px;border-radius:50%;background:var(--ac);display:grid;place-items:center;font-size:24px;font-weight:800}
.empty{color:var(--mut);padding:24px;text-align:center;border:1px dashed var(--line);border-radius:12px}
ul.h{list-style:none;padding:0;margin:0;color:var(--mut);white-space:pre-wrap}ul.h li{padding:6px 0;border-bottom:1px solid var(--line)}
@media(max-width:860px){.up{grid-template-columns:1fr}.mid{position:static}}
@media(prefers-reduced-motion:reduce){#strip{transition:none}}
</style>
</head>
<body>
<header><div class="bar">
  <a class="logo" href="?p=cases">UP<span>GRADER</span></a>
  <nav>
    <a href="?p=cases" class="<?= $page === 'cases' ? 'on' : '' ?>">Кейсы</a>
    <a href="?p=upgrader" class="<?= $page === 'upgrader' ? 'on' : '' ?>">Апгрейдер</a>
    <a href="?p=profile" class="<?= $page === 'profile' ? 'on' : '' ?>">Профиль</a>
  </nav>
  <div class="bal"><?= money((float) $u['bal']) ?></div>
</div></header>
<main>
<?php if ($flash): ?><div class="msg <?= h($flash[0]) ?>"><?= h($flash[1]) ?></div><?php endif; ?>

<?php if ($page === 'cases'): ?>
  <h1>Кейсы</h1>
  <p class="sub">Игровой симулятор: виртуальная валюта, реальные деньги не используются.</p>
  <?php if (isset($_SESSION['spin'])):
      $sp = $_SESSION['spin'];
      unset($_SESSION['spin']);
      $pool = CASES[$sp['case']][1];
      ?>
  <div class="roul"><div id="strip">
    <?php for ($i = 0; $i < 42; $i++) {
        echo card($i === 34 ? (int) $sp['win'] : $pool[array_rand($pool)]);
    } ?>
  </div></div>
  <div class="won" id="won" hidden><b>Вам выпало:</b><?= card((int) $sp['win']) ?>
    <p><a class="btn alt" href="?p=profile">В инвентарь</a></p></div>
  <?php endif; ?>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
  <?php foreach (CASES as $cid => $c):
      $price = casePrice($cid);
      $w = caseWeights($cid);
      $tot = array_sum($w);
      $pv = array_slice($c[1], -3);
      ?>
    <div class="case">
      <div class="pv"><?php foreach ($pv as $pid): ?><img src="?img=<?= $pid ?>" alt="<?= h(SKINS[$pid][0]) ?>" width="300" height="200"><?php endforeach; ?></div>
      <h3><?= h($c[0]) ?> кейс</h3>
      <div class="row">
        <strong><?= money($price) ?></strong>
        <form method="post"><?= csrfField() ?><input type="hidden" name="a" value="open"><input type="hidden" name="case" value="<?= $cid ?>">
          <button class="btn" <?= $u['bal'] + 0.000001 < $price ? 'disabled' : '' ?>>Открыть</button></form>
      </div>
      <details><summary>Содержимое (<?= count($c[1]) ?>)</summary>
        <div class="grid">
        <?php foreach ($c[1] as $pid) {
            echo card($pid, '<span class="ch">' . number_format($w[$pid] * 100 / $tot, 2) . '%</span>');
        } ?>
        </div></details>
    </div>
  <?php endforeach; ?>
  </div>

<?php elseif ($page === 'upgrader'):
    $ur = null;
    if (isset($_SESSION['upres'])) {
        $ur = $_SESSION['upres'];
        unset($_SESSION['upres']);
    }
    $all = SKINS;
    uasort($all, function ($x, $y) {
        return $x[2] <=> $y[2];
    });
    ?>
  <h1>Апгрейдер</h1>
  <p class="sub">Выберите свои предметы и более дорогую цель. Шанс зависит от соотношения цен (максимум 75%).</p>
  <?php if ($ur): ?>
    <div class="res <?= $ur['win'] ? 'w' : 'l' ?>"><?= $ur['win'] ? 'Успех! Предмет в инвентаре.' : 'Не повезло. Предметы потеряны.' ?> Шанс был <?= number_format((float) $ur['chance'], 2) ?>%
      <?= card((int) $ur['id']) ?></div>
  <?php endif; ?>
  <?php if (!$u['inv']): ?>
    <div class="empty">В инвентаре пусто. <a href="?p=cases" style="color:var(--ac)">Откройте кейс</a> или заберите бонус в профиле.</div>
  <?php else: ?>
  <form method="post" id="up"><?= csrfField() ?><input type="hidden" name="a" value="up">
    <div class="up">
      <div class="box"><h2 style="margin-top:0">Ваши предметы</h2><div class="grid">
        <?php foreach ($u['inv'] as $it): ?>
          <label class="pick"><input type="checkbox" name="sel[]" value="<?= h($it['uid']) ?>" data-p="<?= SKINS[$it['id']][2] ?>"><?= card((int) $it['id']) ?></label>
        <?php endforeach; ?>
      </div></div>
      <div class="mid">
        <div class="ring" id="ring"><div><span id="pc">0%<small>шанс</small></span></div></div>
        <button class="btn" id="go" disabled>Апгрейд</button>
      </div>
      <div class="box"><h2 style="margin-top:0">Цель</h2><div class="grid">
        <?php foreach ($all as $tid => $s): ?>
          <label class="pick"><input type="radio" name="target" value="<?= $tid ?>" data-p="<?= $s[2] ?>"><?= card((int) $tid) ?></label>
        <?php endforeach; ?>
      </div></div>
    </div>
  </form>
  <script>
  (function(){
    var f=document.getElementById('up'),ring=document.getElementById('ring'),pc=document.getElementById('pc'),go=document.getElementById('go');
    function calc(){
      var s=0;f.querySelectorAll('input[name="sel[]"]:checked').forEach(function(e){s+=parseFloat(e.dataset.p);});
      var t=f.querySelector('input[name=target]:checked'),tp=t?parseFloat(t.dataset.p):0,c=0;
      if(s>0&&tp>s){c=Math.min(75,Math.max(1,s/tp*95));}
      ring.style.setProperty('--v',c);pc.innerHTML=c.toFixed(2)+'%<small>шанс</small>';go.disabled=!(c>0);
    }
    f.addEventListener('change',calc);calc();
  })();
  </script>
  <?php endif; ?>

<?php else: ?>
  <h1>Профиль</h1>
  <div class="me"><div class="av"><?= h(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></div>
    <div><strong style="font-size:20px"><?= h($u['name']) ?></strong><br><span class="sub" style="margin:0">Баланс: <?= money((float) $u['bal']) ?></span></div></div>
  <div class="stats">
    <div class="stat"><b><?= (int) $u['op'] ?></b><span>кейсов открыто</span></div>
    <div class="stat"><b><?= (int) $u['up'] ?></b><span>апгрейдов</span></div>
    <div class="stat"><b><?= (int) $u['win'] ?></b><span>успешных</span></div>
    <div class="stat"><b><?= count($u['inv']) ?></b><span>предметов</span></div>
  </div>
  <?php $left = 60 - (time() - (int) $u['bonus']); ?>
  <form method="post" style="display:inline"><?= csrfField() ?><input type="hidden" name="a" value="bonus">
    <button class="btn" <?= $left > 0 ? 'disabled' : '' ?>><?= $left > 0 ? 'Бонус через ' . $left . ' с' : 'Забрать бонус $10' ?></button></form>
  <form method="post" style="display:inline" onsubmit="return confirm('Сбросить профиль?')"><?= csrfField() ?><input type="hidden" name="a" value="reset"><button class="btn alt">Сбросить профиль</button></form>

  <h2>Инвентарь</h2>
  <?php if (!$u['inv']): ?><div class="empty">Пока пусто. Откройте кейс в разделе «Кейсы».</div><?php else: ?>
  <div class="grid">
    <?php foreach ($u['inv'] as $it) {
        echo card((int) $it['id'], '<form method="post" style="margin-top:8px">' . csrfField() . '<input type="hidden" name="a" value="sell"><input type="hidden" name="uid" value="' . h($it['uid']) . '"><button class="btn alt" style="width:100%">Продать</button></form>');
    } ?>
  </div>
  <?php endif; ?>

  <h2>История</h2>
  <?php if (!$u['hist']): ?><div class="empty">Событий пока нет.</div><?php else: ?>
  <ul class="h"><?php foreach ($u['hist'] as $line): ?><li><?= h($line) ?></li><?php endforeach; ?></ul>
  <?php endif; ?>
<?php endif; ?>
</main>
<script>
(function(){
  var st=document.getElementById('strip');if(!st)return;
  var won=document.getElementById('won');
  if(window.matchMedia('(prefers-reduced-motion: reduce)').matches){won.hidden=false;return;}
  var it=st.children[34],w=st.parentElement.clientWidth;
  var x=it.offsetLeft+it.offsetWidth/2-w/2;
  requestAnimationFrame(function(){requestAnimationFrame(function(){st.style.transform='translateX(-'+x+'px)';});});
  st.addEventListener('transitionend',function(){won.hidden=false;},{once:true});
})();
</script>
</body>
</html>
