<?php

require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Points.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

function sendJson($data, $code = 200) {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

function getTokenFromRequest($input) {
  return $_SERVER['HTTP_AUTHORIZATION'] ?? ($_GET['token'] ?? ($input['token'] ?? null));
}

function getPDO() {
  return new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
}

function monthKey($dt) {
  // $dt is unix timestamp
  return date('Y-m', $dt);
}

function lastNMonths($n = 6) {
  $out = [];
  $now = time();
  for ($i = $n - 1; $i >= 0; $i--) {
    $ts = strtotime("-{$i} months", $now);
    $out[] = [
      'key' => monthKey($ts),
      'label' => date('M', $ts)
    ];
  }
  return $out;
}

function userOrNull($token) {
  if (!$token) return null;
  return User::findByToken($token);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$token = getTokenFromRequest($input);
$user = userOrNull($token);

if (!$user) {
  sendJson(['error' => 'Unauthorized'], 401);
}

$day = date('Y-m-d');

// GET summary
if ($method === 'GET' && $uri === '/api/tokens/summary') {
  $pdo = getPDO();

  // Daily state (simple best-effort)
  $daily = null;
  try {
    $daily = Points::ensureDailyRow($user->id, $day);
  } catch (Throwable $e) {
    // ignore
  }

  // Totals from users_points_events
  $stmt = $pdo->prepare("SELECT
      COALESCE(SUM(CASE WHEN kind IN ('teach','review','streak','refer','profile','survey') THEN tokens_awarded ELSE 0 END),0) AS earned_total,
      COALESCE(SUM(CASE WHEN kind = 'spent' OR kind = 'booked' OR kind = 'session_booked' THEN tokens_awarded ELSE 0 END),0) AS spent_total
    FROM users_points_events
    WHERE user_id = ?");
  $stmt->execute([$user->id]);
  $totals = $stmt->fetch(PDO::FETCH_ASSOC);

  $totalEarned = (int)($totals['earned_total'] ?? 0);
  $totalSpent = (int)($totals['spent_total'] ?? 0);

  // Sessions booked: best effort from sessions table
  $sessionsBooked = 0;
  try {
    $sstmt = $pdo->prepare("SELECT COUNT(*) AS c FROM sessions WHERE user_id = ? AND status = 'completed'");
    $sstmt->execute([$user->id]);
    $sessionsBooked = (int)($sstmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
  } catch (Throwable $e) {
    $sessionsBooked = 0;
  }

  // Flow for last 6 months (best-effort from events)
  $months = lastNMonths(6);
  $earned = array_fill(0, count($months), 0);
  $spent = array_fill(0, count($months), 0);

  $stmt = $pdo->prepare("SELECT kind, tokens_awarded, created_at
    FROM users_points_events
    WHERE user_id = ?");
  $stmt->execute([$user->id]);
  $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($events as $e) {
    $ts = strtotime($e['created_at']);
    $mkey = monthKey($ts);
    foreach ($months as $idx => $m) {
      if ($m['key'] === $mkey) {
        $kind = $e['kind'];
        $tok = (int)$e['tokens_awarded'];
        if (in_array($kind, ['teach','review','streak','refer','profile','survey'])) {
          $earned[$idx] += $tok;
        } elseif (in_array($kind, ['spent','booked','session_booked'])) {
          $spent[$idx] += $tok;
        }
        break;
      }
    }
  }

  // Allocation breakdown (donut + bars)
  // Using rough grouping from events.
  $teachTokens = 0;
  $bookingTokens = 0;
  $streakTokens = 0;
  $reviewTokens = 0;

  foreach ($events as $e) {
    $kind = $e['kind'];
    $tok = (int)$e['tokens_awarded'];
    if ($kind === 'teach') $teachTokens += $tok;
    if (in_array($kind, ['booked','session_booked','spent'])) $bookingTokens += $tok;
    if ($kind === 'streak') $streakTokens += $tok;
    if ($kind === 'review') $reviewTokens += $tok;
  }

  $allocationTotal = max(1, $teachTokens + $bookingTokens + $streakTokens + $reviewTokens);

  $donut = [
    ['label' => 'Teaching', 'color' => 'a', 'val' => $teachTokens],
    ['label' => 'Bookings', 'color' => 'rd', 'val' => $bookingTokens],
    ['label' => 'Streaks', 'color' => 'am', 'val' => $streakTokens],
    ['label' => 'Reviews', 'color' => 'tl', 'val' => $reviewTokens],
  ];

  sendJson([
    'token_balance' => (int)$user->tokens,
    'total_earned' => $totalEarned,
    'total_spent' => $totalSpent,
    'sessions_booked' => $sessionsBooked,
    'flow_months' => [
      'months' => array_map(fn($m) => $m['label'], $months),
      'earned' => $earned,
      'spent' => $spent
    ],
    'allocation' => [
      'total' => (int)$allocationTotal,
      'segments' => array_map(function($seg) use ($allocationTotal) {
        return [
          'label' => $seg['label'],
          'val' => (int)$seg['val'],
          'pct' => (int)round($seg['val'] / $allocationTotal * 100),
          'color' => $seg['color']
        ];
      }, $donut),
      'breakdown' => [
        ['label' => 'Teaching sessions', 'val' => $teachTokens, 'color' => 'a', 'total' => $allocationTotal],
        ['label' => 'Sessions booked', 'val' => $bookingTokens, 'color' => 'rd', 'total' => $allocationTotal],
        ['label' => 'Daily streaks', 'val' => $streakTokens, 'color' => 'am', 'total' => $allocationTotal],
        ['label' => 'Reviews earned', 'val' => $reviewTokens, 'color' => 'tl', 'total' => $allocationTotal],
      ]
    ],
    'daily_state' => $daily ? [
      'login_claimed' => (int)$daily['login_claimed'],
      'survey_claimed' => (int)$daily['survey_claimed'],
      'ads_watched_count' => (int)$daily['ads_watched_count'],
      'ads_limit' => (int)$daily['ads_limit']
    ] : []
  ]);
}

// GET transactions
if ($method === 'GET' && $uri === '/api/tokens/transactions') {
  $pdo = getPDO();

  $type = $_GET['type'] ?? null;

  // Map kinds to UI types
  $kindToType = function($kind) {
    $k = strtolower((string)$kind);
    if (in_array($k, ['teach','review','streak','refer','profile','survey'])) return 'earned';
    if (in_array($k, ['bonus'])) return 'bonus';
    if (in_array($k, ['ad'])) return 'daily';
    if (in_array($k, ['login','survey'])) return 'daily';
    if (in_array($k, ['booked','spent','session_booked'])) return 'spent';
    // fallback
    return 'earned';
  };

  $stmt = $pdo->prepare("SELECT kind, description, day, tokens_awarded, created_at
    FROM users_points_events
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 100");
  $stmt->execute([$user->id]);
  $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $rows = [];
  $runningBalance = (int)$user->tokens;

  foreach ($events as $e) {
    $uiType = $kindToType($e['kind']);
    if ($type && $uiType !== $type) continue;

    $amt = (int)$e['tokens_awarded'];
    // spent are stored as positive in events; UI expects negative
    if ($uiType === 'spent') $amt = -abs($amt);

    $rows[] = [
      'type' => $uiType,
      'desc' => (string)$e['description'],
      // Back-compat: keep old field name too
      'description' => (string)$e['description'],
      'date' => date('M j, Y', strtotime($e['day'])),
      'amount' => $amt,
      'balance' => $runningBalance
    ];

    $runningBalance -= $amt; // because earned adds to balance, spent subtracts from balance
  }

  sendJson(['transactions' => $rows]);
}

sendJson(['error' => 'Not found'], 404);

