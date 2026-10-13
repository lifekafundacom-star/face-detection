(function () {
    const boot = window.APP_BOOT || {};
    const basePath = boot.basePath || '';
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const tracker = document.getElementById('timeTracker');
    const label = document.getElementById('trackerLabel');
    const rawWorkTimer = document.getElementById('rawWorkTimer');
    const workTimer = document.getElementById('workTimer');
    const breakTimer = document.getElementById('breakTimer');
    const overtimeTimer = document.getElementById('overtimeTimer');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    let rawWorkSeconds = (boot.timeTracking && Number(boot.timeTracking.raw_work_seconds)) || 0;
    let workSeconds = (boot.timeTracking && Number(boot.timeTracking.work_seconds)) || 0;
    let breakSeconds = (boot.timeTracking && Number(boot.timeTracking.break_seconds)) || 0;
    let overtimeSeconds = (boot.timeTracking && Number(boot.timeTracking.overtime_seconds)) || 0;
    let standardDutySeconds = (boot.timeTracking && Number(boot.timeTracking.standard_duty_seconds)) || 32400;
    let currentStatus = (boot.timeTracking && boot.timeTracking.status) || 'offline';

    function formatTime(totalSeconds) {
        const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const seconds = String(totalSeconds % 60).padStart(2, '0');
        return `${hours}:${minutes}:${seconds}`;
    }

    function renderTimers() {
        if (rawWorkTimer) {
            rawWorkTimer.textContent = formatTime(rawWorkSeconds);
        }
        if (workTimer) {
            workTimer.textContent = formatTime(workSeconds);
        }
        if (breakTimer) {
            breakTimer.textContent = formatTime(breakSeconds);
        }
        if (overtimeTimer) {
            overtimeTimer.textContent = formatTime(overtimeSeconds);
        }
        if (tracker) {
            tracker.dataset.status = currentStatus;
        }
        if (label) {
            label.textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
        }
    }

    function applyTrackerSummary(summary) {
        if (!summary) {
            return;
        }

        currentStatus = summary.status || currentStatus;
        rawWorkSeconds = Number(summary.raw_work_seconds || 0);
        workSeconds = Number(summary.work_seconds || 0);
        breakSeconds = Number(summary.break_seconds || 0);
        overtimeSeconds = Number(summary.overtime_seconds || 0);
        standardDutySeconds = Number(summary.standard_duty_seconds || standardDutySeconds);
        renderTimers();
    }

    function startClock() {
        window.setInterval(function () {
            if (currentStatus === 'working') {
                rawWorkSeconds += 1;
                workSeconds += 1;
            } else if (currentStatus === 'break') {
                rawWorkSeconds += 1;
                breakSeconds += 1;
            }

            overtimeSeconds = Math.max(0, workSeconds - standardDutySeconds);
            renderTimers();
        }, 1000);
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload || {})
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    }

    let faceApiLoadPromise = null;

    async function ensureFaceApiModelsLoaded() {
        if (typeof window.faceapi === 'undefined') {
            throw new Error('Face recognition library could not load. Check local face-api assets.');
        }

        if (!faceApiLoadPromise) {
            const modelsUrl = boot.faceApiModelsUrl || `${basePath}/assets/vendor/face-api/models`;
            faceApiLoadPromise = Promise.all([
                window.faceapi.nets.tinyFaceDetector.loadFromUri(modelsUrl),
                window.faceapi.nets.faceLandmark68TinyNet.loadFromUri(modelsUrl),
                window.faceapi.nets.faceRecognitionNet.loadFromUri(modelsUrl)
            ]);
        }

        await faceApiLoadPromise;
        return window.faceapi;
    }

    async function resolveCandidateDescriptor(faceapi, candidate) {
        if (!candidate) {
            return null;
        }

        if (Array.isArray(candidate.descriptor) && candidate.descriptor.length === 128) {
            return candidate.descriptor.map(function (value) {
                return Number(value);
            });
        }

        if (Array.isArray(candidate.runtimeDescriptor) && candidate.runtimeDescriptor.length === 128) {
            return candidate.runtimeDescriptor;
        }

        if (!candidate.image) {
            return null;
        }

        try {
            const image = await faceapi.fetchImage(candidate.image);
            const detection = await faceapi
                .detectSingleFace(image, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            if (!detection || !detection.descriptor || detection.descriptor.length !== 128) {
                return null;
            }

            candidate.runtimeDescriptor = Array.from(detection.descriptor, function (value) {
                return Number(value);
            });
            return candidate.runtimeDescriptor;
        } catch (error) {
            return null;
        }
    }

    async function createLabeledDescriptors(faceapi, candidates, selectedId) {
        const scopedCandidates = candidates.filter(function (candidate) {
            if (!candidate) {
                return false;
            }

            if (selectedId > 0) {
                return Number(candidate.id) === selectedId;
            }

            return true;
        });

        const resolvedDescriptors = await Promise.all(scopedCandidates.map(async function (candidate) {
            const descriptor = await resolveCandidateDescriptor(faceapi, candidate);
            if (!descriptor) {
                return null;
            }

            return new faceapi.LabeledFaceDescriptors(
                String(candidate.id),
                [new Float32Array(descriptor)]
            );
        }));

        return resolvedDescriptors.filter(Boolean);
    }

    function descriptorDistance(left, right) {
        if (!left || !right || left.length !== right.length) {
            return Number.POSITIVE_INFINITY;
        }

        let sum = 0;
        for (let index = 0; index < left.length; index += 1) {
            const delta = Number(left[index]) - Number(right[index]);
            sum += delta * delta;
        }

        return Math.sqrt(sum);
    }

    document.querySelectorAll('[data-action]').forEach(function (button) {
        button.addEventListener('click', async function () {
            try {
                const action = button.getAttribute('data-action');
                const urlMap = {
                    'punch-in': `${basePath}/api/time-tracking/punch-in`,
                    'break': `${basePath}/api/time-tracking/break`,
                    'resume': `${basePath}/api/time-tracking/resume`,
                    'punch-out': `${basePath}/api/time-tracking/punch-out`
                };
                const payload = await postJson(urlMap[action], {});
                currentStatus = payload.status || currentStatus;
                workSeconds = Number(payload.work_seconds || 0);
                breakSeconds = Number(payload.break_seconds || 0);
                overtimeSeconds = Number(payload.overtime_seconds || 0);
                standardDutySeconds = Number(payload.standard_duty_seconds || standardDutySeconds);
                renderTimers();
            } catch (error) {
                window.alert(error.message || 'Timer action failed.');
            }
        });
    });

    document.querySelectorAll('.workflow-advance').forEach(function (button) {
        button.addEventListener('click', async function () {
            try {
                const taskId = button.getAttribute('data-task-id');
                const payload = await postJson(`${basePath}/api/workflows/${taskId}/advance`, {});
                button.textContent = payload.status === 'completed' ? 'Completed' : 'Advanced';
            } catch (error) {
                window.alert(error.message || 'Workflow action failed.');
            }
        });
    });

    document.querySelectorAll('.task-panel-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.getElementById(button.getAttribute('data-target'));
            if (!target) {
                return;
            }

            target.hidden = !target.hidden;
        });
    });

    document.querySelectorAll('.task-create-form').forEach(function (form) {
        const stepList = form.querySelector('.task-step-sequence');
        const stepTemplate = document.getElementById('taskStepTemplate');
        const addStepButton = form.querySelector('.task-step-add');
        const emptyCopy = form.querySelector('.task-step-empty');
        let departmentOptions = [];

        try {
            departmentOptions = JSON.parse(stepList ? (stepList.getAttribute('data-departments') || '[]') : '[]');
        } catch (error) {
            departmentOptions = [];
        }

        function buildDepartmentOptions(selectedValue) {
            const options = ['<option value="">Select department</option>'];
            departmentOptions.forEach(function (department) {
                const id = String(department.id || '');
                const selected = String(selectedValue || '') === id ? ' selected' : '';
                options.push(`<option value="${id}"${selected}>${department.name || 'Department'}</option>`);
            });
            return options.join('');
        }

        function renumberTaskSteps() {
            const rows = Array.prototype.slice.call(form.querySelectorAll('.task-step-row'));
            rows.forEach(function (row, index) {
                const order = row.querySelector('.task-step-order');
                if (order) {
                    order.textContent = `Step ${index + 1}`;
                }
            });
            if (emptyCopy) {
                emptyCopy.hidden = rows.length > 0;
            }
        }

        function addTaskStep(selectedValue, selectedPoints) {
            if (!stepList || !stepTemplate) {
                return;
            }

            const fragment = stepTemplate.content.cloneNode(true);
            const row = fragment.querySelector('.task-step-row');
            const select = fragment.querySelector('.task-step-select');
            const pointsInput = fragment.querySelector('.task-step-points');
            const upButton = fragment.querySelector('.task-step-up');
            const downButton = fragment.querySelector('.task-step-down');
            const removeButton = fragment.querySelector('.task-step-remove');

            if (select) {
                select.innerHTML = buildDepartmentOptions(selectedValue || '');
            }

            if (pointsInput) {
                pointsInput.value = String(Number(selectedPoints || 0));
            }

            if (upButton) {
                upButton.addEventListener('click', function () {
                    const previous = row.previousElementSibling;
                    if (previous) {
                        stepList.insertBefore(row, previous);
                        renumberTaskSteps();
                    }
                });
            }

            if (downButton) {
                downButton.addEventListener('click', function () {
                    const next = row.nextElementSibling;
                    if (next) {
                        stepList.insertBefore(next, row);
                        renumberTaskSteps();
                    }
                });
            }

            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    row.remove();
                    renumberTaskSteps();
                });
            }

            stepList.appendChild(fragment);
            renumberTaskSteps();
        }

        if (addStepButton) {
            addStepButton.addEventListener('click', function () {
                addTaskStep('', 0);
            });
        }

        if (stepList && !stepList.children.length) {
            addTaskStep('', 0);
            addTaskStep('', 0);
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            try {
                const formData = new FormData(form);
                const payload = {};
                const departments = Array.prototype.slice.call(form.querySelectorAll('.task-step-row'))
                    .map(function (row) {
                        const select = row.querySelector('.task-step-select');
                        const points = row.querySelector('.task-step-points');
                        return {
                            department_id: Number(select ? (select.value || 0) : 0),
                            points: Number(points ? (points.value || 0) : 0)
                        };
                    })
                    .filter(function (step) {
                        return step.department_id > 0;
                    });

                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                payload.workflow_departments = departments;
                if (!departments.length) {
                    throw new Error('Add at least one ordered workflow department step.');
                }
                const result = await postJson(form.getAttribute('action'), payload);
                window.alert(result.message || 'Workflow task created.');
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Task creation failed.');
            }
        });
    });

    async function openTaskDetail(taskId) {
        const modal = document.getElementById('taskDetailModal');
        const title = document.getElementById('taskDetailTitle');
        const meta = document.getElementById('taskDetailMeta');
        const timeline = document.getElementById('taskDetailTimeline');

        if (!modal || !title || !meta || !timeline) {
            return;
        }

        const response = await fetch(`${basePath}/api/tasks/${taskId}`);
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Task details failed to load.');
        }

        const task = data.task || {};
        title.textContent = task.title || 'Workflow task';
        meta.innerHTML = [
            ['Created by', task.created_by_name || 'Unknown'],
            ['Client / Project', task.client_name || '--'],
            ['Task Type', task.task_type || '--'],
            ['Priority', task.priority || '--'],
            ['Deadline', task.deadline || '--'],
            ['Current Dept', task.current_step_name || '--']
        ].map(function (item) {
            return `<div><span>${item[0]}</span><strong>${item[1]}</strong></div>`;
        }).join('');

        const steps = Array.isArray(task.timeline) ? task.timeline : [];
        timeline.innerHTML = steps.map(function (step) {
            const notes = Array.isArray(step.notes) ? step.notes.filter(Boolean) : [];
            return `
                <article class="task-timeline-step">
                    <strong>${step.department_name || 'Department'}</strong>
                    <span>Reserved by: ${step.reserved_by || '--'} ${step.reserved_at ? `(${step.reserved_at})` : ''}</span>
                    <span>Started: ${step.started_by || '--'} ${step.started_at ? `(${step.started_at})` : ''}</span>
                    <span>Completed: ${step.completed_by || '--'} ${step.completed_at ? `(${step.completed_at})` : ''}</span>
                    <div class="task-timeline-notes">${notes.length ? notes.map(function (note) { return `<small>${note}</small>`; }).join('') : '<small>No notes yet.</small>'}</div>
                </article>
            `;
        }).join('');

        modal.hidden = false;
        document.body.classList.add('task-modal-open');
    }

    document.querySelectorAll('[data-task-modal-close="true"]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = document.getElementById('taskDetailModal');
            if (!modal) {
                return;
            }
            modal.hidden = true;
            document.body.classList.remove('task-modal-open');
        });
    });

    document.querySelectorAll('.workflow-task-card').forEach(function (card) {
        card.addEventListener('click', function () {
            openTaskDetail(card.getAttribute('data-task-id')).catch(function (error) {
                window.alert(error.message || 'Task details failed to load.');
            });
        });
    });

    document.querySelectorAll('.task-action-btn').forEach(function (button) {
        button.addEventListener('click', async function (event) {
            event.stopPropagation();

            try {
                const taskId = button.getAttribute('data-task-id');
                const action = button.getAttribute('data-task-action');
                let payload = {};

                if (action === 'note' || action === 'complete-step') {
                    const note = window.prompt(action === 'note' ? 'Add task note' : 'Completion note');
                    if (note === null) {
                        return;
                    }
                    payload.note = note;
                }

                const urlMap = {
                    reserve: `${basePath}/api/tasks/${taskId}/reserve`,
                    start: `${basePath}/api/tasks/${taskId}/start`,
                    note: `${basePath}/api/tasks/${taskId}/note`,
                    'complete-step': `${basePath}/api/tasks/${taskId}/complete-step`
                };

                const result = await postJson(urlMap[action], payload);
                window.alert(result.message || 'Task updated.');
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Task action failed.');
            }
        });
    });

    (function () {
        const root = document.getElementById('cameraAttendanceApp');
        if (!root) {
            return;
        }

        const userSelect = document.getElementById('cameraAttendanceUser');
        const startButton = document.getElementById('cameraStartBtn');
        const captureButton = document.getElementById('cameraCaptureBtn');
        const retakeButton = document.getElementById('cameraRetakeBtn');
        const punchButton = document.getElementById('cameraPunchBtn');
        const statusNode = document.getElementById('cameraAttendanceStatus');
        const video = document.getElementById('cameraVideo');
        const overlay = document.getElementById('cameraOverlay');
        const canvas = document.getElementById('cameraCanvas');
        const snapshot = document.getElementById('cameraSnapshot');
        const avatar = document.getElementById('cameraReferenceAvatar');
        const refName = document.getElementById('cameraReferenceName');
        const refDesignation = document.getElementById('cameraReferenceDesignation');
        const refDepartment = document.getElementById('cameraReferenceDepartment');
        const matchResult = document.getElementById('cameraMatchResult');
        const matchName = document.getElementById('cameraMatchName');
        const matchMeta = document.getElementById('cameraMatchMeta');
        let candidates = [];
        let stream = null;
        let capturedImage = '';
        let recognizedEmployee = null;
        let detectionLoopId = 0;
        const recognitionThreshold = 0.48;

        try {
            candidates = JSON.parse(root.getAttribute('data-candidates') || '[]');
        } catch (error) {
            candidates = [];
        }

        function currentCandidate() {
            const selectedId = Number(userSelect ? (userSelect.value || 0) : 0);
            return candidates.find(function (candidate) {
                return Number(candidate.id) === selectedId;
            }) || null;
        }


        function renderCandidate() {
            const candidate = currentCandidate();
            if (!candidate) {
                avatar.innerHTML = '?';
                avatar.classList.remove('camera-reference-avatar-image');
                refName.textContent = 'Auto match mode';
                refDesignation.textContent = 'Capture face and system will compare it with saved employee face reference photos.';
                refDepartment.textContent = 'Optional: select a specific employee to verify against only one face reference image.';
                punchButton.disabled = !recognizedEmployee;
                return;
            }

            if (candidate.image) {
                avatar.innerHTML = `<img src="${candidate.image}" alt="${candidate.name} face reference">`;
                avatar.classList.add('camera-reference-avatar-image');
            } else {
                avatar.textContent = String(candidate.name || '?').charAt(0).toUpperCase();
                avatar.classList.remove('camera-reference-avatar-image');
            }

            refName.textContent = candidate.name || 'Employee';
            refDesignation.textContent = `${candidate.designation || 'Team Member'} | Using ${candidate.reference_source || 'Face Reference'}`;
            refDepartment.textContent = candidate.department || '';
            punchButton.disabled = !recognizedEmployee;
        }

        function setMatchResult(employee, score) {
            if (!matchResult || !matchName || !matchMeta) {
                return;
            }

            if (!employee) {
                matchResult.hidden = true;
                matchName.textContent = '--';
                matchMeta.textContent = 'Match score: --';
                return;
            }

            matchResult.hidden = false;
            matchName.textContent = employee.name || 'Employee';
            matchMeta.textContent = `Match score: ${typeof score !== 'undefined' ? score : '--'}%${employee.designation ? ` | ${employee.designation}` : ''}${employee.department ? ` | ${employee.department}` : ''}${employee.reference_source ? ` | Source: ${employee.reference_source}` : ''}`;
        }

        function drawDetectionBox(result) {
            if (!overlay || !video) {
                return;
            }

            const context = overlay.getContext('2d');
            overlay.width = video.videoWidth || overlay.clientWidth || 0;
            overlay.height = video.videoHeight || overlay.clientHeight || 0;
            context.clearRect(0, 0, overlay.width, overlay.height);

            if (!result || !result.box) {
                return;
            }

            const matched = !!result.employee;
            const strokeColor = matched ? '#2d8a58' : '#c84b31';
            const fillColor = matched ? 'rgba(45, 138, 88, 0.14)' : 'rgba(200, 75, 49, 0.12)';
            const labelText = matched ? `${result.employee.name} ${Math.round((1 - result.distance) * 100)}%` : 'Face detected';
            const box = result.box;

            context.strokeStyle = strokeColor;
            context.fillStyle = fillColor;
            context.lineWidth = 3;
            context.strokeRect(box.x, box.y, box.width, box.height);
            context.fillRect(box.x, box.y, box.width, box.height);

            context.font = '600 15px Manrope';
            const labelWidth = Math.max(120, context.measureText(labelText).width + 18);
            const labelHeight = 28;
            const labelX = box.x;
            const labelY = Math.max(8, box.y - labelHeight - 6);

            context.fillStyle = strokeColor;
            context.fillRect(labelX, labelY, labelWidth, labelHeight);
            context.fillStyle = '#ffffff';
            context.fillText(labelText, labelX + 9, labelY + 18);
        }

        async function analyzeVideoFrame() {
            if (!stream || !video || !video.videoWidth || !video.videoHeight) {
                return;
            }

            const faceapi = await ensureFaceApiModelsLoaded();
            const selectedId = Number(userSelect ? (userSelect.value || 0) : 0);
            const labeledDescriptors = await createLabeledDescriptors(faceapi, candidates, selectedId);

            if (!labeledDescriptors.length) {
                recognizedEmployee = null;
                setMatchResult(null);
                drawDetectionBox(null);
                punchButton.disabled = true;
                statusNode.textContent = 'Kisi usable face reference image ya descriptor wala employee available nahi hai.';
                return;
            }

            const detection = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            if (!detection) {
                recognizedEmployee = null;
                setMatchResult(null);
                drawDetectionBox(null);
                punchButton.disabled = true;
                statusNode.textContent = 'Face detect nahi hua. Camera ke saamne seedha dekhein.';
                return;
            }

            let bestMatch = null;
            labeledDescriptors.forEach(function (entry) {
                const distance = descriptorDistance(detection.descriptor, entry.descriptors[0]);
                if (!bestMatch || distance < bestMatch.distance) {
                    bestMatch = { label: entry.label, distance: distance };
                }
            });

            const matchedCandidate = bestMatch && bestMatch.distance <= recognitionThreshold
                ? candidates.find(function (candidate) { return String(candidate.id) === String(bestMatch.label); }) || null
                : null;

            recognizedEmployee = matchedCandidate;
            punchButton.disabled = !matchedCandidate;
            drawDetectionBox({
                box: detection.detection.box,
                employee: matchedCandidate,
                distance: bestMatch ? bestMatch.distance : 1
            });

            if (matchedCandidate) {
                if (userSelect && (!userSelect.value || Number(userSelect.value) !== Number(matchedCandidate.id))) {
                    userSelect.value = String(matchedCandidate.id);
                }
                renderCandidate();
                setMatchResult({
                    id: matchedCandidate.id,
                    name: matchedCandidate.name,
                    designation: matchedCandidate.designation,
                    department: matchedCandidate.department,
                    reference_source: matchedCandidate.reference_source
                }, Math.round((1 - bestMatch.distance) * 100));
                statusNode.textContent = `Face recognized: ${matchedCandidate.name}. Green box ke saath match lock ho gaya hai.`;
            } else {
                setMatchResult(null);
                statusNode.textContent = 'Face detect hua hai, lekin saved face reference se strong match nahi mila.';
            }
        }

        async function runDetectionLoop() {
            if (!stream) {
                return;
            }

            try {
                await analyzeVideoFrame();
            } catch (error) {
                statusNode.textContent = error.message || 'Face analysis failed.';
            }

            detectionLoopId = window.requestAnimationFrame(runDetectionLoop);
        }

        async function recognizeEmployee() {
            await analyzeVideoFrame();
        }

        async function startCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Camera access is not supported in this browser.');
            }

            await ensureFaceApiModelsLoaded();

            if (stream) {
                return;
            }

            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            video.srcObject = stream;
            if (typeof video.play === 'function') {
                await video.play();
            }
            if (overlay) {
                overlay.hidden = false;
            }
            captureButton.disabled = false;
            statusNode.textContent = 'Camera started. Face detect hote hi red box dikhega, aur match hote hi green box ke saath name show hoga.';
            if (detectionLoopId) {
                window.cancelAnimationFrame(detectionLoopId);
            }
            detectionLoopId = window.requestAnimationFrame(runDetectionLoop);
        }

        function stopSnapshotMode() {
            capturedImage = '';
            recognizedEmployee = null;
            snapshot.hidden = true;
            video.hidden = false;
            retakeButton.disabled = true;
            punchButton.disabled = true;
            setMatchResult(null);
            drawDetectionBox(null);
            renderCandidate();
        }

        function captureFrame() {
            if (!stream || !video.videoWidth || !video.videoHeight) {
                throw new Error('Camera is not ready yet.');
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            capturedImage = canvas.toDataURL('image/jpeg', 0.92);
            snapshot.src = capturedImage;
            snapshot.hidden = false;
            video.hidden = true;
            retakeButton.disabled = false;
            punchButton.disabled = !recognizedEmployee;
            statusNode.textContent = recognizedEmployee ? `Frame captured. ${recognizedEmployee.name} recognized and ready for attendance punch.` : 'Frame captured. Face ko pehle green match box me aane dijiye.';
        }

        async function punchAttendance() {
            if (!recognizedEmployee) {
                throw new Error('Employee ko pehle recognize hone dijiye.');
            }

            if (!capturedImage) {
                throw new Error('Capture an image before punching attendance.');
            }

            const result = await postJson(root.getAttribute('data-action'), {
                user_id: recognizedEmployee.id || '',
                captured_image: capturedImage,
                recognition_mode: 'faceapi-client',
                match_score: matchMeta && matchMeta.textContent ? parseInt(matchMeta.textContent.replace(/[^0-9]/g, ''), 10) || 0 : 0
            });

            if (result && result.employee && userSelect) {
                userSelect.value = String(result.employee.id || '');
            }
            renderCandidate();
            setMatchResult(result.employee || null, result.match && typeof result.match.score !== 'undefined' ? result.match.score : '--');
            statusNode.textContent = `${result.message || 'Attendance punched.'} ${result.employee && result.employee.name ? `Matched: ${result.employee.name}. ` : ''}Match score: ${result.match && typeof result.match.score !== 'undefined' ? result.match.score : '--'}%.`;
            punchButton.disabled = true;
        }

        if (userSelect) {
            userSelect.addEventListener('change', function () {
                recognizedEmployee = null;
                setMatchResult(null);
                punchButton.disabled = true;
                renderCandidate();
            });
        }

        if (startButton) {
            startButton.addEventListener('click', function () {
                startCamera().catch(function (error) {
                    statusNode.textContent = error.message || 'Unable to start camera.';
                });
            });
        }

        if (captureButton) {
            captureButton.addEventListener('click', function () {
                try {
                    captureFrame();
                } catch (error) {
                    statusNode.textContent = error.message || 'Capture failed.';
                }
            });
        }

        if (retakeButton) {
            retakeButton.addEventListener('click', function () {
                stopSnapshotMode();
                statusNode.textContent = 'Retake ready. Naya frame capture karke employee recognize karein.';
            });
        }

        if (punchButton) {
            punchButton.addEventListener('click', function () {
                punchAttendance().catch(function (error) {
                    statusNode.textContent = error.message || 'Attendance punch failed.';
                });
            });
        }

        renderCandidate();
        setMatchResult(null);
    }());

    document.querySelectorAll('.attendance-edit-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.getElementById(button.getAttribute('data-target'));
            if (!target) {
                return;
            }

            target.hidden = !target.hidden;
        });
    });

    document.querySelectorAll('.attendance-update-form').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            try {
                const formData = new FormData(form);
                const payload = {};

                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                if (!formData.has('overtime_approved')) {
                    payload.overtime_approved = 0;
                }

                if (form.classList.contains('attendance-detail-form')) {
                    const row = form.closest('.attendance-detail-row');
                    if (!row) {
                        return;
                    }

                    const clockIn = String(payload.clock_in || '').trim();
                    const clockOut = String(payload.clock_out || '').trim();
                    const breakTime = String(payload.break_time || '').trim();
                    const durationPattern = /^\d{1,2}:\d{2}(:\d{2})?$/;

                    if (breakTime !== '' && !durationPattern.test(breakTime)) {
                        throw new Error('Break Time must be in HH:MM or HH:MM:SS format.');
                    }

                    if (clockIn !== '' && clockOut !== '' && clockOut < clockIn) {
                        throw new Error('Clock Out must be later than Clock In.');
                    }

                    const result = await postJson(form.getAttribute('action'), payload);
                    const record = result.record || {};
                    updateAttendanceDetailRow(row, record);
                    if (result.timeTracking && boot.user && Number(boot.user.id || 0) === Number(record.user_id || 0)) {
                        applyTrackerSummary(result.timeTracking);
                    }
                    setAttendanceDetailRowEditing(row, false);
                    return;
                }

                await postJson(form.getAttribute('action'), payload);
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Attendance update failed.');
            }
        });
    });

    function humanizeStatus(status) {
        return String(status || '').replace(/_/g, ' ').replace(/\b\w/g, function (char) {
            return char.toUpperCase();
        });
    }

    function setAttendanceDetailRowEditing(row, isEditing) {
        const inputs = Array.prototype.slice.call(row.querySelectorAll('.inline-time-input'));
        const primaryButton = row.querySelector('.attendance-row-primary');
        const submitButton = row.querySelector('.attendance-row-submit');
        const cancelButton = row.querySelector('.attendance-row-cancel');

        inputs.forEach(function (input) {
            input.disabled = !isEditing;
        });

        row.classList.toggle('is-editing', isEditing);

        if (primaryButton) {
            primaryButton.hidden = isEditing;
        }
        if (submitButton) {
            submitButton.hidden = !isEditing;
        }
        if (cancelButton) {
            cancelButton.hidden = !isEditing;
        }
    }

    function rememberAttendanceDetailRowValues(row) {
        Array.prototype.slice.call(row.querySelectorAll('.inline-time-input')).forEach(function (input) {
            input.dataset.originalValue = input.value;
        });
    }

    function restoreAttendanceDetailRowValues(row) {
        Array.prototype.slice.call(row.querySelectorAll('.inline-time-input')).forEach(function (input) {
            input.value = input.dataset.originalValue || '';
        });
    }

    function updateAttendanceDetailRow(row, record) {
        const clockInInput = row.querySelector('input[name="clock_in"]');
        const clockOutInput = row.querySelector('input[name="clock_out"]');
        const breakInput = row.querySelector('input[name="break_time"]');
        const rawHoursCell = row.querySelector('.attendance-raw-hours');
        const totalHoursCell = row.querySelector('.attendance-total-hours');
        const overtimeCell = row.querySelector('.attendance-overtime');
        const statusCell = row.querySelector('.attendance-status-cell');

        if (clockInInput) {
            clockInInput.value = record.clock_in ? String(record.clock_in).slice(0, 5) : '';
            clockInInput.dataset.originalValue = clockInInput.value;
        }
        if (clockOutInput) {
            clockOutInput.value = record.clock_out ? String(record.clock_out).slice(0, 5) : '';
            clockOutInput.dataset.originalValue = clockOutInput.value;
        }
        if (breakInput) {
            breakInput.value = record.break_time || '00:00:00';
            breakInput.dataset.originalValue = breakInput.value;
        }
        if (rawHoursCell) {
            rawHoursCell.textContent = record.raw_hours || '00:00:00';
        }
        if (totalHoursCell) {
            totalHoursCell.textContent = record.net_hours || record.total_hours || '00:00:00';
        }
        if (overtimeCell) {
            overtimeCell.textContent = record.overtime || '00:00:00';
        }
        if (statusCell) {
            statusCell.innerHTML = '<span class="badge status-' + String(record.status || 'absent') + '">' + humanizeStatus(record.status || 'absent') + '</span>';
        }
    }

    Array.prototype.slice.call(document.querySelectorAll('.attendance-detail-row')).forEach(function (row) {
        const form = row.querySelector('.attendance-update-form');
        const editButton = row.querySelector('.attendance-row-primary');
        const cancelButton = row.querySelector('.attendance-row-cancel');

        if (!form || !editButton) {
            return;
        }

        form.classList.add('attendance-detail-form');
        rememberAttendanceDetailRowValues(row);
        setAttendanceDetailRowEditing(row, false);

        editButton.addEventListener('click', function () {
            Array.prototype.slice.call(document.querySelectorAll('.attendance-detail-row.is-editing')).forEach(function (editingRow) {
                if (editingRow !== row) {
                    restoreAttendanceDetailRowValues(editingRow);
                    setAttendanceDetailRowEditing(editingRow, false);
                }
            });

            rememberAttendanceDetailRowValues(row);
            setAttendanceDetailRowEditing(row, true);
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                restoreAttendanceDetailRowValues(row);
                setAttendanceDetailRowEditing(row, false);
            });
        }
    });

    document.querySelectorAll('.attendance-rules-form').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            try {
                const formData = new FormData(form);
                const payload = {};

                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                if (!formData.has('overtime_enabled')) {
                    payload.overtime_enabled = 0;
                }

                const result = await postJson(form.getAttribute('action'), payload);
                window.alert(result.message || 'Attendance rules updated.');
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Attendance rules update failed.');
            }
        });
    });

    document.querySelectorAll('.department-form').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            try {
                const formData = new FormData(form);
                const payload = {};

                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                const result = await postJson(form.getAttribute('action'), payload);
                window.alert(result.message || 'Department saved.');
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Department save failed.');
            }
        });
    });

    document.querySelectorAll('.department-delete-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            if (!window.confirm('Delete this department? This only works when no users are assigned to it.')) {
                return;
            }

            try {
                await postJson(button.getAttribute('data-action'), {});
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Department delete failed.');
            }
        });
    });

    document.querySelectorAll('.award-settings-form').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            try {
                const formData = new FormData(form);
                const payload = {};

                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                const result = await postJson(form.getAttribute('action'), payload);
                window.alert(result.message || 'Award saved.');
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Award save failed.');
            }
        });
    });

    document.querySelectorAll('.profile-password-form').forEach(function (form) {
        const errorBox = form.querySelector('.profile-password-message');
        const successBox = form.querySelector('.profile-password-success');
        const toggle = form.querySelector('.password-visibility-toggle');

        function showMessage(type, message) {
            if (errorBox) {
                errorBox.hidden = type !== 'error';
                errorBox.textContent = type === 'error' ? message : '';
            }

            if (successBox) {
                successBox.hidden = type !== 'success';
                successBox.textContent = type === 'success' ? message : '';
            }
        }

        if (toggle) {
            toggle.addEventListener('change', function () {
                Array.prototype.slice.call(form.querySelectorAll('.password-field input')).forEach(function (input) {
                    input.type = toggle.checked ? 'text' : 'password';
                });
            });
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            showMessage('success', '');
            showMessage('error', '');

            try {
                const formData = new FormData(form);
                const payload = {};
                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                const result = await postJson(form.getAttribute('action'), payload);
                showMessage('success', result.message || 'Password updated successfully.');
                form.reset();
                Array.prototype.slice.call(form.querySelectorAll('.password-field input')).forEach(function (input) {
                    input.type = 'password';
                });
            } catch (error) {
                showMessage('error', error.message || 'Password update failed.');
            }
        });
    });

    document.querySelectorAll('.profile-image-form').forEach(function (form) {
        const input = form.querySelector('.profile-image-input');
        const preview = document.getElementById('profileImagePreview');
        const fallback = document.getElementById('profileImagePreviewFallback');
        const errorBox = form.querySelector('.profile-image-message');
        const successBox = form.querySelector('.profile-image-success');
        const cropper = form.querySelector('.profile-cropper');
        const cropCanvas = form.querySelector('.profile-cropper-canvas');
        const cropDataField = form.querySelector('.profile-cropped-image-data');
        const zoomInput = form.querySelector('.profile-crop-zoom');
        const offsetXInput = form.querySelector('.profile-crop-offset-x');
        const offsetYInput = form.querySelector('.profile-crop-offset-y');
        const cropState = {
            image: null,
            objectUrl: '',
            zoom: 1,
            offsetX: 0,
            offsetY: 0
        };

        function showImageMessage(type, message) {
            if (errorBox) {
                errorBox.hidden = type !== 'error';
                errorBox.textContent = type === 'error' ? message : '';
            }

            if (successBox) {
                successBox.hidden = type !== 'success';
                successBox.textContent = type === 'success' ? message : '';
            }
        }

        function renderCropPreview() {
            if (!cropCanvas || !cropState.image) {
                return;
            }

            const context = cropCanvas.getContext('2d');
            const canvasWidth = cropCanvas.width;
            const canvasHeight = cropCanvas.height;
            const image = cropState.image;
            const baseScale = Math.max(canvasWidth / image.width, canvasHeight / image.height);
            const scale = baseScale * cropState.zoom;
            const drawWidth = image.width * scale;
            const drawHeight = image.height * scale;
            const drawX = ((canvasWidth - drawWidth) / 2) + cropState.offsetX;
            const drawY = ((canvasHeight - drawHeight) / 2) + cropState.offsetY;

            context.clearRect(0, 0, canvasWidth, canvasHeight);
            context.fillStyle = '#f5f1e8';
            context.fillRect(0, 0, canvasWidth, canvasHeight);
            context.drawImage(image, drawX, drawY, drawWidth, drawHeight);

            const dataUrl = cropCanvas.toDataURL('image/jpeg', 0.92);
            if (cropDataField) {
                cropDataField.value = dataUrl;
            }
            if (preview) {
                preview.src = dataUrl;
                preview.hidden = false;
            }
            if (fallback) {
                fallback.hidden = true;
            }
        }

        if (input) {
            input.addEventListener('change', function () {
                showImageMessage('success', '');
                showImageMessage('error', '');

                const file = input.files && input.files[0];
                if (!file) {
                    return;
                }

                const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (validTypes.indexOf(file.type) === -1) {
                    showImageMessage('error', 'Only JPG, PNG, and WEBP images are allowed.');
                    input.value = '';
                    return;
                }

                if (file.size > (4 * 1024 * 1024)) {
                    showImageMessage('error', 'Profile image must be 4MB or smaller.');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    const image = new Image();
                    image.onload = function () {
                        cropState.image = image;
                        cropState.zoom = 1;
                        cropState.offsetX = 0;
                        cropState.offsetY = 0;

                        if (zoomInput) {
                            zoomInput.value = '1';
                        }
                        if (offsetXInput) {
                            offsetXInput.value = '0';
                        }
                        if (offsetYInput) {
                            offsetYInput.value = '0';
                        }
                        if (cropper) {
                            cropper.hidden = false;
                        }
                        renderCropPreview();
                    };
                    image.src = String(event.target && event.target.result ? event.target.result : '');
                };
                reader.readAsDataURL(file);
            });
        }

        [zoomInput, offsetXInput, offsetYInput].forEach(function (control) {
            if (!control) {
                return;
            }

            control.addEventListener('input', function () {
                cropState.zoom = zoomInput ? Number(zoomInput.value || 1) : 1;
                cropState.offsetX = offsetXInput ? Number(offsetXInput.value || 0) : 0;
                cropState.offsetY = offsetYInput ? Number(offsetYInput.value || 0) : 0;
                renderCropPreview();
            });
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            showImageMessage('success', '');
            showImageMessage('error', '');

            try {
                const formData = new FormData(form);
                const response = await fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Profile image upload failed.');
                }

                showImageMessage('success', result.message || 'Profile image updated successfully.');
                window.setTimeout(function () {
                    window.location.reload();
                }, 600);
            } catch (error) {
                showImageMessage('error', error.message || 'Profile image upload failed.');
            }
        });
    });

    document.querySelectorAll('.profile-face-reference-form').forEach(function (form) {
        const video = form.querySelector('.profile-face-video');
        const canvas = form.querySelector('.profile-face-canvas');
        const overlay = form.querySelector('.profile-face-overlay');
        const snapshot = form.querySelector('.profile-face-snapshot');
        const startButton = form.querySelector('.profile-face-start');
        const captureButton = form.querySelector('.profile-face-capture');
        const retakeButton = form.querySelector('.profile-face-retake');
        const saveButton = form.querySelector('.profile-face-save');
        const hiddenField = form.querySelector('.profile-face-reference-data');
        const descriptorField = form.querySelector('.profile-face-descriptor-data');
        const statusNode = form.querySelector('.profile-face-status');
        const errorBox = form.querySelector('.profile-face-message');
        const successBox = form.querySelector('.profile-face-success');
        const preview = document.getElementById('faceReferencePreview');
        const previewFallback = document.getElementById('faceReferencePreviewFallback');
        let faceStream = null;
        let faceLoopId = 0;

        function showFaceMessage(type, message) {
            if (errorBox) {
                errorBox.hidden = type !== 'error';
                errorBox.textContent = type === 'error' ? message : '';
            }
            if (successBox) {
                successBox.hidden = type !== 'success';
                successBox.textContent = type === 'success' ? message : '';
            }
        }

        function resetFaceCaptureState() {
            if (hiddenField) {
                hiddenField.value = '';
            }
            if (descriptorField) {
                descriptorField.value = '';
            }
            if (snapshot) {
                snapshot.hidden = true;
            }
            if (video) {
                video.hidden = false;
            }
            if (retakeButton) {
                retakeButton.disabled = true;
            }
            if (saveButton) {
                saveButton.disabled = true;
            }
        }

        function drawProfileFaceBox(box, matched) {
            if (!overlay || !video) {
                return;
            }

            const context = overlay.getContext('2d');
            overlay.width = video.videoWidth || overlay.clientWidth || 0;
            overlay.height = video.videoHeight || overlay.clientHeight || 0;
            context.clearRect(0, 0, overlay.width, overlay.height);

            if (!box) {
                return;
            }

            const strokeColor = matched ? '#2d8a58' : '#c84b31';
            context.strokeStyle = strokeColor;
            context.lineWidth = 3;
            context.strokeRect(box.x, box.y, box.width, box.height);
        }

        async function runProfileFaceDetection() {
            if (!faceStream || !video || !video.videoWidth || !video.videoHeight) {
                return;
            }

            try {
                const faceapi = await ensureFaceApiModelsLoaded();
                const detection = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                    .withFaceLandmarks(true)
                    .withFaceDescriptor();

                drawProfileFaceBox(detection ? detection.detection.box : null, false);
                if (statusNode) {
                    statusNode.textContent = detection ? 'Face detected. Capture karke face reference save kar sakte hain.' : 'Face detect nahi hua. Camera ke saamne seedha dekhein.';
                }
            } catch (error) {
                if (statusNode) {
                    statusNode.textContent = error.message || 'Face detection failed.';
                }
            }

            faceLoopId = window.requestAnimationFrame(runProfileFaceDetection);
        }

        async function startFaceCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Camera access is not supported in this browser.');
            }

            await ensureFaceApiModelsLoaded();

            if (faceStream) {
                return;
            }

            faceStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            video.srcObject = faceStream;
            if (typeof video.play === 'function') {
                await video.play();
            }
            if (captureButton) {
                captureButton.disabled = false;
            }
            if (statusNode) {
                statusNode.textContent = 'Camera started. Face ko center me laakar capture karein.';
            }
            if (faceLoopId) {
                window.cancelAnimationFrame(faceLoopId);
            }
            faceLoopId = window.requestAnimationFrame(runProfileFaceDetection);
        }

        async function captureFaceReference() {
            if (!faceStream || !video || !video.videoWidth || !video.videoHeight || !canvas) {
                throw new Error('Camera is not ready yet.');
            }

            const faceapi = await ensureFaceApiModelsLoaded();
            const detection = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            if (!detection) {
                throw new Error('Clear face detect nahi hua. Face ko center me rakhkar dubara try karein.');
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.92);

            if (hiddenField) {
                hiddenField.value = dataUrl;
            }
            if (descriptorField) {
                descriptorField.value = JSON.stringify(Array.prototype.slice.call(detection.descriptor || []));
            }
            if (snapshot) {
                snapshot.src = dataUrl;
                snapshot.hidden = false;
            }
            if (video) {
                video.hidden = true;
            }
            drawProfileFaceBox(detection.detection.box, true);
            if (retakeButton) {
                retakeButton.disabled = false;
            }
            if (saveButton) {
                saveButton.disabled = false;
            }
            if (preview) {
                preview.src = dataUrl;
                preview.hidden = false;
            }
            if (previewFallback) {
                previewFallback.hidden = true;
            }
            if (statusNode) {
                statusNode.textContent = 'Face captured and descriptor ready. Save karein, phir admin camera attendance is face reference ko use karega.';
            }
        }

        if (startButton) {
            startButton.addEventListener('click', function () {
                showFaceMessage('success', '');
                showFaceMessage('error', '');
                startFaceCamera().catch(function (error) {
                    showFaceMessage('error', error.message || 'Unable to start camera.');
                });
            });
        }

        if (captureButton) {
            captureButton.addEventListener('click', async function () {
                showFaceMessage('success', '');
                showFaceMessage('error', '');
                try {
                    await captureFaceReference();
                } catch (error) {
                    showFaceMessage('error', error.message || 'Unable to capture face reference.');
                }
            });
        }

        if (retakeButton) {
            retakeButton.addEventListener('click', function () {
                resetFaceCaptureState();
                if (statusNode) {
                    statusNode.textContent = 'Retake ready. Naya face frame capture karein.';
                }
            });
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            showFaceMessage('success', '');
            showFaceMessage('error', '');

            try {
                if (!hiddenField || !hiddenField.value) {
                    throw new Error('Capture a face reference before saving.');
                }

                const payload = {
                    captured_face_reference: hiddenField.value,
                    face_reference_descriptor: descriptorField ? descriptorField.value : ''
                };
                const result = await postJson(form.getAttribute('action'), payload);
                showFaceMessage('success', result.message || 'Face reference saved successfully.');
                if (statusNode) {
                    statusNode.textContent = 'Face reference saved. Ye image admin camera attendance recognition me use hogi.';
                }
                window.setTimeout(function () {
                    window.location.reload();
                }, 600);
            } catch (error) {
                showFaceMessage('error', error.message || 'Face reference save failed.');
            }
        });

        resetFaceCaptureState();
    });

    document.querySelectorAll('.users-panel-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.getElementById(button.getAttribute('data-target'));
            if (!target) {
                return;
            }

            target.hidden = !target.hidden;
        });
    });

    document.querySelectorAll('.employee-live-card').forEach(function (card) {
        function openCard() {
            const href = card.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        }

        card.addEventListener('click', openCard);
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openCard();
            }
        });
    });

    document.querySelectorAll('.user-form').forEach(function (form) {
        const roleSelect = form.querySelector('.user-role-select');
        const syncUserRolePayload = function () {
            if (!roleSelect) {
                return;
            }

            const systemRoleInput = form.querySelector('input[name="role"]');
            const customRoleInput = form.querySelector('input[name="custom_role_id"]');
            const selectedValue = roleSelect.value || '';

            if (selectedValue.indexOf('custom:') === 0) {
                if (systemRoleInput) {
                    systemRoleInput.value = roleSelect.dataset.defaultSystemRole || 'employee';
                }
                if (customRoleInput) {
                    customRoleInput.value = selectedValue.replace('custom:', '');
                }
                return;
            }

            if (systemRoleInput) {
                systemRoleInput.value = selectedValue.replace('system:', '') || 'employee';
            }
            if (customRoleInput) {
                customRoleInput.value = '';
            }
        };

        if (roleSelect) {
            syncUserRolePayload();
            roleSelect.addEventListener('change', syncUserRolePayload);
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            try {
                syncUserRolePayload();
                const formData = new FormData(form);
                const payload = {};

                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                if (!formData.has('force_password_change')) {
                    payload.force_password_change = 0;
                }

                await postJson(form.getAttribute('action'), payload);
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'User save failed.');
            }
        });
    });

    document.querySelectorAll('.user-password-reset-form').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            try {
                const formData = new FormData(form);
                const payload = {};

                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                if (!formData.has('force_password_change')) {
                    payload.force_password_change = 0;
                }

                const result = await postJson(form.getAttribute('action'), payload);
                window.alert(`${result.message}${result.temporary_password ? ` Temporary password: ${result.temporary_password}` : ''}`);
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Password reset failed.');
            }
        });
    });

    document.querySelectorAll('.user-delete-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            if (!window.confirm('Deactivate this user? They will no longer be able to log in or punch attendance.')) {
                return;
            }

            try {
                await postJson(button.getAttribute('data-action'), {});
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Deactivate failed.');
            }
        });
    });

    const selectAllUsers = document.getElementById('selectAllUsers');
    const bulkCheckboxes = Array.prototype.slice.call(document.querySelectorAll('.bulk-user-checkbox'));
    const bulkForm = document.querySelector('.bulk-users-form');

    if (selectAllUsers) {
        selectAllUsers.addEventListener('change', function () {
            bulkCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllUsers.checked;
            });
        });
    }

    if (bulkForm) {
        bulkForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            try {
                const selectedIds = bulkCheckboxes.filter(function (checkbox) { return checkbox.checked; }).map(function (checkbox) { return Number(checkbox.value); });
                if (!selectedIds.length) {
                    window.alert('Select at least one user for bulk action.');
                    return;
                }

                const formData = new FormData(bulkForm);
                const payload = {};
                formData.forEach(function (value, key) {
                    payload[key] = value;
                });
                payload.user_ids = selectedIds;

                await postJson(bulkForm.getAttribute('action'), payload);
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Bulk action failed.');
            }
        });
    }

    function chart(id, config) {
        const node = document.getElementById(id);
        if (!node || typeof Chart === 'undefined') {
            return;
        }
        new Chart(node, config);
    }

    const dashboardData = window.DASHBOARD_DATA || {};
    const reportData = window.REPORT_DATA || {};
    const trend = dashboardData.performanceTrend || reportData.performanceTrend || [];
    const workflow = dashboardData.workflowSummary || [];

    chart('performanceChart', {
        type: 'line',
        data: {
            labels: trend.map(function (item) { return item.label; }),
            datasets: [{
                label: 'Performance Score',
                data: trend.map(function (item) { return Number(item.points); }),
                borderColor: '#b65a2a',
                backgroundColor: 'rgba(182, 90, 42, 0.15)',
                fill: true,
                tension: 0.35
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    chart('performanceChartSecondary', {
        type: 'bar',
        data: {
            labels: trend.map(function (item) { return item.label; }),
            datasets: [{
                data: trend.map(function (item) { return Number(item.points); }),
                backgroundColor: ['#e7b95e', '#dba34f', '#cf8d41', '#c37733', '#b65a2a', '#7a3514']
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    chart('workflowChart', {
        type: 'doughnut',
        data: {
            labels: workflow.map(function (item) { return item.name; }),
            datasets: [{
                data: workflow.map(function (item) { return Number(item.task_count); }),
                backgroundColor: ['#e7b95e', '#d79140', '#b65a2a', '#7a3514']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    if (typeof io !== 'undefined') {
        try {
            const socket = io('http://localhost:3001', { autoConnect: false });
            socket.connect();
        } catch (error) {
            // Optional for local development.
        }
    }

    renderTimers();
    startClock();
})();

