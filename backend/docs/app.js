// Paxyo Documentation App
(function () {
    const $ = s => document.querySelector(s);
    const sidebar = $('#sidebar'), sidebarNav = $('#sidebarNav'), content = $('#content');
    const searchModal = $('#searchModal'), modalInput = $('#modalSearchInput'), searchResults = $('#searchResults');

    // Sidebar toggle
    $('#menuBtn').onclick = () => sidebar.classList.toggle('open');
    $('#sidebarClose').onclick = () => sidebar.classList.remove('open');
    document.addEventListener('click', e => {
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !$('#menuBtn').contains(e.target))
            sidebar.classList.remove('open');
    });

    // Search modal (Ctrl+K)
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
        if (e.key === 'Escape') searchModal.classList.remove('open');
    });
    $('#searchInput').onfocus = () => openSearch();
    searchModal.onclick = e => { if (e.target === searchModal) searchModal.classList.remove('open'); };

    function openSearch() {
        searchModal.classList.add('open');
        modalInput.value = ''; modalInput.focus();
        renderSearchResults('');
    }
    modalInput.oninput = () => renderSearchResults(modalInput.value);

    function renderSearchResults(q) {
        if (!q.trim()) {
            searchResults.innerHTML = DOCS.endpoints.map(ep => `
        <div class="search-result-item" data-id="${ep.id}">
          <span class="method-badge ${ep.method.toLowerCase()}">${ep.method}</span>
          <div><div class="sr-title">${ep.title}</div><div class="sr-desc">${ep.path}</div></div>
        </div>`).join('');
        } else {
            const lq = q.toLowerCase();
            const matches = DOCS.endpoints.filter(ep =>
                ep.title.toLowerCase().includes(lq) || ep.path.toLowerCase().includes(lq) || ep.desc.toLowerCase().includes(lq)
            );
            const dbMatches = DOCS.database.filter(t => t.table.toLowerCase().includes(lq) || t.desc.toLowerCase().includes(lq));
            let html = matches.map(ep => `
        <div class="search-result-item" data-id="${ep.id}">
          <span class="method-badge ${ep.method.toLowerCase()}">${ep.method}</span>
          <div><div class="sr-title">${ep.title}</div><div class="sr-desc">${ep.path}</div></div>
        </div>`).join('');
            html += dbMatches.map(t => `
        <div class="search-result-item" data-id="db-${t.table}">
          <span class="method-badge cli">TABLE</span>
          <div><div class="sr-title">${t.table}</div><div class="sr-desc">${t.desc}</div></div>
        </div>`).join('');
            searchResults.innerHTML = html || '<div style="padding:24px;color:var(--text-muted);text-align:center">No results found</div>';
        }
        searchResults.querySelectorAll('.search-result-item').forEach(el => {
            el.onclick = () => { scrollToId(el.dataset.id); searchModal.classList.remove('open'); };
        });
    }

    function scrollToId(id) {
        const el = document.getElementById(id);
        if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); el.querySelector('.api-card-header')?.click(); }
    }

    // Build Sidebar
    function buildSidebar() {
        const sections = [
            { title: 'Getting Started', items: [{ label: 'Overview', id: 'sec-overview', color: 'var(--accent)' }] },
            { title: 'Authentication', items: DOCS.endpoints.filter(e => e.id.includes('auth')).map(e => ({ label: e.title, id: e.id, color: e.method === 'POST' ? 'var(--post)' : 'var(--get)' })) },
            { title: 'Services', items: DOCS.endpoints.filter(e => e.id.includes('service') || e.id === 'get-recommended').map(e => ({ label: e.title, id: e.id, color: 'var(--get)' })) },
            { title: 'Orders', items: DOCS.endpoints.filter(e => e.id.includes('order') || e.id === 'user-actions' || e.id === 'cron-check').map(e => ({ label: e.title, id: e.id, color: e.method === 'POST' ? 'var(--post)' : e.method === 'CLI' ? 'var(--cli)' : 'var(--get)' })) },
            { title: 'Deposits', items: DOCS.endpoints.filter(e => e.id.includes('deposit')).map(e => ({ label: e.title, id: e.id, color: e.method === 'POST' ? 'var(--post)' : 'var(--get)' })) },
            { title: 'Notifications', items: DOCS.endpoints.filter(e => e.id.includes('alert')).map(e => ({ label: e.title, id: e.id, color: 'var(--get)' })) },
            { title: 'Chat & Realtime', items: DOCS.endpoints.filter(e => e.id.includes('chat') || e.id.includes('realtime') || e.id === 'heartbeat').map(e => ({ label: e.title, id: e.id, color: e.method === 'SSE' ? 'var(--sse)' : e.method === 'POST' ? 'var(--post)' : 'var(--get)' })) },
            { title: 'Webhooks & Routing', items: DOCS.endpoints.filter(e => e.id === 'webhook-handler' || e.id === 'index-router').map(e => ({ label: e.title, id: e.id, color: e.method === 'POST' ? 'var(--post)' : 'var(--get)' })) },
            { title: 'Database', items: [{ label: 'Schema & Tables', id: 'sec-database', color: 'var(--cli)' }, { label: 'Helper Functions', id: 'sec-db-helpers', color: 'var(--accent)' }] },
            {
                title: 'Reference', items: [
                    { label: 'Admin Notifications', id: 'sec-notifications', color: 'var(--sse)' },
                    { label: 'Configuration', id: 'sec-config', color: 'var(--post)' },
                    { label: 'Security', id: 'sec-security', color: '#ff6b6b' },
                    { label: 'Design Patterns', id: 'sec-patterns', color: 'var(--get)' }
                ]
            }
        ];
        sidebarNav.innerHTML = sections.map(s => `
      <div class="nav-section">
        <div class="nav-section-title">${s.title}</div>
        ${s.items.map(i => `<a class="nav-item" data-target="${i.id}"><span class="nav-dot" style="background:${i.color}"></span>${i.label}</a>`).join('')}
      </div>`).join('');
        sidebarNav.querySelectorAll('.nav-item').forEach(el => {
            el.onclick = e => {
                e.preventDefault();
                sidebarNav.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
                el.classList.add('active');
                const target = document.getElementById(el.dataset.target);
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                if (window.innerWidth <= 768) sidebar.classList.remove('open');
            };
        });
    }

    function esc(s) { return s.replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    // Build Content
    function buildContent() {
        let html = '';
        // Overview
        const o = DOCS.overview;
        html += `<div class="section" id="sec-overview">
      <h2 class="section-title">${o.title}</h2>
      <p class="section-desc">${o.desc}</p>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-num">${DOCS.endpoints.length}</div><div class="stat-label">API Endpoints</div></div>
        <div class="stat-card"><div class="stat-num">${DOCS.database.length}</div><div class="stat-label">DB Tables</div></div>
        <div class="stat-card"><div class="stat-num">${DOCS.notifications.length}</div><div class="stat-label">Alert Types</div></div>
        <div class="stat-card"><div class="stat-num">${DOCS.security.length}</div><div class="stat-label">Security Layers</div></div>
      </div>
      <div class="detail-label">Technology Stack</div>
      <table class="schema-table"><thead><tr><th>Layer</th><th>Technology</th></tr></thead><tbody>${o.stack.map(s => `<tr><td>${s.layer}</td><td>${s.tech}</td></tr>`).join('')}</tbody></table>
    </div>`;

        // API Endpoints
        html += '<div class="section"><h2 class="section-title">API Endpoints</h2><p class="section-desc">Complete reference for all backend endpoints, including request/response formats and internal logic.</p>';
        DOCS.endpoints.forEach(ep => {
            html += `<div class="api-card" id="${ep.id}">
        <div class="api-card-header" onclick="this.parentElement.classList.toggle('expanded')">
          <span class="method-badge ${ep.method.toLowerCase()}">${ep.method}</span>
          <span class="endpoint-path">${ep.path}</span>
          <span style="color:var(--text-muted);font-size:13px;margin-right:8px">${ep.title}</span>
          <span class="expand-icon">▾</span>
        </div>
        <div class="api-card-body">
          <p class="detail-text">${ep.desc}</p>
          <div class="detail-label">File</div>
          <p class="detail-text"><code>${ep.file}</code></p>
          <div class="detail-label">Request</div>
          <pre>${esc(ep.request)}</pre>
          <div class="detail-label">Response</div>
          <pre>${esc(ep.response)}</pre>
          <div class="detail-label">Internal Logic</div>
          ${ep.logic.map((l, i) => `<div class="flow-step"><div class="flow-num">${i + 1}</div><div class="flow-text">${l}</div></div>`).join('')}
        </div>
      </div>`;
        });
        html += '</div>';

        // Database
        html += `<div class="section" id="sec-database"><h2 class="section-title">Database Schema</h2><p class="section-desc">All MySQL tables and their column definitions.</p>`;
        DOCS.database.forEach(t => {
            html += `<div class="api-card" id="db-${t.table}">
        <div class="api-card-header" onclick="this.parentElement.classList.toggle('expanded')">
          <span class="method-badge cli">TABLE</span>
          <span class="endpoint-path">${t.table}</span>
          <span style="color:var(--text-muted);font-size:13px;margin-right:8px">${t.desc}</span>
          <span class="expand-icon">▾</span>
        </div>
        <div class="api-card-body">
          <table class="schema-table"><thead><tr><th>Column</th><th>Type</th><th>Description</th></tr></thead>
          <tbody>${t.cols.map(c => `<tr><td>${c[0]}</td><td>${c[1]}</td><td>${c[2]}</td></tr>`).join('')}</tbody></table>
        </div>
      </div>`;
        });
        html += '</div>';

        // DB Helpers
        const dh = DOCS.dbHelpers;
        html += `<div class="section" id="sec-db-helpers"><h2 class="section-title">${dh.title}</h2>
      <p class="section-desc">Convenience wrapper functions defined in db.php for common CRUD operations.</p>`;
        dh.funcs.forEach(f => {
            html += `<div class="api-card">
        <div class="api-card-header" onclick="this.parentElement.classList.toggle('expanded')">
          <span class="method-badge" style="background:var(--accent-glow);color:var(--accent)">FN</span>
          <span class="endpoint-path">${f.name}</span>
          <span class="expand-icon">▾</span>
        </div>
        <div class="api-card-body"><p class="detail-text">${f.desc}</p><pre>${esc(f.example)}</pre></div>
      </div>`;
        });
        html += '</div>';

        // Notifications
        html += `<div class="section" id="sec-notifications"><h2 class="section-title">Admin Notification Types</h2>
      <p class="section-desc">Sent via notify_bot_admin() to the Node.js bot backend (BOT_API_URL).</p>
      <table class="schema-table"><thead><tr><th>Type</th><th>Fields</th><th>When</th></tr></thead>
      <tbody>${DOCS.notifications.map(n => `<tr><td>${n.type}</td><td>${n.fields}</td><td>${n.when}</td></tr>`).join('')}</tbody></table></div>`;

        // Config
        html += `<div class="section" id="sec-config"><h2 class="section-title">Configuration</h2>
      <p class="section-desc">Environment variables and configuration values required for deployment.</p>
      <table class="schema-table"><thead><tr><th>Key</th><th>File</th><th>Purpose</th></tr></thead>
      <tbody>${DOCS.config.map(c => `<tr><td>${c.key}</td><td>${c.file}</td><td>${c.purpose}</td></tr>`).join('')}</tbody></table></div>`;

        // Security
        html += `<div class="section" id="sec-security"><h2 class="section-title">Security</h2>
      <p class="section-desc">All security measures implemented across the application.</p>
      ${DOCS.security.map((s, i) => `<div class="flow-step"><div class="flow-num">${i + 1}</div><div class="flow-text">${s}</div></div>`).join('')}</div>`;

        // Patterns
        html += `<div class="section" id="sec-patterns"><h2 class="section-title">Design Patterns</h2>
      <p class="section-desc">Performance optimizations and architectural patterns used throughout the codebase.</p>`;
        DOCS.patterns.forEach(p => {
            html += `<div class="api-card"><div class="api-card-header" onclick="this.parentElement.classList.toggle('expanded')">
        <span class="method-badge" style="background:rgba(0,184,148,.12);color:var(--get)">⚡</span>
        <span class="endpoint-path">${p.name}</span><span class="expand-icon">▾</span>
      </div><div class="api-card-body"><p class="detail-text">${p.desc}</p></div></div>`;
        });
        html += '</div>';

        content.innerHTML = html;
        $('#methodCount').textContent = `${DOCS.endpoints.length} Endpoints`;
    }

    buildSidebar();
    buildContent();
})();
