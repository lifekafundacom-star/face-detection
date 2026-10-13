<section class="page-grid">
    <?php
        $profileImageUrl = !empty($user['profile_image']) ? (($basePath ?? '') . $user['profile_image']) : (!empty($user['profile_image_path']) ? (($basePath ?? '') . $user['profile_image_path']) : '');
        $profileThumbUrl = !empty($user['profile_thumbnail']) ? (($basePath ?? '') . $user['profile_thumbnail']) : $profileImageUrl;
        $faceReferenceUrl = !empty($user['face_reference_image']) ? (($basePath ?? '') . $user['face_reference_image']) : '';
        $faceReferenceThumbUrl = !empty($user['face_reference_thumbnail']) ? (($basePath ?? '') . $user['face_reference_thumbnail']) : $faceReferenceUrl;
        $faceDescriptorReady = !empty($user['face_reference_descriptor']);
    ?>
    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Profile Image</p><h2>Upload and update photo</h2></div></div>
        <form class="stack profile-image-form" action="<?= htmlspecialchars(($basePath ?? '') . '/api/profile/image') ?>" method="POST" enctype="multipart/form-data">
            <div class="alert alert-error profile-image-message" hidden></div>
            <div class="alert profile-image-success" hidden></div>
            <div class="profile-image-preview-card">
                <div class="profile-image-preview-shell">
                    <div id="profileImagePreviewFallback" class="profile-image-fallback" <?= $profileImageUrl ? 'hidden' : '' ?>><?= htmlspecialchars(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></div>
                    <img id="profileImagePreview" class="profile-image-preview" src="<?= htmlspecialchars($profileImageUrl) ?>" alt="<?= htmlspecialchars($user['name'] . ' profile image') ?>" <?= $profileImageUrl ? '' : 'hidden' ?>>
                </div>
                <div class="profile-image-meta">
                    <strong>Current image</strong>
                    <span>Upload JPG, PNG, or WEBP up to 4MB. Crop to square before saving.</span>
                </div>
            </div>
            <div class="profile-cropper" hidden>
                <div class="profile-cropper-canvas-wrap">
                    <canvas id="profileCropCanvas" class="profile-cropper-canvas" width="500" height="500"></canvas>
                </div>
                <div class="profile-cropper-controls">
                    <label class="field">
                        <span>Zoom</span>
                        <input type="range" class="profile-crop-zoom" min="1" max="3" step="0.05" value="1">
                    </label>
                    <label class="field">
                        <span>Move Left / Right</span>
                        <input type="range" class="profile-crop-offset-x" min="-200" max="200" step="1" value="0">
                    </label>
                    <label class="field">
                        <span>Move Up / Down</span>
                        <input type="range" class="profile-crop-offset-y" min="-200" max="200" step="1" value="0">
                    </label>
                </div>
                <input type="hidden" name="cropped_image_data" class="profile-cropped-image-data">
            </div>
            <label class="field">
                <span>Select image</span>
                <input type="file" name="profile_image" class="profile-image-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
            </label>
            <button class="btn btn-primary" type="submit">Upload Profile Image</button>
        </form>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Face Reference</p><h2>Capture live camera face</h2></div></div>
        <form class="stack profile-face-reference-form" action="<?= htmlspecialchars(($basePath ?? '') . '/api/profile/face-reference') ?>" method="POST">
            <div class="alert alert-error profile-face-message" hidden></div>
            <div class="alert profile-face-success" hidden></div>
            <div class="profile-image-preview-card">
                <div class="profile-image-preview-shell">
                    <div id="faceReferencePreviewFallback" class="profile-image-fallback" <?= $faceReferenceUrl ? 'hidden' : '' ?>><?= htmlspecialchars(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></div>
                    <img id="faceReferencePreview" class="profile-image-preview" src="<?= htmlspecialchars($faceReferenceUrl) ?>" alt="<?= htmlspecialchars($user['name'] . ' face reference image') ?>" <?= $faceReferenceUrl ? '' : 'hidden' ?>>
                </div>
                <div class="profile-image-meta">
                    <strong>Face reference image</strong>
                    <span>Front camera se clear face capture karke save karein. Admin camera attendance recognition isi reference ko prefer karega.</span>
                </div>
            </div>
            <div class="profile-face-camera-shell">
                <video class="profile-face-video" autoplay playsinline muted></video>
                <canvas class="profile-face-canvas" hidden></canvas>
                <canvas class="profile-face-overlay"></canvas>
                <img class="profile-face-snapshot" alt="<?= htmlspecialchars($user['name'] . ' captured face reference') ?>" hidden>
            </div>
            <div class="camera-actions">
                <button class="btn btn-light profile-face-start" type="button">Start Camera</button>
                <button class="btn btn-light profile-face-capture" type="button" disabled>Capture Face</button>
                <button class="btn btn-light profile-face-retake" type="button" disabled>Retake</button>
                <button class="btn btn-primary profile-face-save" type="submit" disabled>Save Face Reference</button>
            </div>
            <input type="hidden" name="captured_face_reference" class="profile-face-reference-data">
            <input type="hidden" name="face_reference_descriptor" class="profile-face-descriptor-data">
            <p class="empty-copy profile-face-status">Camera se live face capture karein. Ye dedicated face reference admin camera attendance page me matching ke liye use hoga.</p>
        </form>
    </article>

    <article class="panel panel-span-2">
        <div class="panel-head"><div><p class="eyebrow">Profile</p><h2>User details</h2></div></div>
        <div class="detail-grid profile-detail-grid">
            <div class="profile-detail-item">
                <span>Name</span>
                <strong><?= htmlspecialchars($user['name']) ?></strong>
            </div>
            <div class="profile-detail-item">
                <span>Email</span>
                <strong><?= htmlspecialchars($user['email']) ?></strong>
            </div>
            <div class="profile-detail-item">
                <span>Role</span>
                <strong><?= htmlspecialchars(ucfirst($user['role'])) ?></strong>
            </div>
            <div class="profile-detail-item">
                <span>Department</span>
                <strong><?= htmlspecialchars($user['department'] ?? 'Operations') ?></strong>
            </div>
            <div class="profile-detail-item">
                <span>Profile image</span>
                <strong><?= $profileImageUrl ? 'Uploaded' : 'Not uploaded yet' ?></strong>
            </div>
            <div class="profile-detail-item">
                <span>Profile thumbnail</span>
                <strong><?= $profileThumbUrl ? '50px thumbnail ready' : 'Will be generated on upload' ?></strong>
            </div>
            <div class="profile-detail-item">
                <span>Face reference</span>
                <strong><?= $faceReferenceUrl ? 'Saved from live camera' : 'Not captured yet' ?></strong>
            </div>
            <div class="profile-detail-item">
                <span>Face reference thumbnail</span>
                <strong><?= $faceReferenceThumbUrl ? 'Recognition thumbnail ready' : 'Will be generated on save' ?></strong>
            </div>
            <div class="profile-detail-item">
                <span>Face recognition model</span>
                <strong><?= $faceDescriptorReady ? 'Descriptor ready for matching' : 'Capture face reference to train matching' ?></strong>
            </div>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><div><p class="eyebrow">Security</p><h2>Change password</h2></div></div>
        <form class="stack profile-password-form" action="<?= htmlspecialchars(($basePath ?? '') . '/api/profile/change-password') ?>" method="POST">
            <div class="alert alert-error profile-password-message" hidden></div>
            <div class="alert profile-password-success" hidden></div>
            <label class="field password-field">
                <span>Current password</span>
                <input type="password" name="current_password" placeholder="Current password" autocomplete="current-password" required>
            </label>
            <label class="field password-field">
                <span>New password</span>
                <input type="password" name="new_password" placeholder="New password" autocomplete="new-password" required>
            </label>
            <label class="field password-field">
                <span>Confirm new password</span>
                <input type="password" name="confirm_password" placeholder="Confirm password" autocomplete="new-password" required>
            </label>
            <label class="password-toggle">
                <input type="checkbox" class="password-visibility-toggle">
                <span>Show passwords</span>
            </label>
            <button class="btn btn-primary" type="submit">Update Password</button>
        </form>
    </article>
</section>
