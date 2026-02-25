//  Mouse-follow Border Glow on Cards 
document.addEventListener('mousemove', e => {
    document.querySelectorAll('.tilt-card').forEach(card => {
        const r = card.getBoundingClientRect();
        const x = e.clientX - r.left;
        const y = e.clientY - r.top;
        card.style.setProperty('--mouse-x', x + 'px');
        card.style.setProperty('--mouse-y', y + 'px');
        // 3D tilt
        const inRange = e.clientX >= r.left - 40 && e.clientX <= r.right + 40 && e.clientY >= r.top - 40 && e.clientY <= r.bottom + 40;
        if (inRange) {
            const rx = ((e.clientX - r.left) / r.width - 0.5) * 5;
            const ry = ((e.clientY - r.top) / r.height - 0.5) * 5;
            card.style.transform = `perspective(800px) rotateY(${rx}deg) rotateX(${-ry}deg) scale(1.01)`;
        } else {
            card.style.transform = 'perspective(800px) rotateY(0) rotateX(0) scale(1)';
        }
    });
});

//  Magnetic Hover for Buttons 
document.querySelectorAll('.magnetic').forEach(el => {
    el.addEventListener('mousemove', e => {
        const r = el.getBoundingClientRect();
        const x = (e.clientX - r.left - r.width / 2) * 0.3;
        const y = (e.clientY - r.top - r.height / 2) * 0.3;
        el.style.transform = `translate(${x}px, ${y}px)`;
    });
    el.addEventListener('mouseleave', () => { el.style.transform = 'translate(0, 0)'; });
});

//  Ripple on Click 
document.querySelectorAll('.ripple-container').forEach(el => {
    el.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        ripple.classList.add('ripple');
        const r = this.getBoundingClientRect();
        const size = Math.max(r.width, r.height);
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - r.left - size / 2) + 'px';
        ripple.style.top = (e.clientY - r.top - size / 2) + 'px';
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});

//  Floating Particles 
(function() {
    const c = document.getElementById('particles');
    for (let i = 0; i < 6; i++) {
        const p = document.createElement('div');
        p.classList.add('particle');
        const s = Math.random() * 4 + 2;
        p.style.width = p.style.height = s + 'px';
        p.style.left = Math.random() * 100 + '%';
        p.style.top = Math.random() * 100 + '%';
        p.style.opacity = Math.random() * 0.5 + 0.1;
        p.style.animation = `float ${Math.random() * 4 + 4}s ease-in-out infinite`;
        p.style.animationDelay = Math.random() * 3 + 's';
        c.appendChild(p);
    }
})();

//  DMC Quick Search 
(function() {
    const input = document.getElementById('dmcSearch');
    const spinner = document.getElementById('dmcSpinner');
    if (!input) return;
    input.addEventListener('keydown', async function(e) {
        if (e.key !== 'Enter') return;
        const dmc = this.value.trim();
        if (!dmc) { Swal.fire({icon:'warning',title:'Please enter DMC',text:'Enter DMC number to search',background:'#0f172a',color:'#e2e8f0',confirmButtonColor:'#6366f1'}); return; }
        spinner.classList.remove('hidden');
        try {
            const res = await fetch('api/search_dmc.php?dmc=' + encodeURIComponent(dmc));
            const json = await res.json();
            spinner.classList.add('hidden');
            if (!json.success) { Swal.fire({icon:'info',title:'Not Found',text:json.message||'No DMC found',background:'#0f172a',color:'#e2e8f0',confirmButtonColor:'#6366f1'}); return; }
            let html = '<div style="max-height:400px;overflow-y:auto;"><table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">';
            html += '<thead><tr style="border-bottom:2px solid #334155;"><th style="padding:8px 10px;color:#94a3b8;">Date</th><th style="padding:8px 10px;color:#94a3b8;">Inspector</th><th style="padding:8px 10px;color:#94a3b8;">Method</th><th style="padding:8px 10px;color:#94a3b8;">Result</th></tr></thead><tbody>';
            json.data.forEach(row => {
                const date = row.receive_date ? new Date(row.receive_date).toLocaleDateString('th-TH',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-';
                const badge = row.judgement==='OK' ? '<span style="background:rgba(52,211,153,0.15);color:#34d399;padding:2px 10px;border-radius:20px;font-weight:600;font-size:12px;">OK</span>' : row.judgement==='NG' ? '<span style="background:rgba(248,113,113,0.15);color:#f87171;padding:2px 10px;border-radius:20px;font-weight:600;font-size:12px;">NG</span>' : '<span style="color:#94a3b8;">Pending</span>';
                html += '<tr style="border-bottom:1px solid #1e293b;"><td style="padding:8px 10px;color:#cbd5e1;white-space:nowrap;">'+date+'</td><td style="padding:8px 10px;color:#e2e8f0;">'+(row.inspector_name||'-')+'</td><td style="padding:8px 10px;color:#94a3b8;">'+(row.method_name||'-')+'</td><td style="padding:8px 10px;">'+badge+'</td></tr>';
            });
            html += '</tbody></table></div>';
            Swal.fire({title:'<span style="color:#a5b4fc;">DMC: '+dmc+'</span>',html:html,width:640,background:'#0f172a',color:'#e2e8f0',showCloseButton:true,showConfirmButton:false,customClass:{popup:'rounded-2xl border border-slate-800'}});
        } catch(err) { spinner.classList.add('hidden'); Swal.fire({icon:'error',title:'Error',text:'Search failed',background:'#0f172a',color:'#e2e8f0',confirmButtonColor:'#6366f1'}); }
    });
})();

//  Light/Dark Mode Toggle 
(function() {
    const toggle = document.getElementById('themeToggle');
    const iconDark = document.getElementById('themeIconDark');
    const iconLight = document.getElementById('themeIconLight');
    if (!toggle) return;
    
    // Load saved theme
    if (localStorage.getItem('qc-theme') === 'light') {
        document.body.classList.add('light-mode');
        iconDark.classList.add('hidden');
        iconLight.classList.remove('hidden');
    }
    
    toggle.addEventListener('click', function() {
        document.body.classList.toggle('light-mode');
        const isLight = document.body.classList.contains('light-mode');
        localStorage.setItem('qc-theme', isLight ? 'light' : 'dark');
        iconDark.classList.toggle('hidden', isLight);
        iconLight.classList.toggle('hidden', !isLight);
    });
})();