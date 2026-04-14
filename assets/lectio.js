/**
 * Via Bible – Lectio Continua
 * Tout le state est dans localStorage. Aucun login requis.
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'via_bible_lectio_v1';
  const cfg = window.VBL;

  // ── State ──────────────────────────────────────────────────────────────────
  let state = loadState();
  let currentPlanData = null; // jours du plan actif, chargés via AJAX

  // ── DOM refs ───────────────────────────────────────────────────────────────
  const $app         = document.getElementById('vbl-app');
  const $selector    = document.getElementById('vbl-plan-selector');
  const $reader      = document.getElementById('vbl-reader');
  const $plansGrid   = document.getElementById('vbl-plans-grid');
  const $backBtn     = document.getElementById('vbl-back-btn');
  const $planTitle   = document.getElementById('vbl-plan-title');
  const $streak      = document.getElementById('vbl-streak');
  const $progressBar = document.getElementById('vbl-progress-bar');
  const $progressLbl = document.getElementById('vbl-progress-label');
  const $dayLabel    = document.getElementById('vbl-day-label');
  const $prevDay     = document.getElementById('vbl-prev-day');
  const $nextDay     = document.getElementById('vbl-next-day');
  const $passages    = document.getElementById('vbl-passages');
  const $articles    = document.getElementById('vbl-articles');
  const $markRead    = document.getElementById('vbl-mark-read');
  const $exportBtn   = document.getElementById('vbl-export');
  const $importFile  = document.getElementById('vbl-import-file');
  const $resetBtn    = document.getElementById('vbl-reset');

  // ── Init ───────────────────────────────────────────────────────────────────
  function init() {
    renderPlansGrid();

    // Si un plan est déjà actif, ouvrir directement le lecteur
    const defaultPlan = $app.dataset.defaultPlan;
    if (state.activePlan) {
      openPlan(state.activePlan);
    } else if (defaultPlan && cfg.plans[defaultPlan]) {
      openPlan(defaultPlan);
    }

    // Events
    $backBtn.addEventListener('click', showSelector);
    $prevDay.addEventListener('click', () => navigateDay(-1));
    $nextDay.addEventListener('click', () => navigateDay(1));
    $markRead.addEventListener('click', markTodayRead);
    $exportBtn.addEventListener('click', exportProgression);
    $importFile.addEventListener('change', importProgression);
    $resetBtn.addEventListener('click', resetProgression);
  }

  // ── Rendu grille des plans ─────────────────────────────────────────────────
  function renderPlansGrid() {
    $plansGrid.innerHTML = '';
    Object.values(cfg.plans).forEach(plan => {
      const prog = getProgress(plan.id);
      const pct  = plan.days > 0 ? Math.round((prog.completedDays / plan.days) * 100) : 0;
      const isActive = state.activePlan === plan.id;

      const card = document.createElement('button');
      card.className = 'vbl-plan-card' + (isActive ? ' vbl-active-plan' : '');
      card.setAttribute('aria-label', 'Démarrer le plan ' + plan.name);
      card.innerHTML = `
        <div class="vbl-plan-card-name">${escHtml(plan.name)}</div>
        <div class="vbl-plan-card-desc">${escHtml(plan.description)}</div>
        <div class="vbl-plan-card-meta">
          <span class="vbl-badge">${plan.days} jours</span>
          <span class="vbl-badge">${plan.readings_per_day}×/jour</span>
          ${isActive ? '<span class="vbl-badge vbl-badge-primary">En cours</span>' : ''}
        </div>
        ${prog.completedDays > 0 ? `
        <div class="vbl-plan-progress-mini">
          <div class="vbl-plan-progress-mini-fill" style="width:${pct}%"></div>
        </div>
        ` : ''}
      `;
      card.addEventListener('click', () => openPlan(plan.id));
      $plansGrid.appendChild(card);
    });
  }

  // ── Ouvrir un plan ─────────────────────────────────────────────────────────
  function openPlan(planId) {
    const meta = cfg.plans[planId];
    if (!meta) return;

    state.activePlan = planId;
    saveState();

    $planTitle.textContent = meta.name;
    $selector.style.display = 'none';
    $reader.style.display   = '';

    // Charger les données du plan si pas déjà en mémoire
    if (currentPlanData && state._loadedPlan === planId) {
      renderDay();
    } else {
      $passages.innerHTML = `<div class="vbl-articles-loading">${cfg.i18n.loading}</div>`;
      $articles.innerHTML = '';

      const fd = new FormData();
      fd.append('action',  'vbl_get_plan');
      fd.append('nonce',   cfg.nonce);
      fd.append('plan_id', planId);

      fetch(cfg.ajaxUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
          if (resp.success) {
            currentPlanData = resp.data.days;
            state._loadedPlan = planId;
            renderDay();
          } else {
            $passages.innerHTML = '<p>Erreur lors du chargement du plan.</p>';
          }
        })
        .catch(() => {
          $passages.innerHTML = '<p>Erreur réseau.</p>';
        });
    }
  }

  // ── Rendu du jour courant ──────────────────────────────────────────────────
  function renderDay() {
    if (!currentPlanData) return;

    const planId   = state.activePlan;
    const meta     = cfg.plans[planId];
    const prog     = getProgress(planId);
    const dayIndex = prog.currentDay; // 0-based
    const totalDays = meta.days;
    const dayData  = currentPlanData[dayIndex] || [];

    // Progression
    const pct = totalDays > 0 ? Math.round((prog.completedDays / totalDays) * 100) : 0;
    $progressBar.style.width = pct + '%';
    $progressLbl.textContent = `${prog.completedDays} / ${totalDays} jours — ${pct}%`;

    // Streak
    const streakVal = computeStreak(planId);
    $streak.textContent = streakVal > 0
      ? `🔥 ${streakVal} ${cfg.i18n.days} de suite`
      : '';

    // Navigation
    $dayLabel.textContent = `Jour ${dayIndex + 1} / ${totalDays}`;
    $prevDay.disabled = dayIndex === 0;
    $nextDay.disabled = dayIndex >= totalDays - 1;

    // Bouton "Lu"
    const isRead = prog.readDays.includes(dayIndex);
    $markRead.textContent = isRead ? cfg.i18n.markedRead : cfg.i18n.markRead;
    $markRead.classList.toggle('vbl-btn-done', isRead);
    $markRead.disabled = isRead;

    // Passages
    $passages.innerHTML = '';
    const passages = Array.isArray(dayData) ? dayData : [dayData];
    passages.forEach((p, i) => {
      const icons = ['📖', '✝️', '🙏', '💡'];
      const div = document.createElement('div');
      div.className = 'vbl-passage-item';
      div.innerHTML = `<span class="vbl-passage-icon">${icons[i] || '📜'}</span>
                       <span class="vbl-passage-text">${escHtml(p)}</span>`;
      $passages.appendChild(div);
    });

    // Articles via.bible
    loadArticles(passages);
  }

  // ── Charger articles via AJAX ──────────────────────────────────────────────
  function loadArticles(passages) {
    $articles.innerHTML = `<div class="vbl-articles-loading">${cfg.i18n.loading}</div>`;

    const fd = new FormData();
    fd.append('action', 'vbl_get_articles');
    fd.append('nonce',  cfg.nonce);
    passages.forEach(p => fd.append('passages[]', p));

    fetch(cfg.ajaxUrl, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        $articles.innerHTML = '';
        if (!resp.success || !resp.data.length) {
          $articles.innerHTML = `<p class="vbl-no-articles">${cfg.i18n.noArticles}</p>`;
          return;
        }
        const title = document.createElement('div');
        title.className = 'vbl-articles-title';
        title.textContent = cfg.i18n.articles;
        $articles.appendChild(title);

        const list = document.createElement('div');
        list.className = 'vbl-articles-list';

        resp.data.forEach(article => {
          const a = document.createElement('a');
          a.className = 'vbl-article-item';
          a.href = article.url;
          a.target = '_blank';
          a.rel = 'noopener noreferrer';

          const typeClass = article.article_type.toLowerCase().includes('comprend')
            ? 'vbl-type-comprendre'
            : article.article_type.toLowerCase().includes('médit') || article.article_type.toLowerCase().includes('medit')
              ? 'vbl-type-mediter'
              : 'vbl-type-other';

          a.innerHTML = `
            ${article.article_type
              ? `<span class="vbl-article-type ${typeClass}">${escHtml(article.article_type)}</span>`
              : ''}
            <span>${escHtml(article.title)}</span>
          `;
          list.appendChild(a);
        });
        $articles.appendChild(list);
      })
      .catch(() => {
        $articles.innerHTML = `<p class="vbl-no-articles">${cfg.i18n.noArticles}</p>`;
      });
  }

  // ── Navigation ─────────────────────────────────────────────────────────────
  function navigateDay(delta) {
    const planId = state.activePlan;
    const meta   = cfg.plans[planId];
    const prog   = getProgress(planId);

    const newDay = prog.currentDay + delta;
    if (newDay < 0 || newDay >= meta.days) return;

    state.plans[planId].currentDay = newDay;
    saveState();
    renderDay();
    window.scrollTo({ top: $app.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
  }

  // ── Marquer jour comme lu ─────────────────────────────────────────────────
  function markTodayRead() {
    const planId   = state.activePlan;
    const prog     = getProgress(planId);
    const dayIndex = prog.currentDay;

    if (!state.plans[planId].readDays.includes(dayIndex)) {
      state.plans[planId].readDays.push(dayIndex);
      state.plans[planId].completedDays = state.plans[planId].readDays.length;
      state.plans[planId].lastReadDate  = todayStr();
    }

    // Auto-avancer au jour suivant
    const meta = cfg.plans[planId];
    if (dayIndex + 1 < meta.days) {
      state.plans[planId].currentDay = dayIndex + 1;
    }

    saveState();
    renderPlansGrid();
    renderDay();
    toast('✓ Passage lu !');
  }

  // ── Streak ─────────────────────────────────────────────────────────────────
  function computeStreak(planId) {
    const prog = getProgress(planId);
    if (!prog.lastReadDate) return 0;

    const today     = todayStr();
    const yesterday = dateStr(-1);
    if (prog.lastReadDate !== today && prog.lastReadDate !== yesterday) return 0;

    // Compter jours consécutifs depuis la fin
    let streak  = 0;
    let checked = new Date();
    const readSet = new Set(prog.readDays);
    // Approximation : on compte les jours consécutifs de readDays en partant de la fin
    const sorted = [...prog.readDays].sort((a,b) => b-a);
    let expected = sorted[0];
    for (const d of sorted) {
      if (d === expected) { streak++; expected--; }
      else break;
    }
    return streak;
  }

  // ── Export progression ─────────────────────────────────────────────────────
  function exportProgression() {
    const blob = new Blob([JSON.stringify(state, null, 2)], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'via-bible-lectio-progression.json';
    a.click();
    URL.revokeObjectURL(url);
    toast('⬇ Fichier téléchargé !');
  }

  // ── Import progression ─────────────────────────────────────────────────────
  function importProgression(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(ev) {
      try {
        const imported = JSON.parse(ev.target.result);
        if (typeof imported !== 'object' || !imported.plans) throw new Error();
        state = imported;
        saveState();
        renderPlansGrid();
        if (state.activePlan) {
          currentPlanData = null;
          openPlan(state.activePlan);
        }
        toast(cfg.i18n.importOk);
      } catch {
        toast('⚠ ' + cfg.i18n.importFail, true);
      }
    };
    reader.readAsText(file);
    e.target.value = ''; // reset input
  }

  // ── Reset ──────────────────────────────────────────────────────────────────
  function resetProgression() {
    if (!confirm(cfg.i18n.resetConfirm)) return;
    state = defaultState();
    currentPlanData = null;
    saveState();
    showSelector();
    renderPlansGrid();
    toast('↺ Progression réinitialisée.');
  }

  // ── Afficher sélecteur ─────────────────────────────────────────────────────
  function showSelector() {
    $reader.style.display   = 'none';
    $selector.style.display = '';
    renderPlansGrid();
  }

  // ── State helpers ──────────────────────────────────────────────────────────
  function getProgress(planId) {
    if (!state.plans[planId]) {
      state.plans[planId] = {
        currentDay:    0,
        completedDays: 0,
        readDays:      [],
        lastReadDate:  null,
        startDate:     null,
      };
    }
    return state.plans[planId];
  }

  function defaultState() {
    return { activePlan: null, plans: {}, _loadedPlan: null };
  }

  function loadState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (typeof parsed === 'object' && parsed.plans) return parsed;
      }
    } catch {}
    return defaultState();
  }

  function saveState() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (e) {
      // localStorage indisponible (mode privé strict) — on continue sans crash
      console.warn('Via Bible Lectio : localStorage indisponible.', e);
    }
  }

  // ── Utils ──────────────────────────────────────────────────────────────────
  function todayStr() {
    return new Date().toISOString().slice(0, 10);
  }
  function dateStr(offsetDays) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    return d.toISOString().slice(0, 10);
  }
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
  function toast(msg, isError = false) {
    const existing = document.querySelector('.vbl-toast');
    if (existing) existing.remove();

    const el = document.createElement('div');
    el.className = 'vbl-toast';
    el.style.background = isError ? '#a12c7b' : '';
    el.textContent = msg;
    document.body.appendChild(el);

    setTimeout(() => {
      el.classList.add('vbl-toast-hide');
      setTimeout(() => el.remove(), 500);
    }, 2800);
  }

  // ── Lancer ─────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', init);
  // Si le DOM est déjà prêt (script en footer)
  if (document.readyState !== 'loading') init();

})();
