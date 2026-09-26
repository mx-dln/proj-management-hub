<?php
$projectStatus = $projectStatus ?? [];
$activityStatus = $activityStatus ?? [];
$monthlyActivities = $monthlyActivities ?? [];
$departmentLoad = $departmentLoad ?? [];
$topProjects = $topProjects ?? [];
$budgetSummary = $budgetSummary ?? ['total_budget' => 0, 'average_completion' => 0];
$programPerformance = $programPerformance ?? [];
$participantTotal = $participantTotal ?? 0;
$driveStats = $driveStats ?? [
    'files' => 0,
    'folders' => 0,
    'used_bytes' => 0,
    'disk_total_bytes' => 0,
    'disk_free_bytes' => 0,
    'disk_used_bytes' => 0,
    'effective_upload_limit_bytes' => 0,
];

$totalProjects = array_sum(array_column($projectStatus, 'total')) ?: ($stats['projects'] ?? $stats['my_projects'] ?? 0);
$totalActivities = array_sum(array_column($activityStatus, 'total')) ?: ($stats['activities'] ?? $stats['my_activities'] ?? 0);
$chartedActivities = array_sum(array_column($monthlyActivities, 'total'));
$completedProjects = 0;
$pendingProjects = 0;
$approvedProjects = 0;
foreach ($projectStatus as $row) {
    if (($row['status'] ?? '') === 'completed') $completedProjects = (int)$row['total'];
    if (($row['status'] ?? '') === 'pending') $pendingProjects = (int)$row['total'];
    if (($row['status'] ?? '') === 'approved') $approvedProjects = (int)$row['total'];
}
$completionRate = $totalProjects ? round(($completedProjects / $totalProjects) * 100) : 0;
$maxMonthly = max(array_column($monthlyActivities ?: [['total' => 0]], 'total')) ?: 1;
$maxDepartment = max(array_column($departmentLoad ?: [['total' => 0]], 'total')) ?: 1;
$statusTotal = array_sum(array_column($projectStatus, 'total')) ?: 1;

$kpis = $_SESSION['role'] === 'faculty'
    ? [
        ['label' => 'My Programs', 'value' => $stats['my_programs'] ?? 0, 'meta' => 'Assigned scope', 'icon' => 'fa-layer-group', 'tone' => 'green'],
        ['label' => 'My Projects', 'value' => $stats['my_projects'] ?? 0, 'meta' => $completionRate . '% completed', 'icon' => 'fa-folder-open', 'tone' => 'blue'],
        ['label' => 'My Activities', 'value' => $stats['my_activities'] ?? 0, 'meta' => 'Current workload', 'icon' => 'fa-calendar-check', 'tone' => 'amber'],
        ['label' => 'Participants', 'value' => $participantTotal, 'meta' => 'Linked records', 'icon' => 'fa-user-check', 'tone' => 'rose'],
    ]
    : [
        ['label' => 'Programs', 'value' => $stats['programs'] ?? 0, 'meta' => 'Active portfolio', 'icon' => 'fa-layer-group', 'tone' => 'green'],
        ['label' => 'Projects', 'value' => $stats['projects'] ?? 0, 'meta' => $completionRate . '% completed', 'icon' => 'fa-folder-open', 'tone' => 'blue'],
        ['label' => 'Activities', 'value' => $stats['activities'] ?? 0, 'meta' => 'This year tracked', 'icon' => 'fa-calendar-check', 'tone' => 'amber'],
        ['label' => 'Participants', 'value' => $participantTotal, 'meta' => 'Beneficiary reach', 'icon' => 'fa-user-check', 'tone' => 'rose'],
    ];

$toneClasses = [
    'green' => ['icon' => 'background:#0F643A;color:#ECFDF5', 'bar' => '#0F643A'],
    'blue' => ['icon' => 'background:#1D4ED8;color:#DBEAFE', 'bar' => '#2563EB'],
    'amber' => ['icon' => 'background:#B45309;color:#FEF3C7', 'bar' => '#D97706'],
    'rose' => ['icon' => 'background:#BE123C;color:#FFE4E6', 'bar' => '#E11D48'],
];

function dashboardStatusLabel($status) {
    return ucfirst(str_replace('_', ' ', $status ?? 'Unknown'));
}
function dashboardBytes($bytes) {
    $bytes = (float)$bytes;
    foreach (['B','KB','MB','GB','TB'] as $unit) {
        if ($bytes < 1024 || $unit === 'TB') return rtrim(rtrim(number_format($bytes, 1), '0'), '.') . ' ' . $unit;
        $bytes /= 1024;
    }
}
$driveDiskTotal = (int)($driveStats['disk_total_bytes'] ?? 0);
$driveDiskFree = (int)($driveStats['disk_free_bytes'] ?? 0);
$driveDiskUsed = (int)($driveStats['disk_used_bytes'] ?? 0);
$driveDiskPercent = $driveDiskTotal ? min(100, round(($driveDiskUsed / $driveDiskTotal) * 100)) : 0;
$driveUploadLimit = dashboardBytes($driveStats['effective_upload_limit_bytes'] ?? 0);
?>

<style>
.dashboard-wrap{display:flex;flex-direction:column;gap:22px}
.dashboard-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px}
.dashboard-title{font-size:26px;font-weight:800;color:#F9FAFB;line-height:1.1}
.dashboard-subtitle{color:#9CA3AF;font-size:14px;margin-top:6px}
.kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.kpi-card,.dash-panel{background:#1F2937;border:1px solid #374151;border-radius:8px;box-shadow:0 8px 22px rgba(0,0,0,.18)}
.kpi-card{padding:16px;display:flex;align-items:center;justify-content:space-between;gap:14px}
.kpi-label{font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:700;letter-spacing:.04em}
.kpi-value{font-size:30px;line-height:1;font-weight:800;color:#F9FAFB;margin-top:8px}
.kpi-meta{font-size:12px;color:#9CA3AF;margin-top:8px}
.kpi-icon{width:46px;height:46px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;flex:none}
.dash-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(320px,1fr);gap:22px}
.dash-panel-head{padding:16px 18px;border-bottom:1px solid #374151;display:flex;align-items:center;justify-content:space-between;gap:12px}
.dash-panel-title{font-size:14px;font-weight:800;color:#F9FAFB}
.dash-panel-note{font-size:12px;color:#6B7280}
.dash-panel-body{padding:18px}
.chart-frame{height:306px}
.chart-svg{width:100%;height:100%;display:block}
.status-row{margin-bottom:16px}
.status-row:last-child{margin-bottom:0}
.status-top{display:flex;justify-content:space-between;align-items:center;color:#D1D5DB;font-size:13px;margin-bottom:7px}
.status-track{height:9px;background:#374151;border-radius:999px;overflow:hidden}
.status-fill{height:100%;border-radius:999px;background:#0F643A}
.insight-strip{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.insight-box{background:#111827;border:1px solid #374151;border-radius:8px;padding:14px}
.insight-label{font-size:11px;text-transform:uppercase;color:#9CA3AF;font-weight:700;letter-spacing:.04em}
.insight-value{font-size:20px;font-weight:800;color:#F9FAFB;margin-top:7px}
.split-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}
.compact-list{display:flex;flex-direction:column;gap:10px}
.compact-item{background:#111827;border:1px solid #374151;border-radius:8px;padding:12px}
.progress-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
.progress-name{font-size:13px;color:#F9FAFB;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.progress-percent{font-size:12px;color:#9CA3AF}
.program-table{width:100%;border-collapse:collapse}
.program-table th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#9CA3AF;text-align:left;padding:0 0 10px}
.program-table td{border-top:1px solid #374151;color:#D1D5DB;font-size:13px;padding:12px 0}
.empty-chart{height:306px;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:14px}
.drive-panel{display:grid;grid-template-columns:minmax(0,1.35fr) repeat(3,minmax(150px,1fr));gap:14px}
.drive-storage{background:#111827;border:1px solid #374151;border-radius:8px;padding:16px}
.drive-storage-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px}
.drive-storage-title{font-size:14px;font-weight:800;color:#F9FAFB}
.drive-storage-note{font-size:12px;color:#9CA3AF;margin-top:4px}
.drive-capacity{font-size:12px;color:#D1D5DB;white-space:nowrap}
.drive-track{height:10px;background:#374151;border-radius:999px;overflow:hidden}
.drive-fill{height:100%;background:#0F643A;border-radius:999px}
.drive-card{background:#111827;border:1px solid #374151;border-radius:8px;padding:14px;display:flex;justify-content:space-between;gap:12px;align-items:center}
.drive-card i{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#14532D;color:#BBF7D0}
.notification-list{display:flex;flex-direction:column;gap:10px}
.notification-item{display:flex;gap:12px;align-items:flex-start;background:#111827;border:1px solid #374151;border-radius:8px;padding:12px;text-decoration:none}
.notification-item i{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#1D4ED8;color:#DBEAFE;flex:none}
.notification-title{font-size:13px;font-weight:800;color:#F9FAFB}
.notification-message{font-size:12px;color:#D1D5DB;margin-top:3px}
.notification-time{font-size:11px;color:#6B7280;margin-top:5px}
@media (max-width:1200px){.kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dash-grid,.split-grid,.drive-panel{grid-template-columns:1fr}}
@media (max-width:640px){.dashboard-head{align-items:flex-start;flex-direction:column}.kpi-grid,.insight-strip{grid-template-columns:1fr}.chart-frame{height:250px}.drive-storage-top{flex-direction:column}.drive-capacity{white-space:normal}}
</style>

<div class="dashboard-wrap">
    <div class="dashboard-head">
        <div>
            <h1 class="dashboard-title">Dashboard</h1>
            <p class="dashboard-subtitle">Welcome back, <?= e(currentUser()['name'] ?: currentUser()['username']) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="index.php?module=projects" class="btn-ghost"><i class="fas fa-diagram-project mr-1"></i> Project Management</a>
            <a href="index.php?module=reports" class="btn-primary"><i class="fas fa-clipboard-check mr-1"></i> Accomplishments</a>
        </div>
    </div>

    <section class="flex flex-wrap gap-6 border-b border-[#374151] pb-5" aria-label="Project review overview">
        <a class="text-sm text-[#D1D5DB]" href="index.php?module=projects&amp;status=pending">Pending Projects <strong class="text-[#FCD34D] ml-2"><?= $pendingProjects ?></strong></a>
        <a class="text-sm text-[#D1D5DB]" href="index.php?module=projects&amp;status=approved">Approved Projects <strong class="text-[#86EFAC] ml-2"><?= $approvedProjects ?></strong></a>
        <?php if (Permissions::canApproveProposal()): ?><a class="text-sm text-[#86EFAC]" href="index.php?module=proposals&amp;status=submitted"><i class="fas fa-clipboard-check mr-1" aria-hidden="true"></i> Review Proposals</a><?php endif; ?>
    </section>

    <div class="kpi-grid">
        <?php foreach ($kpis as $card): $tone = $toneClasses[$card['tone']]; ?>
            <div class="kpi-card">
                <div>
                    <p class="kpi-label"><?= e($card['label']) ?></p>
                    <p class="kpi-value"><?= e(number_format((float)$card['value'])) ?></p>
                    <p class="kpi-meta"><?= e($card['meta']) ?></p>
                </div>
                <div class="kpi-icon" style="<?= $tone['icon'] ?>"><i class="fas <?= e($card['icon']) ?>"></i></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="insight-strip">
        <div class="insight-box">
            <p class="insight-label">Total Project Budget</p>
            <p class="insight-value"><?= formatCurrency($budgetSummary['total_budget'] ?? 0) ?></p>
        </div>
        <div class="insight-box">
            <p class="insight-label">Average Completion</p>
            <p class="insight-value"><?= round((float)($budgetSummary['average_completion'] ?? 0)) ?>%</p>
        </div>
        <div class="insight-box">
            <p class="insight-label">Pending Proposals</p>
            <p class="insight-value"><?= number_format((float)($stats['pending_proposals'] ?? $stats['my_pending_proposals'] ?? 0)) ?></p>
        </div>
    </div>

    <?php if (!empty($notifications)): ?>
    <section class="dash-panel">
        <div class="dash-panel-head">
            <h2 class="dash-panel-title">Notifications</h2>
            <a class="dash-panel-note" href="index.php?module=notifications">View all</a>
        </div>
        <div class="dash-panel-body notification-list">
            <?php foreach ($notifications as $notification): ?>
                <a class="notification-item" href="<?= e(!empty($notification['action_url']) ? SITE_URL . $notification['action_url'] : SITE_URL . '/index.php?module=notifications') ?>">
                    <i class="fas fa-bell"></i>
                    <div>
                        <p class="notification-title"><?= e($notification['title']) ?></p>
                        <p class="notification-message"><?= e($notification['message']) ?></p>
                        <p class="notification-time"><?= e(timeAgo($notification['created_at'])) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="dash-panel">
        <div class="dash-panel-head">
            <h2 class="dash-panel-title">Project File Storage</h2>
            <span class="dash-panel-note">Files and hosting capacity</span>
        </div>
        <div class="dash-panel-body drive-panel">
            <div class="drive-storage">
                <div class="drive-storage-top">
                    <div>
                        <p class="drive-storage-title">Storage Capacity</p>
                        <p class="drive-storage-note">Dynamic from the current hosting disk</p>
                    </div>
                    <p class="drive-capacity"><?= dashboardBytes($driveDiskFree) ?> free of <?= dashboardBytes($driveDiskTotal) ?></p>
                </div>
                <div class="drive-track"><div class="drive-fill" style="width:<?= $driveDiskPercent ?>%"></div></div>
                <p class="drive-storage-note mt-3">Project file archive uses <?= dashboardBytes($driveStats['used_bytes'] ?? 0) ?>. Upload limit is <?= e($driveUploadLimit) ?> per file.</p>
            </div>
            <div class="drive-card">
                <div>
                    <p class="insight-label">Uploaded Files</p>
                    <p class="insight-value"><?= number_format((float)($driveStats['files'] ?? 0)) ?></p>
                </div>
                <i class="fas fa-file-arrow-up"></i>
            </div>
            <div class="drive-card">
                <div>
                    <p class="insight-label">Archived Folders</p>
                    <p class="insight-value"><?= number_format((float)($driveStats['folders'] ?? 0)) ?></p>
                </div>
                <i class="fas fa-folder-tree"></i>
            </div>
            <div class="drive-card">
                <div>
                    <p class="insight-label">Per File Limit</p>
                    <p class="insight-value"><?= e($driveUploadLimit) ?></p>
                </div>
                <i class="fas fa-cloud-arrow-up"></i>
            </div>
        </div>
    </section>

    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h2 class="dash-panel-title">Activities by Month</h2>
                <span class="dash-panel-note"><?= date('Y') ?> activity schedule</span>
            </div>
            <div class="dash-panel-body">
                <?php if ($chartedActivities < 1): ?>
                    <div class="empty-chart">No activity records yet</div>
                <?php else: ?>
                    <div class="chart-frame">
                        <svg class="chart-svg" viewBox="0 0 860 300" role="img" aria-label="Monthly activities chart">
                            <line x1="44" y1="238" x2="836" y2="238" stroke="#4B5563" stroke-width="1"/>
                            <line x1="44" y1="184" x2="836" y2="184" stroke="#273244" stroke-width="1"/>
                            <line x1="44" y1="130" x2="836" y2="130" stroke="#273244" stroke-width="1"/>
                            <line x1="44" y1="76" x2="836" y2="76" stroke="#273244" stroke-width="1"/>
                            <?php
                            $points = [];
                            foreach ($monthlyActivities as $i => $month):
                                $value = (int)$month['total'];
                                $x = 58 + ($i * 67);
                                $barHeight = $value > 0 ? max(14, round(($value / $maxMonthly) * 160)) : 3;
                                $y = 238 - $barHeight;
                                $points[] = ($x + 16) . ',' . ($value > 0 ? $y : 238);
                            ?>
                                <rect x="<?= $x ?>" y="<?= $y ?>" width="32" height="<?= $barHeight ?>" rx="5" fill="#0F643A"/>
                                <?php if ($value > 0): ?><text x="<?= $x + 16 ?>" y="<?= $y - 10 ?>" text-anchor="middle" fill="#F9FAFB" font-size="13" font-weight="700"><?= $value ?></text><?php endif; ?>
                                <text x="<?= $x + 16 ?>" y="270" text-anchor="middle" fill="#9CA3AF" font-size="12"><?= e($month['month_label']) ?></text>
                            <?php endforeach; ?>
                            <polyline points="<?= e(implode(' ', $points)) ?>" fill="none" stroke="#F5D20C" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h2 class="dash-panel-title">Project Status</h2>
                <span class="dash-panel-note"><?= (int)$totalProjects ?> total</span>
            </div>
            <div class="dash-panel-body">
                <?php foreach ($projectStatus as $row): $pct = round(((int)$row['total'] / $statusTotal) * 100); ?>
                    <div class="status-row">
                        <div class="status-top">
                            <span><?= e(dashboardStatusLabel($row['status'])) ?></span>
                            <strong><?= (int)$row['total'] ?></strong>
                        </div>
                        <div class="status-track"><div class="status-fill" style="width:<?= $pct ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($projectStatus)): ?><p class="text-sm text-[#6B7280] text-center py-10">No project data</p><?php endif; ?>
            </div>
        </section>
    </div>

    <div class="split-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h2 class="dash-panel-title">Top Project Progress</h2>
                <span class="dash-panel-note">Highest completion</span>
            </div>
            <div class="dash-panel-body compact-list">
                <?php foreach ($topProjects as $project): ?>
                    <div class="compact-item">
                        <div class="progress-title">
                            <span class="progress-name"><?= e($project['title']) ?></span>
                            <?= getStatusBadge($project['status']) ?>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="progress-bar flex-1"><div class="progress-fill" style="width:<?= (int)$project['completion_percentage'] ?>%"></div></div>
                            <span class="progress-percent"><?= (int)$project['completion_percentage'] ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($topProjects)): ?><p class="text-sm text-[#6B7280] text-center py-8">No projects yet</p><?php endif; ?>
            </div>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h2 class="dash-panel-title">Department Workload</h2>
                <span class="dash-panel-note">Faculty distribution</span>
            </div>
            <div class="dash-panel-body">
                <?php foreach ($departmentLoad as $row): $pct = round(((int)$row['total'] / $maxDepartment) * 100); ?>
                    <div class="status-row">
                        <div class="status-top">
                            <span class="truncate"><?= e($row['department']) ?></span>
                            <strong><?= (int)$row['total'] ?></strong>
                        </div>
                        <div class="status-track"><div class="status-fill" style="width:<?= $pct ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($departmentLoad)): ?><p class="text-sm text-[#6B7280] text-center py-8">No department data</p><?php endif; ?>
            </div>
        </section>
    </div>

    <section class="dash-panel">
        <div class="dash-panel-head">
            <h2 class="dash-panel-title">Program Performance</h2>
            <span class="dash-panel-note">Projects and activities by program</span>
        </div>
        <div class="dash-panel-body overflow-x-auto">
            <table class="program-table">
                <thead>
                    <tr><th>Program</th><th>Projects</th><th>Activities</th><th>Avg. Completion</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($programPerformance as $program): ?>
                        <tr>
                            <td class="font-semibold text-[#F9FAFB]"><?= e($program['title']) ?></td>
                            <td><?= (int)$program['projects'] ?></td>
                            <td><?= (int)$program['activities'] ?></td>
                            <td><?= round((float)$program['completion']) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($programPerformance)): ?><tr><td colspan="4" class="text-center text-[#6B7280]">No program data</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
