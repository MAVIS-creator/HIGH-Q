<?php
// public/includes/program-tutors.php
// Display tutors assigned to a specific program
// Usage: include 'includes/program-tutors.php'; echo renderProgramTutors($programType, $limit = 4);

function renderProgramTutors($programType = '', $limit = 4) {
    global $pdo;

    if (empty($programType) || !$pdo) {
        return '';
    }

    // Map program types to tutor specializations or tags
    $specialtyMap = [
        'jamb' => ['JAMB', 'Exam Prep', 'UTME'],
        'waec' => ['WAEC', 'NECO', 'O-Level', 'GCE'],
        'postutme' => ['Post-UTME', 'Screening Prep', 'University'],
        'digital' => ['Web Development', 'Coding', 'Tech', 'Digital Skills'],
        'international' => ['IELTS', 'TOEFL', 'SAT', 'International']
    ];

    $specialties = $specialtyMap[$programType] ?? [];

    // Query for tutors matching the program
    $tutors = [];
    if (!empty($specialties)) {
        try {
            // Search tutors by specialty/bio matching
            $placeholders = implode(',', array_fill(0, count($specialties), '?'));
            $query = "SELECT id, name, photo, specialty, bio FROM tutors WHERE is_active = 1 AND (";
            $params = [];
            
            foreach ($specialties as $spec) {
                $query .= "specialty LIKE ? OR ";
                $params[] = '%' . $spec . '%';
            }
            $query = rtrim($query, 'OR ') . ") ORDER BY RAND() LIMIT ?";
            $params[] = $limit;

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            // Fallback: just get random active tutors
            try {
                $stmt = $pdo->prepare("SELECT id, name, photo, specialty, bio FROM tutors WHERE is_active = 1 ORDER BY RAND() LIMIT ?");
                $stmt->execute([$limit]);
                $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e2) {
                $tutors = [];
            }
        }
    }

    if (empty($tutors)) {
        return '';
    }

    $html = '
    <section class="program-tutors-section">
        <div class="container">
            <div class="tutors-header">
                <h2>Expert Tutors for <span style="color: #fbbf24;">' . htmlspecialchars(ucfirst($programType)) . '</span></h2>
                <p>Learn from experienced educators dedicated to your success</p>
            </div>

            <div class="tutors-grid">
    ';

    foreach ($tutors as $tutor) {
        $photoUrl = !empty($tutor['photo']) ? app_url($tutor['photo']) : app_url('assets/images/avatar-placeholder.png');
        $html .= '
                <div class="tutor-card-program">
                    <div class="tutor-image-program">
                        <img src="' . htmlspecialchars($photoUrl) . '" alt="' . htmlspecialchars($tutor['name']) . '" onerror="this.src=\'' . app_url('assets/images/avatar-placeholder.png') . '\'">
                    </div>
                    <div class="tutor-info-program">
                        <h4>' . htmlspecialchars($tutor['name']) . '</h4>
                        <p class="tutor-specialty">' . htmlspecialchars($tutor['specialty'] ?? 'Expert Tutor') . '</p>
                        <p class="tutor-bio">' . htmlspecialchars(substr($tutor['bio'] ?? '', 0, 100)) . (strlen($tutor['bio'] ?? '') > 100 ? '...' : '') . '</p>
                    </div>
                </div>
        ';
    }

    $html .= '
            </div>
        </div>
    </section>

    <style>
        .program-tutors-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #f8fafc 100%);
            padding: 60px 20px;
            margin: 40px 0;
        }

        .tutors-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .tutors-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .tutors-header p {
            color: #64748b;
            font-size: 1.1rem;
            margin: 0;
        }

        .tutors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .tutor-card-program {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .tutor-card-program:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
            border-color: #fbbf24;
        }

        .tutor-image-program {
            width: 100%;
            height: 240px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tutor-image-program img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tutor-info-program {
            padding: 20px;
            text-align: center;
        }

        .tutor-info-program h4 {
            margin: 0 0 6px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .tutor-specialty {
            margin: 0 0 12px;
            color: #fbbf24;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .tutor-bio {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .tutors-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .tutor-image-program {
                height: 180px;
            }

            .tutors-header h2 {
                font-size: 1.8rem;
            }
        }
    </style>
    ';

    return $html;
}
?>
