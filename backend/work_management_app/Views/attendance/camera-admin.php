<?php
$candidatePayload = array_map(static function (array $user) use ($basePath): array {
    $imageUrl = '';
    if (!empty($user['face_reference_thumbnail'])) {
        $imageUrl = $basePath . $user['face_reference_thumbnail'];
    } elseif (!empty($user['face_reference_image'])) {
        $imageUrl = $basePath . $user['face_reference_image'];
    }

    return [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'designation' => $user['designation'] ?: 'Team Member',
        'department' => $user['department'] ?: 'Unassigned',
        'image' => $imageUrl,
        'has_face_reference' => !empty($user['face_reference_image']) || !empty($user['face_reference_thumbnail']),
        'reference_source' => (!empty($user['face_reference_image']) || !empty($user['face_reference_thumbnail'])) ? 'Face Reference' : 'No Face Reference',
        'descriptor' => !empty($user['face_reference_descriptor']) ? json_decode((string) $user['face_reference_descriptor'], true) : null,
    ];
}, $attendanceCandidates ?? []);
?>
<section class="page-grid">
    <article class="panel panel-span-2">
        <div class="panel-head">
            <div><p class="eyebrow">Admin Only</p><h2>Camera Attendance Punch</h2></div>
        </div>
        <div class="camera-attendance-grid" id="cameraAttendanceApp" data-candidates='<?= htmlspecialchars(json_encode(array_values($candidatePayload)), ENT_QUOTES, 'UTF-8') ?>' data-action="<?= htmlspecialchars(($basePath ?? '') . '/api/admin/camera-attendance/punch') ?>" data-recognize-action="<?= htmlspecialchars(($basePath ?? '') . '/api/admin/camera-attendance/recognize') ?>">
            <div class="camera-attendance-panel">
                <label class="field">
                    <span>Select Employee</span>
                    <select id="cameraAttendanceUser">
                        <option value="">Auto match from all face references</option>
                        <?php foreach (($attendanceCandidates ?? []) as $candidate): ?>
                            <?php if (empty($candidate['face_reference_image']) && empty($candidate['face_reference_thumbnail'])) { continue; } ?>
                            <option value="<?= (int) $candidate['id'] ?>"><?= htmlspecialchars($candidate['name'] . ' - ' . ($candidate['designation'] ?: 'Team Member')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="camera-reference-card">
                    <div class="camera-reference-avatar" id="cameraReferenceAvatar">?</div>
                    <div class="camera-reference-meta">
                        <strong id="cameraReferenceName">Auto match mode</strong>
                        <span id="cameraReferenceDesignation">Capture face and system will compare it only with saved employee face reference photos.</span>
                        <small id="cameraReferenceDepartment">Optional: select a specific employee to verify against only one face reference image.</small>
                    </div>
                </div>
                <div class="camera-actions">
                    <button class="btn btn-light" id="cameraStartBtn" type="button">Start Camera</button>
                    <button class="btn btn-light" id="cameraCaptureBtn" type="button" disabled>Capture</button>
                    <button class="btn btn-light" id="cameraRetakeBtn" type="button" disabled>Retake</button>
                    <button class="btn btn-primary" id="cameraPunchBtn" type="button" disabled>Punch Attendance</button>
                </div>
                <p class="empty-copy" id="cameraAttendanceStatus">Capture face first. System sirf saved face reference image se employee recognize karke name show karega, phir attendance punch hoga.</p>
                <div class="camera-match-result" id="cameraMatchResult" hidden>
                    <span class="camera-match-label">Recognized Employee</span>
                    <strong id="cameraMatchName">--</strong>
                    <small id="cameraMatchMeta">Match score: --</small>
                </div>
            </div>
            <div class="camera-attendance-stage">
                <div class="camera-preview-shell">
                    <video id="cameraVideo" autoplay playsinline muted></video>
                    <canvas id="cameraOverlay" class="camera-overlay"></canvas>
                    <canvas id="cameraCanvas" hidden></canvas>
                    <img id="cameraSnapshot" alt="Captured attendance frame" hidden>
                </div>
            </div>
        </div>
    </article>
</section>
