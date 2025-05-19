document.addEventListener("turbo:submit-start", function (event) {
  document.getElementById("document-form-fields").classList.add("hidden");
  document.getElementById("document-analyse-fields").classList.remove("hidden");
});

function openVoirControlesModal(documentID) {
  const turboFrame = document.getElementById("affichageControles");
  turboFrame.src = `/document/controles/?id=` + documentID;

  // Afficher la modale
  const modal = document.getElementById("modalControles");
  modal.showModal();
}

let toastCounter = 0;
function nextToastId() {
  return ++toastCounter;
}
function removeToast(id) {
  const el = document.getElementById(`toast-success-${id}`);
  if (el) el.remove();
}

document.addEventListener("turbo:before-stream-render", (e) => {
  const streamEl = e.target.closest("turbo-stream");
  if (!streamEl) return;

  const action = streamEl.getAttribute("action");
  const target = streamEl.getAttribute("target");

  // --- SLIDE-OUT pour suppression de ligne ---
  if (action === "remove" && target.startsWith("document-row-")) {
    e.preventDefault();
    const row = document.getElementById(target);
    if (!row) return;
    row.classList.add("slide-out");
    row.addEventListener("transitionend", () => row.remove(), { once: true });
    return;
  }

  // --- SLIDE-IN pour ajout de ligne ---
  if (
    (action === "append" || action === "prepend") &&
    target === "tabresultsTableBody"
  ) {
    e.preventDefault();
    const tpl = streamEl.querySelector("template");
    if (!tpl) return;

    const clone = document.importNode(tpl.content, true);
    const newRow = clone.firstElementChild;
    if (!newRow) return;

    newRow.classList.add("slide-in-start");
    const container = document.getElementById(target);
    if (!container) return;
    if (action === "append") container.appendChild(newRow);
    else container.insertBefore(newRow, container.firstChild);

    void newRow.offsetWidth; // force reflow
    newRow.classList.add("slide-in");
    return;
  }

  // --- TOASTS pour alertes ---
  if (action === "append" && target === "alert") {
    e.preventDefault();
    const tpl = streamEl.querySelector("template");
    if (!tpl) return;

    // clone du toast
    const clone = document.importNode(tpl.content, true);
    const toast = clone.firstElementChild;
    // si pas d'ID fourni, on en génère un
    let id = toast.getAttribute("data-toast-id");
    if (!id) {
      id = nextToastId();
      toast.setAttribute("data-toast-id", id);
      toast.id = `toast-success-${id}`;
    }

    // insère en bas (stack)
    const alertContainer = document.getElementById("alert");
    if (!alertContainer) return;
    alertContainer.appendChild(toast);

    // auto-close à la fin du timer SVG (3s)
    const fg = toast.querySelector(".timer-fg");
    if (fg) {
      fg.addEventListener(
        "animationend",
        () => {
          if (toast.parentNode) toast.remove();
        },
        { once: true }
      );
    }
    return;
  }
});
