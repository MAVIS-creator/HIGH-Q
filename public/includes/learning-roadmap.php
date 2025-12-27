<?php
// public/includes/learning-roadmap.php
// Render program-specific learning roadmap (timeline)
// Usage: include 'includes/learning-roadmap.php'; echo renderLearningRoadmap($programType);

function renderLearningRoadmap($programType = '') {
    $roadmaps = [
        'jamb' => [
            'title' => 'JAMB/UTME 12-Week Intensive Program',
            'duration' => '12 weeks',
            'phases' => [
                [
                    'week' => 'Weeks 1-4',
                    'title' => 'Foundation',
                    'icon' => 'bx-book',
                    'tasks' => ['Subject overview', 'Core concept mastery', 'Basic practice questions']
                ],
                [
                    'week' => 'Weeks 5-8',
                    'title' => 'Speed Drills',
                    'icon' => 'bx-lightning-charge',
                    'tasks' => ['Time management', 'Speed problem-solving', 'Weekly mock tests']
                ],
                [
                    'week' => 'Weeks 9-12',
                    'title' => 'Mock Exams',
                    'icon' => 'bx-trophy',
                    'tasks' => ['Full practice tests', 'Score tracking', 'Final review']
                ]
            ]
        ],
        'waec' => [
            'title' => 'WAEC/NECO O-Level Mastery Program',
            'duration' => '16 weeks',
            'phases' => [
                [
                    'week' => 'Weeks 1-5',
                    'title' => 'Topic Breakdown',
                    'icon' => 'bx-organize',
                    'tasks' => ['Chapter-by-chapter study', 'Concept clarification', 'Guided notes']
                ],
                [
                    'week' => 'Weeks 6-12',
                    'title' => 'Practice & Mastery',
                    'icon' => 'bx-dumbbell',
                    'tasks' => ['Past questions', 'Weekly assessments', 'Weak area focus']
                ],
                [
                    'week' => 'Weeks 13-16',
                    'title' => 'Exam Readiness',
                    'icon' => 'bx-check-shield',
                    'tasks' => ['Full exams', 'Time drills', 'Final prep']
                ]
            ]
        ],
        'postutme' => [
            'title' => 'Post-UTME University Screening Program',
            'duration' => '8 weeks',
            'phases' => [
                [
                    'week' => 'Weeks 1-2',
                    'title' => 'Exam Format',
                    'icon' => 'bx-spreadsheet',
                    'tasks' => ['Test structure', 'Question types', 'Scoring system']
                ],
                [
                    'week' => 'Weeks 3-6',
                    'title' => 'Content Mastery',
                    'icon' => 'bx-brain',
                    'tasks' => ['Subject focus', 'Key topics', 'Practice problems']
                ],
                [
                    'week' => 'Weeks 7-8',
                    'title' => 'Final Drills',
                    'icon' => 'bx-target-lock',
                    'tasks' => ['Timed practice', 'Score targets', 'Admission prep']
                ]
            ]
        ],
        'digital' => [
            'title' => 'Digital Skills Project-Based Learning',
            'duration' => '12 weeks',
            'phases' => [
                [
                    'week' => 'Weeks 1-4',
                    'title' => 'Fundamentals',
                    'icon' => 'bx-code-block',
                    'tasks' => ['Languages intro', 'Dev tools setup', 'First program']
                ],
                [
                    'week' => 'Weeks 5-8',
                    'title' => 'First Project',
                    'icon' => 'bx-rocket',
                    'tasks' => ['Build website', 'Deploy online', 'Code review']
                ],
                [
                    'week' => 'Weeks 9-12',
                    'title' => 'Portfolio & Career',
                    'icon' => 'bx-briefcase',
                    'tasks' => ['Portfolio project', 'Job prep', 'Interview skills']
                ]
            ]
        ],
        'international' => [
            'title' => 'International Exam Preparation',
            'duration' => '16 weeks',
            'phases' => [
                [
                    'week' => 'Weeks 1-5',
                    'title' => 'Language Proficiency',
                    'icon' => 'bx-world',
                    'tasks' => ['Grammar mastery', 'Listening skills', 'Reading practice']
                ],
                [
                    'week' => 'Weeks 6-12',
                    'title' => 'Test Strategy',
                    'icon' => 'bx-strategy',
                    'tasks' => ['Test format', 'Time management', 'Practice exams']
                ],
                [
                    'week' => 'Weeks 13-16',
                    'title' => 'Exam Confidence',
                    'icon' => 'bx-shield-check',
                    'tasks' => ['Full-length tests', 'Score targets', 'Test day prep']
                ]
            ]
        ]
    ];

    if (!isset($roadmaps[$programType])) {
        return '';
    }

    $roadmap = $roadmaps[$programType];
    $html = '
    <section class="learning-roadmap-section">
        <div class="container">
            <div class="roadmap-header">
                <h2>' . htmlspecialchars($roadmap['title']) . '</h2>
                <p class="roadmap-duration">Duration: <strong>' . htmlspecialchars($roadmap['duration']) . '</strong></p>
            </div>

            <div class="roadmap-timeline">
    ';

    foreach ($roadmap['phases'] as $idx => $phase) {
        $html .= '
                <div class="roadmap-phase">
                    <div class="phase-marker">
                        <div class="phase-circle">
                            <i class="bx ' . htmlspecialchars($phase['icon']) . '"></i>
                        </div>
                        <div class="phase-line"></div>
                    </div>
                    <div class="phase-content">
                        <span class="phase-week">' . htmlspecialchars($phase['week']) . '</span>
                        <h4>' . htmlspecialchars($phase['title']) . '</h4>
                        <ul class="phase-tasks">
        ';
        foreach ($phase['tasks'] as $task) {
            $html .= '<li>' . htmlspecialchars($task) . '</li>';
        }
        $html .= '
                        </ul>
                    </div>
                </div>
        ';
    }

    $html .= '
            </div>
        </div>
    </section>
    <style>
        .learning-roadmap-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 60px 20px;
            margin: 40px 0;
        }
        .roadmap-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .roadmap-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }
        .roadmap-duration {
            color: #64748b;
            font-size: 1.1rem;
        }
        .roadmap-timeline {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
        }
        .roadmap-timeline::before {
            content: "";
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 50%, #dc2626 100%);
        }
        .roadmap-phase {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            position: relative;
        }
        .phase-marker {
            flex: 0 0 auto;
            position: relative;
            z-index: 2;
        }
        .phase-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            border: 4px solid #fbbf24;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fbbf24;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
        }
        .phase-line {
            width: 2px;
            height: 30px;
            background: #e2e8f0;
            margin: 8px auto 0;
        }
        .phase-content {
            flex: 1;
            background: white;
            padding: 24px;
            border-radius: 12px;
            border-left: 4px solid #fbbf24;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .phase-week {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .phase-content h4 {
            margin: 0 0 12px;
            color: #0f172a;
            font-size: 1.3rem;
        }
        .phase-tasks {
            list-style: none;
            margin: 0;
            padding: 0;
            color: #475569;
            font-size: 0.95rem;
        }
        .phase-tasks li {
            padding: 6px 0;
            padding-left: 24px;
            position: relative;
        }
        .phase-tasks li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .roadmap-timeline::before {
                left: 10px;
            }
            .phase-circle {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            .phase-content {
                padding: 16px;
            }
            .roadmap-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
    ';

    return $html;
}
?>
