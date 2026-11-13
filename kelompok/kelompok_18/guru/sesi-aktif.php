<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1) {
    http_response_code(404);
    exit('Sesi tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $postedSessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? null;
    if ($postedSessionId !== $sessionId || !in_array($action, ['finish_session', 'cancel_session'], true)) {
        http_response_code(422);
        exit('Data sesi tidak valid.');
    }

    try {
        guruEndSession($sessionId, $action === 'finish_session' ? 'selesai' : 'dibatalkan');
        $returnTo = $_POST['return_to'] ?? null;
        $returnParts = is_string($returnTo) ? parse_url($returnTo) : false;
        if (
            $action === 'cancel_session'
            && is_string($returnTo)
            && is_array($returnParts)
            && !isset($returnParts['scheme'])
            && !isset($returnParts['host'])
            && str_starts_with($returnTo, '/')
            && !str_starts_with($returnTo, '//')
            && !str_contains($returnTo, "\\")
            && !str_contains($returnTo, "\r")
            && !str_contains($returnTo, "\n")
        ) {
            header('Location: ' . $returnTo);
        } elseif ($action === 'cancel_session') {
            $cancelledSession = guruOwnedSession($sessionId);
            if ($cancelledSession === null) {
                http_response_code(404);
                exit('Sesi tidak ditemukan.');
            }
            header('Location: kelas-detail.php?assignment_id=' . (int) $cancelledSession['assignment_id']);
        } else {
            header('Location: sesi-aktif.php?id=' . $sessionId);
        }
        exit;
    } catch (DomainException $exception) {
        http_response_code(409);
        header('Content-Type: text/plain; charset=utf-8');
        exit($exception->getMessage());
    } catch (Throwable $exception) {
        guruReportBackendError($exception);
    }
}

try {
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Sesi tidak ditemukan.');
    }
    if ($session['status'] === 'berlangsung') {
        header('Location: tap-sesi.php?session_id=' . $sessionId);
        exit;
    }
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Sesi Pembelajaran', 'kelas.php');
?>
<?php guru_session_header($session, '', false); ?>
<p class="text-sm text-slate-600">Mulai aktual: <?= attendara_ui_escape(date('d M Y H:i', strtotime($session['mulai_at']))) ?></p>
<a href="kelas-detail.php?assignment_id=<?= (int) $session['assignment_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f] hover:bg-[#f2f6f2]">Kembali ke Kelas</a>

<?php guru_page_end(); ?>
