<?php
// public/includes/outcome-dashboard.php
// Render program outcome statistics dashboard
// Usage: include 'includes/outcome-dashboard.php'; echo renderOutcomeDashboard($programType);

function renderOutcomeDashboard($programType = '') {
    global $pdo;

    // Outcome data by program
    $outcomes = [
        'jamb' => [
            'icon' => 'bx-trophy',
            'color' => 'gold',
            'metrics' => [
                ['number' => '305', 'label' => 'Highest JAMB Score', 'icon' => 'bx-target'],
                ['number' => '95%', 'label' => 'University Admission Rate', 'icon' => 'bx-check-circle'],
                ['number' => '500+', 'label' => 'Students Admitted', 'icon' => 'bx-graduation'],
                ['number' => '12', 'label' => 'Weeks Average Program', 'icon' => 'bx-calendar']
            ]
        ],
        'waec' => [
            'icon' => 'bx-book-open',
            'color' => 'blue',
            'metrics' => [
                ['number' => '98%', 'label' => 'Exam Pass Rate', 'icon' => 'bx-check-circle'],
                ['number' => 'A1-C6', 'label' => 'Grade Range', 'icon' => 'bx-bar-chart-alt-2'],
                ['number' => '300+', 'label' => 'Students Trained', 'icon' => 'bx-group'],
                ['number' => '8', 'label' => 'Subject Coverage', 'icon' => 'bx-book']
            ]
        ],
        'postutme' => [
            'icon' => 'bx-school',
            'color' => 'purple',
            'metrics' => [
                ['number' => '89%', 'label' => 'Screening Success Rate', 'icon' => 'bx-check-shield'],
                ['number' => '200+', 'label' => 'Admitted to Universities', 'icon' => 'bx-building'],
                ['number' => '8', 'label' => 'Weeks Average Duration', 'icon' => 'bx-time'],
                ['number' => '150+', 'label' => 'Students Served', 'icon' => 'bx-user-check']
            ]
        ],
        'digital' => [
            'icon' => 'bx-code-block',
            'color' => 'green',
            'metrics' => [
                ['number' => '85+', 'label' => 'Students Trained', 'icon' => 'bx-user-check'],
                ['number' => '12', 'label' => 'Weeks Program Duration', 'icon' => 'bx-time-five'],
                ['number' => '6', 'label' => 'Core Skills Covered', 'icon' => 'bx-book'],
                ['number' => '100%', 'label' => 'Course Completion Rate', 'icon' => 'bx-star']
            ]
        ],
        'international' => [
            'icon' => 'bx-world',
            'color' => 'cyan',
            'metrics' => [
                ['number' => '16', 'label' => 'Weeks Program', 'icon' => 'bx-time'],
                ['number' => '4', 'label' => 'Main English Tests', 'icon' => 'bx-book-open'],
                ['number' => '60+', 'label' => 'Students Enrolled', 'icon' => 'bx-group'],
                ['number' => '100%', 'label' => 'Learning Support Included', 'icon' => 'bx-check-circle']
            ]
        ]
    ];

    if (!isset($outcomes[$programType])) {
        return '';
    }

    $data = $outcomes[$programType];
    $colorMap = [
        'gold' => '#fbbf24',
        'blue' => '#3b82f6',
        'purple' => '#a855f7',
        'green' => '#10b981',
        'cyan' => '#06b6d4'
    ];
    $color = $colorMap[$data['color']] ?? '#fbbf24';

    $html = '
    <section class="outcome-dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="dashboard-title-icon">
                    <i class="bx ' . htmlspecialchars($data['icon']) . '"></i>
                </div>
                <h2>By The Numbers</h2>
                <p>Real outcomes from our students and programs</p>
            </div>

            <div class="metrics-grid">
    ';

    foreach ($data['metrics'] as $metric) {
        $html .= '
                <div class="metric-card" style="border-top-color: ' . $color . ';">
                    <div class="metric-icon" style="background: ' . $color . '20; color: ' . $color . ';">
                        <i class="bx ' . htmlspecialchars($metric['icon']) . '"></i>
                    </div>
                    <div class="metric-number" style="color: ' . $color . ';">' . htmlspecialchars($metric['number']) . '</div>
                    <div class="metric-label">' . htmlspecialchars($metric['label']) . '</div>
                </div>
        ';
    }

    $html .= '
            </div>
        </div>
    </section>

    <style>
        .outcome-dashboard {
            background: white;
            padding: 60px 20px;
            margin: 40px 0;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .dashboard-title-icon {
            font-size: 3.5rem;
            color: #fbbf24;
            margin-bottom: 15px;
        }

        .dashboard-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px;
        }

        .dashboard-header p {
            color: #64748b;
            font-size: 1.1rem;
            margin: 0;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .metric-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-top: 4px solid #fbbf24;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: default;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-top-color: inherit;
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }

        .metric-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 8px;
            line-height: 1;
        }

        .metric-label {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .metric-card {
                padding: 20px 15px;
            }

            .metric-number {
                font-size: 1.8rem;
            }

            .dashboard-header h2 {
                font-size: 1.8rem;
            }
        }
    </style>
    ';

    return $html;
}
?>
