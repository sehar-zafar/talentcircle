<?php

require_once __DIR__ . '/../../Models/User.php';

$input = json_decode(file_get_contents('php://input'), true);

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getPDO() {
    return new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

function verifyGoogleToken($idToken) {
    $clientId = 'YOUR_GOOGLE_CLIENT_ID.googleusercontent.com';
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
    $response = file_get_contents($url);
    $payload = json_decode($response, true);
    if ($payload && $payload['aud'] === $clientId) {
        return $payload;
    }
    return null;
}

function saveOTP($phone, $code, $name = null) {
    $pdo = getPDO();
    $expires = time() + 300; // 5 minutes
    // Mark any existing unused OTPs for this phone as used
    $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE phone = ? AND used = 0")
        ->execute([$phone]);
    // Insert new OTP
    $stmt = $pdo->prepare("INSERT INTO otp_codes (phone, code, expires_at, name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$phone, $code, $expires, $name]);
}

function verifyOTP($phone, $code) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM otp_codes WHERE phone = ? AND code = ? AND used = 0 AND expires_at > ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$phone, $code, time()]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        // Mark as used
        $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?")->execute([$row['id']]);
        return $row;
    }
    return null;
}

function canSendOTP($phone) {
    $pdo = getPDO();
    // Rate limit: max 1 OTP per 60 seconds per phone
    $stmt = $pdo->prepare("SELECT created_at FROM otp_codes WHERE phone = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$phone]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $lastTime = strtotime($row['created_at']);
        if (time() - $lastTime < 60) {
            return false;
        }
    }
    return true;
}

// Validate E.164 phone format (e.g., +1234567890, +923001234567)
function isValidE164($phone) {
    return preg_match('/^\+[1-9]\d{7,15}$/', $phone);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($uri == '/api/forgot-password') {
        $email = trim($input['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(['error' => 'Invalid email'], 400);
        }

        // Prevent user enumeration: always return success.
        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 30 * 60; // 30 minutes

            $pdo = User::getPDO();
            $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE email = ? AND used = 0")
                ->execute([$email]);

            $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (email, token, expires_at, used) VALUES (?, ?, ?, 0)');
            $stmt->execute([$email, $token, $expires]);

            sendJson([
                'success' => true,
                'message' => 'Reset link sent (demo).',
                'debug_reset_token' => $token
            ]);
        }

        sendJson(['success' => true, 'message' => 'If that email exists, a reset link has been sent.']);

    } elseif ($uri == '/api/register') {
        // Email-based registration (single handler)
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = (string)($input['password'] ?? '');

        if ($name === '' || $email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(['error' => 'Invalid registration data'], 400);
        }

        if (User::findByEmail($email)) {
            sendJson(['error' => 'User already exists'], 400);
        }

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
            'image' => $input['image'] ?? null,
            'age' => $input['age'] ?? null,
            'education' => $input['education'] ?? null,
            'certificates' => $input['certificates'] ?? [],
            'skills_teach' => $input['skills_teach'] ?? [],
            'skills_learn' => $input['skills_learn'] ?? [],
            'bio' => $input['bio'] ?? ''
        ];

        $pdo = User::getPDO();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, image, age, education, certificates, skills_teach, skills_learn, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        ksort($userData);
        $stmt->execute(array_values($userData));

        $user = User::findByEmail($email);
        $token = $user ? $user->id : null;
        sendJson(['token' => $token, 'user' => $user]);

    } elseif ($uri == '/api/login') {
        $user = User::findByEmail($input['email']);
        if (!$user || !password_verify($input['password'], $user->password)) {
            sendJson(['error' => 'Invalid credentials'], 401);
        }
        $today = date('Y-m-d');
        $bonus = ($user->tokens == 0 || $user->last_login != $today) ? 250 : 50;
        $user->updateTokens($bonus);
        $token = $user->id;
        $user->remember_token = $token;
        sendJson(['token' => $token, 'user' => (array)$user]);

    } elseif ($uri == '/api/auth/google') {
        $idToken = $input['id_token'] ?? '';
        $payload = verifyGoogleToken($idToken);
        if (!$payload) sendJson(['error' => 'Invalid Google token'], 401);
        $user = User::findByEmail($payload['email']) ?: new User();
        $user->name = $payload['name'];
        $user->email = $payload['email'];
        $user->google_id = $payload['sub'];
        $user->image = $payload['picture'];
        $user->role = $user->role ?? 'user';
        $user->tokens = $user->tokens ?? 250;
        if (!$user->id) {
            $pdo = User::getPDO();
            $stmt = $pdo->prepare('INSERT INTO users (name, email, google_id, image, role, tokens) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user->name, $user->email, $user->google_id, $user->image, $user->role, $user->tokens]);
            $user->id = $pdo->lastInsertId();
        }
        $token = $user->id;
        $user->remember_token = $token;
        $user->updateTokens(0);
        sendJson(['token' => $token, 'user' => (array)$user]);

    } elseif ($uri == '/api/otp/send') {
        $phone = $input['phone'] ?? '';
        $name = $input['name'] ?? null;

        // Validate phone format
        if (!isValidE164($phone)) {
            sendJson(['error' => 'Invalid phone format. Use international format (e.g., +1234567890)'], 400);
        }

        // Rate limit check
        if (!canSendOTP($phone)) {
            sendJson(['error' => 'Please wait 60 seconds before requesting another OTP'], 429);
        }

        $code = sprintf('%06d', mt_rand(0, 999999));
        saveOTP($phone, $code, $name);

        // TODO: Send SMS via Twilio/Nexmo
        // For demo: log to error log and optionally email
        error_log("OTP for $phone: $code");
        if (!empty($input['email'])) {
            mail($input['email'], 'Your OTP Code', "Your OTP is: $code. Valid for 5 minutes.");
        }

        sendJson([
            'success' => true,
            'message' => 'OTP sent. Check your phone (or console/logs for demo).',
            'debug_code' => $code // Remove in production!
        ]);

    } elseif ($uri == '/api/otp/verify') {
        $phone = $input['phone'] ?? '';
        $code = $input['code'] ?? '';
        $name = $input['name'] ?? null;
        $isRegister = !empty($input['register']);

        if (!isValidE164($phone)) {
            sendJson(['error' => 'Invalid phone format'], 400);
        }

        $otpRecord = verifyOTP($phone, $code);
        if (!$otpRecord) {
            sendJson(['error' => 'Invalid or expired OTP'], 400);
        }

        // Find or create user by phone
        $user = User::findByPhone($phone);

        if (!$user) {
            // Use provided name or fall back to OTP record name or default
            $userName = $name ?? $otpRecord['name'] ?? 'Phone User';
            $pdo = User::getPDO();
            $stmt = $pdo->prepare('INSERT INTO users (phone, name, email, role, tokens) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$phone, $userName, '', 'user', 250]);
            $user = new User();
            $user->id = $pdo->lastInsertId();
            $user->phone = $phone;
            $user->name = $userName;
            $user->email = '';
            $user->role = 'user';
            $user->tokens = 250;
        }

        $token = $user->id;
        $user->remember_token = $token;
        $user->updateTokens(0);
        sendJson(['token' => $token, 'user' => (array)$user]);

    } elseif ($uri == '/api/profile/update') {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $input['token'] ?? null;
        $user = User::findByToken($token);
        if (!$user) sendJson(['error' => 'Unauthorized'], 401);
        $user->updateProfile($input);
        sendJson(['success' => true, 'user' => (array)$user]);

    } elseif ($uri == '/api/profile/setup') {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $input['token'] ?? null;
        $user = User::findByToken($token);
        if (!$user) sendJson(['error' => 'Unauthorized'], 401);

        $name = trim((string)($input['name'] ?? ''));
        $bio = (string)($input['bio'] ?? '');

        if ($name === '') {
            sendJson(['error' => 'Name is required'], 400);
        }

        $skillsHave = $input['skills_have'] ?? $input['skills_teach'] ?? [];
        $skillsLearn = $input['skills_learn'] ?? [];

        if (!is_array($skillsHave)) $skillsHave = [];
        if (!is_array($skillsLearn)) $skillsLearn = [];

        // Accept skill items as either strings or objects
        $normalizeSkillName = function ($item) {
            if (is_string($item)) return trim($item);
            if (is_array($item)) {
                if (!empty($item['name'])) return trim((string)$item['name']);
                if (!empty($item['skill_name'])) return trim((string)$item['skill_name']);
            }
            return '';
        };

        $skillNamesHave = [];
        foreach ($skillsHave as $it) {
            $sn = $normalizeSkillName($it);
            if ($sn !== '') $skillNamesHave[] = $sn;
        }

        $skillNamesLearn = [];
        foreach ($skillsLearn as $it) {
            $sn = $normalizeSkillName($it);
            if ($sn !== '') $skillNamesLearn[] = $sn;
        }

        // Persist basic profile
        $profileUpdate = [
            'name' => $name,
            'bio' => $bio,
            // Keep JSON fields consistent with existing columns.
            'skills_teach' => $skillNamesHave,
            'skills_learn' => $skillNamesLearn,
        ];

        if (isset($input['certificates']) && is_array($input['certificates'])) {
            $profileUpdate['certificates'] = array_values($input['certificates']);
        }

        $user->updateProfile($profileUpdate);

        // Ensure skills rows exist in user_skills
        require_once __DIR__ . '/../../Models/Skill.php';
        require_once __DIR__ . '/../../Models/UserSkill.php';

        $addSkills = function ($type, $skillNames) use ($user) {
            $added = [];
            foreach ($skillNames as $skillName) {
                $skill = Skill::findByName($skillName);
                if (!$skill) {
                    // Create on demand to avoid hard failure
                    $pdo = (new ReflectionClass('Skill'))->getStaticProperties()['pdo'] ?? null;
                    // Fallback: create using Skill model PDO by calling search/all path is messy.
                    // We'll use direct PDO to keep it simple.
                    $pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $stmt = $pdo->prepare('INSERT INTO skills (name, category, description, created_at) VALUES (?, ?, ?, NOW())');
                    $stmt->execute([$skillName, 'tech', null]);
                    $skillId = (int)$pdo->lastInsertId();
                } else {
                    $skillId = (int)$skill->id;
                }

                // Insert into user_skills; ignore duplicates by catching DB errors
                try {
                    UserSkill::add($user->id, $skillId, $type, 0, 0);
                    $added[] = ['skill' => $skillName, 'type' => $type];
                } catch (Throwable $e) {
                    // Duplicate/constraint -> ignore
                }
            }
            return $added;
        };

        $addedHave = $addSkills('have', $skillNamesHave);
        $addedLearn = $addSkills('learn', $skillNamesLearn);

        $stats = UserSkill::getUserStats($user->id);
        $haveSkillsRows = UserSkill::forUser($user->id, 'have');
        $learnSkillsRows = UserSkill::forUser($user->id, 'learn');

        sendJson([
            'success' => true,
            'user' => (array)$user,
            'stats' => $stats,
            'skills_have' => $haveSkillsRows,
            'skills_learn' => $learnSkillsRows,
            'added' => [
                'have' => $addedHave,
                'learn' => $addedLearn
            ]
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && $uri == '/api/profile') {
    $token = $_GET['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    $user = User::findByToken($token);
    if (!$user) sendJson(['error' => 'Unauthorized'], 401);
    sendJson(['user' => (array)$user]);
}

