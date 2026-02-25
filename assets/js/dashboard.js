// Relative Time for Recent Activities
(function() {
    function timeAgo(dateStr) {
        const now = new Date();
        const past = new Date(dateStr);
        const diffMs = now - past;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHr = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHr / 24);

        if (diffSec < 60) return 'just now';
        if (diffMin < 60) return diffMin + ' min' + (diffMin > 1 ? 's' : '') + ' ago';
        if (diffHr < 24) return diffHr + ' hr' + (diffHr > 1 ? 's' : '') + ' ago';
        if (diffDay < 7) return diffDay + ' day' + (diffDay > 1 ? 's' : '') + ' ago';
        return past.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
    }

    document.querySelectorAll('.relative-time').forEach(el => {
        const t = el.getAttribute('data-time');
        if (t) {
            el.textContent = timeAgo(t);
            el.title = new Date(t).toLocaleString('th-TH');
        }
    });
})();