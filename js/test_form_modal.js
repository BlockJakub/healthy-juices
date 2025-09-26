/**
 * File: js/test_form_modal.js
 * Author: Healthy Blog Team
 * Created: 2025-09-25
 * Description: Initializes & validates daily health form, handles submission, local fallback and preview chart.
 */
document.addEventListener('DOMContentLoaded', function () {
    // init modals
    var modalElems = document.querySelectorAll('.modal');
    M.Modal.init(modalElems, {});

    // set today's date as max and default for date input
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const isoDate = `${yyyy}-${mm}-${dd}`;
    const dateInput = document.getElementById('entryDate');
    if (dateInput) {
        dateInput.setAttribute('max', isoDate);
        dateInput.value = isoDate;
        // If Materialize textfields need update
        if (typeof M !== 'undefined' && M.updateTextFields) M.updateTextFields();
    }

    // handle form submit
    const healthForm = document.getElementById('healthForm');
    healthForm && healthForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Instead of relying on a separate whoami endpoint, attempt a server save.
        // If the server responds 401 (unauthenticated) redirect to the login page.
        // On network or server errors (not 401) fall back to localStorage so the user doesn't lose data.

        // require consent if any substances fields are used
        const consentCheckbox = document.getElementById('consentSubstances');
        if (consentCheckbox && !consentCheckbox.checked) {
            M.toast({ html: 'Please acknowledge the safety notice before submitting.' });
            return;
        }

        // validate date is today
        const enteredDate = document.getElementById('entryDate').value; // allow historical (past) dates
        if (!enteredDate) {
            M.toast({ html: 'Date is required.' });
            return;
        }
        if (enteredDate > isoDate) {
            M.toast({ html: 'Future dates are not allowed.' });
            return;
        }

        const water = parseFloat(document.getElementById('water').value) || 0;
        const sleep = parseFloat(document.getElementById('sleep').value) || 0;
        const steps = parseInt(document.getElementById('steps').value) || 0;

        // meals: count yes radios
        function mealYes(name) {
            const sel = document.querySelector(`input[name="${name}"]:checked`);
            return sel && sel.value === 'yes';
        }

        const meals = [mealYes('breakfast'), mealYes('lunch'), mealYes('dinner'), mealYes('snack')].filter(Boolean).length;

        // evaluation
        let advice = [];
        if (water < 2) advice.push('Drink more water (2–3 liters recommended).');
        if (sleep < 7) advice.push('Try to sleep 7–9 hours.');
        if (meals < 3) advice.push('Aim for at least 3 main meals.');
        if (steps < 6000) advice.push('Increase walking, target 6k–10k steps.');
        if (advice.length === 0) advice = ['Great job! Keep up the healthy habits.'];

        // open modal
        const modalInstance = M.Modal.getInstance(document.getElementById('resultModal'));
        modalInstance && modalInstance.open();

        // disclaimer will be rendered after we collect lifestyle inputs

        // render chart
        const ctxEl = document.getElementById('resultChart');
        if (ctxEl && typeof Chart !== 'undefined') {
            const ctx = ctxEl.getContext('2d');
            // destroy previous chart if exists
            if (window._healthChart) {
                window._healthChart.destroy();
            }
            window._healthChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Water (L)', 'Sleep (h)', 'Meals', 'Steps'],
                    datasets: [{
                        label: 'Your Input',
                        data: [water, sleep, meals, Math.min(steps, 300000)],
                        backgroundColor: ['#4dc9f6', '#f67019', '#f53794', '#537bc4']
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        const adviceEl = document.getElementById('healthAdvice');
        if (adviceEl) adviceEl.innerText = advice.join(' ');
        // collect additional lifestyle inputs
        const smokingPattern = document.getElementById('smokingPattern') ? document.getElementById('smokingPattern').value : 'never';
        const smoked24 = document.querySelector('input[name="smoked24"]:checked') ? document.querySelector('input[name="smoked24"]:checked').value : 'no';
        const cigarettes = document.getElementById('cigarettes') ? parseInt(document.getElementById('cigarettes').value) || 0 : 0;
        const feelWeak = document.querySelector('input[name="feelWeak"]:checked') ? document.querySelector('input[name="feelWeak"]:checked').value : 'no';
        const alcoholUnits = document.getElementById('alcoholUnits') ? parseFloat(document.getElementById('alcoholUnits').value) || 0 : 0;
        // NOTE: fixed selector typo (had an extra closing bracket) preventing correct detection
        const drugsYes = (function () {
            const sel = document.querySelector('input[name="drugs"]:checked');
            return sel ? sel.value : 'no';
        })();
        const drugType = document.getElementById('drugType') ? document.getElementById('drugType').value : '';

        // show legal/disclaimer area inside modal if substances indicated
        try {
            const disclaimerElId = 'modalDisclaimer';
            let el = document.getElementById(disclaimerElId);
            if (!el) {
                el = document.createElement('p');
                el.id = disclaimerElId;
                el.style.color = '#b71c1c';
                document.querySelector('#resultModal .modal-content').appendChild(el);
            }
            if (drugsYes === 'yes' || alcoholUnits > 5 || cigarettes > 10) {
                el.innerText = 'NOTICE: Your responses indicate recent substance use. This tool does NOT replace professional assessment. If you are concerned about addiction or withdrawal, seek immediate medical help.';
            } else {
                el.innerText = '';
            }
        } catch (err) {
            // ignore
        }

        // simple risk and health scoring (heuristic)
        // risk increases with cigarettes, alcohol, drugs, low sleep, low water, and low steps
        let risk = 0;
        risk += Math.min(cigarettes / 10, 10); // up to 10
        risk += Math.min(alcoholUnits / 5, 6); // up to ~6
        if (drugsYes === 'yes') risk += 8;
        if (sleep < 6) risk += 4;
        if (water < 1.0) risk += 3;
        if (steps < 3000) risk += 3;
        if (feelWeak === 'yes') risk += 2;

        // health score (higher is better)
        let healthScore = 50;
        healthScore += Math.min(water * 5, 20);
        healthScore += Math.min(sleep * 3, 20);
        healthScore += Math.min(steps / 1000, 20);
        healthScore += meals * 5;
        if (drugsYes === 'yes') healthScore -= 15;
        healthScore -= Math.min(cigarettes / 2, 15);
        healthScore -= Math.min(alcoholUnits / 2, 10);
        healthScore = Math.max(0, Math.min(100, Math.round(healthScore)));

        // save daily entry to localStorage (array of entries)
        try {
            const historyKey = 'dailyHealthHistoryV1';
            const existing = JSON.parse(localStorage.getItem(historyKey) || '[]');
            // Extended fields
            const nutritionKcal = parseInt(document.getElementById('nutritionKcal')?.value) || 0;
            const vitamins = Array.from(document.querySelectorAll('.vitamin-check:checked')).map(c => c.value).slice(0, 12);
            const trainingType = document.getElementById('trainingType')?.value || 'none';
            const trainingMinutes = Math.min(1440, Math.max(0, parseInt(document.getElementById('trainingMinutes')?.value) || 0));
            const breathingType = document.getElementById('breathingType')?.value || 'none';
            const breathingMinutes = Math.min(600, Math.max(0, parseInt(document.getElementById('breathingMinutes')?.value) || 0));
            const coldMethod = document.getElementById('coldMethod')?.value || 'none';
            const coldMinutes = Math.min(300, Math.max(0, parseInt(document.getElementById('coldMinutes')?.value) || 0));
            const juiceType = document.getElementById('juiceType')?.value || 'none';
            const juiceFrequency = document.getElementById('juiceFrequency')?.value || 'none';
            const sleepQualityEl = document.querySelector('input[name="sleepQuality"]:checked');
            const sleepQuality = sleepQualityEl ? sleepQualityEl.value : 'undisturbed';

            const entry = {
                date: enteredDate,
                water, sleep, steps, meals,
                smokingPattern, smoked24, cigarettes,
                feelWeak, alcoholUnits, drugsYes, drugType,
                risk, healthScore,
                nutritionKcal, vitamins, trainingType, trainingMinutes,
                breathingType, breathingMinutes, coldMethod, coldMinutes,
                juiceType, juiceFrequency, sleepQuality
            };
            existing.push(entry);
            // keep last 60 entries
            const truncated = existing.slice(-60);
            localStorage.setItem(historyKey, JSON.stringify(truncated));
        } catch (err) {
            console.warn('Could not persist health entry', err);
        }

        // Attempt to save to server-side API (if user is authenticated). This will fail silently
        // if the server returns 401 (not authenticated) or network error.
        (async function trySaveServer() {
            try {
                const consentTimestamp = consentCheckbox && consentCheckbox.checked ? new Date().toISOString() : null;
                const csrf = (window.appState && window.appState.csrfToken) || '';
                const resp = await fetch('php/api_save_entry.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ date: enteredDate, water, sleep, steps, meals, smokingPattern, smoked24, cigarettes, feelWeak, alcoholUnits, drugsYes, drugType, risk, healthScore, consentTimestamp, csrf, nutritionKcal, vitamins, trainingType, trainingMinutes, breathingType, breathingMinutes, coldMethod, coldMinutes, juiceType, juiceFrequency, sleepQuality })
                });
                if (resp.status === 401) {
                    M.toast({ html: 'Please login to save entries to your account.' });
                    setTimeout(() => { window.location = 'php/login.html'; }, 900);
                    return;
                }
                if (resp.status === 403) {
                    M.toast({ html: 'Security token missing or invalid. Please reload and login.' });
                    return;
                }
                if (!resp.ok) {
                    const txt = await resp.text().catch(() => '');
                    M.toast({ html: 'Server save failed; entry saved locally.' });
                    console.warn('Server save failed', resp.status, txt);
                } else {
                    M.toast({ html: 'Saved to your account.' });
                    console.log('Entry saved on server');
                }
            } catch (e) {
                M.toast({ html: 'Network error; entry saved locally.' });
                console.warn('Network error saving entry', e);
            }
        })();

        // render the two extra charts (risk over time, health score trend)
        function renderSmallLine(id, labels, data, color) {
            const el = document.getElementById(id);
            if (!el || typeof Chart === 'undefined') return;
            const ctx = el.getContext('2d');
            if (el._chart) el._chart.destroy();
            el._chart = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets: [{ label: id, data, borderColor: color, fill: false }] },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        }

        try {
            const history = JSON.parse(localStorage.getItem('dailyHealthHistoryV1') || '[]');
            const labels = history.map(h => h.date);
            const riskData = history.map(h => Math.round(h.risk));
            const healthData = history.map(h => h.healthScore);
            renderSmallLine('riskChart', labels, riskData, '#e53935');
            renderSmallLine('healthTrendChart', labels, healthData, '#43a047');
        } catch (err) {
            // ignore
        }
    });

    // show/hide conditional fields
    const smokedRadios = document.querySelectorAll('input[name="smoked24"]');
    smokedRadios.forEach(r => r.addEventListener('change', function () {
        const wrap = document.getElementById('cigarettesWrap');
        if (this.value === 'yes') wrap.style.display = '';
        else wrap.style.display = 'none';
    }));

    const drugsRadios = document.querySelectorAll('input[name="drugs"]');
    drugsRadios.forEach(r => r.addEventListener('change', function () {
        const wrap = document.getElementById('drugTypeWrap');
        if (this.value === 'yes') wrap.style.display = '';
        else wrap.style.display = 'none';
    }));

    // initialize Materialize selects
    var selectElems = document.querySelectorAll('select');
    if (selectElems && selectElems.length && typeof M !== 'undefined' && M.FormSelect) {
        M.FormSelect.init(selectElems);
    }

    // --- History UI & helpers ---
    const historyKey = 'dailyHealthHistoryV1';

    function getHistory() {
        try { return JSON.parse(localStorage.getItem(historyKey) || '[]'); }
        catch (e) { return []; }
    }

    function saveHistory(arr) {
        try { localStorage.setItem(historyKey, JSON.stringify(arr.slice(-60))); }
        catch (e) { console.warn('saveHistory failed', e); }
    }

    function renderHistoryList() {
        const container = document.getElementById('historyListContainer');
        const history = getHistory().slice().reverse(); // newest first
        if (!container) return;
        if (!history.length) {
            container.innerHTML = '<p>No saved entries.</p>';
            return;
        }
        let html = '<table class="striped"><thead><tr><th>Date</th><th>Water L</th><th>Sleep h</th><th>Health</th><th>Risk</th><th>Actions</th></tr></thead><tbody>';
        history.forEach((h, idx) => {
            const id = history.length - 1 - idx; // original index in storage (approx)
            html += `<tr data-idx="${id}"><td>${h.date}</td><td>${h.water}</td><td>${h.sleep}</td><td>${h.healthScore}</td><td>${Math.round(h.risk)}</td><td><a href="#" class="loadEntry" data-date="${h.date}">Load</a> | <a href="#" class="deleteEntry" data-date="${h.date}">Delete</a></td></tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;

        // wire actions
        container.querySelectorAll('.loadEntry').forEach(a => a.addEventListener('click', function (ev) {
            ev.preventDefault();
            const date = this.getAttribute('data-date');
            const entry = getHistory().find(x => x.date === date);
            if (entry) loadEntryIntoForm(entry);
            const hm = M.Modal.getInstance(document.getElementById('historyModal'));
            hm && hm.close();
        }));

        container.querySelectorAll('.deleteEntry').forEach(a => a.addEventListener('click', function (ev) {
            ev.preventDefault();
            const date = this.getAttribute('data-date');
            const arr = getHistory().filter(x => x.date !== date);
            saveHistory(arr);
            renderHistoryList();
        }));
    }

    document.getElementById('viewHistoryBtn') && document.getElementById('viewHistoryBtn').addEventListener('click', function () {
        const elems = document.querySelectorAll('.modal');
        if (elems && elems.length && typeof M !== 'undefined') M.Modal.init(elems);
        const hm = M.Modal.getInstance(document.getElementById('historyModal')) || M.Modal.init(document.getElementById('historyModal'));
        renderHistoryList();
        hm && hm.open();
    });

    // export CSV
    document.getElementById('exportHistoryBtn') && document.getElementById('exportHistoryBtn').addEventListener('click', function (e) {
        const history = getHistory();
        if (!history.length) return;
        const keys = Object.keys(history[0]);
        const csv = [keys.join(',')].concat(history.map(r => keys.map(k => `"${String(r[k] || '')}"`).join(',')).join('\n'));
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'daily_health_history.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });

    // clear history
    document.getElementById('clearHistoryBtn') && document.getElementById('clearHistoryBtn').addEventListener('click', function () {
        try { localStorage.removeItem(historyKey); renderHistoryList(); } catch (e) { }
    });

    function loadEntryIntoForm(entry) {
        try {
            if (!entry) return;
            document.getElementById('entryDate').value = entry.date || '';
            document.getElementById('water').value = entry.water || '';
            document.getElementById('sleep').value = entry.sleep || '';
            document.getElementById('steps').value = entry.steps || '';
            if (entry.smokingPattern) document.getElementById('smokingPattern').value = entry.smokingPattern;
            if (entry.smoked24) {
                const r = document.querySelector(`input[name="smoked24"][value="${entry.smoked24}"]`);
                if (r) r.checked = true;
            }
            if (entry.cigarettes) {
                document.getElementById('cigarettes').value = entry.cigarettes;
                document.getElementById('cigarettesWrap').style.display = '';
            }
            if (entry.feelWeak) {
                const r = document.querySelector(`input[name="feelWeak"][value="${entry.feelWeak}"]`);
                if (r) r.checked = true;
            }
            if (entry.alcoholUnits !== undefined) document.getElementById('alcoholUnits').value = entry.alcoholUnits;
            if (entry.drugsYes) {
                const r = document.querySelector(`input[name="drugs"][value="${entry.drugsYes}"]`);
                if (r) r.checked = true;
                if (entry.drugsYes === 'yes') document.getElementById('drugTypeWrap').style.display = '';
            }
            if (entry.drugType) document.getElementById('drugType').value = entry.drugType;
            // update Materialize fields
            if (typeof M !== 'undefined' && M.updateTextFields) M.updateTextFields();
            // scroll to form
            document.querySelector('html, body').scrollTop = document.getElementById('healthForm').offsetTop;
        } catch (err) { console.warn(err); }
    }

});
