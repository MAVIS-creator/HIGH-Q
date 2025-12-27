<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// SEO Variables
$page_title = 'Find Your Path Quiz - Personalized Educational Program Recommendation | High Q Tutorial';
$page_description = 'Take our intelligent quiz to discover your perfect educational program. Get personalized recommendations based on your goals, learning style, and schedule.';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal = trim($_POST['goal'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $learningStyle = trim($_POST['learning_style'] ?? '');
    $commitment = trim($_POST['commitment'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $budget = trim($_POST['budget'] ?? '');
    
    // Validate all inputs
    if (empty($goal) || empty($qualification) || empty($learningStyle) || empty($commitment) 
        || empty($schedule) || empty($experience) || empty($budget)) {
        $error = 'Please answer all questions to get your personalized recommendation.';
    } else {
        // Intelligent scoring system
        $scores = [
            'jamb' => 0,
            'waec' => 0,
            'postutme' => 0,
            'digital' => 0,
            'international' => 0
        ];
        
        // Score based on goal (40% weight)
        if ($goal === 'university') {
            $scores['jamb'] += 3;
            $scores['waec'] += 2;
            $scores['postutme'] += 3;
        } elseif ($goal === 'career') {
            $scores['digital'] += 4;
            $scores['international'] += 1;
        } elseif ($goal === 'international') {
            $scores['international'] += 5;
            $scores['postutme'] += 1;
        }
        
        // Score based on qualification (20% weight)
        if (in_array($qualification, ['inschool', 'ssce', 'gce'])) {
            $scores['jamb'] += 2;
            $scores['waec'] += 2;
        } elseif ($qualification === 'diploma' || $qualification === 'degree') {
            $scores['postutme'] += 3;
            $scores['international'] += 1;
        }
        
        // Score based on learning style (15% weight)
        if ($learningStyle === 'structured') {
            $scores['jamb'] += 2;
            $scores['waec'] += 2;
            $scores['postutme'] += 1;
        } elseif ($learningStyle === 'project') {
            $scores['digital'] += 3;
            $scores['international'] += 1;
        } elseif ($learningStyle === 'mixed') {
            foreach ($scores as $key => $val) {
                $scores[$key] += 1;
            }
        }
        
        // Score based on commitment (10% weight)
        if ($commitment === 'flexible') {
            $scores['digital'] += 2;
        } elseif ($commitment === 'parttime') {
            foreach ($scores as $key => $val) {
                $scores[$key] += 1;
            }
        } elseif ($commitment === 'intensive') {
            $scores['jamb'] += 2;
            $scores['waec'] += 2;
        }
        
        // Score based on schedule (8% weight)
        if ($schedule === 'weekday') {
            $scores['postutme'] += 1;
        } elseif ($schedule === 'weekend') {
            $scores['digital'] += 1;
        }
        
        // Score based on experience (5% weight)
        if ($experience === 'experienced') {
            $scores['international'] += 1;
            $scores['postutme'] += 1;
        }
        
        // Score based on budget (2% weight)
        if ($budget === 'flexible') {
            $scores['international'] += 1;
        }
        
        // Find top recommendation
        $recommendedPath = array_key_first(array_filter($scores, fn($score) => $score === max($scores)));
        $matchScore = max($scores);
        
        // Build percentage match (max score is 13, so multiply by ~7.7 to get percentage)
        $percentMatch = min(100, round(($matchScore / 13) * 100));
        
        // Map path to page
        // Map path to page and brand colors
        $pathMap = [
            'jamb' => ['page' => 'path-jamb.php', 'color' => '#4f46e5', 'colorLight' => '#7c3aed'],
            'waec' => ['page' => 'path-waec.php', 'color' => '#059669', 'colorLight' => '#047857'],
            'postutme' => ['page' => 'path-postutme.php', 'color' => '#dc2626', 'colorLight' => '#b91c1c'],
            'digital' => ['page' => 'path-digital.php', 'color' => '#2563eb', 'colorLight' => '#1d4ed8'],
            'international' => ['page' => 'path-international.php', 'color' => '#9333ea', 'colorLight' => '#7e22ce']
        ];
        
        $pathInfo = $pathMap[$recommendedPath] ?? $pathMap['jamb'];
        $pathPage = $pathInfo['page'];
        
        // Store colors in session for use in result display
        $_SESSION['path_colors'] = [
            'primary' => $pathInfo['color'],
            'secondary' => $pathInfo['colorLight']
        ];
        
        // Redirect to personalized path landing page
        header("Location: $pathPage?goal=$goal&qual=$qualification&match=$percentMatch");
        exit;
    // Get path colors for display (will be set during form processing)
    $pathColors = $_SESSION['path_colors'] ?? [
        'primary' => '#4f46e5',
        'secondary' => '#7c3aed'
    ];
    unset($_SESSION['path_colors']); // Clear after using

    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Add SEO Meta Tags Before HTML Output -->
<?php if (!isset($seo_tags_added)): 
    $seo_tags_added = true;
?>
<meta name="description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="keywords" content="education quiz, program recommendation, learning style assessment, career path guidance, JAMB, WAEC, digital skills">
<meta name="og:title" content="Find Your Path Quiz | High Q Tutorial">
<meta property="og:description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo htmlspecialchars(current_url(), ENT_QUOTES, 'UTF-8'); ?>">
<link rel="canonical" href="<?php echo htmlspecialchars(app_url('find-your-path-quiz.php'), ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>

<style>
        /* Path brand colors (will be dynamically applied when results show) */
        :root {
            --path-primary: <?php echo htmlspecialchars($pathColors['primary'], ENT_QUOTES, 'UTF-8'); ?>;
            --path-secondary: <?php echo htmlspecialchars($pathColors['secondary'], ENT_QUOTES, 'UTF-8'); ?>;
        }

    .quiz-hero {
        background: linear-gradient(135deg, var(--path-primary) 0%, var(--path-secondary) 100%);
        color: white;
        padding: 3rem 1rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .quiz-hero h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .quiz-hero p {
        font-size: 1.1rem;
        opacity: 0.95;
    }

    .quiz-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .quiz-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .question-section {
        margin-bottom: 2rem;
    }

    .question-number {
        display: inline-block;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .question-text {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.5rem;
    }

    .option {
        display: flex;
        align-items: center;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .option:hover {
        border-color: #4f46e5;
        background-color: #f0f4ff;
    }

    .option input[type="radio"] {
        margin-right: 1rem;
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .option-label {
        flex: 1;
        cursor: pointer;
        font-size: 1rem;
    }

    .option-description {
        font-size: 0.9rem;
        color: #64748b;
        margin-left: 2.5rem;
        margin-top: -0.5rem;
    }

    .error-message {
        background-color: #fee2e2;
        color: #991b1b;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        border-left: 4px solid #dc2626;
    }

    .quiz-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-submit {
        flex: 1;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
    }

    .btn-back {
        flex: 0.5;
        background: #f1f5f9;
        color: #1e293b;
        border: 2px solid #e2e8f0;
        padding: 1rem;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background: #e2e8f0;
    }

    .quiz-info {
        background: #f0f9ff;
        border-left: 4px solid #06b6d4;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 2rem;
    }

    .quiz-info i {
        color: #06b6d4;
        margin-right: 0.5rem;
    }

    @media (max-width: 768px) {
        .quiz-hero h1 {
            font-size: 1.75rem;
        }

        .quiz-container {
            padding: 1rem;
        }

        .quiz-card {
            padding: 1.5rem;
        }

        .quiz-actions {
            flex-direction: column;
        }

        .btn-back {
            flex: 1;
        }
    }
</style>

<div class="quiz-hero">
    <h1>🎯 Find Your Path</h1>
    <p>Answer a few quick questions to discover the perfect program for you</p>
</div>

<div class="quiz-container">
    <div class="quiz-card">
        <div class="quiz-info">
            <i class="bx bxs-info-circle"></i>
            <span>This intelligent quiz takes 2-3 minutes and analyzes your goals, schedule, learning style, and more to recommend your perfect program.</span>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="bx bxs-error-circle" style="margin-right: 0.5rem;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Question 1: Main Goal -->
            <div class="question-section">
                <div class="question-number">1</div>
                <div class="question-text">What is your primary educational goal?</div>

                <label class="option">
                    <input type="radio" name="goal" value="university" required>
                    <span class="option-label">University Admission</span>
                </label>
                <div class="option-description">Get admitted to a Nigerian university</div>

                <label class="option">
                    <input type="radio" name="goal" value="career" required>
                    <span class="option-label">Career & Skill Development</span>
                </label>
                <div class="option-description">Build practical skills for immediate job opportunities</div>

                <label class="option">
                    <input type="radio" name="goal" value="international" required>
                    <span class="option-label">International Education</span>
                </label>
                <div class="option-description">Prepare for international exams and studying abroad</div>
            </div>

            <!-- Question 2: Current Qualification -->
            <div class="question-section">
                <div class="question-number">2</div>
                <div class="question-text">What is your current highest qualification?</div>

                <label class="option">
                    <input type="radio" name="qualification" value="inschool" required>
                    <span class="option-label">Currently in Secondary School</span>
                </label>
                <div class="option-description">JSS 3 or SS classes</div>

                <label class="option">
                    <input type="radio" name="qualification" value="ssce" required>
                    <span class="option-label">SSCE/O-Levels Graduate</span>
                </label>
                <div class="option-description">Completed senior secondary school</div>

                <label class="option">
                    <input type="radio" name="qualification" value="gce" required>
                    <span class="option-label">GCE/WAEC Graduate</span>
                </label>
                <div class="option-description">Completed GCE or WAEC exams</div>

                <label class="option">
                    <input type="radio" name="qualification" value="diploma" required>
                    <span class="option-label">Diploma Graduate</span>
                </label>
                <div class="option-description">Completed a diploma program</div>

                <label class="option">
                    <input type="radio" name="qualification" value="degree" required>
                    <span class="option-label">University Degree Holder</span>
                </label>
                <div class="option-description">Have a bachelor's degree</div>
            </div>

            <!-- Question 3: Learning Style -->
            <div class="question-section">
                <div class="question-number">3</div>
                <div class="question-text">How do you prefer to learn?</div>

                <label class="option">
                    <input type="radio" name="learning_style" value="structured" required>
                    <span class="option-label">Structured & Exam-Focused</span>
                </label>
                <div class="option-description">Prefer clear syllabus, practice questions, and mock exams</div>

                <label class="option">
                    <input type="radio" name="learning_style" value="project" required>
                    <span class="option-label">Project & Hands-On Learning</span>
                </label>
                <div class="option-description">Learn by doing real projects and building things</div>

                <label class="option">
                    <input type="radio" name="learning_style" value="mixed" required>
                    <span class="option-label">Mixed/Flexible Approach</span>
                </label>
                <div class="option-description">Like a blend of both theory and practical work</div>
            </div>

            <!-- Question 4: Time Commitment -->
            <div class="question-section">
                <div class="question-number">4</div>
                <div class="question-text">How much time can you commit to learning?</div>

                <label class="option">
                    <input type="radio" name="commitment" value="intensive" required>
                    <span class="option-label">Intensive (8+ hours/week)</span>
                </label>
                <div class="option-description">I can dedicate significant time to studying</div>

                <label class="option">
                    <input type="radio" name="commitment" value="parttime" required>
                    <span class="option-label">Part-Time (4-8 hours/week)</span>
                </label>
                <div class="option-description">I balance work/school with studies</div>

                <label class="option">
                    <input type="radio" name="commitment" value="flexible" required>
                    <span class="option-label">Very Flexible (Self-Paced)</span>
                </label>
                <div class="option-description">I need maximum flexibility with my schedule</div>
            </div>

            <!-- Question 5: Available Schedule -->
            <div class="question-section">
                <div class="question-number">5</div>
                <div class="question-text">When are you available to attend classes?</div>

                <label class="option">
                    <input type="radio" name="schedule" value="weekday" required>
                    <span class="option-label">Weekdays (Mon-Fri afternoons/evenings)</span>
                </label>
                <div class="option-description">I can come after school/work on weekdays</div>

                <label class="option">
                    <input type="radio" name="schedule" value="weekend" required>
                    <span class="option-label">Weekends (Saturdays & Sundays)</span>
                </label>
                <div class="option-description">I prefer to study on weekends only</div>

                <label class="option">
                    <input type="radio" name="schedule" value="mixed" required>
                    <span class="option-label">Both Weekdays & Weekends</span>
                </label>
                <div class="option-description">I can attend classes any day/time</div>
            </div>

            <!-- Question 6: Prior Experience -->
            <div class="question-section">
                <div class="question-number">6</div>
                <div class="question-text">Do you have experience/background in the subject area?</div>

                <label class="option">
                    <input type="radio" name="experience" value="beginner" required>
                    <span class="option-label">Beginner - No Prior Experience</span>
                </label>
                <div class="option-description">I'm starting from scratch</div>

                <label class="option">
                    <input type="radio" name="experience" value="experienced" required>
                    <span class="option-label">Experienced - Some Background</span>
                </label>
                <div class="option-description">I have some knowledge or experience</div>
            </div>

            <!-- Question 7: Budget -->
            <div class="question-section">
                <div class="question-number">7</div>
                <div class="question-text">What's your budget flexibility?</div>

                <label class="option">
                    <input type="radio" name="budget" value="budget" required>
                    <span class="option-label">Budget-Conscious</span>
                </label>
                <div class="option-description">I need the most affordable option</div>

                <label class="option">
                    <input type="radio" name="budget" value="flexible" required>
                    <span class="option-label">Flexible Budget</span>
                </label>
                <div class="option-description">Price is secondary to quality and fit</div>
            </div>

            <!-- Actions -->
            <div class="quiz-actions">
                <button type="button" class="btn-back" onclick="window.history.back();">← Back</button>
                <button type="submit" class="btn-submit">Get My Recommendation →</button>
            </div>
        </form>
    </div>

    <!-- Alternative CTA -->
    <div style="text-align: center; margin-top: 2rem;">
        <p style="color: #64748b; margin-bottom: 1rem;">
            Already know what you want? 
            <a href="register-new.php" style="color: #4f46e5; font-weight: 600; text-decoration: none;">Go directly to registration →</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
