/**
 * File: assets/app.js
 * Author: Healthy Blog Team
 * Created: 2025-09-25
 * Description: Global UI enhancements (Materialize init, lazy load, search, modals, UX polish).
 */

document.addEventListener('DOMContentLoaded', function () {
    // ======== 1. Materialize Components ========
    M.Sidenav.init(document.querySelectorAll('.sidenav'));
    M.Collapsible.init(document.querySelectorAll('.collapsible'));
    M.Tooltip.init(document.querySelectorAll('.tooltipped'));
    // Initialize parallax on all pages (applies to any element with .parallax)
    M.Parallax.init(document.querySelectorAll('.parallax'), {
        // options left intentionally empty; adjust speed/behavior here if needed
    });
    M.Modal.init(document.querySelectorAll('.modal'), { opacity: 0.9 });

    // ======== 2. Lazy Load Images ========
    const lazyImages = document.querySelectorAll('img[data-src]');
    const lazyObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                lazyObserver.unobserve(img);
            }
        });
    });
    lazyImages.forEach(img => lazyObserver.observe(img));

    // ======== 3. Card Hover Effects ========
    const cards = document.querySelectorAll('.article-card .card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-6px)';
            card.style.boxShadow = '0 12px 25px rgba(0,0,0,0.18)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
            card.style.boxShadow = '';
        });
    });

    // ======== 4. Modal Preview on Image Click ========
    const cardImages = document.querySelectorAll('.article-card .card-image img');
    cardImages.forEach(img => {
        img.addEventListener('click', () => {
            const modalContent = document.getElementById('modal-content');
            if (modalContent) {
                modalContent.innerHTML = `<img src="${img.src}" class="responsive-img">`;
                const modalElem = document.getElementById('imageModal');
                const instance = M.Modal.getInstance(modalElem);
                instance.open();
            }
        });
    });

    // ======== 5. Live Search ========
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const cards = document.querySelectorAll('.article-card');
            cards.forEach(card => {
                const title = card.dataset.title.toLowerCase();
                const excerpt = card.dataset.excerpt.toLowerCase();
                card.style.display = title.includes(query) || excerpt.includes(query) ? '' : 'none';
            });
        });
    }

    // ======== 6. Chart.js Example ========
    if (document.getElementById('hydrationChart')) {
        const ctx = document.getElementById('hydrationChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Water', 'Juices', 'Other Drinks'],
                datasets: [{
                    data: [60, 25, 15],
                    backgroundColor: ['#42a5f5', '#ffca28', '#66bb6a']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Daily Healthy Drink Intake (%)' }
                }
            }
        });
    }
});
// Fetch CSRF token and update nav links (no external whoami dependency)
document.addEventListener('DOMContentLoaded', async function () {
    try {
        // get CSRF token for this session
        const tokenResp = await fetch('php/csrf.php', { credentials: 'include' });
        if (tokenResp.ok) {
            const tj = await tokenResp.json();
            window.appState = window.appState || {};
            window.appState.csrfToken = tj.csrf_token || '';
        }
        // Optional: try to detect auth by requesting whoami.php but allow it to fail
        let j = { ok: false };
        try {
            const r = await fetch('php/whoami.php', { credentials: 'include' });
            if (r.ok) j = await r.json();
        } catch (e) { /* ignore */ }
        const navUl = document.getElementById('nav-mobile');
        if (!navUl) return;
        // remove any fallback auth list items
        navUl.querySelectorAll('.auth-fallback').forEach(n => n.remove());
        if (j.ok && j.user) {
            // logged in: show dashboard and logout without using innerHTML
            const liDash = document.createElement('li');
            const aDash = document.createElement('a');
            aDash.className = 'hoverable';
            aDash.href = 'php/dashboard.php';
            aDash.textContent = 'Dashboard';
            liDash.appendChild(aDash);

            const liLogout = document.createElement('li');
            const aLogout = document.createElement('a');
            aLogout.className = 'hoverable';
            aLogout.href = '#';
            aLogout.id = 'logoutBtn';
            aLogout.textContent = 'Logout';
            liLogout.appendChild(aLogout);

            navUl.appendChild(liDash);
            navUl.appendChild(liLogout);

            // mobile
            const mobileAuth = document.getElementById('mobileAuthLinks');
            if (mobileAuth) {
                while (mobileAuth.firstChild) mobileAuth.removeChild(mobileAuth.firstChild);
                const mDash = document.createElement('a');
                mDash.href = 'php/dashboard.php';
                mDash.textContent = 'Dashboard';
                const br = document.createElement('br');
                const mLogout = document.createElement('a');
                mLogout.href = '#';
                mLogout.id = 'mobileLogout';
                mLogout.textContent = 'Logout';
                mobileAuth.appendChild(mDash);
                mobileAuth.appendChild(br);
                mobileAuth.appendChild(mLogout);
            }
        } else {
            // logged out: show login/register without innerHTML
            const liLogin = document.createElement('li');
            const aLogin = document.createElement('a');
            aLogin.className = 'hoverable';
            aLogin.href = 'php/login.html';
            aLogin.textContent = 'Login';
            liLogin.appendChild(aLogin);

            const liRegister = document.createElement('li');
            const aRegister = document.createElement('a');
            aRegister.className = 'hoverable';
            aRegister.href = 'php/register.html';
            aRegister.textContent = 'Register';
            liRegister.appendChild(aRegister);

            navUl.appendChild(liLogin);
            navUl.appendChild(liRegister);

            const mobileAuth = document.getElementById('mobileAuthLinks');
            if (mobileAuth) {
                while (mobileAuth.firstChild) mobileAuth.removeChild(mobileAuth.firstChild);
                const mLogin = document.createElement('a');
                mLogin.href = 'php/login.html';
                mLogin.textContent = 'Login';
                const br = document.createElement('br');
                const mRegister = document.createElement('a');
                mRegister.href = 'php/register.html';
                mRegister.textContent = 'Register';
                mobileAuth.appendChild(mLogin);
                mobileAuth.appendChild(br);
                mobileAuth.appendChild(mRegister);
            }
        }
        // attach logout handler
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const csrf = (window.appState && window.appState.csrfToken) || '';
                await fetch('php/logout.php', { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf }) });
                window.location.reload();
            });
        }
        const mobileLogout = document.getElementById('mobileLogout');
        if (mobileLogout) {
            mobileLogout.addEventListener('click', async (e) => {
                e.preventDefault();
                const csrf = (window.appState && window.appState.csrfToken) || '';
                await fetch('php/logout.php', { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf }) });
                window.location.reload();
            });
        }
    } catch (err) {
        // non-fatal
        console.warn('whoami check failed', err);
    }
});
