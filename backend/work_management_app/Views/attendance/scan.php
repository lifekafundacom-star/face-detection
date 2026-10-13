<?php
// backend/work_management_app/Views/attendance/scan.php
?>
<section class="page-grid scan-attendance-page">
    <article class="panel panel-span-2">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Biometric Attendance Station</p>
                <h2>Face Recognition Attendance Scanner</h2>
            </div>
        </div>

        <div class="grid-2-col-layout" style="display: grid; grid-template-columns: 1.25fr 0.75fr; gap: 24px; margin-top: 15px;">
            <!-- Left Column: Camera Scanner & Matched Employee Action Station -->
            <div class="glass-container-panel" style="background: var(--bg-card, #1c1c24); border: 1px solid var(--border, rgba(255,255,255,0.06)); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; align-items: center;">

                <!-- Camera Selection & Scanner Toggle Toolbar -->
                <div class="kiosk-cam-toolbar" style="display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 540px; margin-bottom: 12px; gap: 10px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="kioskCameraSelect" style="font-size: 0.82rem; color: var(--text-muted, #8e90a6); margin: 0; white-space: nowrap;"><i class="fa-solid fa-video"></i> Camera:</label>
                        <select id="kioskCameraSelect" style="font-size: 0.82rem; padding: 5px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.12); background: #111119; color: #fff; min-width: 190px; max-width: 250px;">
                            <option value="">Detecting cameras...</option>
                        </select>
                        <button type="button" id="kioskSwitchCamBtn" class="btn btn-secondary btn-small" title="Switch between connected physical webcams" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; padding: 5px 10px; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-camera-rotate"></i> Switch
                        </button>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button id="startKioskScanBtn" class="btn btn-primary btn-small" style="display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-play"></i> Turn On Scanner</button>
                        <button id="stopKioskScanBtn" class="btn btn-danger btn-small" style="display: none; align-items: center; gap: 6px;"><i class="fa-solid fa-stop"></i> Turn Off Scanner</button>
                    </div>
                </div>

                <!-- OBS Virtual Camera Warning Banner -->
                <div id="kioskObsWarningBanner" style="display: none; width: 100%; max-width: 540px; margin-bottom: 12px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 8px; padding: 10px 14px; font-size: 0.84rem; color: #fbbf24; align-items: center; justify-content: space-between; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                        <span><strong>OBS Virtual Camera detected:</strong> If you see an OBS logo or black screen, switch to your physical webcam.</span>
                    </div>
                    <button type="button" id="kioskAutoSwitchRealCamBtn" class="btn btn-warning btn-small" style="font-size: 0.78rem; padding: 4px 10px; white-space: nowrap; flex-shrink: 0; cursor: pointer;">
                        <i class="fa-solid fa-camera-rotate"></i> Switch to Real Webcam
                    </button>
                </div>

                <!-- Live Camera Viewport -->
                <div class="camera-scanner-viewport" id="attendanceCamViewport" style="position: relative; width: 100%; max-width: 540px; height: 360px; background: #0c0c12; border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                    <!-- Webcam Stream & Detection Canvas -->
                    <video id="kioskWebcam" style="width: 100%; height: 100%; object-fit: cover;" autoplay muted playsinline></video>
                    <canvas id="kioskOverlayCanvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></canvas>

                    <!-- Scanning Overlays -->
                    <div class="kiosk-scan-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; border: 2px solid rgba(59, 130, 246, 0.2); box-sizing: border-box;">
                        <div class="kiosk-scan-line" style="position: absolute; width: 100%; height: 3px; background: linear-gradient(90deg, transparent, #3b82f6, transparent); animation: scanLineMotion 3s linear infinite; top: 0;"></div>
                        <div class="kiosk-scan-corners" style="position: absolute; top: 15px; left: 15px; right: 15px; bottom: 15px; border: 2px dashed rgba(255,255,255,0.22); border-radius: 8px;"></div>
                    </div>

                    <!-- Placeholder Initial Screen -->
                    <div class="camera-loading-placeholder" id="kioskCamPlaceholder" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #0d0d14; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; z-index: 5;">
                        <i class="fa-solid fa-face-viewfinder" style="font-size: 3.5rem; color: rgba(255,255,255,0.18);"></i>
                        <span id="kioskPlaceholderText" style="font-size: 0.95rem; color: var(--text-muted, #8e90a6); text-align: center; padding: 0 24px;">Scanner ready. Select your webcam and click "Turn On Scanner" to start.</span>
                    </div>

                    <!-- Scan Success Match Screen Overlay -->
                    <div class="match-success-splash" id="kioskSuccessOverlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(16, 185, 129, 0.95); z-index: 10; flex-direction: column; align-items: center; justify-content: center; color: #fff; text-align: center; padding: 20px; animation: splashFadeIn 0.3s ease;">
                        <div class="success-chime-icon" style="width: 65px; height: 65px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 12px; animation: bounceIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                            <i class="fa-solid fa-check" id="kioskSuccessIcon"></i>
                        </div>
                        <h3 id="kioskSuccessTitle" style="font-size: 1.6rem; font-weight: 700; margin: 0;">Verified!</h3>
                        <p id="kioskSuccessName" style="font-size: 1.25rem; font-weight: 600; margin: 8px 0 2px 0;"></p>
                        <p id="kioskSuccessTime" style="font-size: 0.95rem; opacity: 0.85; margin: 0;"></p>
                    </div>
                </div>

                <!-- Matched Employee Profile & Punch Actions Section (Directly Under Scanner) -->
                <div id="matchedEmployeeSection" style="width: 100%; max-width: 540px; margin-top: 18px;">
                    <!-- Standby Placeholder (Before Face is Matched) -->
                    <div id="idleEmployeePlaceholder" style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.12); border-radius: 12px; padding: 22px; text-align: center; color: var(--text-muted, #8e90a6);">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(59,130,246,0.08); border: 2px dashed rgba(59,130,246,0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; color: #3b82f6; font-size: 1.6rem;">
                            <i class="fa-solid fa-id-card-clip"></i>
                        </div>
                        <h4 style="margin: 0 0 5px; color: var(--text, #fff); font-size: 1.05rem;">Employee Verification Standby</h4>
                        <p style="margin: 0; font-size: 0.84rem; line-height: 1.4;">Stand in front of the scanner. When recognized, your saved photo and <strong>Punch In</strong>, <strong>Break</strong>, <strong>Resume</strong> & <strong>Punch Out</strong> controls will appear below.</p>
                    </div>

                    <!-- Active Matched Employee Card -->
                    <div id="activeEmployeeCard" style="display: none; background: linear-gradient(145deg, rgba(28,28,40,0.95), rgba(18,18,26,0.98)); border: 1px solid rgba(59,130,246,0.35); border-radius: 12px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); animation: splashFadeIn 0.3s ease;">
                        <!-- Top Row: Pre-saved Photo + Employee Info + Live Status -->
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <!-- Pre-saved photo -->
                            <div style="position: relative; width: 95px; height: 95px; min-width: 95px; border-radius: 12px; overflow: hidden; border: 3px solid #3b82f6; box-shadow: 0 4px 15px rgba(59,130,246,0.25); background: #111;">
                                <img id="matchedEmpPhoto" src="" alt="Employee Photo" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>

                            <!-- Details -->
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                    <div>
                                        <h3 id="matchedEmpName" style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1.2;"></h3>
                                        <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px; flex-wrap: wrap;">
                                            <span id="matchedEmpCode" class="badge" style="background: rgba(59,130,246,0.18); color: #60a5fa; font-weight: 600; font-size: 0.78rem; padding: 2px 7px; border-radius: 4px;">EMP-0000</span>
                                            <span id="matchedEmpDept" style="font-size: 0.82rem; color: var(--text-muted, #94a3b8);"></span>
                                        </div>
                                    </div>
                                    <!-- Live Status Badge -->
                                    <span id="matchedEmpStatusBadge" class="badge" style="padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600;">Offline</span>
                                </div>

                                <!-- Live Metrics Row -->
                                <div id="matchedEmpMetricsRow" style="display: flex; gap: 14px; margin-top: 10px; font-size: 0.8rem; color: var(--text-muted, #94a3b8); flex-wrap: wrap;">
                                    <span><i class="fa-solid fa-clock" style="color: #60a5fa;"></i> In: <strong id="matchedClockInTime" style="color: #fff;">--:--</strong></span>
                                    <span><i class="fa-solid fa-briefcase" style="color: #10b981;"></i> Work: <strong id="matchedWorkTime" style="color: #fff;">00h 00m</strong></span>
                                    <span><i class="fa-solid fa-mug-hot" style="color: #f59e0b;"></i> Break: <strong id="matchedBreakTime" style="color: #fff;">00m</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Feedback Message Banner -->
                        <div id="punchActionFeedbackMsg" style="display: none; margin-top: 14px; padding: 9px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 500; text-align: center;"></div>

                        <!-- 4 Action Buttons: Punch In, Break, Resume, Punch Out -->
                        <div style="margin-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 14px;">
                            <div style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted, #94a3b8); margin-bottom: 8px; font-weight: 600;">
                                Attendance Actions for Scanned Employee:
                            </div>
                            <div class="kiosk-action-btn-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                                <!-- Punch In Button -->
                                <button type="button" class="btn kiosk-punch-action-btn" id="btnActionPunchIn" data-action="punchIn" style="background: #10b981; color: #fff; border: none; border-radius: 8px; padding: 10px 4px; font-size: 0.84rem; font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 5px; cursor: pointer; transition: all 0.15s;">
                                    <i class="fa-solid fa-right-to-bracket" style="font-size: 1.15rem;"></i>
                                    <span>Punch In</span>
                                </button>

                                <!-- Break Button -->
                                <button type="button" class="btn kiosk-punch-action-btn" id="btnActionBreak" data-action="takeBreak" style="background: #f59e0b; color: #fff; border: none; border-radius: 8px; padding: 10px 4px; font-size: 0.84rem; font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 5px; cursor: pointer; transition: all 0.15s;">
                                    <i class="fa-solid fa-mug-hot" style="font-size: 1.15rem;"></i>
                                    <span>Break</span>
                                </button>

                                <!-- Resume Button -->
                                <button type="button" class="btn kiosk-punch-action-btn" id="btnActionResume" data-action="resume" style="background: #3b82f6; color: #fff; border: none; border-radius: 8px; padding: 10px 4px; font-size: 0.84rem; font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 5px; cursor: pointer; transition: all 0.15s;">
                                    <i class="fa-solid fa-play" style="font-size: 1.15rem;"></i>
                                    <span>Resume</span>
                                </button>

                                <!-- Punch Out Button -->
                                <button type="button" class="btn kiosk-punch-action-btn" id="btnActionPunchOut" data-action="punchOut" style="background: #ef4444; color: #fff; border: none; border-radius: 8px; padding: 10px 4px; font-size: 0.84rem; font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 5px; cursor: pointer; transition: all 0.15s;">
                                    <i class="fa-solid fa-right-from-bracket" style="font-size: 1.15rem;"></i>
                                    <span>Punch Out</span>
                                </button>
                            </div>

                            <!-- Bottom helper row -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <span style="font-size: 0.75rem; color: var(--text-muted, #94a3b8);"><i class="fa-solid fa-shield-halved" style="color: #10b981;"></i> Biometric Verified Face ID</span>
                                <button type="button" id="kioskNextPersonBtn" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.78rem; padding: 5px 12px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-user-plus"></i> Next Person / Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Live Check-Ins Feed -->
            <div class="glass-container-panel" style="background: var(--bg-card, #1c1c24); border: 1px solid var(--border, rgba(255,255,255,0.06)); border-radius: 12px; padding: 22px; display: flex; flex-direction: column;">
                <h3 style="font-size: 1.15rem; font-weight: 600; margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fa-solid fa-bolt" style="color: var(--primary, #3b82f6); margin-right: 6px;"></i> Live Attendance Activity</span>
                    <span style="font-size: 0.75rem; background: rgba(16,185,129,0.12); color: #10b981; padding: 2px 8px; border-radius: 12px; font-weight: 600;">Today</span>
                </h3>

                <div class="live-logs-scroll-container" style="flex: 1; overflow-y: auto; max-height: 480px;">
                    <ul id="kioskLogsList" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                        <?php if (empty($recentLogs)): ?>
                            <li id="kioskNoLogsPlaceholder" style="text-align: center; color: var(--text-muted, #8e90a6); font-style: italic; padding: 50px 0;">
                                <i class="fa-solid fa-clock-rotate-left" style="font-size: 2.2rem; display: block; margin-bottom: 8px; opacity: 0.4;"></i>
                                No scans marked today yet. Turn on the scanner to start check-ins.
                            </li>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <li class="log-history-item" style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 10px; align-items: center;">
                                    <div>
                                        <strong style="color: var(--text-bright, #fff); font-size: 0.95rem;"><?= htmlspecialchars($log['name']) ?></strong>
                                        <span style="font-size: 0.8rem; color: var(--text-muted, #8e90a6); display: block;"><?= htmlspecialchars($log['designation'] ?: 'Team Member') ?></span>
                                    </div>
                                    <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                        <span class="badge" style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.75rem; padding: 2px 6px; border-radius: 4px;">Present</span>
                                        <span style="font-size: 0.8rem; color: var(--text-muted, #8e90a6);"><?= date('h:i A', strtotime($log['clock_in'])) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </article>
</section>

<!-- Audio & Notification Toasts -->
<div id="kioskToastContainer" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; display: flex; flex-direction: column; gap: 10px; pointer-events: none;"></div>

<!-- Styles & Keyframes -->
<style>
@keyframes scanLineMotion {
    0% { top: 0%; }
    50% { top: 100%; }
    100% { top: 0%; }
}
@keyframes splashFadeIn {
    from { opacity: 0; transform: scale(0.97); }
    to { opacity: 1; transform: scale(1); }
}
@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); opacity: 0.8; }
    70% { transform: scale(0.9); opacity: 0.9; }
    100% { transform: scale(1); opacity: 1; }
}
@keyframes slideInLog {
    from { transform: translateY(-15px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.kiosk-toast {
    background: #1e1e2d;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    padding: 12px 18px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    pointer-events: auto;
    animation: slideInLog 0.3s ease;
    min-width: 260px;
}
.kiosk-toast.success { border-left: 4px solid #10b981; }
.kiosk-toast.info { border-left: 4px solid #3b82f6; }
.kiosk-toast.warning { border-left: 4px solid #f59e0b; }
.kiosk-toast.error { border-left: 4px solid #ef4444; }
.kiosk-toast.fade-out { opacity: 0; transform: translateY(10px); transition: all 0.35s ease; }

.kiosk-punch-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
}
.kiosk-punch-action-btn:active {
    transform: translateY(0);
}
.kiosk-punch-action-btn.suggested-action {
    box-shadow: 0 0 0 3px rgba(255,255,255,0.4), 0 6px 20px rgba(0,0,0,0.35);
    animation: pulseSuggested 1.8s infinite;
}
@keyframes pulseSuggested {
    0% { box-shadow: 0 0 0 0px rgba(255,255,255,0.5); }
    70% { box-shadow: 0 0 0 8px rgba(255,255,255,0); }
    100% { box-shadow: 0 0 0 0px rgba(255,255,255,0); }
}
</style>

<!-- Face Recognition & Interactive Punch Engine -->
<script>
(function() {
    let modelsLoaded = false;
    let isScannerRunning = false;
    let videoEl = document.getElementById('kioskWebcam');
    let canvasEl = document.getElementById('kioskOverlayCanvas');
    let streamTrack = null;
    let faceMatcher = null;
    let registeredEmployees = [];
    let checkScanInterval = null;
    let isProcessingMatch = false;
    let currentMatchedEmployee = null;
    let resetTimer = null;
    let availableVideoDevices = [];
    let isDetecting = false;

    const basePath = '<?= htmlspecialchars(($basePath ?? '')) ?>';
    const startBtn = document.getElementById('startKioskScanBtn');
    const stopBtn = document.getElementById('stopKioskScanBtn');
    const cameraSelect = document.getElementById('kioskCameraSelect');
    const placeholder = document.getElementById('kioskCamPlaceholder');
    const placeholderText = document.getElementById('kioskPlaceholderText');
    const viewport = document.getElementById('attendanceCamViewport');

    const idlePlaceholder = document.getElementById('idleEmployeePlaceholder');
    const activeEmployeeCard = document.getElementById('activeEmployeeCard');
    const empPhoto = document.getElementById('matchedEmpPhoto');
    const empName = document.getElementById('matchedEmpName');
    const empCode = document.getElementById('matchedEmpCode');
    const empDept = document.getElementById('matchedEmpDept');
    const empStatusBadge = document.getElementById('matchedEmpStatusBadge');
    const clockInTimeEl = document.getElementById('matchedClockInTime');
    const workTimeEl = document.getElementById('matchedWorkTime');
    const breakTimeEl = document.getElementById('matchedBreakTime');
    const actionFeedback = document.getElementById('punchActionFeedbackMsg');
    const nextPersonBtn = document.getElementById('kioskNextPersonBtn');

    const defaultAvatarSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='95' height='95' viewBox='0 0 24 24' fill='%2364748b'><path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/></svg>";

    async function loadFaceApiScript() {
        if (typeof faceapi !== 'undefined') return;
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = `${basePath}/assets/vendor/face-api/face-api.min.js`;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error("Failed to load face-api script."));
            document.head.appendChild(script);
        });
    }

    function playBeep(frequency, type = 'sine', duration = 0.15) {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.type = type;
            oscillator.frequency.value = frequency;
            gainNode.gain.setValueAtTime(0.12, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + duration);
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.start();
            oscillator.stop(audioCtx.currentTime + duration);
        } catch (err) {}
    }

    function playSuccessChime() {
        playBeep(523.25, 'sine', 0.08); // C5
        setTimeout(() => playBeep(659.25, 'sine', 0.08), 85); // E5
        setTimeout(() => playBeep(783.99, 'sine', 0.22), 170); // G5
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('kioskToastContainer');
        const toast = document.createElement('div');
        toast.className = `kiosk-toast ${type}`;

        const icon = type === 'success' ? 'fa-circle-check' : (type === 'info' ? 'fa-circle-info' : (type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'));
        const iconColor = type === 'success' ? '#10b981' : (type === 'info' ? '#3b82f6' : (type === 'warning' ? '#f59e0b' : '#ef4444'));

        toast.innerHTML = `
            <i class="fa-solid ${icon}" style="color: ${iconColor}; font-size: 1.1rem;"></i>
            <span>${message}</span>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 350);
        }, 4000);
    }

    function isVirtualCam(label) {
        if (!label) return false;
        const low = label.toLowerCase();
        return low.includes('obs') || low.includes('virtual') || low.includes('manycam') || low.includes('droidcam') || low.includes('vysor');
    }

    function checkObsWarning() {
        const obsBanner = document.getElementById('kioskObsWarningBanner');
        if (!obsBanner) return;
        const selectedOpt = cameraSelect.selectedOptions[0];
        const selectedLabel = selectedOpt ? selectedOpt.textContent : '';
        const hasPhysical = availableVideoDevices.some(d => !isVirtualCam(d.label || ''));

        if (isVirtualCam(selectedLabel) && hasPhysical) {
            obsBanner.style.display = 'flex';
        } else {
            obsBanner.style.display = 'none';
        }
    }

    async function enumerateKioskCameras() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableVideoDevices = devices.filter(d => d.kind === 'videoinput');

            if (availableVideoDevices.length === 0) {
                cameraSelect.innerHTML = '<option value="">Default Webcam</option>';
                checkObsWarning();
                return;
            }

            const currentVal = cameraSelect.value;
            cameraSelect.innerHTML = '';

            const physicalGroup = document.createElement('optgroup');
            physicalGroup.label = "Physical Webcams (Recommended)";
            const virtualGroup = document.createElement('optgroup');
            virtualGroup.label = "Virtual Cameras (OBS / Software)";

            let defaultPhysicalId = null;

            availableVideoDevices.forEach((dev, idx) => {
                const opt = document.createElement('option');
                opt.value = dev.deviceId;
                const label = dev.label || `Camera ${idx + 1}`;
                opt.textContent = label;

                if (isVirtualCam(label)) {
                    virtualGroup.appendChild(opt);
                } else {
                    physicalGroup.appendChild(opt);
                    if (!defaultPhysicalId) {
                        defaultPhysicalId = dev.deviceId;
                    }
                }
            });

            if (physicalGroup.children.length > 0) cameraSelect.appendChild(physicalGroup);
            if (virtualGroup.children.length > 0) cameraSelect.appendChild(virtualGroup);

            // Select priority: currentVal if valid -> default physical -> first device
            const allDeviceIds = availableVideoDevices.map(d => d.deviceId);
            if (currentVal && allDeviceIds.includes(currentVal)) {
                cameraSelect.value = currentVal;
            } else if (defaultPhysicalId) {
                cameraSelect.value = defaultPhysicalId;
            } else if (availableVideoDevices[0]) {
                cameraSelect.value = availableVideoDevices[0].deviceId;
            }

            checkObsWarning();
        } catch (err) {
            console.warn("Camera enum error:", err);
        }
    }

    async function loadModelsAndDatabase() {
        placeholderText.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="font-size:1.8rem; color:#3b82f6;"></i><div style="margin-top:10px">Loading High-Accuracy AI Face Models...</div>';
        try {
            await loadFaceApiScript();
            const modelsUrl = `${basePath}/assets/vendor/face-api/models`;

            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(modelsUrl),
                faceapi.nets.faceLandmark68TinyNet.loadFromUri(modelsUrl),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelsUrl)
            ]);

            try {
                await faceapi.nets.ssdMobilenetv1.loadFromUri(modelsUrl);
            } catch (e) {
                console.warn("SSD model load note:", e);
            }

            modelsLoaded = true;

            placeholderText.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="font-size:1.8rem; color:#3b82f6;"></i><div style="margin-top:10px">Loading Employee Face Database...</div>';
            const response = await fetch(`${basePath}/api/attendance/scan-candidates`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "Failed to load employee database.");
            }

            registeredEmployees = data.employees || [];
            if (registeredEmployees.length === 0) {
                showToast("No active employee face references found in database. Register face profiles in Manage Users first.", "warning");
                placeholderText.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="font-size:2rem; color:#f59e0b;"></i><div style="margin-top:10px; color:#f59e0b;">No face profiles registered yet.</div>';
                return false;
            }

            // Create LabeledFaceDescriptors supporting single and multi-angle descriptors
            const labeledDescriptors = [];
            registeredEmployees.forEach(emp => {
                const descriptorList = emp.descriptors || (emp.face_descriptor ? [emp.face_descriptor] : []);
                const float32Arrays = descriptorList.map(d => new Float32Array(d));
                if (float32Arrays.length > 0) {
                    labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(String(emp.employee_id), float32Arrays));
                }
            });

            // Distance threshold 0.55 for accurate and reliable recognition without false rejections
            faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.55);
            showToast(`Loaded ${registeredEmployees.length} employee face profiles. Ready to scan!`, "info");
            return true;

        } catch (err) {
            console.error("Scanner init error:", err);
            showToast("Initialization error: " + err.message, "error");
            placeholderText.innerHTML = '<i class="fa-solid fa-circle-xmark" style="font-size:2.2rem; color:#ef4444;"></i><div style="margin-top:10px; color:#ef4444;">Model loading error. Refresh page.</div>';
            return false;
        }
    }

    async function startScanner() {
        if (!modelsLoaded || !faceMatcher) {
            const success = await loadModelsAndDatabase();
            if (!success) return;
        }

        try {
            // First: If devices haven't been granted permissions yet, enumerateDevices() will return empty labels.
            // A quick probe unlocks device labels so we can accurately detect physical vs virtual cameras.
            const initialDevices = await navigator.mediaDevices.enumerateDevices().catch(() => []);
            const hasLabels = initialDevices.some(d => d.kind === 'videoinput' && d.label);
            if (!hasLabels) {
                try {
                    const probe = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    probe.getTracks().forEach(t => t.stop());
                } catch (e) {
                    console.warn("Permission probe warning:", e);
                }
            }

            await enumerateKioskCameras();

            // Auto-switch to physical camera if OBS is selected and a physical camera is available
            const selectedOpt = cameraSelect.selectedOptions[0];
            const selectedLabel = selectedOpt ? selectedOpt.textContent : '';
            if (isVirtualCam(selectedLabel)) {
                const physical = availableVideoDevices.find(d => !isVirtualCam(d.label || ''));
                if (physical) {
                    cameraSelect.value = physical.deviceId;
                    checkObsWarning();
                }
            }

            if (streamTrack) {
                streamTrack.stop();
                streamTrack = null;
            }

            const chosenDeviceId = cameraSelect ? cameraSelect.value : '';
            const videoConstraints = {
                width: { ideal: 1280 },
                height: { ideal: 720 }
            };
            if (chosenDeviceId) {
                videoConstraints.deviceId = { ideal: chosenDeviceId };
            }

            const stream = await navigator.mediaDevices.getUserMedia({
                video: videoConstraints,
                audio: false
            });

            videoEl.srcObject = stream;
            streamTrack = stream.getVideoTracks()[0];

            await enumerateKioskCameras();

            videoEl.onloadedmetadata = () => {
                videoEl.play();
                viewport.classList.add('scanning');
                placeholder.style.display = 'none';
                startBtn.style.display = 'none';
                stopBtn.style.display = 'inline-flex';
                isScannerRunning = true;

                const displaySize = { width: videoEl.offsetWidth, height: videoEl.offsetHeight };
                canvasEl.width = displaySize.width;
                canvasEl.height = displaySize.height;
                faceapi.matchDimensions(canvasEl, displaySize);

                showToast("Scanner is active. Please look directly at camera.", "success");
                runScanLoop();
            };
        } catch (err) {
            console.error("Webcam start failed:", err);
            showToast("Camera access error. Select another camera from the dropdown.", "error");
        }
    }

    function stopScanner() {
        if (checkScanInterval) {
            clearInterval(checkScanInterval);
            checkScanInterval = null;
        }
        if (streamTrack) {
            streamTrack.stop();
            streamTrack = null;
        }
        videoEl.srcObject = null;
        isScannerRunning = false;
        isProcessingMatch = false;
        isDetecting = false;

        viewport.classList.remove('scanning');
        placeholder.style.display = 'flex';
        placeholderText.textContent = "Scanner ready. Turn on scanner to load face database and start.";
        startBtn.style.display = 'inline-flex';
        stopBtn.style.display = 'none';

        const ctx = canvasEl.getContext('2d');
        ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);
    }

    function resetEmployeeView() {
        currentMatchedEmployee = null;
        isProcessingMatch = false;
        activeEmployeeCard.style.display = 'none';
        idlePlaceholder.style.display = 'block';
        actionFeedback.style.display = 'none';
        actionFeedback.textContent = '';
        if (resetTimer) {
            clearTimeout(resetTimer);
            resetTimer = null;
        }
        document.querySelectorAll('.kiosk-punch-action-btn').forEach(b => {
            b.classList.remove('suggested-action');
            b.disabled = false;
        });
    }

    function scheduleAutoReset(seconds = 14) {
        if (resetTimer) clearTimeout(resetTimer);
        resetTimer = setTimeout(() => {
            resetEmployeeView();
        }, seconds * 1000);
    }

    function runScanLoop() {
        checkScanInterval = setInterval(async () => {
            if (!isScannerRunning || isProcessingMatch || isDetecting) return;
            isDetecting = true;

            const displaySize = { width: videoEl.offsetWidth, height: videoEl.offsetHeight };
            if (canvasEl.width !== displaySize.width) {
                canvasEl.width = displaySize.width;
                canvasEl.height = displaySize.height;
                faceapi.matchDimensions(canvasEl, displaySize);
            }

            try {
                // High-speed, lightweight TinyFaceDetector (30-60ms inference)
                let detections = await faceapi.detectAllFaces(videoEl, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                    .withFaceLandmarks(true)
                    .withFaceDescriptors();

                // If TinyFaceDetector found nothing, fallback to SSD MobileNet if loaded
                if (detections.length === 0 && faceapi.nets.ssdMobilenetv1 && faceapi.nets.ssdMobilenetv1.params) {
                    detections = await faceapi.detectAllFaces(videoEl, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 }))
                        .withFaceLandmarks(true)
                        .withFaceDescriptors();
                }

                const resized = faceapi.resizeResults(detections, displaySize);
                const ctx = canvasEl.getContext('2d');
                ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);

                // Pair raw detections with resized boxes and filter out small noise (< 50x50 px)
                const validPairs = detections.map((det, idx) => ({
                    det,
                    resized: resized[idx],
                    box: resized[idx] ? (resized[idx].box || (resized[idx].detection ? resized[idx].detection.box : null)) : null
                })).filter(p => p.box && p.box.width >= 50 && p.box.height >= 50);

                // Sort by box area descending (largest face in foreground first)
                validPairs.sort((a, b) => (b.box.width * b.box.height) - (a.box.width * a.box.height));

                // Draw bounding boxes on canvas
                validPairs.forEach((pair, idx) => {
                    const b = pair.box;
                    ctx.strokeStyle = idx === 0 ? '#3b82f6' : 'rgba(59, 130, 246, 0.4)';
                    ctx.lineWidth = idx === 0 ? 3 : 1.5;
                    ctx.strokeRect(b.x, b.y, b.width, b.height);
                });

                // Match against registered employee face database
                if (validPairs.length > 0 && !isProcessingMatch && faceMatcher) {
                    for (const pair of validPairs) {
                        const match = faceMatcher.findBestMatch(pair.det.descriptor);
                        if (match.label !== 'unknown' && match.distance <= 0.55) {
                            const matchedEmpId = parseInt(match.label, 10);
                            const empObj = registeredEmployees.find(e => e.employee_id === matchedEmpId);
                            if (empObj && (!currentMatchedEmployee || currentMatchedEmployee.employee_id !== matchedEmpId)) {
                                isProcessingMatch = true;

                                // Turn box green on instant match
                                ctx.strokeStyle = '#10b981';
                                ctx.lineWidth = 4;
                                ctx.strokeRect(pair.box.x, pair.box.y, pair.box.width, pair.box.height);

                                await handleEmployeeRecognized(empObj, pair.det.descriptor);
                                break;
                            }
                        }
                    }
                }
            } catch (err) {
                console.error("Scan loop error:", err);
            } finally {
                isDetecting = false;
            }
        }, 180);
    }

    async function handleEmployeeRecognized(emp, descriptor) {
        currentMatchedEmployee = emp;
        playBeep(659.25, 'sine', 0.15);

        // Fetch live status from server
        let summaryData = null;
        try {
            const res = await fetch(`${basePath}/api/attendance/scan-status/${emp.employee_id}`);
            const json = await res.json();
            if (json.success) {
                summaryData = json.summary;
            }
        } catch (e) {
            console.warn("Could not fetch employee summary:", e);
        }

        renderMatchedEmployeeCard(emp, summaryData);
        scheduleAutoReset(15);
    }

    function renderMatchedEmployeeCard(emp, summary) {
        empName.textContent = emp.name;
        empCode.textContent = emp.employee_code || `EMP-${emp.employee_id}`;
        empDept.textContent = emp.department ? `${emp.department} • ${emp.designation || 'Team Member'}` : (emp.designation || 'Team Member');

        // Pre-saved photo
        let photoSrc = defaultAvatarSvg;
        if (emp.photo) {
            photoSrc = (emp.photo.startsWith('http') || emp.photo.startsWith('/')) ? `${basePath}${emp.photo}` : `${basePath}/${emp.photo}`;
        }
        empPhoto.src = photoSrc;

        const currentStatus = summary ? (summary.status || 'offline') : 'offline';
        updateStatusBadge(currentStatus);

        if (summary) {
            clockInTimeEl.textContent = summary.clock_in ? formatTime(summary.clock_in) : '--:--';
            workTimeEl.textContent = summary.work_time || '00h 00m';
            breakTimeEl.textContent = summary.break_time || '00m';
        } else {
            clockInTimeEl.textContent = '--:--';
            workTimeEl.textContent = '00h 00m';
            breakTimeEl.textContent = '00m';
        }

        // Highlight recommended action button
        highlightSuggestedAction(currentStatus);

        idlePlaceholder.style.display = 'none';
        activeEmployeeCard.style.display = 'block';
        actionFeedback.style.display = 'none';

        showToast(`Face Verified: ${emp.name}`, "info");
    }

    function updateStatusBadge(status) {
        if (status === 'working') {
            empStatusBadge.style.background = 'rgba(16, 185, 129, 0.18)';
            empStatusBadge.style.color = '#10b981';
            empStatusBadge.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            empStatusBadge.innerHTML = '<i class="fa-solid fa-circle-dot" style="animation: pulseSuggested 1.5s infinite;"></i> Working';
            empPhoto.parentElement.style.borderColor = '#10b981';
        } else if (status === 'break') {
            empStatusBadge.style.background = 'rgba(245, 158, 11, 0.18)';
            empStatusBadge.style.color = '#f59e0b';
            empStatusBadge.style.border = '1px solid rgba(245, 158, 11, 0.3)';
            empStatusBadge.innerHTML = '<i class="fa-solid fa-mug-hot"></i> On Break';
            empPhoto.parentElement.style.borderColor = '#f59e0b';
        } else {
            empStatusBadge.style.background = 'rgba(148, 163, 184, 0.18)';
            empStatusBadge.style.color = '#94a3b8';
            empStatusBadge.style.border = '1px solid rgba(148, 163, 184, 0.3)';
            empStatusBadge.innerHTML = '<i class="fa-solid fa-circle-pause"></i> Offline';
            empPhoto.parentElement.style.borderColor = '#3b82f6';
        }
    }

    function highlightSuggestedAction(status) {
        document.querySelectorAll('.kiosk-punch-action-btn').forEach(b => {
            b.classList.remove('suggested-action');
            b.style.opacity = '0.75';
        });

        const btnIn = document.getElementById('btnActionPunchIn');
        const btnBreak = document.getElementById('btnActionBreak');
        const btnResume = document.getElementById('btnActionResume');
        const btnOut = document.getElementById('btnActionPunchOut');

        if (status === 'offline') {
            btnIn.classList.add('suggested-action');
            btnIn.style.opacity = '1';
        } else if (status === 'working') {
            btnBreak.classList.add('suggested-action');
            btnBreak.style.opacity = '1';
            btnOut.style.opacity = '1';
        } else if (status === 'break') {
            btnResume.classList.add('suggested-action');
            btnResume.style.opacity = '1';
            btnOut.style.opacity = '1';
        }
    }

    function formatTime(timeStr) {
        if (!timeStr) return '--:--';
        const parts = timeStr.split(':');
        if (parts.length < 2) return timeStr;
        let hours = parseInt(parts[0], 10);
        const minutes = parts[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }

    // Execute Punch Action (Punch In, Break, Resume, Punch Out)
    async function executePunchAction(action) {
        if (!currentMatchedEmployee) {
            showToast("No employee currently recognized. Please look into the camera.", "warning");
            return;
        }

        const empId = currentMatchedEmployee.employee_id;
        const targetBtn = document.querySelector(`.kiosk-punch-action-btn[data-action="${action}"]`);
        const originalContent = targetBtn ? targetBtn.innerHTML : '';

        if (targetBtn) {
            targetBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="font-size:1.15rem;"></i><span>Saving...</span>';
            document.querySelectorAll('.kiosk-punch-action-btn').forEach(b => b.disabled = true);
        }

        try {
            const response = await fetch(`${basePath}/api/attendance/scan-punch`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    employee_id: empId,
                    action: action
                })
            });

            const result = await response.json();

            if (result.success) {
                playSuccessChime();

                // Show feedback banner in employee card
                actionFeedback.style.display = 'block';
                actionFeedback.style.background = 'rgba(16, 185, 129, 0.18)';
                actionFeedback.style.color = '#10b981';
                actionFeedback.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                actionFeedback.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${result.message}`;

                showToast(result.message, "success");

                // Update employee live status & metrics
                if (result.summary) {
                    updateStatusBadge(result.summary.status);
                    highlightSuggestedAction(result.summary.status);
                    clockInTimeEl.textContent = result.summary.clock_in ? formatTime(result.summary.clock_in) : '--:--';
                    workTimeEl.textContent = result.summary.work_time || '00h 00m';
                    breakTimeEl.textContent = result.summary.break_time || '00m';
                }

                // Add to live activity feed on right column
                updateLogsSidebar(result.employee_name, result.designation, result.time, result.status_label || action);

                // Auto reset after 8 seconds
                scheduleAutoReset(8);

            } else {
                playBeep(220, 'sawtooth', 0.35);
                actionFeedback.style.display = 'block';
                actionFeedback.style.background = 'rgba(239, 68, 68, 0.18)';
                actionFeedback.style.color = '#ef4444';
                actionFeedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                actionFeedback.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${result.message || 'Action failed'}`;
                showToast(result.message || "Attendance action failed.", "error");
            }
        } catch (err) {
            console.error("Execute punch error:", err);
            playBeep(220, 'sawtooth', 0.35);
            showToast("Server communication error. Please try again.", "error");
        } finally {
            if (targetBtn) {
                targetBtn.innerHTML = originalContent;
            }
            document.querySelectorAll('.kiosk-punch-action-btn').forEach(b => b.disabled = false);
        }
    }

    function updateLogsSidebar(name, designation, time, actionLabel) {
        const list = document.getElementById('kioskLogsList');
        const placeholder = document.getElementById('kioskNoLogsPlaceholder');
        if (placeholder) {
            placeholder.remove();
        }

        let badgeBg = 'rgba(16,185,129,0.15)';
        let badgeColor = '#10b981';
        if (actionLabel.toLowerCase().includes('break')) {
            badgeBg = 'rgba(245,158,11,0.15)';
            badgeColor = '#f59e0b';
        } else if (actionLabel.toLowerCase().includes('resume')) {
            badgeBg = 'rgba(59,130,246,0.15)';
            badgeColor = '#3b82f6';
        } else if (actionLabel.toLowerCase().includes('out')) {
            badgeBg = 'rgba(239,68,68,0.15)';
            badgeColor = '#ef4444';
        }

        const li = document.createElement('li');
        li.className = 'log-history-item';
        li.style.cssText = 'display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 10px; align-items: center; animation: slideInLog 0.4s ease;';
        li.innerHTML = `
            <div>
                <strong style="color: var(--text-bright, #fff); font-size: 0.95rem;">${name}</strong>
                <span style="font-size: 0.8rem; color: var(--text-muted, #8e90a6); display: block;">${designation}</span>
            </div>
            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                <span class="badge" style="background: ${badgeBg}; color: ${badgeColor}; font-size: 0.75rem; padding: 2px 7px; border-radius: 4px;">${actionLabel}</span>
                <span style="font-size: 0.8rem; color: var(--text-muted, #8e90a6);">${time}</span>
            </div>
        `;
        list.insertBefore(li, list.firstChild);

        const items = list.querySelectorAll('.log-history-item');
        if (items.length > 8) {
            items[items.length - 1].remove();
        }
    }

    // Attach Event Listeners
    startBtn.addEventListener('click', startScanner);
    stopBtn.addEventListener('click', stopScanner);

    cameraSelect.addEventListener('change', () => {
        checkObsWarning();
        if (isScannerRunning) {
            stopScanner();
            startScanner();
        }
    });

    const switchCamBtn = document.getElementById('kioskSwitchCamBtn');
    if (switchCamBtn) {
        switchCamBtn.addEventListener('click', async () => {
            if (availableVideoDevices.length <= 1) {
                showToast("Only 1 camera detected.", "info");
                return;
            }
            const currentId = cameraSelect.value;
            const currentIdx = availableVideoDevices.findIndex(d => d.deviceId === currentId);
            const nextIdx = (currentIdx + 1) % availableVideoDevices.length;
            cameraSelect.value = availableVideoDevices[nextIdx].deviceId;
            checkObsWarning();
            if (isScannerRunning) {
                stopScanner();
                await startScanner();
            }
            showToast("Switched to: " + (availableVideoDevices[nextIdx].label || `Camera ${nextIdx + 1}`), "info");
        });
    }

    const autoSwitchRealCamBtn = document.getElementById('kioskAutoSwitchRealCamBtn');
    if (autoSwitchRealCamBtn) {
        autoSwitchRealCamBtn.addEventListener('click', async () => {
            const physical = availableVideoDevices.find(d => !isVirtualCam(d.label || ''));
            if (physical) {
                cameraSelect.value = physical.deviceId;
                checkObsWarning();
                if (isScannerRunning) {
                    stopScanner();
                    await startScanner();
                }
                showToast("Switched to physical webcam: " + (physical.label || 'Webcam'), "success");
            } else {
                showToast("No physical webcam detected.", "warning");
            }
        });
    }

    nextPersonBtn.addEventListener('click', () => {
        resetEmployeeView();
        showToast("Station reset for next person.", "info");
    });

    document.querySelectorAll('.kiosk-punch-action-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.getAttribute('data-action');
            if (action) {
                executePunchAction(action);
            }
        });
    });

    // Enumerate cameras on page load
    if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
        navigator.mediaDevices.enumerateDevices().then(() => enumerateKioskCameras());
    }

    window.addEventListener('beforeunload', () => {
        stopScanner();
    });
})();
</script>
