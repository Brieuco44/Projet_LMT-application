import * as fabric from 'fabric';
import * as pdfjsLib from 'pdfjs-dist';
import workerSrc from 'pdfjs-dist/build/pdf.worker.min.js?url';
import * as Turbo from '@hotwired/turbo';
Turbo.start();

// Configuration du worker pour PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

// Événement déclenché lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', async () => {
  const container = document.getElementById('pdf-editor'); // Conteneur principal
  const pdfUrl = container?.dataset.pdfEditorUrlValue; // URL du PDF à charger
  if (!container || !pdfUrl) return; // Si le conteneur ou l'URL est manquant, on arrête

  const htmlCanvas = container.querySelector('[data-pdf-editor-target="canvas"]'); // Canvas HTML pour afficher le PDF
  if (!(htmlCanvas instanceof HTMLCanvasElement)) return;

  // Ratio pour convertir les coordonnées entre le canvas et le PDF
  let PyRatio = {
    'x': 5.379,
    'y': 5.385
  };

  let currentPage = 1; // Page actuelle du PDF
  let totalPages = 0; // Nombre total de pages dans le PDF
  let drawnZones = []; // Liste des zones dessinées
  let fabricCanvas; // Canvas Fabric.js
  let drawingMode = false; // Mode dessin activé ou non

  // Chargement du document PDF
  const pdf = await pdfjsLib.getDocument({
    url: pdfUrl,
    disableFontFace: true,
    cMapUrl: '/cmaps/',
    cMapPacked: true
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

    return { page, viewport };
  };

  // Création du canvas Fabric.js
  const createFabricCanvas = async (pageNum) => {
    const { page, viewport } = await initCanvasDimensions(pageNum); // Initialisation des dimensions
    htmlCanvas.width = viewport.width;
    htmlCanvas.height = viewport.height;

    // Initialisation du canvas Fabric.js
    fabricCanvas = new fabric.Canvas(htmlCanvas, { selection: false, backgroundColor: null });

    // Événement déclenché lorsqu'un objet est modifié
    fabricCanvas.on('object:modified', (e) => {
      const target = e.target;
      if (!(target instanceof fabric.Rect)) return;

      // Mise à jour des coordonnées de la zone modifiée
      const zone = drawnZones.find(z => z.fabricObj === target);
      if (!zone) return;

      zone.coords = {
        x1: Math.round(target.left * PyRatio.x),
        x2: Math.round((target.left + target.width * target.scaleX) * PyRatio.x),
        y1: Math.round(target.top * PyRatio.y),
        y2: Math.round((target.top + target.height * target.scaleY) * PyRatio.y)
      };

      // updateZoneList(); // Mise à jour de la liste des zones
    });
  };

  // Rendu d'une page du PDF sur le canvas
  const renderPage = async (pageNum) => {
    const { page, viewport } = await initCanvasDimensions(pageNum);

    // Création d'un canvas temporaire pour le rendu
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = viewport.width;
    tempCanvas.height = viewport.height;
    const tempCtx = tempCanvas.getContext('2d');

    // Rendu de la page sur le canvas temporaire
    await page.render({
      canvasContext: tempCtx,
      viewport: viewport
    }).promise;

    // Conversion du canvas temporaire en image
    const dataUrl = tempCanvas.toDataURL('image/png');
    const img = new Image();

    img.onload = () => {
      // Ajout de l'image en arrière-plan du canvas Fabric.js
      const imgObj = new fabric.Image(img, {
        selectable: false,
        centeredRotation: true,
        centeredScaling: true,
        scaleX: 1,
        scaleY: 1,
        perPixelTargetFind: false
      });
      fabricCanvas.add(imgObj);
    };

    img.src = dataUrl;
  };

  // Mise à jour de la liste des zones affichées
  const updateZoneList = () => {
    const zoneList = document.getElementById('zone-list');
    drawnZones.forEach((z, index) => {
    let newZoneCard = document.createElement(cardContent);

    zoneList.innerHTML = ""; // Réinitialisation de la liste




      const div = document.createElement('div');
      div.className = 'border p-2 mb-4 rounded bg-base-100 shadow relative';

      // Bouton de suppression
      const deleteBtn = document.createElement('button');
      deleteBtn.innerHTML = '🗑️';
      deleteBtn.className = 'absolute top-2 right-2 text-red-500 hover:text-red-700 ml-2';
      deleteBtn.onclick = () => {
        fabricCanvas.remove(z.fabricObj); // Suppression de l'objet du canvas
        drawnZones.splice(index, 1); // Suppression de la zone de la liste
        updateZoneList(); // Mise à jour de la liste
      };

      // Bouton d'édition
      const editBtn = document.createElement('button');
      editBtn.innerHTML = '✏️';
      editBtn.className = 'absolute top-2 right-10 text-blue-500 hover:text-blue-700';
      editBtn.onclick = () => {
        const newLabel = prompt('Nouveau libellé :', z.libelle); // Demande d'un nouveau libellé
        if (newLabel) {
          drawnZones[index].libelle = newLabel; // Mise à jour du libellé
          updateZoneList(); // Mise à jour de la liste
        }
      };

      // Contenu de la zone
      div.innerHTML = `
      <h3 class="font-bold">${z.libelle}</h3>
      <p class="text-xs">Page : ${z.page}</p>
      <pre class="text-xs">Coords: {'x1': ${z.coords.x1}, 'x2': ${z.coords.x2}, 'y1': ${z.coords.y1}, 'y2': ${z.coords.y2} }</pre>
    `;

      div.appendChild(editBtn);
      div.appendChild(deleteBtn);
      list.appendChild(div);
    });
  };

  // Configuration du mode dessin
  const setupDrawing = () => {
    drawingMode = true;

    fabricCanvas.selection = false;

    let rect, isDrawing = false, origX = 0, origY = 0;

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
        fill: 'rgba(255,0,0,0.1)',
        stroke: 'red',
        strokeWidth: 1,
        selectable: true,
        evented: true,
        hasControls: true,
        hasBorders: true,
        lockScalingFlip: true,
        lockRotation: true,
        lockMovementX: false,
        lockMovementY: false,
        strokeDashArray: [5, 5]
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
        top: Math.min(origY, p.y)
      });
      fabricCanvas.renderAll();
    };

    // Événement déclenché lors du relâchement de la souris pour terminer le dessin
    const onMouseUp = () => {
      if (!isDrawing || !drawingMode) return;
      isDrawing = false;

      // Calcul des coordonnées de la zone
      const coords = {
        x1: Math.round((rect.left) * PyRatio.x),
        x2: Math.round((rect.left + rect.width) * PyRatio.x),
        y1: Math.round((rect.top) * PyRatio.y),
        y2: Math.round((rect.top + rect.height) * PyRatio.y),
      };


      document.getElementById('zone_coordonnees').setAttribute('value', JSON.stringify(coords)); // Mise à jour des coordonnées
      document.getElementById('zone_page').setAttribute('value', currentPage); // Mise à jour de la page
      
      // Affichage de la modal pour entrer un libellé
      const dialog = document.getElementById('modal-zone');
      dialog.showModal(); // Demande d'un libellé

      drawingMode = false;
      fabricCanvas.defaultCursor = 'default';
      fabricCanvas.off('mouse:down', onMouseDown);
      fabricCanvas.off('mouse:move', onMouseMove);
      fabricCanvas.off('mouse:up', onMouseUp);
    };

    fabricCanvas.on('mouse:down', onMouseDown);
    fabricCanvas.on('mouse:move', onMouseMove);
    fabricCanvas.on('mouse:up', onMouseUp);
  };

  // Configuration des contrôles pour changer de page ou ajouter une zone
  const setupControls = () => {
    document.getElementById('addZoneBtn')?.addEventListener('click', setupDrawing);

    document.getElementById('prevPage')?.addEventListener('click', async () => {
      if (currentPage > 1) {
        currentPage--;
        await renderPage(currentPage);
      }
    });

    document.getElementById('nextPage')?.addEventListener('click', async () => {
      if (currentPage < totalPages) {
        currentPage++;
        await renderPage(currentPage);
      }
    });
  };

  // Fonction pour envoyer les zones au serveur
  const PostZoneBtn = async () => {
    const url = ''; // Route Symfony pour gérer les zones

    // Filtrer les zones dessinées pour inclure uniquement celles de la page actuelle
    const zoneData = drawnZones
        .map(zone => ({
          label: zone.libelle,
          coords: zone.coords,
          page: zone.page
        }));

    if (zoneData.length === 0) {
      alert('No zones to post for the current page.');
      return;
    }

    const data = {
      zones: zoneData
    };

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json' // Assurez-vous que la réponse est en JSON
        },
        body: JSON.stringify(data)
      });

      if (response.ok) {
        const responseData = await response.json();
        alert('Zones have been successfully posted!');
        console.log(responseData);
      } else {
        alert('Failed to post zones.');
      }
    } catch (error) {
      console.error('Error posting zones:', error);
      alert('An error occurred while posting zones.');
    }
  };

  // Initialisation du canvas et des contrôles
  await createFabricCanvas(currentPage);
  setupControls();
  await renderPage(currentPage);
});


