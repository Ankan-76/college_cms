<?php
// controllers/process_quiz.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/QuizController.php';

use Controllers\QuizController;

$controller = new QuizController();

// ── POST Actions ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ── Faculty: Create Quiz ─────────────────────────
    if ($_POST['action'] === 'create_quiz') {
        require_role('FACULTY');
        $facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
        
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $duration = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 30;
        $startTime = !empty($_POST['start_time']) ? trim($_POST['start_time']) : null;
        $endTime = !empty($_POST['end_time']) ? trim($_POST['end_time']) : null;
        
        if ($courseId > 0 && !empty($title) && $duration > 0) {
            $result = $controller->createQuiz($courseId, $facultyId, $title, $description, $duration, $startTime, $endTime);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
            
            // Redirect to manage questions if created
            if ($result['success'] && isset($result['quiz_id'])) {
                header('Location: ../views/faculty/manage_quiz.php?quiz_id=' . $result['quiz_id']);
                exit;
            }
        } else {
            $_SESSION['flash_error'] = 'Please provide course, title, and duration.';
        }
        
        $redirectUrl = '../views/faculty/quizzes.php';
        if ($courseId > 0) $redirectUrl .= '?course_id=' . $courseId;
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // ── Faculty: Add Question ────────────────────────
    if ($_POST['action'] === 'add_question') {
        require_role('FACULTY');
        
        $quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
        $questionText = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
        $optA = isset($_POST['option_a']) ? trim($_POST['option_a']) : '';
        $optB = isset($_POST['option_b']) ? trim($_POST['option_b']) : '';
        $optC = isset($_POST['option_c']) ? trim($_POST['option_c']) : '';
        $optD = isset($_POST['option_d']) ? trim($_POST['option_d']) : '';
        $correct = isset($_POST['correct_option']) ? trim($_POST['correct_option']) : '';
        $marks = isset($_POST['marks']) ? (int)$_POST['marks'] : 1;
        
        if ($quizId > 0 && !empty($questionText)) {
            $result = $controller->addQuestion($quizId, $questionText, $optA, $optB, $optC, $optD, $correct, $marks);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = 'Please fill all question fields.';
        }
        
        header('Location: ../views/faculty/manage_quiz.php?quiz_id=' . $quizId);
        exit;
    }
    
    // ── Faculty: Update Question ─────────────────────
    if ($_POST['action'] === 'update_question') {
        require_role('FACULTY');
        
        $questionId = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
        $quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
        $questionText = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
        $optA = isset($_POST['option_a']) ? trim($_POST['option_a']) : '';
        $optB = isset($_POST['option_b']) ? trim($_POST['option_b']) : '';
        $optC = isset($_POST['option_c']) ? trim($_POST['option_c']) : '';
        $optD = isset($_POST['option_d']) ? trim($_POST['option_d']) : '';
        $correct = isset($_POST['correct_option']) ? trim($_POST['correct_option']) : '';
        $marks = isset($_POST['marks']) ? (int)$_POST['marks'] : 1;
        
        if ($questionId > 0) {
            $result = $controller->updateQuestion($questionId, $questionText, $optA, $optB, $optC, $optD, $correct, $marks);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = 'Invalid question.';
        }
        
        header('Location: ../views/faculty/manage_quiz.php?quiz_id=' . $quizId);
        exit;
    }
    
    // ── Faculty: Update Quiz Status ──────────────────
    if ($_POST['action'] === 'update_status') {
        require_role('FACULTY');
        $facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
        
        $quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        
        if ($quizId > 0 && !empty($status)) {
            $result = $controller->updateQuizStatus($quizId, $status, $facultyId);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
        }
        
        $redirectUrl = '../views/faculty/quizzes.php';
        if ($courseId > 0) $redirectUrl .= '?course_id=' . $courseId;
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // ── Student: Submit Quiz Attempt ─────────────────
    if ($_POST['action'] === 'submit_attempt') {
        require_role('STUDENT');
        $studentId = $_SESSION['user_id'];
        
        $quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
        $answers = isset($_POST['answers']) ? $_POST['answers'] : [];
        $timeTaken = isset($_POST['time_taken']) ? (int)$_POST['time_taken'] : 0;
        $startedAt = isset($_POST['started_at']) ? trim($_POST['started_at']) : date('Y-m-d H:i:s');
        
        if ($quizId > 0) {
            $result = $controller->submitAttempt($quizId, $studentId, $answers, $timeTaken, $startedAt);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
            
            if ($result['success']) {
                header('Location: ../views/student/quiz_result.php?quiz_id=' . $quizId);
                exit;
            }
        } else {
            $_SESSION['flash_error'] = 'Invalid quiz.';
        }
        
        header('Location: ../views/student/quizzes.php');
        exit;
    }
}

// ── GET Actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    // ── Faculty: Delete Quiz ─────────────────────────
    if ($_GET['action'] === 'delete_quiz') {
        require_role('FACULTY');
        $facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
        
        $quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        
        if ($quizId > 0) {
            $result = $controller->deleteQuiz($quizId, $facultyId);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
        }
        
        $redirectUrl = '../views/faculty/quizzes.php';
        if ($courseId > 0) $redirectUrl .= '?course_id=' . $courseId;
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // ── Faculty: Delete Question ─────────────────────
    if ($_GET['action'] === 'delete_question') {
        require_role('FACULTY');
        
        $questionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
        
        if ($questionId > 0) {
            $result = $controller->deleteQuestion($questionId);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
        }
        
        header('Location: ../views/faculty/manage_quiz.php?quiz_id=' . $quizId);
        exit;
    }
}

// Fallback
header('Location: ../views/faculty/quizzes.php');
exit;
