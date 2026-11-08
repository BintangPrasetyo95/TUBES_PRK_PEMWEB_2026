<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/** Return the PDO connection created by the shared application config. */
function guruDb(): PDO
{
    global $pdo;

    return $pdo;
}

/** Return the teacher profile linked to the authenticated user. */
function guruProfile(): ?array
{
    $statement = guruDb()->prepare(
        'SELECT id, user_id, nomor_induk, nama, email, no_telepon
         FROM guru
         WHERE user_id = :user_id AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['user_id' => currentUser()['id']]);
    $profile = $statement->fetch();

    return is_array($profile) ? $profile : null;
}

/** Return the single active semester, or null when none (or multiple) are configured. */
function guruActiveSemester(): ?array
{
    $statement = guruDb()->prepare(
        "SELECT id, kode, nama, tanggal_mulai, tanggal_selesai
         FROM semester
         WHERE status = :status
         ORDER BY tanggal_mulai DESC, id DESC
         LIMIT 2"
    );
    $statement->execute(['status' => 'aktif']);
    $semesters = $statement->fetchAll();

    if (count($semesters) > 1) {
        error_log('ATTENDARA Guru: more than one active semester is configured.');
        return null;
    }

    return $semesters[0] ?? null;
}

/** Fetch active semester assignments for the authenticated teacher. */
function guruActiveAssignments(int $guruId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT pg.id AS assignment_id, k.id AS id, k.kode AS code, k.nama AS name,
                k.tingkat AS level, s.id AS semester_id, s.nama AS semester,
                COUNT(DISTINCT ak.siswa_id) AS student_count
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = pg.semester_id
         LEFT JOIN anggota_kelas ak ON ak.kelas_id = k.id AND ak.semester_id = s.id
         LEFT JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         WHERE pg.guru_id = :guru_id AND pg.semester_id = :semester_id
         GROUP BY pg.id, k.id, k.kode, k.nama, k.tingkat, s.id, s.nama
         ORDER BY k.nama, k.id'
    );
    $statement->execute(['guru_id' => $guruId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Fetch schedules belonging to the teacher's active-semester assignments. */
function guruActiveSchedules(int $guruId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT jm.id, pg.id AS assignment_id, k.id AS class_id, k.nama AS class_name,
                s.nama AS semester, jm.hari AS day_number, jm.jam_mulai AS start,
                jm.jam_selesai AS end, jm.berlaku_mulai AS valid_from,
                jm.berlaku_sampai AS valid_until
         FROM jadwal_mengajar jm
         INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = pg.semester_id
         WHERE pg.guru_id = :guru_id AND pg.semester_id = :semester_id
           AND (jm.berlaku_mulai IS NULL OR jm.berlaku_mulai <= CURRENT_DATE())
           AND (jm.berlaku_sampai IS NULL OR jm.berlaku_sampai >= CURRENT_DATE())
         ORDER BY jm.hari, jm.jam_mulai, k.nama'
    );
    $statement->execute(['guru_id' => $guruId, 'semester_id' => $semesterId]);
    $schedules = $statement->fetchAll();

    foreach ($schedules as &$schedule) {
        $schedule['day_label'] = 'Hari ' . $schedule['day_number'];
        $schedule['valid_from'] = $schedule['valid_from'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_from']));
        $schedule['valid_until'] = $schedule['valid_until'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_until']));
    }
    unset($schedule);

    return $schedules;
}

/** Find one class only when the teacher owns its active-semester assignment. */
function guruOwnedActiveClass(int $guruId, int $semesterId, int $classId): ?array
{
    $statement = guruDb()->prepare(
        'SELECT pg.id AS assignment_id, k.id, k.kode AS code, k.nama AS name,
                k.tingkat AS level, s.id AS semester_id, s.nama AS semester
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = pg.semester_id
         WHERE pg.guru_id = :guru_id AND pg.semester_id = :semester_id AND k.id = :class_id
         LIMIT 1'
    );
    $statement->execute([
        'guru_id' => $guruId,
        'semester_id' => $semesterId,
        'class_id' => $classId,
    ]);
    $class = $statement->fetch();

    return is_array($class) ? $class : null;
}

/** Fetch active students assigned to a class in the specified semester. */
function guruClassStudents(int $classId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name
         FROM anggota_kelas ak
         INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
         ORDER BY sis.nama, sis.id'
    );
    $statement->execute(['class_id' => $classId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Fetch schedules for one teacher-owned active-semester class. */
function guruClassSchedules(int $assignmentId): array
{
    $statement = guruDb()->prepare(
        'SELECT id, hari AS day_number, jam_mulai AS start, jam_selesai AS end,
                berlaku_mulai AS valid_from, berlaku_sampai AS valid_until
         FROM jadwal_mengajar
         WHERE penugasan_guru_id = :assignment_id
           AND (berlaku_mulai IS NULL OR berlaku_mulai <= CURRENT_DATE())
           AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURRENT_DATE())
         ORDER BY hari, jam_mulai'
    );
    $statement->execute(['assignment_id' => $assignmentId]);
    $schedules = $statement->fetchAll();
    foreach ($schedules as &$schedule) {
        $schedule['day_label'] = 'Hari ' . $schedule['day_number'];
        $schedule['valid_from'] = $schedule['valid_from'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_from']));
        $schedule['valid_until'] = $schedule['valid_until'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_until']));
    }
    unset($schedule);

    return $schedules;
}

/** Create or resume today's ongoing session for a teacher-owned schedule. */
function guruStartSession(int $classId, int $scheduleId): int
{
    $profile = guruProfile();
    $semester = guruActiveSemester();
    if ($profile === null || $semester === null) {
        throw new DomainException('Profil Guru atau semester aktif tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $assignmentQuery = $pdo->prepare(
            'SELECT pg.id
             FROM penugasan_guru pg
             INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
             INNER JOIN semester sem ON sem.id = pg.semester_id AND sem.status = :active_status
             WHERE pg.guru_id = :guru_id AND pg.kelas_id = :class_id
               AND pg.semester_id = :semester_id
             LIMIT 1 FOR UPDATE'
        );
        $assignmentQuery->execute([
            'active_status' => 'aktif',
            'guru_id' => $profile['id'],
            'class_id' => $classId,
            'semester_id' => $semester['id'],
        ]);
        $assignmentId = $assignmentQuery->fetchColumn();
        if ($assignmentId === false) {
            throw new DomainException('Kelas tidak ditemukan.');
        }

        $scheduleQuery = $pdo->prepare(
            'SELECT id
             FROM jadwal_mengajar
             WHERE id = :schedule_id AND penugasan_guru_id = :assignment_id
               AND (berlaku_mulai IS NULL OR berlaku_mulai <= CURRENT_DATE())
               AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURRENT_DATE())
             LIMIT 1'
        );
        $scheduleQuery->execute(['schedule_id' => $scheduleId, 'assignment_id' => $assignmentId]);
        if ($scheduleQuery->fetchColumn() === false) {
            throw new DomainException('Jadwal tidak tersedia untuk kelas ini.');
        }

        $ongoingQuery = $pdo->prepare(
            "SELECT id FROM sesi_pembelajaran
             WHERE penugasan_guru_id = :assignment_id AND status = :status
             ORDER BY id DESC LIMIT 1 FOR UPDATE"
        );
        $ongoingQuery->execute(['assignment_id' => $assignmentId, 'status' => 'berlangsung']);
        $ongoingSessionId = $ongoingQuery->fetchColumn();
        if ($ongoingSessionId !== false) {
            $pdo->commit();
            return (int) $ongoingSessionId;
        }

        $insert = $pdo->prepare(
            "INSERT INTO sesi_pembelajaran
                (penugasan_guru_id, jadwal_mengajar_id, tanggal, waktu_mulai, status)
             VALUES (:assignment_id, :schedule_id, CURRENT_DATE(), CURRENT_TIMESTAMP(), :status)"
        );
        $insert->execute([
            'assignment_id' => $assignmentId,
            'schedule_id' => $scheduleId,
            'status' => 'berlangsung',
        ]);
        $sessionId = (int) $pdo->lastInsertId();
        $pdo->commit();

        return $sessionId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Fetch a session only when it belongs to the authenticated teacher. */
function guruOwnedSession(int $sessionId): ?array
{
    $statement = guruDb()->prepare(
        'SELECT sp.id, sp.penugasan_guru_id AS assignment_id, sp.jadwal_mengajar_id AS schedule_id,
                sp.tanggal, sp.waktu_mulai, sp.waktu_selesai, sp.status,
                k.id AS class_id, k.kode AS class_code, k.nama AS class_name,
                sem.id AS semester_id, sem.nama AS semester, sem.status AS semester_status,
                jm.hari AS day_number, jm.jam_mulai AS scheduled_start, jm.jam_selesai AS scheduled_end
         FROM sesi_pembelajaran sp
         INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = pg.semester_id
         LEFT JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
         WHERE sp.id = :session_id AND g.user_id = :user_id
         LIMIT 1'
    );
    $statement->execute(['session_id' => $sessionId, 'user_id' => currentUser()['id']]);
    $session = $statement->fetch();

    if (!is_array($session)) {
        return null;
    }

    $session['day_label'] = $session['day_number'] === null ? null : 'Hari ' . $session['day_number'];
    return $session;
}

/** Change an owned, ongoing session to a terminal state. */
function guruEndSession(int $sessionId, string $status): void
{
    if (!in_array($status, ['selesai', 'dibatalkan'], true)) {
        throw new InvalidArgumentException('Status sesi tidak valid.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            "SELECT sp.id
             FROM sesi_pembelajaran sp
             INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN semester sem ON sem.id = pg.semester_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
               AND sem.status = :active_semester AND sp.status = :ongoing_status
             LIMIT 1 FOR UPDATE"
        );
        $statement->execute([
            'session_id' => $sessionId,
            'user_id' => currentUser()['id'],
            'active_semester' => 'aktif',
            'ongoing_status' => 'berlangsung',
        ]);
        if ($statement->fetchColumn() === false) {
            throw new DomainException('Sesi tidak ditemukan atau tidak dapat diubah.');
        }

        $update = $pdo->prepare(
            'UPDATE sesi_pembelajaran
             SET status = :status, waktu_selesai = CURRENT_TIMESTAMP()
             WHERE id = :session_id AND status = :ongoing_status'
        );
        $update->execute(['status' => $status, 'session_id' => $sessionId, 'ongoing_status' => 'berlangsung']);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Return a generic response for backend data loading failures. */
function guruReportBackendError(Throwable $exception): never
{
    error_log('ATTENDARA Guru page failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Data halaman Guru sementara tidak dapat dimuat.');
}
