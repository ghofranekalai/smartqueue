/**
 * SmartQueue – smartqueue.js
 * Fonctions JavaScript utilisant Bootstrap 5 + CSS3
 * Technologies autorisées : PHP + PDO + MySQL + Bootstrap 5 + Layout header/footer
 * ============================================================ */

"use strict";

/* ============================================================
   1. INITIALISATION GLOBALE
   ============================================================ */
document.addEventListener("DOMContentLoaded", () => {
  SQ.init();
});

const SQ = {

  init() {
    this.initTooltips();
    this.initAutoAlerts();
    this.initFadeIn();
    this.initCounters();
    this.initTableSearch();
    this.initConfirmDelete();
    this.initBackTop();
    this.initLiveTimers();
    this.initProgressBars();
    this.initQueueRefresh();
    this.initFormValidation();
    this.initToastQueue();
  },

  /* ----------------------------------------------------------
     2. TOOLTIPS Bootstrap 5 (sur tous les [data-bs-toggle="tooltip"])
  ---------------------------------------------------------- */
  initTooltips() {
    const els = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    els.forEach(el => new bootstrap.Tooltip(el, { trigger: "hover" }));
  },

  /* ----------------------------------------------------------
     3. AUTO-DISMISS DES ALERTES (après 5 s)
  ---------------------------------------------------------- */
  initAutoAlerts() {
    document.querySelectorAll(".alert-dismissible").forEach(alertEl => {
      // Bootstrap Alert instance
      const bsAlert = new bootstrap.Alert(alertEl);
      setTimeout(() => {
        try { bsAlert.close(); } catch (_) {}
      }, 5000);
    });
  },

  /* ----------------------------------------------------------
     4. FADE-IN AU SCROLL (Intersection Observer)
  ---------------------------------------------------------- */
  initFadeIn() {
    const targets = document.querySelectorAll(".sq-fade-in");
    if (!targets.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    targets.forEach(el => observer.observe(el));
  },

  /* ----------------------------------------------------------
     5. COMPTEURS ANIMÉS (.sq-counter[data-target="X"])
  ---------------------------------------------------------- */
  initCounters() {
    document.querySelectorAll(".sq-counter").forEach(el => {
      const target  = parseInt(el.dataset.target ?? el.textContent, 10);
      const duration = parseInt(el.dataset.duration ?? 1200, 10);
      const step     = Math.ceil(target / (duration / 16));
      let current    = 0;

      const tick = () => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString("fr-FR");
        if (current < target) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    });
  },

  /* ----------------------------------------------------------
     6. RECHERCHE EN TEMPS RÉEL DANS LES TABLEAUX
        Usage : <input data-sq-search="tableId">
  ---------------------------------------------------------- */
  initTableSearch() {
    document.querySelectorAll("[data-sq-search]").forEach(input => {
      const tableId = input.dataset.sqSearch;
      const table   = document.getElementById(tableId);
      if (!table) return;

      input.addEventListener("input", () => {
        const q    = input.value.toLowerCase().trim();
        const rows = table.querySelectorAll("tbody tr");

        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(q) ? "" : "none";

          // Surbrillance simple
          row.querySelectorAll("td").forEach(td => {
            td.innerHTML = td.textContent; // reset
            if (q && td.textContent.toLowerCase().includes(q)) {
              td.innerHTML = td.textContent.replace(
                new RegExp(`(${q})`, "gi"),
                `<mark class="sq-highlight">$1</mark>`
              );
            }
          });
        });

        // Afficher un message "Aucun résultat"
        const tbody = table.querySelector("tbody");
        const noResult = table.parentElement.querySelector(".sq-no-result");
        const visible = [...rows].some(r => r.style.display !== "none");
        if (noResult) noResult.style.display = visible ? "none" : "";
      });
    });
  },

  /* ----------------------------------------------------------
     7. MODAL DE CONFIRMATION SUPPRESSION
        Usage : <button data-sq-confirm="Êtes-vous sûr ?" data-sq-action="/url">
  ---------------------------------------------------------- */
  initConfirmDelete() {
    // Créer le modal une seule fois
    if (!document.getElementById("sqConfirmModal")) {
      document.body.insertAdjacentHTML("beforeend", `
        <div class="modal fade" id="sqConfirmModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
              <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body" id="sqConfirmBody">Voulez-vous vraiment supprimer cet élément ?</div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a id="sqConfirmBtn" href="#" class="btn btn-danger">
                  <i class="bi bi-trash me-1"></i>Supprimer
                </a>
              </div>
            </div>
          </div>
        </div>`);
    }

    const modal    = new bootstrap.Modal(document.getElementById("sqConfirmModal"));
    const bodyEl   = document.getElementById("sqConfirmBody");
    const actionEl = document.getElementById("sqConfirmBtn");

    document.querySelectorAll("[data-sq-confirm]").forEach(btn => {
      btn.addEventListener("click", e => {
        e.preventDefault();
        bodyEl.textContent   = btn.dataset.sqConfirm || "Confirmer la suppression ?";
        actionEl.href        = btn.dataset.sqAction  || "#";
        modal.show();
      });
    });
  },

  /* ----------------------------------------------------------
     8. BOUTON RETOUR EN HAUT DE PAGE
  ---------------------------------------------------------- */
  initBackTop() {
    const btn = document.createElement("button");
    btn.id = "sq-back-top";
    btn.title = "Retour en haut";
    btn.innerHTML = `<i class="bi bi-arrow-up"></i>`;
    document.body.appendChild(btn);

    window.addEventListener("scroll", () => {
      btn.classList.toggle("visible", window.scrollY > 300);
    });
    btn.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));
  },

  /* ----------------------------------------------------------
     9. MINUTEURS EN DIRECT (tickets "appele" / "en_service")
        Usage : <span class="sq-live-timer" data-since="2025-04-25T10:00:00">
  ---------------------------------------------------------- */
  initLiveTimers() {
    const timers = document.querySelectorAll(".sq-live-timer[data-since]");
    if (!timers.length) return;

    const pad = n => String(n).padStart(2, "0");

    const tick = () => {
      const now = Date.now();
      timers.forEach(el => {
        const since = new Date(el.dataset.since).getTime();
        const diff  = Math.floor((now - since) / 1000);
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        el.textContent = h > 0
          ? `${pad(h)}:${pad(m)}:${pad(s)}`
          : `${pad(m)}:${pad(s)}`;
      });
    };

    tick();
    setInterval(tick, 1000);
  },

  /* ----------------------------------------------------------
     10. BARRES DE PROGRESSION ANIMÉES
         Usage : <div class="progress"><div class="sq-progress-bar progress-bar"
                 data-value="75" role="progressbar"></div></div>
  ---------------------------------------------------------- */
  initProgressBars() {
    document.querySelectorAll(".sq-progress-bar[data-value]").forEach(bar => {
      setTimeout(() => {
        bar.style.width = bar.dataset.value + "%";
        bar.setAttribute("aria-valuenow", bar.dataset.value);
      }, 300);
    });
  },

  /* ----------------------------------------------------------
     11. RAFRAÎCHISSEMENT AUTO DU NOMBRE EN ATTENTE (AJAX-like fetch)
         Usage : <span data-sq-live-count="serviceId">
  ---------------------------------------------------------- */
  initQueueRefresh() {
    const counters = document.querySelectorAll("[data-sq-live-count]");
    if (!counters.length) return;

    const refresh = () => {
      counters.forEach(el => {
        const sid = el.dataset.sqLiveCount;
        fetch(`/smartqueue/api/queue_count.php?service_id=${sid}`)
          .then(r => r.json())
          .then(data => {
            if (typeof data.count === "number") {
              const prev = parseInt(el.textContent, 10);
              el.textContent = data.count;
              // Badge change: flash si nouveau ticket
              if (data.count > prev) {
                el.closest(".card")?.classList.add("border-warning");
                setTimeout(() => el.closest(".card")?.classList.remove("border-warning"), 2000);
              }
            }
          })
          .catch(() => {}); // Silencieux si API pas encore branchée
      });
    };

    // Rafraîchissement toutes les 30 secondes
    setInterval(refresh, 30_000);
  },

  /* ----------------------------------------------------------
     12. VALIDATION DE FORMULAIRE (Bootstrap 5 native)
         Usage : <form class="sq-validate needs-validation" novalidate>
  ---------------------------------------------------------- */
  initFormValidation() {
    document.querySelectorAll("form.sq-validate").forEach(form => {
      form.addEventListener("submit", e => {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
          // Shake le bouton submit
          const btn = form.querySelector("[type=submit]");
          btn?.classList.add("sq-shake");
          setTimeout(() => btn?.classList.remove("sq-shake"), 500);
        }
        form.classList.add("was-validated");
      });
    });
  },

  /* ----------------------------------------------------------
     13. SYSTÈME DE TOASTS PROGRAMMATIQUES
         Appel : SQ.toast("Message", "success" | "danger" | "warning" | "info")
  ---------------------------------------------------------- */
  initToastQueue() {
    if (!document.getElementById("sq-toast-container")) {
      const c = document.createElement("div");
      c.id = "sq-toast-container";
      document.body.appendChild(c);
    }
  },

  toast(message, type = "info") {
    const icons = {
      success: "bi-check-circle-fill",
      danger:  "bi-x-circle-fill",
      warning: "bi-exclamation-triangle-fill",
      info:    "bi-info-circle-fill",
    };
    const icon = icons[type] || icons.info;

    const wrapper = document.createElement("div");
    wrapper.innerHTML = `
      <div class="toast align-items-center text-bg-${type} border-0 show shadow"
           role="alert" aria-live="assertive">
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi ${icon} me-2"></i>${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto"
                  data-bs-dismiss="toast"></button>
        </div>
      </div>`;

    const toastEl = wrapper.firstElementChild;
    document.getElementById("sq-toast-container").appendChild(toastEl);

    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    bsToast.show();
    toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
  },

  /* ----------------------------------------------------------
     14. OVERLAY DE CHARGEMENT GLOBAL
         SQ.showLoader() / SQ.hideLoader()
  ---------------------------------------------------------- */
  showLoader() {
    let ov = document.getElementById("sq-overlay");
    if (!ov) {
      ov = document.createElement("div");
      ov.id = "sq-overlay";
      ov.innerHTML = `<div class="spinner-border" role="status">
        <span class="visually-hidden">Chargement…</span></div>`;
      document.body.appendChild(ov);
    }
    ov.classList.add("active");
  },

  hideLoader() {
    document.getElementById("sq-overlay")?.classList.remove("active");
  },

  /* ----------------------------------------------------------
     15. COPIE DANS LE PRESSE-PAPIER
         Usage : <button data-sq-copy="texte">
  ---------------------------------------------------------- */
  initCopyButtons() {
    document.querySelectorAll("[data-sq-copy]").forEach(btn => {
      btn.addEventListener("click", () => {
        navigator.clipboard.writeText(btn.dataset.sqCopy).then(() => {
          SQ.toast("Copié dans le presse-papier !", "success");
        });
      });
    });
  },

};

/* ============================================================
   EXPOSITION GLOBALE (pour usage inline dans les templates PHP)
   Exemple : onclick="SQ.toast('Ticket annulé', 'warning')"
   ============================================================ */
window.SQ = SQ;
