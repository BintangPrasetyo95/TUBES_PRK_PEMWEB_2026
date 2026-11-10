<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class GuruTapCooldownException extends RuntimeException
{
    public function __construct(string $message, public readonly int $retryAfter = 2)
    {
        parent::__construct($message);
    }
}

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
                mp.id AS subject_id, mp.kode AS subject_code, mp.nama AS subject_name,
                COUNT(DISTINCT sis.id) AS student_count
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         LEFT JOIN anggota_kelas ak ON ak.kelas_id = k.id AND ak.semester_id = s.id
         LEFT JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         WHERE pg.guru_id = :guru_id AND k.semester_id = :semester_id
         GROUP BY pg.id, k.id, k.kode, k.nama, k.tingkat, s.id, s.nama,
                  mp.id, mp.kode, mp.nama
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
                mp.nama AS subject_name, s.nama AS semester, jm.hari AS day_number, jm.jam_mulai AS start,
                jm.jam_selesai AS end, jm.berlaku_mulai AS valid_from,
                jm.berlaku_sampai AS valid_until
         FROM jadwal_mengajar jm
         INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE pg.guru_id = :guru_id AND k.semester_id = :semester_id
           AND (jm.berlaku_mulai IS NULL OR jm.berlaku_mulai <= CURRENT_DATE())
           AND (jm.berlaku_sampai IS NULL OR jm.berlaku_sampai >= CURRENT_DATE())
         ORDER BY jm.hari, jm.jam_mulai, k.nama'
    );
    $statement->execute(['guru_id' => $guruId, 'semester_id' => $semesterId]);
    $schedules = $statement->fetchAll();

    foreach ($schedules as &$schedule) {
        $schedule['day_label'] = guruDayLabel((int) $schedule['day_number']);
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
                k.tingkat AS level, s.id AS semester_id, s.nama AS semester,
                pg.mata_pelajaran_id AS subject_id, mp.kode AS subject_code,
                mp.nama AS subject_name
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE pg.guru_id = :guru_id AND k.semester_id = :semester_id AND k.id = :class_id
         ORDER BY pg.id
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

/** Find one specific active assignment owned by a teacher. */
function guruOwnedActiveAssignment(int $guruId, int $semesterId, int $assignmentId): ?array
{
    $statement = guruDb()->prepare(
        'SELECT pg.id AS assignment_id, pg.guru_id, pg.kelas_id AS id,
                pg.mata_pelajaran_id AS subject_id, mp.kode AS subject_code,
                mp.nama AS subject_name, k.kode AS code, k.nama AS name,
                k.tingkat AS level, k.semester_id, s.nama AS semester
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = k.semester_id AND s.status = :active_status
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE pg.id = :assignment_id AND pg.guru_id = :guru_id
           AND k.semester_id = :semester_id
         LIMIT 1'
    );
    $statement->execute([
        'active_status' => 'aktif', 'assignment_id' => $assignmentId,
        'guru_id' => $guruId, 'semester_id' => $semesterId,
    ]);
    $assignment = $statement->fetch();

    return is_array($assignment) ? $assignment : null;
}

/** Translate the SQL weekday number (1=Monday ... 7=Sunday) to Indonesian. */
function guruDayLabel(int $dayNumber): string
{
    return [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][$dayNumber] ?? 'Hari tidak valid';
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
        $schedule['day_label'] = guruDayLabel((int) $schedule['day_number']);
        $schedule['valid_from'] = $schedule['valid_from'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_from']));
        $schedule['valid_until'] = $schedule['valid_until'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_until']));
    }
    unset($schedule);

    return $schedules;
}

/** Create or resume the ongoing session for one owned schedule, with its roster snapshot. */
function guruStartSession(int $assignmentId, int $scheduleId): int
{
    $semester = guruActiveSemester();
    if ($semester === null) {
        throw new DomainException('Profil Guru atau semester aktif tidak tersedia.');
    }

    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        // Lock the schedule row first so simultaneous start requests for it serialize.
        $scheduleQuery = $pdo->prepare(
            'SELECT jm.id AS schedule_id, pg.id AS assignment_id,
                    pg.kelas_id, k.semester_id
             FROM jadwal_mengajar jm
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
             INNER JOIN semester sem ON sem.id = k.semester_id AND sem.status = :active_status
             WHERE jm.id = :schedule_id AND pg.id = :assignment_id
               AND g.user_id = :user_id AND k.semester_id = :semester_id
               AND (jm.berlaku_mulai IS NULL OR jm.berlaku_mulai <= CURRENT_DATE())
               AND (jm.berlaku_sampai IS NULL OR jm.berlaku_sampai >= CURRENT_DATE())
             LIMIT 1 FOR UPDATE'
        );
        $scheduleQuery->execute([
            'active_status' => 'aktif', 'schedule_id' => $scheduleId,
            'assignment_id' => $assignmentId, 'user_id' => $user['id'],
            'semester_id' => $semester['id'],
        ]);
        $schedule = $scheduleQuery->fetch();
        if (!is_array($schedule)) {
            throw new DomainException('Jadwal tidak ditemukan, tidak berlaku, atau bukan milik penugasan Anda.');
        }

        $ongoingQuery = $pdo->prepare(
            "SELECT id FROM sesi_pembelajaran
             WHERE jadwal_mengajar_id = :schedule_id AND status = :status
             ORDER BY id DESC LIMIT 1 FOR UPDATE"
        );
        $ongoingQuery->execute(['schedule_id' => $schedule['schedule_id'], 'status' => 'berlangsung']);
        $ongoingSessionId = $ongoingQuery->fetchColumn();
        if ($ongoingSessionId !== false) {
            $pdo->commit();
            return (int) $ongoingSessionId;
        }

        $insert = $pdo->prepare(
            "INSERT INTO sesi_pembelajaran
                (jadwal_mengajar_id, mulai_at, status, jumlah_tap)
             VALUES (:schedule_id, CURRENT_TIMESTAMP(), :status, 0)"
        );
        $insert->execute([
            'schedule_id' => $schedule['schedule_id'],
            'status' => 'berlangsung',
        ]);
        $sessionId = (int) $pdo->lastInsertId();

        $attendance = $pdo->prepare(
            "INSERT INTO absensi
                (sesi_pembelajaran_id, siswa_id, status, dicatat_oleh_user_id, waktu_dicatat)
             SELECT :session_id, ak.siswa_id, :attendance_status, :user_id, CURRENT_TIMESTAMP()
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id"
        );
        $attendance->execute([
            'session_id' => $sessionId,
            'attendance_status' => 'hadir',
            'user_id' => $user['id'],
            'class_id' => $schedule['kelas_id'],
            'semester_id' => $schedule['semester_id'],
        ]);
        if ($attendance->rowCount() < 1) {
            throw new DomainException('Sesi tidak dapat dimulai karena roster kelas belum memiliki siswa aktif.');
        }
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
        'SELECT sp.id, pg.id AS assignment_id, jm.id AS schedule_id,
                DATE(sp.mulai_at) AS tanggal, sp.mulai_at AS waktu_mulai,
                sp.selesai_at AS waktu_selesai, sp.mulai_at, sp.selesai_at,
                sp.jumlah_tap, sp.tap_terakhir_at, sp.status,
                k.id AS class_id, k.kode AS class_code, k.nama AS class_name,
                sem.id AS semester_id, sem.nama AS semester, sem.status AS semester_status,
                mp.id AS subject_id, mp.nama AS subject_name,
                jm.hari AS day_number, jm.jam_mulai AS scheduled_start, jm.jam_selesai AS scheduled_end
         FROM sesi_pembelajaran sp
         INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
         INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE sp.id = :session_id AND g.user_id = :user_id
         LIMIT 1'
    );
    $statement->execute(['session_id' => $sessionId, 'user_id' => currentUser()['id']]);
    $session = $statement->fetch();

    if (!is_array($session)) {
        return null;
    }

    $session['day_label'] = $session['day_number'] === null ? null : guruDayLabel((int) $session['day_number']);
    return $session;
}

/** Fetch attendance and point totals from one owned session's fixed snapshot. */
function guruSessionAttendance(int $sessionId): array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    $statement = guruDb()->prepare(
        "SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                a.status AS attendance_status, a.catatan AS note,
                COALESCE(p.total_points, 0) AS points
         FROM absensi a
         INNER JOIN siswa sis ON sis.id = a.siswa_id
         LEFT JOIN (
             SELECT sesi_pembelajaran_id, siswa_id, SUM(poin) AS total_points
             FROM poin_keaktifan WHERE sesi_pembelajaran_id = :points_session_id
             GROUP BY sesi_pembelajaran_id, siswa_id
         ) p ON p.sesi_pembelajaran_id = :joined_session_id AND p.siswa_id = sis.id
         WHERE a.sesi_pembelajaran_id = :session_id
         ORDER BY sis.nama, sis.id"
    );
    $statement->execute([
        'session_id' => $sessionId,
        'points_session_id' => $sessionId,
        'joined_session_id' => $sessionId,
    ]);

    return $statement->fetchAll();
}

/** Increment an anonymous tap counter using the session row as a global lock. */
function guruTapAttendance(int $sessionId): array
{
    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            "SELECT sp.id, sp.jumlah_tap, sp.status,
                    TIMESTAMPDIFF(SECOND, sp.tap_terakhir_at, CURRENT_TIMESTAMP()) AS elapsed_seconds
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE"
        );
        $sessionQuery->execute(['session_id' => $sessionId, 'user_id' => $user['id']]);
        $session = $sessionQuery->fetch();
        if (!is_array($session) || $session['status'] !== 'berlangsung') {
            throw new DomainException('Sesi tidak ditemukan atau tidak sedang berlangsung.');
        }
        // The schema stores tap_terakhir_at at whole-second precision; a 3-second
        // database bucket guarantees that two real tap events are at least 2s apart.
        if ($session['elapsed_seconds'] !== null && (int) $session['elapsed_seconds'] < 3) {
            $retryAfter = max(1, 3 - (int) $session['elapsed_seconds']);
            throw new GuruTapCooldownException('Tunggu hingga jeda tap terpenuhi.', $retryAfter);
        }

        $rosterQuery = $pdo->prepare(
            'SELECT COUNT(*) FROM absensi WHERE sesi_pembelajaran_id = :session_id'
        );
        $rosterQuery->execute(['session_id' => $sessionId]);
        $rosterCount = (int) $rosterQuery->fetchColumn();
        $tapCount = (int) $session['jumlah_tap'];
        if ($tapCount >= $rosterCount) {
            throw new DomainException('Jumlah tap sudah mencapai jumlah siswa pada sesi ini.');
        }

        $update = $pdo->prepare(
            "UPDATE sesi_pembelajaran
             SET jumlah_tap = jumlah_tap + 1, tap_terakhir_at = CURRENT_TIMESTAMP()
             WHERE id = :session_id AND status = :ongoing_status"
        );
        $update->execute(['session_id' => $sessionId, 'ongoing_status' => 'berlangsung']);
        $timestamp = $pdo->prepare(
            "SELECT DATE_FORMAT(tap_terakhir_at, '%H:%i:%s')
             FROM sesi_pembelajaran WHERE id = :session_id"
        );
        $timestamp->execute(['session_id' => $sessionId]);
        $tapTime = (string) $timestamp->fetchColumn();
        $pdo->commit();

        return ['time' => $tapTime, 'tap_count' => $tapCount + 1, 'roster_count' => $rosterCount];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Return the persisted anonymous tap count for one owned session. */
function guruTapCount(int $sessionId): int
{
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    return (int) $session['jumlah_tap'];
}

/** Count students in a session's fixed attendance snapshot. */
function guruSessionRosterCount(int $sessionId): int
{
    if (guruOwnedSession($sessionId) === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    $statement = guruDb()->prepare(
        'SELECT COUNT(*) FROM absensi WHERE sesi_pembelajaran_id = :session_id'
    );
    $statement->execute(['session_id' => $sessionId]);

    return (int) $statement->fetchColumn();
}

/** Correct the anonymous tap count while the owned session is still ongoing. */
function guruCorrectTapCount(int $sessionId, int $tapCount): void
{
    if ($tapCount < 0) {
        throw new DomainException('Jumlah tap tidak boleh kurang dari nol.');
    }
    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT sp.id, sp.status
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute(['session_id' => $sessionId, 'user_id' => $user['id']]);
        $session = $sessionQuery->fetch();
        if (!is_array($session) || $session['status'] !== 'berlangsung') {
            throw new DomainException('Jumlah tap hanya dapat dikoreksi pada sesi yang sedang berlangsung milik Anda.');
        }

        $rosterQuery = $pdo->prepare(
            'SELECT COUNT(*) FROM absensi WHERE sesi_pembelajaran_id = :session_id'
        );
        $rosterQuery->execute(['session_id' => $sessionId]);
        $rosterCount = (int) $rosterQuery->fetchColumn();
        if ($tapCount > $rosterCount) {
            throw new DomainException('Jumlah tap tidak boleh melebihi jumlah siswa pada sesi ini.');
        }

        $update = $pdo->prepare(
            'UPDATE sesi_pembelajaran SET jumlah_tap = :tap_count
             WHERE id = :session_id AND status = :ongoing_status'
        );
        $update->execute([
            'tap_count' => $tapCount, 'session_id' => $sessionId,
            'ongoing_status' => 'berlangsung',
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Add a manual participation score to an owned, ongoing session. */
function guruAddPoints(int $sessionId, int $studentId, string $points, ?string $note): void
{
    $points = trim($points);
    if (
        !preg_match('/^(?:0|[1-9][0-9]{0,4})(?:\.[0-9]{1,2})?$/', $points)
        || preg_match('/^0(?:\.0{1,2})?$/', $points)
    ) {
        throw new DomainException('Poin harus lebih dari 0 dan maksimal 99999.99 dengan maksimal 2 angka desimal.');
    }
    if ($note !== null && (function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note)) > 500) {
        throw new DomainException('Catatan maksimal 500 karakter.');
    }

    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT sp.id, sp.status
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute([
            'session_id' => $sessionId,
            'user_id' => $user['id'],
        ]);
        $session = $sessionQuery->fetch();
        if (!is_array($session) || $session['status'] !== 'berlangsung') {
            throw new DomainException('Poin hanya dapat dicatat pada sesi Guru yang sedang berlangsung.');
        }

        $studentQuery = $pdo->prepare(
            'SELECT id FROM absensi
             WHERE sesi_pembelajaran_id = :session_id AND siswa_id = :student_id
             LIMIT 1 FOR UPDATE'
        );
        $studentQuery->execute([
            'session_id' => $sessionId,
            'student_id' => $studentId,
        ]);
        if ($studentQuery->fetchColumn() === false) {
            throw new DomainException('Siswa tidak terdaftar pada kelas sesi ini.');
        }

        $insert = $pdo->prepare(
            'INSERT INTO poin_keaktifan
                (sesi_pembelajaran_id, siswa_id, poin, dicatat_oleh_user_id, catatan, waktu_dicatat)
             VALUES (:session_id, :student_id, :points, :user_id, :note, CURRENT_TIMESTAMP())'
        );
        $insert->execute([
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'points' => $points,
            'user_id' => $user['id'],
            'note' => $note === '' ? null : $note,
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Fetch score history for one student in an owned session. */
function guruStudentPointHistory(int $sessionId, int $studentId): array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    $snapshot = guruDb()->prepare(
        'SELECT id FROM absensi WHERE sesi_pembelajaran_id = :session_id AND siswa_id = :student_id LIMIT 1'
    );
    $snapshot->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
    if ($snapshot->fetchColumn() === false) {
        throw new DomainException('Siswa tidak memiliki baris absensi pada snapshot sesi ini.');
    }

    $statement = guruDb()->prepare(
        'SELECT pk.poin, pk.waktu_dicatat, pk.catatan, u.username AS recorded_by
         FROM poin_keaktifan pk
         INNER JOIN users u ON u.id = pk.dicatat_oleh_user_id
         WHERE pk.sesi_pembelajaran_id = :session_id AND pk.siswa_id = :student_id
         ORDER BY pk.waktu_dicatat DESC, pk.id DESC'
    );
    $statement->execute(['session_id' => $sessionId, 'student_id' => $studentId]);

    return $statement->fetchAll();
}

/** Return class and semester options from all of the teacher's assignments. */
function guruArchiveFilters(): array
{
    $classes = guruDb()->prepare(
        'SELECT DISTINCT k.id, k.nama AS name
         FROM penugasan_guru pg
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         WHERE g.user_id = :user_id
         ORDER BY k.nama, k.id'
    );
    $classes->execute(['user_id' => currentUser()['id']]);

    $semesters = guruDb()->prepare(
        'SELECT DISTINCT sem.id, sem.nama AS name, sem.tanggal_mulai
         FROM penugasan_guru pg
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN semester sem ON sem.id = pg.semester_id
         WHERE g.user_id = :user_id
         ORDER BY sem.tanggal_mulai DESC, sem.id DESC'
    );
    $semesters->execute(['user_id' => currentUser()['id']]);

    return ['classes' => $classes->fetchAll(), 'semesters' => $semesters->fetchAll()];
}

/** Fetch completed sessions for the authenticated teacher with optional filters. */
function guruArchiveSessions(?int $classId = null, ?int $semesterId = null): array
{
    $sql =
        'SELECT sp.id, sp.tanggal, sp.waktu_mulai, sp.waktu_selesai,
                jm.jam_mulai AS scheduled_start, jm.jam_selesai AS scheduled_end,
                k.id AS class_id, k.nama AS class_name,
                sem.id AS semester_id, sem.nama AS semester, sp.status,
                COUNT(DISTINCT ak.siswa_id) AS student_count
         FROM sesi_pembelajaran sp
         INNER JOIN penugasan_guru pg ON pg.id = sp.penugasan_guru_id
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = pg.semester_id
         LEFT JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
         LEFT JOIN anggota_kelas ak ON ak.kelas_id = pg.kelas_id AND ak.semester_id = pg.semester_id
         WHERE g.user_id = :user_id AND sp.status = :completed_status';
    $parameters = ['user_id' => currentUser()['id'], 'completed_status' => 'selesai'];

    if ($classId !== null) {
        $sql .= ' AND k.id = :class_id';
        $parameters['class_id'] = $classId;
    }
    if ($semesterId !== null) {
        $sql .= ' AND sem.id = :semester_id';
        $parameters['semester_id'] = $semesterId;
    }

    $sql .=
        ' GROUP BY sp.id, sp.tanggal, sp.waktu_mulai, sp.waktu_selesai,
                  jm.jam_mulai, jm.jam_selesai, k.id, k.nama,
                  sem.id, sem.nama, sp.status
          ORDER BY sp.tanggal DESC, sp.id DESC';
    $statement = guruDb()->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

/** Return one completed session and its historical class roster, attendance and scores. */
function guruArchiveDetail(int $sessionId): ?array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null || $session['status'] !== 'selesai') {
        return null;
    }

    $statement = guruDb()->prepare(
        "SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                COALESCE(a.status, 'hadir') AS attendance_status,
                COALESCE(p.total_points, 0) AS points
         FROM anggota_kelas ak
         INNER JOIN siswa sis ON sis.id = ak.siswa_id
         LEFT JOIN absensi a ON a.sesi_pembelajaran_id = :session_id AND a.siswa_id = sis.id
         LEFT JOIN (
             SELECT sesi_pembelajaran_id, siswa_id, SUM(poin) AS total_points
             FROM nilai_keaktifan WHERE sesi_pembelajaran_id = :points_session_id
             GROUP BY sesi_pembelajaran_id, siswa_id
         ) p ON p.sesi_pembelajaran_id = :joined_session_id AND p.siswa_id = sis.id
         WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
         ORDER BY sis.nama, sis.id"
    );
    $statement->execute([
        'session_id' => $sessionId,
        'points_session_id' => $sessionId,
        'joined_session_id' => $sessionId,
        'class_id' => $session['class_id'],
        'semester_id' => $session['semester_id'],
    ]);
    $session['students'] = $statement->fetchAll();

    return $session;
}

/** Fetch semester-scoped groups for one active class assignment. */
function guruGroupsForClass(int $guruId, int $classId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT kg.id, kg.nama AS name, COUNT(ak.siswa_id) AS member_count
         FROM kelompok kg
         INNER JOIN penugasan_guru pg ON pg.kelas_id = kg.kelas_id AND pg.semester_id = kg.semester_id
         LEFT JOIN anggota_kelompok ak ON ak.kelompok_id = kg.id
         WHERE pg.guru_id = :guru_id AND pg.kelas_id = :class_id AND pg.semester_id = :semester_id
         GROUP BY kg.id, kg.nama
         ORDER BY kg.nama, kg.id'
    );
    $statement->execute(['guru_id' => $guruId, 'class_id' => $classId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Fetch current eligible students and whether each belongs to a specific group. */
function guruGroupStudents(int $classId, int $semesterId, ?int $groupId = null): array
{
    $statement = guruDb()->prepare(
        'SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                CASE WHEN ag.kelompok_id IS NULL THEN 0 ELSE 1 END AS is_member
         FROM anggota_kelas ak
         INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         LEFT JOIN anggota_kelompok ag ON ag.siswa_id = sis.id AND ag.kelompok_id = :group_id
         WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
         ORDER BY sis.nama, sis.id'
    );
    $statement->execute(['group_id' => $groupId, 'class_id' => $classId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Create semester-scoped groups after validating a complete class roster partition. */
function guruCreateGroups(int $classId, string $namePrefix, array $memberGroups): void
{
    $namePrefix = trim($namePrefix);
    if ($namePrefix === '' || (function_exists('mb_strlen') ? mb_strlen($namePrefix, 'UTF-8') : strlen($namePrefix)) > 80) {
        throw new DomainException('Nama kelompok wajib diisi dan maksimal 80 karakter.');
    }
    if ($memberGroups === [] || count($memberGroups) > 20) {
        throw new DomainException('Jumlah kelompok harus antara 1 sampai 20.');
    }

    $profile = guruProfile();
    $semester = guruActiveSemester();
    if ($profile === null || $semester === null) {
        throw new DomainException('Profil Guru atau semester aktif tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $assignment = $pdo->prepare(
            'SELECT pg.id
             FROM penugasan_guru pg
             INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
             INNER JOIN semester sem ON sem.id = pg.semester_id AND sem.status = :active_status
             WHERE pg.guru_id = :guru_id AND pg.kelas_id = :class_id AND pg.semester_id = :semester_id
             LIMIT 1 FOR UPDATE'
        );
        $assignment->execute([
            'active_status' => 'aktif', 'guru_id' => $profile['id'],
            'class_id' => $classId, 'semester_id' => $semester['id'],
        ]);
        if ($assignment->fetchColumn() === false) {
            throw new DomainException('Kelas tidak ditugaskan kepada Anda pada semester aktif.');
        }

        $rosterQuery = $pdo->prepare(
            'SELECT ak.siswa_id
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id'
        );
        $rosterQuery->execute(['class_id' => $classId, 'semester_id' => $semester['id']]);
        $roster = array_map('intval', $rosterQuery->fetchAll(PDO::FETCH_COLUMN));
        $submitted = [];
        foreach ($memberGroups as $members) {
            if (!is_array($members) || $members === []) {
                throw new DomainException('Setiap kelompok harus memiliki anggota.');
            }
            foreach ($members as $studentId) {
                if (!is_int($studentId) || !in_array($studentId, $roster, true) || isset($submitted[$studentId])) {
                    throw new DomainException('Anggota kelompok tidak sesuai dengan siswa kelas aktif.');
                }
                $submitted[$studentId] = true;
            }
        }
        sort($roster);
        $submittedIds = array_map('intval', array_keys($submitted));
        sort($submittedIds);
        if ($roster === [] || $submittedIds !== $roster) {
            throw new DomainException('Semua siswa aktif harus masuk tepat ke satu kelompok.');
        }

        $insertGroup = $pdo->prepare(
            'INSERT INTO kelompok (kelas_id, semester_id, nama) VALUES (:class_id, :semester_id, :name)'
        );
        $insertMember = $pdo->prepare(
            'INSERT INTO anggota_kelompok (kelompok_id, siswa_id) VALUES (:group_id, :student_id)'
        );
        foreach (array_values($memberGroups) as $index => $members) {
            $groupName = $namePrefix . ' ' . ($index + 1);
            if ((function_exists('mb_strlen') ? mb_strlen($groupName, 'UTF-8') : strlen($groupName)) > 100) {
                throw new DomainException('Nama kelompok melebihi batas database.');
            }
            $insertGroup->execute(['class_id' => $classId, 'semester_id' => $semester['id'], 'name' => $groupName]);
            $groupId = (int) $pdo->lastInsertId();
            foreach ($members as $studentId) {
                $insertMember->execute(['group_id' => $groupId, 'student_id' => $studentId]);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Return an editable group only when it belongs to the teacher's active semester class. */
function guruOwnedActiveGroup(int $groupId): ?array
{
    $profile = guruProfile();
    $semester = guruActiveSemester();
    if ($profile === null || $semester === null) {
        return null;
    }

    $statement = guruDb()->prepare(
        'SELECT kg.id, kg.kelas_id AS class_id, kg.semester_id,
                kg.nama AS name, k.nama AS class_name, sem.nama AS semester
         FROM kelompok kg
         INNER JOIN penugasan_guru pg ON pg.kelas_id = kg.kelas_id AND pg.semester_id = kg.semester_id
         INNER JOIN kelas k ON k.id = kg.kelas_id
         INNER JOIN semester sem ON sem.id = kg.semester_id AND sem.status = :active_status
         WHERE kg.id = :group_id AND pg.guru_id = :guru_id
           AND kg.semester_id = :semester_id
         LIMIT 1'
    );
    $statement->execute([
        'active_status' => 'aktif', 'group_id' => $groupId,
        'guru_id' => $profile['id'], 'semester_id' => $semester['id'],
    ]);
    $group = $statement->fetch();

    return is_array($group) ? $group : null;
}

/** Update a group name and member set transactionally in the active semester. */
function guruUpdateGroup(int $groupId, string $name, array $studentIds): void
{
    $name = trim($name);
    if ($name === '' || (function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name)) > 100) {
        throw new DomainException('Nama kelompok wajib diisi dan maksimal 100 karakter.');
    }
    if ($studentIds === [] || count(array_unique($studentIds)) !== count($studentIds)) {
        throw new DomainException('Pilih satu atau lebih siswa tanpa duplikasi.');
    }

    $profile = guruProfile();
    $semester = guruActiveSemester();
    if ($profile === null || $semester === null) {
        throw new DomainException('Profil Guru atau semester aktif tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $groupQuery = $pdo->prepare(
            'SELECT kg.id, kg.kelas_id, kg.semester_id
             FROM kelompok kg
             INNER JOIN penugasan_guru pg ON pg.kelas_id = kg.kelas_id AND pg.semester_id = kg.semester_id
             INNER JOIN semester sem ON sem.id = kg.semester_id AND sem.status = :active_status
             WHERE kg.id = :group_id AND pg.guru_id = :guru_id AND kg.semester_id = :semester_id
             LIMIT 1 FOR UPDATE'
        );
        $groupQuery->execute([
            'active_status' => 'aktif', 'group_id' => $groupId,
            'guru_id' => $profile['id'], 'semester_id' => $semester['id'],
        ]);
        $group = $groupQuery->fetch();
        if (!is_array($group)) {
            throw new DomainException('Kelompok tidak ditemukan atau semester tidak aktif.');
        }

        $rosterQuery = $pdo->prepare(
            'SELECT ak.siswa_id
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id'
        );
        $rosterQuery->execute(['class_id' => $group['kelas_id'], 'semester_id' => $group['semester_id']]);
        $roster = array_map('intval', $rosterQuery->fetchAll(PDO::FETCH_COLUMN));
        foreach ($studentIds as $studentId) {
            if (!is_int($studentId) || !in_array($studentId, $roster, true)) {
                throw new DomainException('Anggota kelompok harus siswa aktif di kelas dan semester ini.');
            }
        }

        $update = $pdo->prepare('UPDATE kelompok SET nama = :name WHERE id = :group_id');
        $update->execute(['name' => $name, 'group_id' => $groupId]);
        $deleteMembers = $pdo->prepare('DELETE FROM anggota_kelompok WHERE kelompok_id = :group_id');
        $deleteMembers->execute(['group_id' => $groupId]);
        $insertMember = $pdo->prepare(
            'INSERT INTO anggota_kelompok (kelompok_id, siswa_id) VALUES (:group_id, :student_id)'
        );
        foreach ($studentIds as $studentId) {
            $insertMember->execute(['group_id' => $groupId, 'student_id' => $studentId]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Update one snapshot attendance row during its owned session. */
function guruSaveAttendance(int $sessionId, int $studentId, string $status, ?string $note): void
{
    if (!in_array($status, ['hadir', 'izin', 'alpha', 'sakit'], true)) {
        throw new DomainException('Status kehadiran tidak valid.');
    }
    if ($note !== null && (function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note)) > 500) {
        throw new DomainException('Catatan maksimal 500 karakter.');
    }

    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT sp.id, sp.status
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute([
            'session_id' => $sessionId,
            'user_id' => $user['id'],
        ]);
        $session = $sessionQuery->fetch();
        if (!is_array($session) || $session['status'] !== 'berlangsung') {
            throw new DomainException('Absensi hanya dapat diubah pada sesi yang sedang berlangsung milik Anda.');
        }

        $existing = $pdo->prepare(
            'SELECT id FROM absensi
             WHERE sesi_pembelajaran_id = :session_id AND siswa_id = :student_id
             LIMIT 1 FOR UPDATE'
        );
        $existing->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
        $attendanceId = $existing->fetchColumn();
        if ($attendanceId === false) {
            throw new DomainException('Siswa tidak memiliki baris absensi pada snapshot sesi ini.');
        }

        $save = $pdo->prepare(
            'UPDATE absensi
             SET status = :status, dicatat_oleh_user_id = :user_id,
                 catatan = :note, waktu_dicatat = CURRENT_TIMESTAMP()
             WHERE id = :attendance_id AND sesi_pembelajaran_id = :session_id'
        );
        $save->execute([
            'status' => $status,
            'user_id' => $user['id'],
            'note' => $note === '' ? null : $note,
            'attendance_id' => $attendanceId,
            'session_id' => $sessionId,
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Finish an owned ongoing session only when its tap and absence counts reconcile, or cancel it. */
function guruEndSession(int $sessionId, string $status): void
{
    if (!in_array($status, ['selesai', 'dibatalkan'], true)) {
        throw new InvalidArgumentException('Status sesi tidak valid.');
    }
    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            "SELECT sp.id, sp.status, sp.jumlah_tap
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE"
        );
        $statement->execute([
            'session_id' => $sessionId,
            'user_id' => $user['id'],
        ]);
        $session = $statement->fetch();
        if (!is_array($session)) {
            throw new DomainException('Sesi tidak ditemukan atau bukan milik Anda.');
        }

        if ($status === 'selesai') {
            if ($session['status'] !== 'berlangsung') {
                throw new DomainException('Hanya sesi yang sedang berlangsung yang dapat diselesaikan.');
            }
            $counts = $pdo->prepare(
                "SELECT COUNT(*) AS roster_count,
                        COALESCE(SUM(CASE WHEN status <> 'hadir' THEN 1 ELSE 0 END), 0) AS nonpresent_count
                 FROM absensi WHERE sesi_pembelajaran_id = :session_id"
            );
            $counts->execute(['session_id' => $sessionId]);
            $result = $counts->fetch();
            $rosterCount = (int) $result['roster_count'];
            $nonpresentCount = (int) $result['nonpresent_count'];
            $tapCount = (int) $session['jumlah_tap'];
            if ($tapCount + $nonpresentCount !== $rosterCount) {
                throw new DomainException(
                    'Sesi belum dapat diselesaikan: jumlah tap ditambah siswa bukan hadir harus sama dengan jumlah siswa pada snapshot.'
                );
            }
            $update = $pdo->prepare(
                "UPDATE sesi_pembelajaran
                 SET status = :terminal_status, selesai_at = CURRENT_TIMESTAMP()
                 WHERE id = :session_id AND status = :ongoing_status"
            );
            $update->execute([
                'terminal_status' => 'selesai', 'session_id' => $sessionId,
                'ongoing_status' => 'berlangsung',
            ]);
        } elseif ($session['status'] === 'berlangsung') {
            $update = $pdo->prepare(
                "UPDATE sesi_pembelajaran
                 SET status = :terminal_status, selesai_at = CURRENT_TIMESTAMP()
                 WHERE id = :session_id AND status = :ongoing_status"
            );
            $update->execute([
                'terminal_status' => 'dibatalkan', 'session_id' => $sessionId,
                'ongoing_status' => 'berlangsung',
            ]);
        } elseif ($session['status'] === 'selesai') {
            // Cancellation of a completed session changes only its terminal label.
            $update = $pdo->prepare(
                'UPDATE sesi_pembelajaran SET status = :terminal_status
                 WHERE id = :session_id AND status = :completed_status'
            );
            $update->execute([
                'terminal_status' => 'dibatalkan', 'session_id' => $sessionId,
                'completed_status' => 'selesai',
            ]);
        } else {
            throw new DomainException('Sesi yang sudah dibatalkan tidak dapat diubah kembali.');
        }
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
