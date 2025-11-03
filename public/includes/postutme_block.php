<?php
// Extracted Post-UTME handling block from register.php
// This file is intended to be included within register.php when $registration_type === 'postutme'

			// IMPORTANT: Post-UTME applicants MUST pay the compulsory form fee immediately.
			// Ignore any admin "verify_registration_before_payment" toggle for Post-UTME flows.
			// This variable documents the intent and can be used by other logic if needed.
			$forceImmediatePostPayment = true;
			// Read a subset of Post-UTME fields (best-effort sanitization)
			$institution = trim($_POST['institution'] ?? '') ?: null;
			$first_name_post = trim($_POST['first_name_post'] ?? '') ?: null;
			$surname = trim($_POST['surname'] ?? '') ?: null;
			$other_name = trim($_POST['other_name'] ?? '') ?: null;
			$post_gender = trim($_POST['post_gender'] ?? '') ?: null;
			$address = trim($_POST['address'] ?? '') ?: null;
			$parent_phone = trim($_POST['parent_phone'] ?? '') ?: null;
			$email_post = trim($_POST['email_post'] ?? '') ?: null;
			$nin_number = trim($_POST['nin_number'] ?? '') ?: null;
			$state_of_origin = trim($_POST['state_of_origin'] ?? '') ?: null;
			$local_government = trim($_POST['local_government'] ?? '') ?: null;
			$place_of_birth = trim($_POST['place_of_birth'] ?? '') ?: null;
			$nationality = trim($_POST['nationality'] ?? '') ?: null;
			$religion = trim($_POST['religion'] ?? '') ?: null;

			$jamb_registration_number = trim($_POST['jamb_registration_number'] ?? '') ?: null;
			$jamb_score = ($_POST['jamb_score'] ?? '') !== '' ? intval($_POST['jamb_score']) : null;
			// Build jamb_subjects array and a human-friendly jamb_subjects_text from the four subject/score inputs
			$jamb_subjects = [];
			$jamb_pairs = [];
			for ($j=1;$j<=4;$j++) {
				$sj = trim($_POST['jamb_subj_' . $j] ?? '');
				$sc = trim($_POST['jamb_score_' . $j] ?? '');
				if ($sj !== '') {
					$jamb_subjects[] = ['subject' => $sj, 'score' => ($sc !== '' ? intval($sc) : null)];
					$jamb_pairs[] = $sj . ($sc !== '' ? ' (' . $sc . ')' : '');
				}
			}
			$jamb_subjects_text = !empty($jamb_pairs) ? implode('; ', $jamb_pairs) : null;

			// ----------------------
			// Server-side validation for Post-UTME specific fields
			// Enforce JAMB score ranges (0-100) and require English + three other subjects
			// Also require WAEC presence of English Language, Mathematics, Civic Education
			// These errors will be pushed into $errors and abort registration if present
			// ----------------------
			if ($jamb_score !== null && ($jamb_score < 0 || $jamb_score > 100)) {
				$errors[] = 'JAMB score must be between 0 and 100.';
			}

			// validate individual JAMB subject scores and subject presence
			for ($jsi = 1; $jsi <= 4; $jsi++) {
				$sub = trim($_POST['jamb_subj_' . $jsi] ?? '');
				$scRaw = trim($_POST['jamb_score_' . $jsi] ?? '');
				if ($scRaw !== '') {
					if (!is_numeric($scRaw) || intval($scRaw) < 0 || intval($scRaw) > 100) {
						$errors[] = 'JAMB subject ' . $jsi . ' score must be a number between 0 and 100.';
					}
				}
				// require three other subjects in addition to English (subj 2..4)
				if ($jsi > 1 && $sub === '') {
					$errors[] = 'Please provide three other JAMB subjects in addition to English.';
				}
			}

			// ensure subject 1 is English
			$sub1 = trim($_POST['jamb_subj_1'] ?? '');
			if ($sub1 === '' || !preg_match('/eng/i', $sub1)) {
				$errors[] = 'JAMB Subject 1 must be English.';
			}

			// WAEC presence checks (from $olevel_results built earlier)
			$requiredWaec = [
				'english' => 'English Language',
				'mathematics' => 'Mathematics',
				'civic' => 'Civic Education'
			];
			$foundWaec = ['english' => false, 'mathematics' => false, 'civic' => false];
			foreach (!empty($olevel_results) ? $olevel_results : [] as $r) {
				$s = strtolower(trim($r['subject'] ?? ''));
				if ($s === '') continue;
				if (preg_match('/eng/i', $s)) $foundWaec['english'] = true;
				if (preg_match('/math|mth/i', $s)) $foundWaec['mathematics'] = true;
				if (preg_match('/civic/i', $s)) $foundWaec['civic'] = true;
			}
			foreach ($foundWaec as $k => $v) {
				if (!$v) $errors[] = $requiredWaec[$k] . " is required in O'Level results.";
			}

			$course_first_choice = trim($_POST['course_first_choice'] ?? '') ?: null;
			$course_second_choice = trim($_POST['course_second_choice'] ?? '') ?: null;
			$institution_first_choice = trim($_POST['institution_first_choice'] ?? '') ?: null;

			$father_name = trim($_POST['father_name'] ?? '') ?: null;
			$father_phone = trim($_POST['father_phone'] ?? '') ?: null;
			$father_email = trim($_POST['father_email'] ?? '') ?: null;
			$father_occupation = trim($_POST['father_occupation'] ?? '') ?: null;
			$mother_name = trim($_POST['mother_name'] ?? '') ?: null;
			$mother_phone = trim($_POST['mother_phone'] ?? '') ?: null;
			$mother_occupation = trim($_POST['mother_occupation'] ?? '') ?: null;

			$primary_school = trim($_POST['primary_school'] ?? '') ?: null;
			$primary_year_ended = ($_POST['primary_year_ended'] ?? '') !== '' ? intval($_POST['primary_year_ended']) : null;
			$secondary_school = trim($_POST['secondary_school'] ?? '') ?: null;
			$secondary_year_ended = ($_POST['secondary_year_ended'] ?? '') !== '' ? intval($_POST['secondary_year_ended']) : null;

			$exam_type = trim($_POST['exam_type'] ?? '') ?: null;
			$candidate_name = trim($_POST['candidate_name'] ?? '') ?: null;
			$exam_number = trim($_POST['exam_number'] ?? '') ?: null;
			$exam_year_month = trim($_POST['exam_year_month'] ?? '') ?: null;
			// Collect O'Level inputs (up to 8)
			$olevel_results = [];
			for ($i=1;$i<=8;$i++) {
				$sub = trim($_POST['olevel_subj_' . $i] ?? '');
				$gr = trim($_POST['olevel_grade_' . $i] ?? '');
				if ($sub !== '') $olevel_results[] = ['subject'=>$sub,'grade'=>$gr];
			}

			// Fees: compulsory form fee 1,000 and optional tutor fee 8,000
			$post_form_fee = 1000.00;
			$post_tutor_fee = (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee'] === '1') ? 8000.00 : 0.00;
			// small random service charge <= 167.54
			$service_charge = round(mt_rand(0, 16754) / 100.0, 2);
			$total_amount = $post_form_fee + $post_tutor_fee + $service_charge;

			// Additional Post-UTME optional fields
			$mode_of_entry = trim($_POST['mode_of_entry'] ?? '') ?: null;
			$marital_status = trim($_POST['marital_status'] ?? '') ?: null;
			$disability = trim($_POST['disability'] ?? '') ?: null;
			$waec_token = trim($_POST['waec_token'] ?? '') ?: null;
			$waec_serial = trim($_POST['waec_serial'] ?? '') ?: null;
			$sponsor_name = trim($_POST['sponsor_name'] ?? '') ?: null;
			$sponsor_address = trim($_POST['sponsor_address'] ?? '') ?: null;
			$sponsor_email = trim($_POST['sponsor_email'] ?? '') ?: null;
			$sponsor_phone = trim($_POST['sponsor_phone'] ?? '') ?: null;
			$sponsor_relationship = trim($_POST['sponsor_relationship'] ?? '') ?: null;
			$next_of_kin_name = trim($_POST['next_of_kin_name'] ?? '') ?: null;
			$next_of_kin_address = trim($_POST['next_of_kin_address'] ?? '') ?: null;
			$next_of_kin_email = trim($_POST['next_of_kin_email'] ?? '') ?: null;
			$next_of_kin_phone = trim($_POST['next_of_kin_phone'] ?? '') ?: null;
			$next_of_kin_relationship = trim($_POST['next_of_kin_relationship'] ?? '') ?: null;

			
			try {
				$pdo->beginTransaction();

				$insertSql = 'INSERT INTO post_utme_registrations (user_id, status, institution, first_name, surname, other_name, gender, address, parent_phone, email, nin_number, state_of_origin, local_government, place_of_birth, nationality, religion, mode_of_entry, marital_status, disability, jamb_registration_number, jamb_score, jamb_subjects, jamb_subjects_text, course_first_choice, course_second_choice, institution_first_choice, father_name, father_phone, father_email, father_occupation, mother_name, mother_phone, mother_occupation, primary_school, primary_year_ended, secondary_school, secondary_year_ended, exam_type, candidate_name, exam_number, exam_year_month, olevel_results, waec_token, waec_serial, sponsor_name, sponsor_address, sponsor_email, sponsor_phone, sponsor_relationship, next_of_kin_name, next_of_kin_address, next_of_kin_email, next_of_kin_phone, next_of_kin_relationship, passport_photo, payment_status, form_fee_paid, tutor_fee_paid, created_at) VALUES (NULL, :status, :institution, :first_name, :surname, :other_name, :gender, :address, :parent_phone, :email, :nin_number, :state_of_origin, :local_government, :place_of_birth, :nationality, :religion, :mode_of_entry, :marital_status, :disability, :jamb_registration_number, :jamb_score, :jamb_subjects, :jamb_subjects_text, :course_first_choice, :course_second_choice, :institution_first_choice, :father_name, :father_phone, :father_email, :father_occupation, :mother_name, :mother_phone, :mother_occupation, :primary_school, :primary_year_ended, :secondary_school, :secondary_year_ended, :exam_type, :candidate_name, :exam_number, :exam_year_month, :olevel_results, :waec_token, :waec_serial, :sponsor_name, :sponsor_address, :sponsor_email, :sponsor_phone, :sponsor_relationship, :next_of_kin_name, :next_of_kin_address, :next_of_kin_email, :next_of_kin_phone, :next_of_kin_relationship, :passport_photo, :payment_status, :form_fee_paid, :tutor_fee_paid, NOW())';

				$stmtIns = $pdo->prepare($insertSql);
				$stmtIns->execute([
					':status' => 'pending',
					':institution' => $institution,
					':first_name' => $first_name_post,
					':surname' => $surname,
					':other_name' => $other_name,
					':gender' => $post_gender,
					':address' => $address,
					':parent_phone' => $parent_phone,
					':email' => $email_post ?: $email_contact ?: null,
					':nin_number' => $nin_number,
					':state_of_origin' => $state_of_origin,
					':local_government' => $local_government,
					':place_of_birth' => $place_of_birth,
					':nationality' => $nationality,
					':religion' => $religion,
					':mode_of_entry' => $mode_of_entry,
					':marital_status' => $marital_status,
					':disability' => $disability,
					':jamb_registration_number' => $jamb_registration_number,
					':jamb_score' => $jamb_score,
					':jamb_subjects' => $jamb_subjects ? json_encode($jamb_subjects, JSON_UNESCAPED_UNICODE) : null,
					':jamb_subjects_text' => $jamb_subjects_text,
					':course_first_choice' => $course_first_choice,
					':course_second_choice' => $course_second_choice,
					':institution_first_choice' => $institution_first_choice,
					':father_name' => $father_name,
					':father_phone' => $father_phone,
					':father_email' => $father_email,
					':father_occupation' => $father_occupation,
					':mother_name' => $mother_name,
					':mother_phone' => $mother_phone,
					':mother_occupation' => $mother_occupation,
					':primary_school' => $primary_school,
					':primary_year_ended' => $primary_year_ended,
					':secondary_school' => $secondary_school,
					':secondary_year_ended' => $secondary_year_ended,
					':exam_type' => $exam_type,
					':candidate_name' => $candidate_name,
					':exam_number' => $exam_number,
					':exam_year_month' => $exam_year_month,
					':olevel_results' => !empty($olevel_results) ? json_encode($olevel_results, JSON_UNESCAPED_UNICODE) : null,
					':waec_token' => $waec_token,
					':waec_serial' => $waec_serial,
					':sponsor_name' => $sponsor_name,
					':sponsor_address' => $sponsor_address,
					':sponsor_email' => $sponsor_email,
					':sponsor_phone' => $sponsor_phone,
					':sponsor_relationship' => $sponsor_relationship,
					':next_of_kin_name' => $next_of_kin_name,
					':next_of_kin_address' => $next_of_kin_address,
					':next_of_kin_email' => $next_of_kin_email,
					':next_of_kin_phone' => $next_of_kin_phone,
					':next_of_kin_relationship' => $next_of_kin_relationship,
					':passport_photo' => null,
					':payment_status' => 'pending',
					':form_fee_paid' => 0,
					':tutor_fee_paid' => (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee']==='1') ? 1 : 0,
				]);
				$newId = $pdo->lastInsertId();

				// handle passport upload if present (store public URL)
				if (!empty($_FILES['passport']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {
					$u = $_FILES['passport'];
					$ext = pathinfo($u['name'], PATHINFO_EXTENSION);
					$allowed = ['jpg','jpeg','png'];
					if (in_array(strtolower($ext), $allowed)) {
						$dstDir = __DIR__ . '/uploads/passports';
						if (!is_dir($dstDir)) @mkdir($dstDir, 0755, true);
						$fname = 'postutme_passport_' . $newId . '_' . time() . '.' . $ext;
						$dst = $dstDir . '/' . $fname;
						if (move_uploaded_file($u['tmp_name'], $dst)) {
							// Prefer app_url() when present so generated URLs respect .env APP_URL.
							if (function_exists('app_url')) {
								$base = rtrim(app_url(''), '/');
								$fullUrl = $base . '/uploads/passports/' . $fname;
							} else {
								$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
								$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
								$baseUrl = rtrim($proto . '://' . $host, '/');
								// Preserve any subdirectory the app is installed under by using SCRIPT_NAME
								$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
								$fullUrl = $baseUrl . $scriptDir . '/uploads/passports/' . $fname;
							}
							$upd = $pdo->prepare('UPDATE post_utme_registrations SET passport_photo = ? WHERE id = ?');
							$upd->execute([$fullUrl, $newId]);
						}
					}
				}

				// create a payment placeholder (include metadata for accounting clarity)
				// Debug: double-check detection before creating a PTU payment and record context for forensic analysis
				try {
					@file_put_contents(__DIR__ . '/../storage/logs/registration_payment_debug.log', date('c') . " PRE-PTU-CHECK: posted_reg_type=" . ($posted_reg_type ?: 'NULL') . " detected_reg_type={$registration_type} submitted_form_action=" . ($submitted_form_action ?: 'NULL') . " selectedHasAnyFixed=" . (!empty($selectedHasAnyFixed) ? '1' : '0') . " selectedAllVaries=" . (!empty($selectedAllVaries) ? '1' : '0') . " keys=" . implode(',', array_keys($_POST)) . "\n", FILE_APPEND | LOCK_EX);
				} catch (Throwable $_) { }

				// Safety: only proceed to create a PTU payment when detection strongly indicates Post-UTME
				$proceedCreatePTU = (
					$registration_type === 'postutme'
					&& (
						$submitted_form_action === 'postutme'
						|| !empty($_POST['first_name_post'])
						|| !empty($_POST['jamb_registration_number'])
						|| !empty($_POST['jamb_score'])
						|| !empty($_POST['post_tutor_fee'])
					)
				);

				if (!$proceedCreatePTU) {
					// Anomaly: avoid creating PTU when detection doesn't match — log full POST for analysis and skip
					try {
						@file_put_contents(__DIR__ . '/../storage/logs/registration_payment_debug.log', date('c') . " ANOMALY PTU SKIPPED: posted_reg_type=" . ($posted_reg_type ?: 'NULL') . " detected_reg_type={$registration_type} submitted_form_action=" . ($submitted_form_action ?: 'NULL') . " keys=" . implode(',', array_keys($_POST)) . " POST=" . json_encode($_POST) . " BACKTRACE=" . str_replace("\n", '\\n', print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true)) . "\n", FILE_APPEND | LOCK_EX);
					} catch (Throwable $_) { }
				} else {
					// Debug log: record why we are creating a PTU payment here
					try {
						@file_put_contents(__DIR__ . '/../storage/logs/registration_payment_debug.log', date('c') . " CREATE PTU: posted_reg_type=" . ($posted_reg_type ?: 'NULL') . " detected_reg_type={$registration_type} selectedHasAnyFixed=" . (!empty($selectedHasAnyFixed) ? '1' : '0') . " selectedAllVaries=" . (!empty($selectedAllVaries) ? '1' : '0') . "\n", FILE_APPEND | LOCK_EX);
					} catch (Throwable $_) { }

					$reference = generatePaymentReference('PTU');
					$paymentMetadata = json_encode([
					'components' => [
						'post_form_fee' => number_format((float)$post_form_fee, 2, '.', ''),
						'tutor_fee' => number_format((float)$post_tutor_fee, 2, '.', ''),
						'service_charge' => number_format((float)$service_charge, 2, '.', ''),
					],
					'total' => number_format((float)$total_amount, 2, '.', ''),
					'registration_type' => 'postutme'
					], JSON_UNESCAPED_SLASHES);
					$insP = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata, form_fee_paid, tutor_fee_paid, registration_type) VALUES (NULL, ?, ?, ?, "pending", NOW(), ?, ?, ?, "postutme")');
					$insP->execute([$total_amount, 'bank', $reference, $paymentMetadata, 0, (!empty($_POST['post_tutor_fee']) && $_POST['post_tutor_fee']==='1') ? 1 : 0]);
					$paymentId = $pdo->lastInsertId();

				}

				$pdo->commit();

				// send admin notification (best-effort)
				try { $insNotif = $pdo->prepare('INSERT INTO notifications (user_id, title, body, type, metadata, is_read, created_at) VALUES (NULL,?,?,?,?,0,NOW())'); $insNotif->execute(['New Post-UTME registration', ($first_name_post ?: '') . ' submitted a Post-UTME registration', 'postutme', json_encode(['id'=>$newId,'email'=>$email_post ?: $email_contact])]); } catch (Throwable $_) {}

				// set session and redirect to payment wait page
				$_SESSION['last_payment_id'] = $paymentId;
				$_SESSION['last_payment_reference'] = $reference;
				header('Location: payments_wait.php?ref=' . urlencode($reference));
				exit;
			} catch (Exception $e) {
				if ($pdo->inTransaction()) $pdo->rollBack();
				$errors[] = 'Failed to submit Post-UTME registration: ' . $e->getMessage();
			}

?>
