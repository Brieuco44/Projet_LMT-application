import * as fabric from "fabric";
import * as pdfjsLib from "pdfjs-dist";
import workerSrc from "pdfjs-dist/build/pdf.worker.min.js?url";
import * as Turbo from "@hotwired/turbo";
Turbo.start();

// Configuration du worker pour PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

// Événement déclenché lorsque le DOM est chargé
document.addEventListener("DOMContentLoaded", async () => {
  const container = document.getElementById("pdf-editor"); // Conteneur principal
  const pdfUrl = container?.dataset.pdfEditorUrlValue; // URL du PDF à charger
  if (!container || !pdfUrl) return; // Si le conteneur ou l'URL est manquant, on arrête

  const htmlCanvas = container.querySelector(
    '[data-pdf-editor-target="canvas"]'
  ); // Canvas HTML pour afficher le PDF
  if (!(htmlCanvas instanceof HTMLCanvasElement)) return;

  // Ratio pour convertir les coordonnées entre le canvas et le PDF
  // let PyRatio = {
  //   x: 5.379,
  //   y: 5.385,
  // };
  let PyRatio = {};

  let currentPage = 1; // Page actuelle du PDF
  let totalPages = 0; // Nombre total de pages dans le PDF
  let drawnZones = []; // Liste des zones dessinées
  let fabricCanvas; // Canvas Fabric.js
  let drawingMode = false; // Mode dessin activé ou non

  // Chargement du document PDF
  const pdf = await pdfjsLib.getDocument({
    url: pdfUrl,
    disableFontFace: true,
    cMapUrl: "/cmaps/",
    cMapPacked: true,
  }).promise;

  totalPages = pdf.numPages; // Récupération du nombre total de pages

  // Initialisation des dimensions du canvas pour une page donnée
  const initCanvasDimensions = async (pageNum) => {
    const page = await pdf.getPage(pageNum); // Récupération de la page
    const containerWidth = container.clientWidth; // Largeur du conteneur
    const unscaled = page.getViewport({ scale: 1 }); // Vue non mise à l'échelle
    const scale = containerWidth / unscaled.width; // Calcul du facteur d'échelle
    const viewport = page.getViewport({ scale }); // Vue mise à l'échelle

    htmlCanvas.width = viewport.width; // Largeur du canvas
    htmlCanvas.height = viewport.height; // Hauteur du canvas

    PyRatio = {
      x: 2480/viewport.width,
      y: 3506/viewport.height,
    };

    return { page, viewport };
  };

  // Création du canvas Fabric.js
  const createFabricCanvas = async (pageNum) => {
    const { page, viewport } = await initCanvasDimensions(pageNum); // Initialisation des dimensions
    htmlCanvas.width = viewport.width;
    htmlCanvas.height = viewport.height;

    // Initialisation du canvas Fabric.js
    fabricCanvas = new fabric.Canvas(htmlCanvas, {
      selection: false,
      backgroundColor: null,
    });

    // Événement déclenché lorsqu'un objet est modifié
    fabricCanvas.on("object:modified", (e) => {
      const target = e.target;
      if (!(target instanceof fabric.Rect)) return;

      // Mise à jour des coordonnées de la zone modifiée
      const zone = drawnZones.find((z) => z.fabricObj === target);
      if (!zone) return;

      zone.coords = {
        x1: Math.round(target.left * PyRatio.x),
        x2: Math.round(
          (target.left + target.width * target.scaleX) * PyRatio.x
        ),
        y1: Math.round(target.top * PyRatio.y),
        y2: Math.round(
          (target.top + target.height * target.scaleY) * PyRatio.y
        ),
      };

      // updateZoneList(); // Mise à jour de la liste des zones
    });
  };

  // Rendu d'une page du PDF sur le canvas
  const renderPage = async (pageNum) => {
    const { page, viewport } = await initCanvasDimensions(pageNum);

    // Création d'un canvas temporaire pour le rendu
    const tempCanvas = document.createElement("canvas");
    tempCanvas.width = viewport.width;
    tempCanvas.height = viewport.height;
    const tempCtx = tempCanvas.getContext("2d");

    // Rendu de la page sur le canvas temporaire
    await page.render({
      canvasContext: tempCtx,
      viewport: viewport,
    }).promise;

    // Conversion du canvas temporaire en image
    const dataUrl = tempCanvas.toDataURL("image/png");
    await new Promise(resolve => {
      const img = new Image();
      img.onload = () => {
        const imgObj = new fabric.Image(img, {
          selectable: false,
          centeredRotation: true,
          centeredScaling: true,
          scaleX: 1,
          scaleY: 1,
          perPixelTargetFind: false,
        });
        fabricCanvas.add(imgObj);
        resolve();          // only resolve after the image is in the canvas
      };
      img.src = dataUrl;
    });


    document.getElementById(
      "pageInfo"
    ).innerText = `Page ${pageNum} sur ${totalPages}`; // Mise à jour de l'info de page
    if (pageNum == totalPages) {
      document.getElementById("nextPage").classList.add("btn-disabled"); // Masquer le bouton "Suivant" si on est à la dernière page
      document.getElementById('nextPage').classList.add("cursor-not-allowed");
    } else {
      document.getElementById("nextPage").classList.remove("btn-disabled");
      document.getElementById("nextPage").classList.remove("cursor-not-allowed");
    }
    if (pageNum == 1) {
      document.getElementById("prevPage").classList.add("btn-disabled"); // Masquer le bouton "Précédent" si on est à la première page
      document.getElementById('prevPage').classList.add("cursor-not-allowed");
    } else {
      document.getElementById("prevPage").classList.remove("btn-disabled");
      document.getElementById('prevPage').classList.remove("cursor-not-allowed");
    }
  };


  // Configuration du mode dessin
  const setupDrawing = () => {
    drawingMode = true;

    fabricCanvas.selection = false;

    let rect,
      isDrawing = false,
      origX = 0,
      origY = 0;

    // Événement déclenché lors du clic pour commencer à dessiner
    const onMouseDown = (e) => {
      if (!drawingMode) return;
      isDrawing = true;
      const p = fabricCanvas.getPointer(e.e);
      origX = p.x;
      origY = p.y;

      // Création d'un rectangle temporaire
      rect = new fabric.Rect({
        left: origX,
        top: origY,
        width: 0,
        height: 0,
        fill: "rgba(255,0,0,0.1)",
        stroke: "red",
        strokeWidth: 1,
        selectable: true,
        evented: true,
        hasControls: true,
        hasBorders: true,
        lockScalingFlip: true,
        lockRotation: true,
        lockMovementX: false,
        lockMovementY: false,
        strokeDashArray: [5, 5],
      });

      fabricCanvas.add(rect);
    };

    // Événement déclenché lors du déplacement de la souris pour redimensionner le rectangle
    const onMouseMove = (e) => {
      if (!isDrawing || !drawingMode) return;
      const p = fabricCanvas.getPointer(e.e);
      rect.set({
        width: Math.abs(origX - p.x),
        height: Math.abs(origY - p.y),
        left: Math.min(origX, p.x),
        top: Math.min(origY, p.y),
      });
      fabricCanvas.renderAll();
    };

    // Événement déclenché lors du relâchement de la souris pour terminer le dessin
    const onMouseUp = () => {
      if (!isDrawing || !drawingMode) return;
      isDrawing = false;

      // Calcul des coordonnées de la zone
      const coords = {
        x1: Math.round(rect.left * PyRatio.x),
        x2: Math.round((rect.left + rect.width) * PyRatio.x),
        y1: Math.round(rect.top * PyRatio.y),
        y2: Math.round((rect.top + rect.height) * PyRatio.y),
      };

      document
        .getElementById("zone_coordonnees")
        .setAttribute("value", JSON.stringify(coords)); // Mise à jour des coordonnées
      document.getElementById("zone_page").setAttribute("value", currentPage); // Mise à jour de la page

      // Affichage de la modal pour entrer un libellé
      const dialog = document.getElementById("modal-zone");
      dialog.showModal(); // Demande d'un libellé

      drawingMode = false;
      fabricCanvas.defaultCursor = "default";
      fabricCanvas.off("mouse:down", onMouseDown);
      fabricCanvas.off("mouse:move", onMouseMove);
      fabricCanvas.off("mouse:up", onMouseUp);
    };

    fabricCanvas.on("mouse:down", onMouseDown);
    fabricCanvas.on("mouse:move", onMouseMove);
    fabricCanvas.on("mouse:up", onMouseUp);
  };

  // Configuration des contrôles pour changer de page ou ajouter une zone
  const setupControls = () => {
    document
      .getElementById("addZoneBtn")
      ?.addEventListener("click", setupDrawing);

    document.getElementById("prevPage")?.addEventListener("click", async () => {
      if (currentPage > 1) {
        currentPage--;
        await renderPage(currentPage);
      }
    });

    document.getElementById("nextPage")?.addEventListener("click", async () => {
      if (currentPage < totalPages) {
        currentPage++;
        await renderPage(currentPage);
      }
    });
  };

  const initialZones = JSON.parse(container.dataset.zones || '[]');



  /**
   * Create and register Fabric rectangles for existing zones
   */
  const loadExistingZones = () => {
    initialZones.forEach(z => {
      // Only load rectangles for the current PDF page
      if (z.page !== currentPage) return;

      // Convert PDF coords back to canvas coords
      const left = z.coords.x1 / PyRatio.x;
      const top = z.coords.y1 / PyRatio.y;
      const width = (z.coords.x2 - z.coords.x1) / PyRatio.x;
      const height = (z.coords.y2 - z.coords.y1) / PyRatio.y;

      const rect = new fabric.Rect({
        left,
        top,
        width,
        height,
        fill: "rgba(255,0,0,0.1)",
        stroke: "red",
        strokeWidth: 1,
        selectable: true,
        visible: false,
        hasControls: true,
        lockRotation: true
      });

      fabricCanvas.add(rect);
      drawnZones.push({
        id: z.id,
        page: z.page,
        coords: z.coords,
        fabricObj: rect
      });
    });

    fabricCanvas.renderAll();
  };

  // Override renderPage to clear and reload zones on each page change
  const renderPageWithZones = async (pageNum) => {
    // Remove all existing fabric objects except background image
    fabricCanvas.getObjects().forEach(o => {
      if (!(o instanceof fabric.Image)) fabricCanvas.remove(o);
    });
    drawnZones = []; // reset

    await renderPage(pageNum);
    loadExistingZones();
  };

  // Replace calls to renderPage with renderPageWithZones
  await createFabricCanvas(currentPage);
  setupControls();
  await renderPageWithZones(currentPage);

  // Update prev/next page handlers to use renderPageWithZones
  document.getElementById("prevPage")?.addEventListener("click", async () => {
    if (currentPage > 1) {
      currentPage--;
      await renderPageWithZones(currentPage);
    }
  });
  document.getElementById("nextPage")?.addEventListener("click", async () => {
    if (currentPage < totalPages) {
      currentPage++;
      await renderPageWithZones(currentPage);
    }
  });

  /**
   * Show rectangles on the fabric canvas for a given zone collapse ID
   * @param {string} collapseId - ID of the collapse element, e.g. 'zone-42'
   */
  function showZoneRects(collapseId) {
    const zoneId = parseInt(collapseId.replace('zone-', ''), 10);

    drawnZones.forEach(z => {

      if (z.id === zoneId) {
        console.log(z.id, zoneId);
        z.fabricObj.set({ visible: true });
      }
    });
    fabricCanvas.renderAll();
  }

  /**
   * Hide rectangles for a given zone collapse ID
   * @param {string} collapseId
   */
  function hideZoneRects(collapseId) {
    const zoneId = parseInt(collapseId.replace('zone-', ''), 10);
    drawnZones.forEach(z => {
      if (z.id === zoneId) {
        z.fabricObj.set({ visible: false });
      }
    });
    fabricCanvas.renderAll();
  }

  document.querySelectorAll('#zone-list .collapse').forEach(collapseEl => {
    const checkbox = collapseEl.querySelector('input[type="checkbox"]');
    if (!checkbox) return;
    checkbox.addEventListener('change', () => {
      const id = collapseEl.id;
      console.log(id);
      if (checkbox.checked) {
        document.querySelectorAll('#zone-list .collapse input[type="checkbox"]').forEach(otherCb => {
          if (otherCb !== checkbox && otherCb.checked) {
            otherCb.checked = false;
            hideZoneRects(otherCb.closest('.collapse').id);
          }
        });
        showZoneRects(id);
      } else {
        hideZoneRects(id);
      }
    });
  });
});


