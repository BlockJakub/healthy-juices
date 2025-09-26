// auth.js - small helpers for authentication actions
async function logout() {
    try {
        // prefer POST and include CSRF when available
        const csrf = (window.appState && window.appState.csrfToken) || '';
        await fetch('php/logout.php', { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf }) });
    } catch (e) {
        try {
            await fetch('php/logout.php', { credentials: 'include' });
        } catch (e) {
            console.warn('Logout failed', e);
        }
    }
    window.location.reload();
}

// Export for console usage
window.appAuth = { logout };
