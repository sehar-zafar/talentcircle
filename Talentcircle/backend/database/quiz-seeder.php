<?php
// Dynamic Quiz Seeder: generates quiz_questions for ALL skills in `skills`.
// Run: cd Talentcircle/backend && php database/quiz-seeder.php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=talentcircle', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$skillsStmt = $pdo->query('SELECT id, name, category, description FROM skills');
$skills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);

// Basic per-skill question generator (template-based for scale).
function buildQuizForSkill($skillName, $category) {
    $skillNameClean = trim($skillName);
    $cat = $category ?: 'general';

    // 10 questions per skill, options are generic but correct_index matches.
    // These questions are designed to verify basic understanding/usage.
    $templates = [
        [
            'q' => "Which action best demonstrates beginner-level understanding of {$skillNameClean}?",
            'options' => [
                'Following instructions and practicing fundamentals',
                'Avoiding practice until advanced',
                'Guessing randomly without learning concepts',
                'Skipping feedback and iteration'
            ],
            'correct' => 0,
            'difficulty' => 'easy'
        ],
        [
            'q' => "What should you prioritize first when learning {$skillNameClean}?",
            'options' => [
                'Core concepts and safe fundamentals',
                'Only advanced shortcuts immediately',
                'Ignoring terminology and theory',
                'Working without goals or milestones'
            ],
            'correct' => 0,
            'difficulty' => 'easy'
        ],
        [
            'q' => "Which approach improves skill growth fastest for {$skillNameClean}?",
            'options' => [
                'Deliberate practice + feedback + review',
                'Repeating the same step without improvement',
                'Practicing only when motivation is highest',
                'Never measuring progress'
            ],
            'correct' => 0,
            'difficulty' => 'medium'
        ],
        [
            'q' => "In {$skillNameClean}, what helps reduce mistakes over time?",
            'options' => [
                'Consistent routines and learning from errors',
                'Never reviewing performance',
                'Avoiding checklists or steps',
                'Changing methods randomly every session'
            ],
            'correct' => 0,
            'difficulty' => 'medium'
        ],
        [
            'q' => "Which is the best way to validate your progress in {$skillNameClean}?",
            'options' => [
                'Quizzes/tests and timed practice sessions',
                'Only subjective guessing',
                'Comparing without tracking',
                'Avoiding measurable goals'
            ],
            'correct' => 0,
            'difficulty' => 'medium'
        ],
        [
            'q' => "When facing difficulty in {$skillNameClean}, what is the most effective next step?",
            'options' => [
                'Break down the task and practice the weak part',
                'Stop completely and wait',
                'Increase complexity instantly',
                'Copy outcomes without understanding'
            ],
            'correct' => 0,
            'difficulty' => 'easy'
        ],
        [
            'q' => "Which practice style best supports long-term mastery of {$skillNameClean}?",
            'options' => [
                'Structured learning with milestones and repetition',
                'One-time learning then quitting',
                'Only watching content without practicing',
                'No documentation or notes'
            ],
            'correct' => 0,
            'difficulty' => 'medium'
        ],
        [
            'q' => "What is a good habit while performing {$skillNameClean}?",
            'options' => [
                'Following best practices and safety/quality checks',
                'Ignoring quality until the end',
                'Skipping preparation steps',
                'Working without reviewing requirements'
            ],
            'correct' => 0,
            'difficulty' => 'hard'
        ],
        [
            'q' => "If you want to become better at {$skillNameClean}, what should you do with errors?",
            'options' => [
                'Analyze them and adjust your process',
                'Pretend they did not happen',
                'Repeat the same approach unchanged',
                'Avoid feedback'
            ],
            'correct' => 0,
            'difficulty' => 'hard'
        ],
        [
            'q' => "Which statement best fits a growth mindset for {$skillNameClean}?",
            'options' => [
                'Improvement comes from effort and targeted practice',
                'Talent alone determines results with no practice',
                'Ability is fixed and cannot be developed',
                'Mistakes are a sign to stop learning'
            ],
            'correct' => 0,
            'difficulty' => 'easy'
        ],
    ];

    // If you want category-specific variation later, you can adjust templates here.
    return $templates;
}

$insertStmt = $pdo->prepare(
    'INSERT INTO quiz_questions (skill_id, question, options, correct_index, difficulty)
     VALUES (?, ?, ?, ?, ?)'
);

$pdo->beginTransaction();
try {
    $inserted = 0;

    foreach ($skills as $s) {
        $skillId = (int)$s['id'];
        $name = $s['name'] ?? '';
        $category = $s['category'] ?? 'tech';

        // If quiz already exists for this skill, skip to prevent duplicates.
        $check = $pdo->prepare('SELECT COUNT(*) AS c FROM quiz_questions WHERE skill_id = ?');
        $check->execute([$skillId]);
        $countRow = $check->fetch(PDO::FETCH_ASSOC);
        if ((int)($countRow['c'] ?? 0) > 0) continue;

        $questions = buildQuizForSkill($name, $category);
        foreach ($questions as $qq) {
            $insertStmt->execute([
                $skillId,
                $qq['q'],
                json_encode($qq['options']),
                (int)$qq['correct'],
                $qq['difficulty']
            ]);
            $inserted++;
        }
    }

    $pdo->commit();
    echo "✅ Quiz seeding complete. Inserted {$inserted} quiz questions.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "❌ Quiz seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>

