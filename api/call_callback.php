<?php
/**
 * Twilio Call Status Callback
 * Updates call_logs/leads lifecycle when call state changes.
 */

define('SKIP_SESSION_AUTH', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$callSid       = sanitize($_POST['CallSid'] ?? '');
$callStatusRaw = sanitize($_POST['CallStatus'] ?? '');
$duration      = (int)($_POST['CallDuration'] ?? 0);

if (!$callSid) {
    http_response_code(200);
    exit;
}

$log = DB::fetchOne('SELECT * FROM call_logs WHERE call_sid = ? ORDER BY id DESC LIMIT 1', [$callSid]);
if (!$log) {
    http_response_code(200);
    exit;
}

$normalized = mapCallStatus($callStatusRaw);
$isTerminal = in_array($normalized, ['completed', 'no_answer', 'failed'], true);

if ($isTerminal) {
    DB::execute(
        'UPDATE call_logs SET status = ?, duration = ?, ended_at = NOW() WHERE id = ?',
        [$normalized, $duration, $log['id']]
    );

    $leadStatus = match ($normalized) {
        'completed' => 'called',
        'no_answer' => 'no_answer',
        default => 'failed',
    };

    $retryIncrement = $normalized === 'no_answer' ? 1 : 0;

    DB::execute(
        'UPDATE leads SET status = ?, retry_count = retry_count + ?, last_called_at = NOW() WHERE id = ?',
        [$leadStatus, $retryIncrement, $log['lead_id']]
    );
} else {
    DB::execute('UPDATE call_logs SET status = ? WHERE id = ?', [$normalized, $log['id']]);

    // Keep lead as calling until final callback arrives
    if (in_array($normalized, ['calling', 'connected'], true)) {
        DB::execute('UPDATE leads SET status = "calling" WHERE id = ?', [$log['lead_id']]);
    }
}

http_response_code(200);
exit;

function mapCallStatus(string $twilioStatus): string {
    return match (strtolower($twilioStatus)) {
        'queued', 'initiated', 'ringing', 'in-progress' => 'calling',
        'answered' => 'connected',
        'completed' => 'completed',
        'busy', 'no-answer', 'canceled' => 'no_answer',
        'failed' => 'failed',
        default => 'calling',
    };
}
