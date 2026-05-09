<?php
require_once __DIR__ . '/public/config/db.php';

$programs = [
    [
        'title' => 'JAMB/UTME Preparation',
        'slug' => 'jamb',
        'description' => 'Comprehensive preparation for JAMB and university entrance exams.',
        'price' => 10000,
        'duration' => '3 Months',
        'status' => 'active',
        'category' => 'Tutorials'
    ],
    [
        'title' => 'WAEC/NECO/GCE',
        'slug' => 'waec',
        'description' => 'O-Level exam preparation and tutoring for secondary school leavers.',
        'price' => 8000,
        'duration' => '4 Months',
        'status' => 'active',
        'category' => 'Tutorials'
    ],
    [
        'title' => 'Post-UTME Screening',
        'slug' => 'post-utme',
        'description' => 'University-specific screening exam preparation and past questions.',
        'price' => 10000,
        'duration' => '1 Month',
        'status' => 'active',
        'category' => 'Tutorials'
    ],
    [
        'title' => 'Digital Skills Training',
        'slug' => 'digital-skills',
        'description' => 'Web development, coding, and basic tech skills for the modern world.',
        'price' => 30000,
        'duration' => '6 Months',
        'status' => 'active',
        'category' => 'Professional'
    ],
    [
        'title' => 'International Programs',
        'slug' => 'international-programs',
        'description' => 'SAT, IELTS, TOEFL, and JUPEB exam preparation.',
        'price' => 15000,
        'duration' => 'Flexible',
        'status' => 'active',
        'category' => 'International'
    ]
];

foreach ($programs as $p) {
    // check if exists
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE slug = ?");
    $stmt->execute([$p['slug']]);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("INSERT INTO courses (title, slug, description, price, duration, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $ins->execute([
            $p['title'], $p['slug'], $p['description'], $p['price'], $p['duration']
        ]);
        echo "Inserted {$p['title']} ({$p['slug']})\n";
    } else {
        echo "Exists: {$p['slug']}\n";
    }
}
echo "Done.\n";
