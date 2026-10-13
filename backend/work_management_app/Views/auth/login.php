<div class="auth-shell">
    <section class="auth-panel auth-brand">
        <p class="eyebrow">Modern workforce operations</p>
        <h1>Run teams, workflows, attendance, and reports from one clean dashboard.</h1>
        <p class="lede">Built for admins, managers, and employees with role-aware access, live productivity tracking, and structured workflow handoffs.</p>
        <div class="feature-list">
            <article>
                <strong>Workflow Automation</strong>
                <span>Move work from Camera to Editor to Marketing automatically.</span>
            </article>
            <article>
                <strong>Actionable Reports</strong>
                <span>Track performance, overdue tasks, attendance health, and team trends.</span>
            </article>
            <article>
                <strong>Global Timer</strong>
                <span>Punch in, break, resume, and punch out from any screen.</span>
            </article>
        </div>
    </section>

    <section class="auth-panel auth-form">
        <div>
            <p class="eyebrow">Welcome back</p>
            <h2>Sign in</h2>
        </div>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="<?= htmlspecialchars(($basePath ?? '') . '/login') ?>" method="POST" class="stack">
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="info@lifekafunda.com" required>
            </label>
            <label class="field">
                <span>Password</span>
                <input type="password" name="password" value="password123" required>
            </label>
            <button class="btn btn-primary btn-full" type="submit">Enter Dashboard</button>
        </form>
        <div class="demo-credentials">
            <strong>Demo access</strong>
            <span>Admin: info@lifekafunda.com</span>
            <span>Manager: manager@productivity.local</span>
            <span>Employee: employee@productivity.local</span>
        </div>
    </section>
</div>
