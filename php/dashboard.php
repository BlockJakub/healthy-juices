<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
// Use the hardened session helper (ensures cookie flags + csrf token)
secure_session_start();
if (empty($_SESSION['user_id'])) {
    // relative redirect for subfolder compatibility
    header('Location: login.html');
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — HealthyJuices</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/login_system_style.css"> <!-- reuse file per request -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body class="dashboard-page">
    <header>
        <nav class="site-navbar">
            <div class="nav-wrapper container">
                <a href="../index.html" class="brand-logo">HealthyJuices</a>
                <a href="#" data-target="mobile-menu" class="sidenav-trigger"><i class="material-icons">menu</i></a>
                <ul id="nav-mobile" class="right hide-on-med-and-down" aria-label="Main navigation">
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="../resources.html">Resources</a></li>
                    <li><a href="../about.html">About</a></li>
                    <li><a href="../contact.html">Contact</a></li>
                    <li class="active"><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="#" id="dashboardLogoutNav">Logout</a></li>
                </ul>
            </div>
        </nav>
        <ul class="sidenav" id="mobile-menu">
            <li><a href="../index.html">Home</a></li>
            <li><a href="../resources.html">Resources</a></li>
            <li><a href="../about.html">About</a></li>
            <li><a href="../contact.html">Contact</a></li>
            <li class="active"><a href="dashboard.php">Dashboard</a></li>
            <li><a href="#" id="dashboardLogoutMobile">Logout</a></li>
        </ul>
    </header>

    <main class="dashboard container">
        <div class="section">
            <h4 class="dash-title"><i class="material-icons left">insights</i>Your Health Dashboard</h4>
            <p class="dash-subtle">Recent tracked entries and quick stats. (Data shown is from your saved entries.)</p>
        </div>
        <div class="row" id="summaryRow" aria-live="polite">
            <!-- summary metric cards injected here -->
        </div>
        <!-- Goals & Progress -->
        <div class="row" id="goalsRow">
            <div class="col s12">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">flag</i>Goals & Progress</span>
                        <form id="goalsForm" class="goals-form">
                            <div class="row">
                                <div class="input-field col s6 m3">
                                    <input id="goalWater" name="water" type="number" step="0.1" min="0" />
                                    <label for="goalWater">Water (L)</label>
                                </div>
                                <div class="input-field col s6 m3">
                                    <input id="goalSleep" name="sleep" type="number" step="0.1" min="0" />
                                    <label for="goalSleep">Sleep (h)</label>
                                </div>
                                <div class="input-field col s6 m3">
                                    <input id="goalSteps" name="steps" type="number" step="100" min="0" />
                                    <label for="goalSteps">Steps</label>
                                </div>
                                <div class="input-field col s6 m3">
                                    <input id="goalHealthScore" name="healthScore" type="number" step="1" min="0" max="100" />
                                    <label for="goalHealthScore">Health Score</label>
                                </div>
                            </div>
                            <button class="btn small green" id="saveGoalsBtn" type="submit"><i class="material-icons left">save</i>Save Goals</button>
                            <span id="goalsMsg" class="goal-msg"></span>
                        </form>
                        <div id="progressBars" class="progress-bars"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row dash-charts">
            <div class="col s12 m6">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">query_stats</i>Health Score Trend</span>
                        <canvas id="healthScoreChart" height="160" aria-label="Health Score Trend" role="img"></canvas>
                    </div>
                </div>
            </div>
            <div class="col s12 m6">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">monitor_heart</i>Risk Trend</span>
                        <canvas id="riskTrendChart" height="160" aria-label="Risk Trend" role="img"></canvas>
                    </div>
                </div>
            </div>
            <div class="col s12">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">water_drop</i>Key Metrics Over Time</span>
                        <canvas id="metricsChart" height="140" aria-label="Water Sleep Steps" role="img"></canvas>
                        <div class="divider" style="margin:1.2rem 0"></div>
                        <div class="row" style="margin-bottom:0">
                            <div class="col s12 m6">
                                <span class="card-title" style="font-size:1.0rem"><i class="material-icons left">pie_chart</i>Lifestyle Breakdown</span>
                                <canvas id="lifestyleChart" height="140" aria-label="Lifestyle Breakdown" role="img"></canvas>
                            </div>
                            <div class="col s12 m6">
                                <span class="card-title" style="font-size:1.0rem"><i class="material-icons left">monitor_weight</i>BMI</span>
                                <form id="bmiForm" class="bmi-form" novalidate>
                                    <div class="row" style="margin-bottom:.2rem;">
                                        <div class="input-field col s6">
                                            <input id="bmiWeight" name="weight" type="number" inputmode="decimal" min="20" max="500" step="0.1" autocomplete="off" />
                                            <label for="bmiWeight">Weight (kg)</label>
                                        </div>
                                        <div class="input-field col s6">
                                            <input id="bmiHeight" name="height" type="number" inputmode="decimal" min="50" max="250" step="0.1" autocomplete="off" />
                                            <label for="bmiHeight">Height (cm)</label>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-bottom:.3rem;">
                                        <div class="input-field col s12">
                                            <input id="bmiDate" name="date" type="date" max="<?php echo date('Y-m-d'); ?>" />
                                            <label for="bmiDate" class="active">Date (optional)</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn small green" id="bmiCalcBtn"><i class="material-icons left">calculate</i>Save BMI</button>
                                    <button type="button" class="btn-flat" id="bmiResetBtn">Reset</button>
                                    <span id="bmiMsg" class="bmi-msg" aria-live="polite"></span>
                                </form>
                                <div class="bmi-result" id="bmiResult" aria-live="polite"></div>
                                <canvas id="bmiChart" height="120" aria-label="BMI Trend" role="img" style="margin-top:10px"></canvas>
                                <div class="bmi-scale" aria-hidden="true">
                                    <span class="bmi-band under">Under &lt;18.5</span>
                                    <span class="bmi-band normal">Normal 18.5–24.9</span>
                                    <span class="bmi-band over">Over 25–29.9</span>
                                    <span class="bmi-band obese">Obesity ≥30</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col s12">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">timeline</i>Goals vs Actual (History)</span>
                        <p class="dash-subtle" style="margin-top:0.2rem">Tracks each metric against its goal across your logged dates (steps use right axis).</p>
                        <canvas id="goalsComparisonChart" height="170" aria-label="Goals vs Actual" role="img"></canvas>
                    </div>
                </div>
            </div>
            <div class="col s12">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">trending_down</i>Deficits vs Targets</span>
                        <p class="dash-subtle" style="margin-top:0.2rem">Negative bars show how much you were below target (baseline or goal). Positive means you met/exceeded.</p>
                        <canvas id="deficitsChart" height="170" aria-label="Deficits vs Targets" role="img"></canvas>
                    </div>
                </div>
            </div>
            <div class="col s12">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">assistant</i>Personalized Advice</span>
                        <ul id="adviceList" class="dash-advice"></ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col s12">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">list</i>Recent Entries (JSON)</span>
                        <div class="export-actions">
                            <button class="btn-flat export-btn" id="exportJsonBtn"><i class="material-icons left">download</i>JSON</button>
                            <button class="btn-flat export-btn" id="exportCsvBtn"><i class="material-icons left">table_chart</i>CSV</button>
                            <span id="exportMsg" class="export-msg"></span>
                        </div>
                        <pre id="entriesPre" class="entries-pre" aria-label="Raw entries JSON"></pre>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col s12 m6">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">analytics</i>Progress</span>
                        <p class="dash-subtle" style="margin-top:0.2rem">Live progress toward key habit milestones. Bars adapt color: red (low) → yellow (building) → green (met) → blue (optimal).</p>
                        <div class="progress-controls">
                            <label><input type="checkbox" id="toggleHideCompleted" style="position:relative; top:2px" /> Hide Completed</label>
                            <span id="progressCount" style="font-size:.65rem; color:#546e64"></span>
                        </div>
                        <ul id="progressList" class="badges-list"></ul>
                        <div id="progressLegend" aria-live="polite"></div>
                        <div id="badgeCelebration" class="badge-celebration" aria-live="polite" hidden></div>
                    </div>
                </div>
            </div>
            <div class="col s12 m6">
                <div class="card dash-card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">psychology</i>Wellness Breakdown</span>
                        <ul id="breakdownList" class="breakdown-list"></ul>
                        <div class="did-you-know" id="factBox"></div>
                        <div class="divider" style="margin:1rem 0"></div>
                        <span class="card-title" style="font-size:1rem"><i class="material-icons left">history</i>Entry History</span>
                        <div class="entry-history-head" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin:.25rem 0 .35rem;">
                            <div style="display:flex;align-items:center;gap:.5rem;font-size:.65rem;">
                                <label for="entryPageSize" style="text-transform:uppercase;letter-spacing:.5px;color:#2e7d32">Show
                                    <select id="entryPageSize" style="font-size:.7rem;padding:.15rem .25rem;border:1px solid #c8e6c9;border-radius:4px;background:#fff;">
                                        <option value="11" selected>11</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </label>
                            </div>
                            <div id="entryPager" class="entry-pager" style="display:flex;align-items:center;gap:.35rem;">
                                <button id="entryPrev" class="btn-flat" style="padding:0 .4rem;font-size:.65rem;" aria-label="Previous entries" disabled>&lt;</button>
                                <span id="entryPageInfo" style="font-size:.6rem;color:#546e64">1/1</span>
                                <button id="entryNext" class="btn-flat" style="padding:0 .4rem;font-size:.65rem;" aria-label="Next entries" disabled>&gt;</button>
                            </div>
                        </div>
                        <ul id="entryHistory" class="entry-history-list" aria-label="Recent entry history with health score categories"></ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- BMI card removed; now integrated above -->
        <div class="center">
            <a class="btn green darken-1" href="../index.html#health-form">Add New Entry</a>
        </div>
    </main>

    <footer class="page-footer">
        <div class="container">
            <div class="row">
                <div class="col s12 m6">
                    <h5 class="white-text">HealthyJuices</h5>
                    <p class="grey-text text-lighten-4">Practical guides and friendly nutrition information.</p>
                </div>
                <div class="col s12 m4 offset-m2">
                    <h5 class="white-text">Links</h5>
                    <ul>
                        <li><a class="grey-text text-lighten-3" href="../pages/privacy.html">Privacy</a></li>
                        <li><a class="grey-text text-lighten-3" href="../pages/terms.html">Terms</a></li>
                        <li><a class="grey-text text-lighten-3" href="../pages/about.html">About</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-copyright">
            <div class="container">© 2025 HealthyJuices. All rights reserved.</div>
        </div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const elems = document.querySelectorAll('.sidenav');
            if (window.M && elems.length) M.Sidenav.init(elems);
        });
        // Fetch CSRF token for this page (dashboard doesn't include index's nav script)
        async function ensureCsrf() {
            if (window.appState && window.appState.csrfToken) return window.appState.csrfToken;
            try {
                const r = await fetch('csrf.php', {
                    credentials: 'include'
                });
                if (r.ok) {
                    const j = await r.json();
                    window.appState = window.appState || {};
                    window.appState.csrfToken = j.csrf_token || '';
                    return window.appState.csrfToken;
                }
            } catch (e) {
                /* ignore */
            }
            return '';
        }

        function buildSummary(rows) {
            // Basic aggregate metrics over last 7 entries (or fewer)
            const last = rows.slice(0, 7);
            const water = avg(last.map(r => r.payload?.water || r.payload?.waterLiters || r.payload?.waterMl || 0));
            const sleep = avg(last.map(r => r.payload?.sleep || r.payload?.sleepHours || 0));
            const steps = avg(last.map(r => r.payload?.steps || 0));
            const healthScore = avg(last.map(r => r.payload?.healthScore || 0));
            // Extended lifestyle metrics
            const nutritionKcal = avg(last.map(r => r.payload?.nutritionKcal || 0));
            const trainingMinutes = avg(last.map(r => r.payload?.trainingMinutes || 0));
            const breathingMinutes = avg(last.map(r => r.payload?.breathingMinutes || 0));
            const coldMinutes = avg(last.map(r => r.payload?.coldMinutes || 0));
            const sleepQuality = avg(last.map(r => r.payload?.sleepQuality || 0));
            // Juice frequency (mode of last entries)
            const freqCounts = {};
            last.forEach(r => {
                const f = (r.payload?.juiceFrequency || '').toLowerCase();
                if (!f) return;
                freqCounts[f] = (freqCounts[f] || 0) + 1;
            });
            const juiceFrequency = Object.entries(freqCounts).sort((a, b) => b[1] - a[1])[0]?.[0] || 'none';
            const container = document.getElementById('summaryRow');
            container.innerHTML = '';
            const hsCat = classifyHealthScore(healthScore);
            // Threshold logic
            function classifyMetric(key, val) {
                if (val === 0 || isNaN(val)) return 'danger';
                switch (key) {
                    case 'water':
                        if (val < 1.2) return 'danger';
                        if (val < 1.8) return 'warn';
                        if (val < 2.6) return 'good';
                        return 'excellent';
                    case 'sleep':
                        if (val < 5.5) return 'danger';
                        if (val < 6.8) return 'warn';
                        if (val <= 8.5) return 'good';
                        return 'excellent'; // >8.5 still okay
                    case 'steps':
                        if (val < 3000) return 'danger';
                        if (val < 6500) return 'warn';
                        if (val < 12000) return 'good';
                        return 'excellent';
                    case 'health':
                        if (healthScore <= 40) return 'danger';
                        if (healthScore <= 60) return 'warn';
                        if (healthScore <= 80) return 'good';
                        return 'excellent';
                    case 'nutrition':
                        // Broad, simplified daily energy adequacy window
                        if (val < 1200) return 'danger';
                        if (val < 1800) return 'warn';
                        if (val <= 3200) return 'good';
                        if (val <= 3800) return 'excellent';
                        return 'warn'; // very high
                    case 'training':
                        if (val === 0) return 'danger';
                        if (val < 30) return 'warn';
                        if (val < 150) return 'good';
                        return 'excellent';
                    case 'breathing':
                        if (val === 0) return 'danger';
                        if (val < 3) return 'warn';
                        if (val < 10) return 'good';
                        return 'excellent';
                    case 'cold':
                        if (val === 0) return 'danger';
                        if (val < 1) return 'warn';
                        if (val <= 3) return 'good';
                        if (val <= 6) return 'excellent';
                        return 'warn'; // caution on excessive exposure
                    case 'sleepq':
                        if (val <= 40) return 'danger';
                        if (val <= 60) return 'warn';
                        if (val <= 80) return 'good';
                        return 'excellent';
                    case 'juice':
                        // Frequency: none/occasional/weekly/daily/multiple
                        if (val === 0) return 'danger'; // none – encourage fruit/veg intake (or neutral?)
                        if (val === 1) return 'good'; // occasional / weekly
                        if (val === 2) return 'good';
                        if (val >= 3) return 'warn'; // very frequent — sugar load
                        return 'good';
                }
                return 'good';
            }
            const metrics = [{
                    key: 'water',
                    icon: 'opacity',
                    label: 'Avg Water (L)',
                    value: water ? water.toFixed(2) : '—',
                    raw: water
                },
                {
                    key: 'sleep',
                    icon: 'bedtime',
                    label: 'Avg Sleep (h)',
                    value: sleep ? sleep.toFixed(1) : '—',
                    raw: sleep
                },
                {
                    key: 'steps',
                    icon: 'directions_walk',
                    label: 'Avg Steps',
                    value: steps ? Math.round(steps) : '—',
                    raw: steps
                },
                {
                    key: 'health',
                    icon: 'favorite',
                    label: 'Avg Health Score',
                    value: healthScore ? `${healthScore.toFixed(1)} <span class="score-badge ${hsCat.className}">${hsCat.label}</span>` : '—',
                    raw: healthScore
                },
                {
                    key: 'nutrition',
                    icon: 'local_hospital',
                    label: 'Avg Energy (kcal)',
                    value: nutritionKcal ? Math.round(nutritionKcal) : '—',
                    raw: nutritionKcal,
                    benefit: nutritionKcal ? 'Supports recovery & metabolic balance' : 'Log meals to assess intake'
                },
                {
                    key: 'training',
                    icon: 'fitness_center',
                    label: 'Training (min)',
                    value: trainingMinutes ? Math.round(trainingMinutes) : '—',
                    raw: trainingMinutes,
                    benefit: trainingMinutes ? 'Activity drives cardiovascular & strength gains' : 'Add sessions for fitness adaptation'
                },
                {
                    key: 'breathing',
                    icon: 'air',
                    label: 'Breathing (min)',
                    value: breathingMinutes ? breathingMinutes.toFixed(1) : '—',
                    raw: breathingMinutes,
                    benefit: breathingMinutes ? 'Down‑regulates stress response' : 'Try 5 min guided breathing'
                },
                {
                    key: 'cold',
                    icon: 'ac_unit',
                    label: 'Cold (min)',
                    value: coldMinutes ? coldMinutes.toFixed(1) : '—',
                    raw: coldMinutes,
                    benefit: coldMinutes ? 'Short exposure may aid alertness' : 'Log any cold exposure'
                },
                {
                    key: 'juice',
                    icon: 'local_drink',
                    label: 'Juice Frequency',
                    value: (() => {
                        if (!juiceFrequency || juiceFrequency === 'none') return 'None';
                        return juiceFrequency.charAt(0).toUpperCase() + juiceFrequency.slice(1);
                    })(),
                    raw: (() => { // map to numeric for classify
                        switch (juiceFrequency) {
                            case 'none':
                                return 0;
                            case 'occasional':
                                return 1;
                            case 'weekly':
                                return 1;
                            case 'daily':
                                return 2;
                            default:
                                return 3; // multiple/day
                        }
                    })(),
                    benefit: juiceFrequency === 'daily' ? 'Moderate intake; favor whole fruit too' : (juiceFrequency === 'none' ? 'Consider veg/fruit micronutrients' : 'Balanced juice use')
                },
                {
                    key: 'sleepq',
                    icon: 'hotel',
                    label: 'Sleep Quality',
                    value: sleepQuality ? sleepQuality.toFixed(1) : '—',
                    raw: sleepQuality,
                    benefit: sleepQuality ? 'Quality sleep boosts recovery' : 'Rate your sleep quality'
                }
            ];
            metrics.forEach(m => {
                const col = document.createElement('div');
                // On large screens show more columns (l2) once we exceed 4 cards
                col.className = 'col s12 m6 l3 xl2';
                const state = classifyMetric(m.key, m.raw);
                const valueText = (m.value + '').replace(/<[^>]+>/g, '');
                const aria = `${m.label} ${valueText} status ${state}`;
                const benefitHtml = m.benefit ? `<div class="metric-benefit">${m.benefit}</div>` : '';
                col.innerHTML = `\n<div class="card metric-card state-${state}" aria-label="${aria}">\n  <span class="status-indicator" aria-hidden="true"></span>\n  <div class="card-content">\n    <span class="metric-icon"><i class="material-icons">${m.icon}</i></span>\n    <div class="metric-value">${m.value}</div>\n    <div class="metric-label">${m.label}</div>\n    ${benefitHtml}\n  </div>\n</div>`;
                container.appendChild(col);
            });
        }

        function classifyHealthScore(score) {
            if (!score && score !== 0) return {
                label: '',
                className: ''
            };
            if (score <= 20) return {
                label: 'Critical',
                className: 'critical'
            };
            if (score <= 40) return {
                label: 'Poor',
                className: 'poor'
            };
            if (score <= 60) return {
                label: 'Fair',
                className: 'fair'
            };
            if (score <= 80) return {
                label: 'Good',
                className: 'goodcat'
            };
            return {
                label: 'Excellent',
                className: 'excellent'
            };
        }

        function avg(arr) {
            const nums = arr.map(Number).filter(v => !isNaN(v) && isFinite(v));
            if (!nums.length) return 0;
            return nums.reduce((a, b) => a + b, 0) / nums.length;
        }

        function buildAdvice(rows) {
            const list = document.getElementById('adviceList');
            list.innerHTML = '';
            if (!rows.length) {
                list.innerHTML = '<li>No data yet. Add an entry to see advice.</li>';
                return;
            }
            // consider last 14 days or all if fewer
            const recent = rows.slice(0, 14);
            const avg = k => {
                const vals = recent.map(r => r.payload?.[k]).map(Number).filter(n => !isNaN(n));
                if (!vals.length) return 0;
                return vals.reduce((a, b) => a + b, 0) / vals.length;
            };
            const waterAvg = avg('water');
            const sleepAvg = avg('sleep');
            const stepsAvg = avg('steps');
            const hsAvg = avg('healthScore');
            const riskAvg = avg('risk');
            const alcoholAvg = avg('alcoholUnits');
            const cigsAvg = avg('cigarettes');
            const latest = rows[0]?.payload || {};
            const BASE = { // baseline targets
                water: 2.0,
                sleep: 7.0,
                steps: 1000,
                meals: 3,
                healthScore: 60
            };
            const items = []; // {text, level}

            // High alerts first
            if ((latest.cigarettes || 0) >= 10 || cigsAvg >= 8) {
                items.push({
                    level: 'high',
                    text: 'High smoking exposure — strong medical consensus supports quitting to reduce cardiovascular, respiratory and cancer risks. Seek cessation support.'
                });
            } else if ((latest.cigarettes || 0) > 0) {
                items.push({
                    level: 'warn',
                    text: 'Any smoking increases health risks. Cutting down and quitting improves lung and heart health.'
                });
            }
            if ((latest.alcoholUnits || 0) >= 6 || alcoholAvg >= 5) {
                items.push({
                    level: 'high',
                    text: 'Alcohol intake is high — guidelines advise moderating to reduce liver, cardiovascular, and sleep disruption risks.'
                });
            } else if ((latest.alcoholUnits || 0) > 3 || alcoholAvg > 3) {
                items.push({
                    level: 'warn',
                    text: 'Alcohol above moderate levels can impair recovery and sleep — consider reducing intake.'
                });
            }
            if (waterAvg < 1.0) items.push({
                level: 'high',
                text: 'Severely low hydration — increase water intake promptly; dehydration affects cognition and circulation.'
            });
            if (sleepAvg < 5.5) items.push({
                level: 'high',
                text: 'Very short sleep duration — chronic sleep deficit impacts immune, metabolic and mental health; prioritize rest.'
            });

            // Standard improvement advice
            const adv = [];
            if (waterAvg < BASE.water) adv.push(`Water deficit: ${(waterAvg - BASE.water).toFixed(2)}L vs target ≥ ${BASE.water}L.`);
            if (sleepAvg < BASE.sleep) adv.push(`Sleep deficit: ${(sleepAvg - BASE.sleep).toFixed(2)}h vs target ≥ ${BASE.sleep}h.`);
            if (stepsAvg < BASE.steps) adv.push(`Low activity: ${(stepsAvg - BASE.steps)} steps vs baseline ≥ ${BASE.steps}. Incorporate brief walks.`);
            const hsCat = classifyHealthScore(hsAvg);
            if (hsCat.label === 'Critical' || hsCat.label === 'Poor') {
                items.push({
                    level: 'high',
                    text: `Health score in ${hsCat.label} range — prioritize fundamentals (hydration, sleep, movement, nutrition) and reduce risk factors.`
                });
            } else if (hsCat.label === 'Fair') {
                adv.push('Health score is Fair — consistent improvements in hydration, sleep quality, and daily movement can elevate you to Good.');
            }
            if (hsAvg < BASE.healthScore && hsCat.label !== 'Critical' && hsCat.label !== 'Poor') {
                adv.push('Health score below baseline — reinforce hydration, balanced meals, movement, and sleep.');
            }
            if (riskAvg > 12) adv.push('Elevated risk trend — address smoking/alcohol and low movement factors.');
            if (!adv.length && items.length === 0) adv.push('Great consistency — maintain habits and consider progressive fitness goals.');

            // Merge standard advice at normal level
            adv.forEach(a => items.push({
                level: 'info',
                text: a
            }));

            // Render with classes
            items.forEach(obj => {
                const li = document.createElement('li');
                li.textContent = obj.text;
                li.className = 'advice-' + obj.level;
                list.appendChild(li);
            });
        }

        function buildCharts(rows) {
            if (typeof Chart === 'undefined' || !rows.length) return;
            const labels = rows.slice().reverse().map(r => r.entry_date_formatted ? r.entry_date_formatted.split(' ')[0] : (r.entry_date || r.payload?.date || '?'));
            const hs = rows.slice().reverse().map(r => r.payload?.healthScore || null);
            const risk = rows.slice().reverse().map(r => r.payload?.risk || null);
            const water = rows.slice().reverse().map(r => r.payload?.water || null);
            const sleep = rows.slice().reverse().map(r => r.payload?.sleep || null);
            const steps = rows.slice().reverse().map(r => r.payload?.steps || null);
            const cigarettes = rows.slice().reverse().map(r => r.payload?.cigarettes || 0);
            const alcohol = rows.slice().reverse().map(r => r.payload?.alcoholUnits || 0);
            const meals = rows.slice().reverse().map(r => r.payload?.meals || 0);

            const makeLine = (id, data, color, label) => {
                const el = document.getElementById(id);
                if (!el) return;
                if (el._chart) el._chart.destroy();
                el._chart = new Chart(el.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label,
                            data,
                            borderColor: color,
                            tension: .25,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            };
            makeLine('healthScoreChart', hs, '#2e7d32', 'Health Score');
            makeLine('riskTrendChart', risk, '#c62828', 'Risk');
            const metricsCanvas = document.getElementById('metricsChart');
            if (metricsCanvas) {
                if (metricsCanvas._chart) metricsCanvas._chart.destroy();
                metricsCanvas._chart = new Chart(metricsCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                                label: 'Water (L)',
                                data: water,
                                borderColor: '#0288d1',
                                tension: .25
                            },
                            {
                                label: 'Sleep (h)',
                                data: sleep,
                                borderColor: '#6a1b9a',
                                tension: .25
                            },
                            {
                                label: 'Steps',
                                data: steps,
                                borderColor: '#ff8f00',
                                tension: .25,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });
            }
            // Lifestyle breakdown pie (latest entry)
            const lifestyleCanvas = document.getElementById('lifestyleChart');
            if (lifestyleCanvas) {
                if (lifestyleCanvas._chart) lifestyleCanvas._chart.destroy();
                const latest = rows[0]?.payload || {};
                const pieData = {
                    labels: ['Meals', 'Cigarettes', 'Alcohol Units', 'Water (L)'],
                    datasets: [{
                        data: [latest.meals || 0, latest.cigarettes || 0, latest.alcoholUnits || 0, latest.water || 0],
                        backgroundColor: ['#66bb6a', '#ef5350', '#8d6e63', '#29b6f6']
                    }]
                };
                lifestyleCanvas._chart = new Chart(lifestyleCanvas.getContext('2d'), {
                    type: 'pie',
                    data: pieData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }

        // Goals handling
        async function loadGoals() {
            try {
                const r = await fetch('api_goals_get.php', {
                    credentials: 'include'
                });
                const txt = await r.text();
                try {
                    return JSON.parse(txt);
                } catch (e) {
                    console.error('Goals GET non-JSON response:', txt.slice(0, 300));
                    return null;
                }
            } catch (e) {
                return null;
            }
        }

        async function saveGoals(payload) {
            const csrf = (window.appState && window.appState.csrfToken) || '';
            try {
                const r = await fetch('api_goals_save.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ...payload,
                        csrf
                    })
                });
                const txt = await r.text();
                try {
                    return JSON.parse(txt);
                } catch (e) {
                    console.error('Goals SAVE non-JSON response:', txt.slice(0, 300));
                    return {
                        ok: false,
                        error: 'parse'
                    };
                }
            } catch (e) {
                console.error('Goals SAVE network error', e);
                return {
                    ok: false,
                    error: 'network'
                };
            }
        }

        function buildProgressBars(goals, rows) {
            const el = document.getElementById('progressBars');
            if (!el) return;
            if (!rows.length) {
                el.innerHTML = '<em>No data yet to measure progress.</em>';
                return;
            }
            const last = rows.slice(0, 14);
            const avgVal = k => {
                const vals = last.map(r => Number(r.payload?.[k])).filter(n => !isNaN(n));
                return vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : 0;
            };
            const metrics = [{
                    key: 'water',
                    label: 'Water (L)',
                    goal: goals.water_goal,
                    val: avgVal('water')
                },
                {
                    key: 'sleep',
                    label: 'Sleep (h)',
                    goal: goals.sleep_goal,
                    val: avgVal('sleep')
                },
                {
                    key: 'steps',
                    label: 'Steps',
                    goal: goals.steps_goal,
                    val: avgVal('steps')
                },
                {
                    key: 'healthScore',
                    label: 'Health Score',
                    goal: goals.health_score_goal,
                    val: avgVal('healthScore')
                }
            ];
            el.innerHTML = metrics.map(m => {
                const pct = m.goal > 0 ? Math.min(1, m.val / m.goal) : 0;
                let cls = 'bad';
                if (pct >= 1) cls = 'good';
                else if (pct >= 0.7) cls = 'warn';
                return `<div class="goal-progress"><div class="gp-label">${m.label}: <strong>${m.val.toFixed(2)}</strong> / ${m.goal}</div><div class="gp-bar"><span class="gp-fill ${cls}" style="width:${(pct*100).toFixed(0)}%"></span></div></div>`;
            }).join('');
        }

        function computeStreak(rows, predicate) {
            let streak = 0;
            for (const r of rows) {
                if (predicate(r.payload)) streak++;
                else break;
            }
            return streak;
        }

        function buildBadges(rows) { // now renders unified progress list
            const list = document.getElementById('progressList');
            if (!list) return;
            list.innerHTML = '';
            list.classList.remove('hide-completed');
            const latest = rows[0]?.payload || {};
            const histAvg = k => {
                const vals = rows.map(r => Number(r.payload?.[k])).filter(v => !isNaN(v));
                if (!vals.length) return 0;
                return vals.reduce((a, b) => a + b, 0) / vals.length;
            };
            const avgWaterAll = histAvg('water');
            const avgSleepAll = histAvg('sleep');
            const avgStepsAll = histAvg('steps');
            const avgHSAll = histAvg('healthScore');

            function streak(pred) {
                return computeStreak(rows, pred);
            }
            const waterStreak = streak(p => (p.water || 0) >= 2);
            const sleepStreak = streak(p => (p.sleep || 0) >= 7 && (p.sleep || 0) <= 9);
            const smokeFreeStreak = streak(p => (p.cigarettes || 0) === 0 && p.smoked24 === 'no');
            const stepStreak = streak(p => (p.steps || 0) >= 8000);
            const hydration7 = waterStreak >= 7;
            const hydration30 = waterStreak >= 30;
            const sleep7 = sleepStreak >= 7;
            const steps10k = (latest.steps || 0) >= 10000;
            const healthHigh = (latest.healthScore || 0) >= 85;
            const riskLow = (latest.risk || 0) <= 5 && smokeFreeStreak >= 3;

            const catalog = [{
                    key: 'hydration_streak_3',
                    icon: 'opacity',
                    label: '3-day Hydration',
                    test: () => waterStreak >= 3
                },
                {
                    key: 'hydration_streak_7',
                    icon: 'opacity',
                    label: '7-day Hydration',
                    test: () => hydration7
                },
                {
                    key: 'hydration_streak_30',
                    icon: 'opacity',
                    label: '30-day Hydration',
                    test: () => hydration30
                },
                {
                    key: 'sleep_streak_3',
                    icon: 'bedtime',
                    label: '3-night Sleep',
                    test: () => sleepStreak >= 3
                },
                {
                    key: 'sleep_streak_7',
                    icon: 'bedtime',
                    label: '7-night Sleep',
                    test: () => sleep7
                },
                {
                    key: 'smoke_free_1',
                    icon: 'smoke_free',
                    label: 'Smoke-free Day',
                    test: () => smokeFreeStreak >= 1
                },
                {
                    key: 'smoke_free_7',
                    icon: 'smoke_free',
                    label: '7 Smoke-free Days',
                    test: () => smokeFreeStreak >= 7
                },
                {
                    key: 'steps_8k_streak_3',
                    icon: 'directions_walk',
                    label: '3-day 8k Steps',
                    test: () => stepStreak >= 3
                },
                {
                    key: 'steps_10k_day',
                    icon: 'hiking',
                    label: '10k Steps Day',
                    test: () => steps10k
                },
                {
                    key: 'health_score_85',
                    icon: 'favorite',
                    label: 'Health Score 85+',
                    test: () => healthHigh
                },
                {
                    key: 'risk_low',
                    icon: 'shield',
                    label: 'Low Risk',
                    test: () => riskLow
                }
            ];

            const progressResolvers = {
                hydration_streak_3: () => Math.min(1, waterStreak / 3),
                hydration_streak_7: () => Math.min(1, waterStreak / 7),
                hydration_streak_30: () => Math.min(1, waterStreak / 30),
                sleep_streak_3: () => Math.min(1, sleepStreak / 3),
                sleep_streak_7: () => Math.min(1, sleepStreak / 7),
                smoke_free_1: () => Math.min(1, smokeFreeStreak / 1),
                smoke_free_7: () => Math.min(1, smokeFreeStreak / 7),
                steps_8k_streak_3: () => Math.min(1, stepStreak / 3),
                steps_10k_day: () => Math.min(1, (latest.steps || 0) / 10000),
                health_score_85: () => Math.min(1, (latest.healthScore || 0) / 85),
                risk_low: () => {
                    const r = latest.risk || 0;
                    return Math.min(1, Math.max(0, (12 - r) / 12));
                }
            };

            function progressBar(pct, qual) {
                // Base classification
                let cls = 'bad'; // negative / low
                if (pct >= 1) cls = 'good'; // met target
                else if (pct >= 0.6) cls = 'warn'; // approaching

                // Metric-specific refinement (using historical averages for quality nuance)
                if (qual === 'hydration') {
                    if (avgWaterAll < 1.5) cls = 'bad';
                    else if (avgWaterAll < 2) cls = 'warn';
                    else if (pct >= 1) cls = 'good';
                    if (avgWaterAll >= 2.7 && pct >= 1) cls = 'highplus'; // robust hydration
                }
                if (qual === 'sleep') {
                    if (avgSleepAll < 6) cls = 'bad';
                    else if (avgSleepAll < 7) cls = 'warn';
                    else if (pct >= 1) cls = 'good';
                    // Consider 7–8.5h consistent as optimal; >9 not boosting color beyond good
                    if (avgSleepAll >= 7 && avgSleepAll <= 8.5 && pct >= 1) cls = 'highplus';
                }
                if (qual === 'steps') {
                    if (avgStepsAll < 3000) cls = 'bad';
                    else if (avgStepsAll < 6000) cls = 'warn';
                    else if (pct >= 1) cls = 'good';
                    if (avgStepsAll >= 12000 && pct >= 1) cls = 'highplus';
                }
                if (qual === 'risk') { // risk_low pseudo metric (inverse progress)
                    // For risk we invert meaning: highplus = very low risk sustained
                    if (pct >= 0.9) cls = 'highplus';
                    else if (pct >= 0.75) cls = 'good';
                    else if (pct >= 0.5) cls = 'warn';
                    else cls = 'bad';
                }
                if (qual === 'healthScore') {
                    // Use categorical mapping to refine color when below full completion (pct<1) too
                    // avgHSAll range: 0-100
                    if (avgHSAll <= 40) cls = 'bad';
                    else if (avgHSAll <= 60) cls = 'warn';
                    else if (avgHSAll <= 80) cls = (pct >= 1 ? 'good' : 'warn');
                    else if (avgHSAll > 80) cls = (pct >= 0.85 ? 'highplus' : 'good');
                }
                const w = Math.round(Math.min(1, Math.max(0, pct)) * 100);
                return `<div class="badge-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${w}" aria-label="${qual||'progress'} ${w} percent"><span class="bp-fill ${cls}" style="width:${w}%"></span></div>`;
            }

            const earnedKeys = new Set((window._earnedBadges || []).map(b => b.badge_key));
            const newlyEarned = [];
            catalog.forEach(b => {
                if (b.test() && !earnedKeys.has(b.key)) newlyEarned.push(b.key);
            });
            if (newlyEarned.length) awardBadges(newlyEarned);

            catalog.forEach(b => {
                const pct = progressResolvers[b.key] ? progressResolvers[b.key]() : 0;
                let qual = '';
                if (b.key.startsWith('hydration')) qual = 'hydration';
                else if (b.key.startsWith('sleep')) qual = 'sleep';
                else if (b.key.startsWith('steps')) qual = 'steps';
                else if (b.key === 'risk_low') qual = 'risk';
                else if (b.key === 'health_score_85') qual = 'healthScore';
                const bar = progressBar(pct, qual);
                const earned = earnedKeys.has(b.key) || newlyEarned.includes(b.key) || pct >= 1;
                const li = document.createElement('li');
                li.className = 'progress-item' + (earned ? ' completed' : '');
                const pctNum = Math.round(pct * 100);
                li.innerHTML = `<span class="badge-icon"><i class="material-icons">${b.icon}</i></span><div class="badge-text">${b.label}${bar}<div class="progress-meta" aria-label="${b.label} progress ${pctNum} percent${earned ? ', completed' : ''}">${pctNum}%${earned ? ' · Completed' : ''}</div></div>`;
                if (!earned) li.classList.add('incomplete');
                list.appendChild(li);
            });

            // Counts & legend
            const total = catalog.length;
            const completed = list.querySelectorAll('li.completed').length;
            const pctDone = total ? Math.round((completed / total) * 100) : 0;
            const countEl = document.getElementById('progressCount');
            if (countEl) countEl.textContent = `${completed}/${total} (${pctDone}%) completed`;
            const legend = document.getElementById('progressLegend');
            if (legend) legend.innerHTML = `
<span class="tooltip-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false" aria-label="Progress color legend help">
<strong>Legend:</strong> <span style="color:#c62828">Low</span> · <span style="color:#ff8f00">Building</span> · <span style="color:#2e7d32">Met</span> · <span style="color:#1565c0">Optimal</span>
</span>
<div class="tooltip-panel" role="tooltip">Colors reflect relative progress and historical quality: red (low baseline), yellow (improving), green (goal met), blue (sustained optimal pattern).</div>`;

            // Hide completed toggle wiring
            const toggle = document.getElementById('toggleHideCompleted');
            if (toggle && !toggle._bound) {
                toggle._bound = true;
                toggle.addEventListener('change', () => {
                    if (toggle.checked) list.classList.add('hide-completed');
                    else list.classList.remove('hide-completed');
                });
            }
        }

        async function fetchBadges() {
            try {
                const r = await fetch('api_badges_get.php', {
                    credentials: 'include'
                });
                const j = await r.json();
                if (j && j.ok) {
                    window._earnedBadges = j.badges || [];
                }
            } catch (e) {
                /* ignore */
            }
        }

        async function awardBadges(keys) {
            if (!Array.isArray(keys) || !keys.length) return;
            const csrf = (window.appState && window.appState.csrfToken) || '';
            const celebration = document.getElementById('badgeCelebration');
            for (const k of keys) {
                try {
                    const r = await fetch('api_badges_award.php', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            badge: k,
                            csrf
                        })
                    });
                    const j = await r.json().catch(() => null);
                    if (j && j.ok) {
                        // Add to in-memory earned list
                        window._earnedBadges = window._earnedBadges || [];
                        if (!window._earnedBadges.find(b => b.badge_key === k)) {
                            window._earnedBadges.push({
                                badge_key: k,
                                earned_at: new Date().toISOString()
                            });
                            if (celebration) {
                                celebration.hidden = false;
                                celebration.textContent = '🎉 Badge Unlocked: ' + k.replace(/_/g, ' ') + '!';
                                celebration.classList.add('pop');
                                setTimeout(() => {
                                    celebration.classList.remove('pop');
                                }, 1200);
                                setTimeout(() => {
                                    celebration.hidden = true;
                                }, 4000);
                            }
                        }
                    }
                } catch (e) {
                    /* ignore single failure */
                }
            }
        }

        function buildWellnessBreakdown(rows) {
            const list = document.getElementById('breakdownList');
            if (!list) return;
            list.innerHTML = '';
            if (!rows.length) {
                list.innerHTML = '<li>No data yet.</li>';
                return;
            }
            const latest = rows[0].payload || {};
            const notes = [];
            if ((latest.water || 0) < 1.5) notes.push('Water intake is low (<1.5L). Aim for 2–3L for better hydration.');
            if ((latest.sleep || 0) < 6) notes.push('Sleep below 6h reduces recovery. Target 7–9h.');
            if ((latest.sleep || 0) > 10) notes.push('Sleep duration unusually high; consistent oversleep may signal fatigue.');
            if ((latest.steps || 0) < 4000) notes.push('Movement is limited; a short walk can boost circulation.');
            if ((latest.cigarettes || 0) > 0) notes.push('Smoking adds to risk score — consider reducing gradually.');
            if ((latest.alcoholUnits || 0) > 3) notes.push('Alcohol intake elevated; moderation supports recovery.');
            if (latest.drugsYes === 'yes') notes.push('Substance use increases risk; seek professional guidance if needed.');
            const hsCat = classifyHealthScore(latest.healthScore || 0);
            if (hsCat.label === 'Excellent' || hsCat.label === 'Good') {
                notes.push(`Overall health score ${hsCat.label} — maintain habits.`);
            } else if (hsCat.label === 'Fair') {
                notes.push('Overall score Fair — modest improvements in daily basics can lift you to Good.');
            } else if (hsCat.label === 'Poor' || hsCat.label === 'Critical') {
                notes.push('Overall score is low — focus on sleep routine, hydration, and reducing smoking/alcohol this week.');
            }
            if (!notes.length) notes.push('Balanced day — keep consistent!');
            notes.forEach(n => {
                const li = document.createElement('li');
                li.textContent = n;
                list.appendChild(li);
            });
            buildFact();
        }

        const HEALTH_FACTS = [
            'Hydration can improve cognitive performance by up to 15%.',
            'Short walking breaks every hour help regulate blood sugar.',
            'Consistent sleep strengthens immune function and mood.',
            'Reducing daily cigarettes by even a few lowers cardiovascular strain.',
            'Balanced meals with protein + fiber improve satiety.'
        ];

        function buildFact() {
            const box = document.getElementById('factBox');
            if (!box) return;
            const fact = HEALTH_FACTS[Math.floor(Math.random() * HEALTH_FACTS.length)];
            box.textContent = 'Did you know? ' + fact;
        }

        function addExportHandlers(rows) {
            const jsonBtn = document.getElementById('exportJsonBtn');
            const csvBtn = document.getElementById('exportCsvBtn');
            if (jsonBtn) jsonBtn.onclick = () => {
                const blob = new Blob([JSON.stringify(rows, null, 2)], {
                    type: 'application/json'
                });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'entries.json';
                a.click();
            };
            if (csvBtn) csvBtn.onclick = () => {
                if (!rows.length) return;
                const headers = ['date', 'water', 'sleep', 'steps', 'meals', 'cigarettes', 'alcoholUnits', 'risk', 'healthScore'];
                const lines = [headers.join(',')];
                rows.forEach(r => {
                    const p = r.payload || {};
                    lines.push([
                        r.entry_date || p.date || '', p.water || p.waterLiters || '', p.sleep || '', p.steps || '', p.meals || '', p.cigarettes || '', p.alcoholUnits || '', p.risk || '', p.healthScore || ''
                    ].join(','));
                });
                const blob = new Blob([lines.join('\n')], {
                    type: 'text/csv'
                });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'entries.csv';
                a.click();
            };
        }

        async function loadEntries() {
            try {
                const r = await fetch('api_fetch_entries.php', {
                    credentials: 'include'
                });
                const j = await r.json();
                if (!j.ok) {
                    const ep = document.getElementById('entriesPre');
                    if (ep) ep.innerText = 'Error fetching entries';
                    return;
                }
                const rows = j.entries;
                window._latestEntries = rows; // cache globally for reuse
                // Replace raw dates in pre display to show formatted forms first if present
                const enhanced = rows.map(r => ({
                    id: r.id,
                    entry_date: r.entry_date_formatted || r.entry_date,
                    created_at: r.created_at_formatted || r.created_at,
                    payload: r.payload
                }));
                document.getElementById('entriesPre').innerText = JSON.stringify(enhanced, null, 2);
                buildSummary(rows);
                buildCharts(rows);
                buildAdvice(rows);
                buildBadges(rows);
                buildWellnessBreakdown(rows);
                buildEntryHistory(rows);
                addExportHandlers(rows);
                // After we have goals loaded (async), progress bars will be built
                if (window._loadedGoals) {
                    buildProgressBars(window._loadedGoals, rows);
                    buildGoalsComparison(rows, window._loadedGoals);
                    buildDeficitChart(rows, window._loadedGoals);
                }
            } catch (e) {
                const ep = document.getElementById('entriesPre');
                if (ep) ep.innerText = 'Network error fetching entries';
            }
        }

        function buildEntryHistory(rows) {
            const wrap = document.getElementById('entryHistory');
            if (!wrap) return;
            const pageSizeSel = document.getElementById('entryPageSize');
            const prevBtn = document.getElementById('entryPrev');
            const nextBtn = document.getElementById('entryNext');
            const info = document.getElementById('entryPageInfo');
            if (!rows || !rows.length) {
                wrap.innerHTML = '<li>No entries yet.</li>';
                if (info) info.textContent = '0/0';
                prevBtn && (prevBtn.disabled = true);
                nextBtn && (nextBtn.disabled = true);
                return;
            }
            // Ensure sorted newest first
            const sorted = [...rows].sort((a, b) => new Date(b.entry_date || b.logged_at) - new Date(a.entry_date || a.logged_at));
            const state = window._entryPaginateState || {
                page: 1,
                size: parseInt(pageSizeSel?.value || '11')
            };
            state.size = parseInt(pageSizeSel?.value || state.size || 11);
            const totalPages = Math.max(1, Math.ceil(sorted.length / state.size));
            if (state.page > totalPages) state.page = totalPages;
            const start = (state.page - 1) * state.size;
            const pageItems = sorted.slice(start, start + state.size);
            wrap.innerHTML = '';
            pageItems.forEach(r => {
                const p = r.payload || {};
                const date = (r.entry_date || r.logged_at || '').split('T')[0] || p.date || '—';
                const hs = Number(p.healthScore || 0);
                const cat = classifyHealthScore(hs);
                const li = document.createElement('li');
                li.innerHTML = `<span class="entry-h-date">${date}</span>
        <span class="entry-h-metric" aria-label="Health score ${hs} category ${cat.label}"><i class="material-icons" style="color:#ef5350">favorite</i>${hs}<span class="score-badge inline ${cat.className}" title="${cat.label}">${cat.label}</span></span>
        <span class="entry-h-metric" aria-label="Water ${(p.water||0)} liters"><i class="material-icons" style="color:#42a5f5">opacity</i>${(p.water||0)}</span>
        <span class="entry-h-metric" aria-label="Sleep ${(p.sleep||0)} hours"><i class="material-icons" style="color:#5e35b1">bedtime</i>${(p.sleep||0)}</span>
        <span class="entry-h-metric" aria-label="Steps ${(p.steps||0)}"><i class="material-icons" style="color:#2e7d32">directions_walk</i>${(p.steps||0)}</span>`;
                wrap.appendChild(li);
            });
            if (info) info.textContent = `${state.page}/${totalPages}`;
            if (prevBtn) prevBtn.disabled = state.page <= 1;
            if (nextBtn) nextBtn.disabled = state.page >= totalPages;
            window._entryPaginateState = state;
            // Wire events once
            if (pageSizeSel && !pageSizeSel._bound) {
                pageSizeSel._bound = true;
                pageSizeSel.addEventListener('change', () => {
                    window._entryPaginateState.page = 1;
                    buildEntryHistory(rows);
                });
            }
            if (prevBtn && !prevBtn._bound) {
                prevBtn._bound = true;
                prevBtn.addEventListener('click', () => {
                    if (window._entryPaginateState.page > 1) {
                        window._entryPaginateState.page--;
                        buildEntryHistory(rows);
                    }
                });
            }
            if (nextBtn && !nextBtn._bound) {
                nextBtn._bound = true;
                nextBtn.addEventListener('click', () => {
                    const total = Math.ceil(sorted.length / window._entryPaginateState.size);
                    if (window._entryPaginateState.page < total) {
                        window._entryPaginateState.page++;
                        buildEntryHistory(rows);
                    }
                });
            }
        }

        async function init() {
            await ensureCsrf();
            await fetchBadges(); // get previously earned badges first
            // Load goals in parallel with entries
            loadGoals().then(g => {
                if (g && g.ok !== false) {
                    window._loadedGoals = g;
                    populateGoalsForm(g);
                    if (window._latestEntries) buildGoalsComparison(window._latestEntries, g);
                    if (window._latestEntries) buildDeficitChart(window._latestEntries, g);
                }
            });
            await loadEntries();
            const addLogoutHandler = (id) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const csrf = (window.appState && window.appState.csrfToken) || '';
                    const resp = await fetch('logout.php', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf
                        })
                    });
                    if (!resp.ok) {
                        M && M.toast ? M.toast({
                            html: 'Logout failed (' + resp.status + ')'
                        }) : alert('Logout failed');
                    } else {
                        window.location = '../index.html';
                    }
                });
            };
            addLogoutHandler('dashboardLogoutNav');
            addLogoutHandler('dashboardLogoutMobile');
            const gf = document.getElementById('goalsForm');
            if (gf) {
                gf.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const goals = {
                        water_goal: parseFloat(document.getElementById('goalWater').value) || 0,
                        sleep_goal: parseFloat(document.getElementById('goalSleep').value) || 0,
                        steps_goal: parseInt(document.getElementById('goalSteps').value) || 0,
                        health_score_goal: parseInt(document.getElementById('goalHealthScore').value) || 0,
                    };
                    const res = await saveGoals(goals);
                    const msgEl = document.getElementById('goalsMsg');
                    if (res && res.ok) {
                        window._loadedGoals = goals;
                        msgEl.textContent = 'Saved';
                        msgEl.className = 'goal-msg success';
                        // Rebuild progress bars (needs entries)
                        if (window._latestEntries) {
                            buildProgressBars(goals, window._latestEntries);
                            buildGoalsComparison(window._latestEntries, goals);
                            buildDeficitChart(window._latestEntries, goals);
                        }
                    } else {
                        msgEl.textContent = 'Failed';
                        msgEl.className = 'goal-msg error';
                    }
                });
            }
        }
        init();

        function populateGoalsForm(g) {
            document.getElementById('goalWater').value = g.water_goal || '';
            document.getElementById('goalSleep').value = g.sleep_goal || '';
            document.getElementById('goalSteps').value = g.steps_goal || '';
            document.getElementById('goalHealthScore').value = g.health_score_goal || '';
            if (window.M && M.updateTextFields) M.updateTextFields();
        }

        // ================= BMI (Stored) =================
        (function initBMIStored() {
            const form = document.getElementById('bmiForm');
            const wEl = document.getElementById('bmiWeight');
            const hEl = document.getElementById('bmiHeight');
            const dEl = document.getElementById('bmiDate');
            const msgEl = document.getElementById('bmiMsg');
            const resEl = document.getElementById('bmiResult');
            const resetBtn = document.getElementById('bmiResetBtn');
            const chartEl = document.getElementById('bmiChart');
            let chartInstance = null;
            let bmiRows = [];

            function sanitizeNum(inputEl, min, max) {
                let v = (inputEl.value || '').trim();
                v = v.replace(/[^0-9.,]/g, '').replace(/,/g, '.');
                let num = parseFloat(v);
                if (isNaN(num)) return null;
                if (num < min) num = min;
                else if (num > max) num = max;
                num = Math.round(num * 10) / 10;
                inputEl.value = num;
                return num;
            }

            function classify(bmi) {
                if (bmi < 18.5) return {
                    label: 'Underweight',
                    cls: 'under'
                };
                if (bmi < 25) return {
                    label: 'Normal',
                    cls: 'normal'
                };
                if (bmi < 30) return {
                    label: 'Overweight',
                    cls: 'over'
                };
                return {
                    label: 'Obesity',
                    cls: 'obese'
                };
            }

            function buildBMIChart() {
                if (!chartEl) return;
                const labels = bmiRows.slice().reverse().map(r => r.entry_date_formatted ? r.entry_date_formatted.split(' ')[0] : r.entry_date);
                const bmiData = bmiRows.slice().reverse().map(r => r.bmi);
                if (chartInstance) chartInstance.destroy();
                if (typeof Chart === 'undefined') return;
                chartInstance = new Chart(chartEl.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'BMI',
                            data: bmiData,
                            borderColor: '#00796b',
                            tension: .25,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false
                            }
                        }
                    }
                });
            }
            async function fetchBMI() {
                try {
                    const r = await fetch('api_bmi_fetch.php', {
                        credentials: 'include'
                    });
                    const j = await r.json();
                    if (j && j.ok) {
                        bmiRows = Array.isArray(j.entries) ? j.entries : [];
                        buildBMIChart();
                    }
                } catch (e) {
                    /* ignore */
                }
            }
            async function saveBMI(weight, heightCm, dateOpt) {
                const csrf = (window.appState && window.appState.csrfToken) || '';
                const payload = {
                    weight,
                    height: heightCm,
                    csrf
                };
                if (dateOpt) payload.date = dateOpt;
                const r = await fetch('api_bmi_save.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const j = await r.json().catch(() => null);
                return j;
            }
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    msgEl.textContent = '';
                    resEl.textContent = '';
                    resEl.className = 'bmi-result';
                    const weight = sanitizeNum(wEl, 20, 500);
                    const heightCm = sanitizeNum(hEl, 50, 250);
                    const dateVal = (dEl && dEl.value) ? dEl.value : '';
                    if (weight === null || heightCm === null) {
                        msgEl.textContent = 'Enter valid weight & height.';
                        msgEl.className = 'bmi-msg error';
                        return;
                    }
                    const hM = heightCm / 100.0;
                    if (hM <= 0) {
                        msgEl.textContent = 'Invalid height';
                        msgEl.className = 'bmi-msg error';
                        return;
                    }
                    msgEl.textContent = 'Saving...';
                    const resp = await saveBMI(weight, heightCm, dateVal);
                    if (!resp || !resp.ok) {
                        msgEl.textContent = 'Save failed';
                        msgEl.className = 'bmi-msg error';
                        return;
                    }
                    const cls = classify(resp.bmi);
                    resEl.textContent = `BMI: ${resp.bmi} (${cls.label})`;
                    resEl.classList.add(cls.cls);
                    msgEl.textContent = 'Saved';
                    msgEl.className = 'bmi-msg success';
                    try {
                        localStorage.setItem('bmi_last', JSON.stringify({
                            w: weight,
                            h: heightCm
                        }));
                    } catch (_) {}
                    fetchBMI();
                });
            }
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    wEl.value = '';
                    hEl.value = '';
                    if (dEl) dEl.value = '';
                    msgEl.textContent = '';
                    resEl.textContent = '';
                    resEl.className = 'bmi-result';
                    try {
                        localStorage.removeItem('bmi_last');
                    } catch (_) {}
                    if (window.M && M.updateTextFields) M.updateTextFields();
                });
            }
            // Prefill from localStorage
            try {
                const cached = JSON.parse(localStorage.getItem('bmi_last') || 'null');
                if (cached && typeof cached.w === 'number' && typeof cached.h === 'number') {
                    wEl.value = cached.w;
                    hEl.value = cached.h;
                }
                if (window.M && M.updateTextFields) M.updateTextFields();
            } catch (_) {}
            fetchBMI();
        })();

        // =============== Goals vs Actual Chart =================
        function buildGoalsComparison(rows, goals) {
            const canvas = document.getElementById('goalsComparisonChart');
            if (!canvas || typeof Chart === 'undefined' || !rows.length) return;
            const labels = rows.slice().reverse().map(r => r.entry_date_formatted ? r.entry_date_formatted.split(' ')[0] : (r.entry_date || '?'));
            const rev = rows.slice().reverse();
            const arr = (k) => rev.map(r => r.payload?.[k] ?? null);
            const water = arr('water');
            const sleep = arr('sleep');
            const steps = arr('steps');
            const hs = arr('healthScore');
            const ds = [];
            // Actual datasets
            ds.push({
                label: 'Water (L)',
                data: water,
                borderColor: '#0288d1',
                tension: .25,
                fill: false,
                yAxisID: 'y'
            });
            ds.push({
                label: 'Sleep (h)',
                data: sleep,
                borderColor: '#6a1b9a',
                tension: .25,
                fill: false,
                yAxisID: 'y'
            });
            ds.push({
                label: 'Health Score',
                data: hs,
                borderColor: '#2e7d32',
                tension: .25,
                fill: false,
                yAxisID: 'y'
            });
            ds.push({
                label: 'Steps',
                data: steps,
                borderColor: '#ff8f00',
                tension: .25,
                fill: false,
                yAxisID: 'y1'
            });
            const repeat = (val) => labels.map(() => val);
            // Goal lines (only if >0)
            if (goals.water_goal > 0) ds.push({
                label: 'Water Goal',
                data: repeat(goals.water_goal),
                borderColor: '#0288d1',
                borderDash: [6, 6],
                pointRadius: 0,
                fill: false,
                yAxisID: 'y'
            });
            if (goals.sleep_goal > 0) ds.push({
                label: 'Sleep Goal',
                data: repeat(goals.sleep_goal),
                borderColor: '#6a1b9a',
                borderDash: [6, 6],
                pointRadius: 0,
                fill: false,
                yAxisID: 'y'
            });
            if (goals.health_score_goal > 0) ds.push({
                label: 'Health Score Goal',
                data: repeat(goals.health_score_goal),
                borderColor: '#2e7d32',
                borderDash: [6, 6],
                pointRadius: 0,
                fill: false,
                yAxisID: 'y'
            });
            if (goals.steps_goal > 0) ds.push({
                label: 'Steps Goal',
                data: repeat(goals.steps_goal),
                borderColor: '#ff8f00',
                borderDash: [6, 6],
                pointRadius: 0,
                fill: false,
                yAxisID: 'y1'
            });
            if (canvas._chart) canvas._chart.destroy();
            canvas._chart = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: ds
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Water / Sleep / Health Score'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Steps'
                            }
                        }
                    }
                }
            });
        }

        // ================= Deficit Chart ===================
        function buildDeficitChart(rows, goals) {
            const canvas = document.getElementById('deficitsChart');
            if (!canvas || typeof Chart === 'undefined' || !rows.length) return;
            const labels = rows.slice().reverse().map(r => r.entry_date_formatted ? r.entry_date_formatted.split(' ')[0] : (r.entry_date || '?'));
            const rev = rows.slice().reverse();
            // Baselines: use goals if set, otherwise defaults
            const baseWater = goals.water_goal > 0 ? goals.water_goal : 2.0;
            const baseSleep = goals.sleep_goal > 0 ? goals.sleep_goal : 7.0;
            const baseSteps = goals.steps_goal > 0 ? goals.steps_goal : 1000;
            const baseHS = goals.health_score_goal > 0 ? goals.health_score_goal : 60;
            const waterDelta = rev.map(r => ((r.payload?.water ?? 0) - baseWater));
            const sleepDelta = rev.map(r => ((r.payload?.sleep ?? 0) - baseSleep));
            const stepsDelta = rev.map(r => ((r.payload?.steps ?? 0) - baseSteps));
            const hsDelta = rev.map(r => ((r.payload?.healthScore ?? 0) - baseHS));
            if (canvas._chart) canvas._chart.destroy();
            canvas._chart = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                            label: 'Water Δ (L)',
                            data: waterDelta,
                            backgroundColor: waterDelta.map(v => v < 0 ? '#c62828' : '#66bb6a')
                        },
                        {
                            label: 'Sleep Δ (h)',
                            data: sleepDelta,
                            backgroundColor: sleepDelta.map(v => v < 0 ? '#ad1457' : '#8e24aa')
                        },
                        {
                            label: 'Steps Δ',
                            data: stepsDelta,
                            backgroundColor: stepsDelta.map(v => v < 0 ? '#ef6c00' : '#ffb300'),
                            yAxisID: 'y1'
                        },
                        {
                            label: 'Health Score Δ',
                            data: hsDelta,
                            backgroundColor: hsDelta.map(v => v < 0 ? '#b71c1c' : '#2e7d32')
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Δ vs Baseline/Goal'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>