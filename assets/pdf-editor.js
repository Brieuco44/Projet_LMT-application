import * as fabric from 'fabric';
import * as pdfjsLib from 'pdfjs-dist';
import workerSrc from 'pdfjs-dist/build/pdf.worker.min.js?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

document.addEventListener('DOMContentLoaded', async () => {
  const container = document.getElementById('pdf-editor');
  const pdfUrl = container?.dataset.pdfEditorUrlValue;
  if (!container || !pdfUrl) return;

  const htmlCanvas = container.querySelector('[data-pdf-editor-target="canvas"]');
  if (!(htmlCanvas instanceof HTMLCanvasElement)) return;

  let PyRatio = {
    'x': 5.379,
    'y': 5.385
  };

  let currentPage = 1;
  let totalPages = 0;
  let drawnZones = [];
  let fabricCanvas;
  let drawingMode = false;

  const pdf = await pdfjsLib.getDocument({
    url: pdfUrl,
    disableFontFace: true,
    cMapUrl: '/cmaps/',
    cMapPacked: true
  }).promise;

  totalPages = pdf.numPages;

  const initCanvasDimensions = async (pageNum) => {
    const page = await pdf.getPage(pageNum);
    const containerWidth = container.clientWidth;
    const unscaled = page.getViewport({ scale: 1 });
    const scale = containerWidth / unscaled.width;
    const viewport = page.getViewport({ scale });

    htmlCanvas.width = viewport.width;
    htmlCanvas.height = viewport.height;

    return { page, viewport };
  };


  const createFabricCanvas = async (pageNum) => {
    const { page, viewport } = await initCanvasDimensions(pageNum);
    htmlCanvas.width = viewport.width;
    htmlCanvas.height = viewport.height;

    fabricCanvas = new fabric.Canvas(htmlCanvas, { selection: false,backgroundColor:null });

    fabricCanvas.on('object:modified', (e) => {
      const target = e.target;
      if (!(target instanceof fabric.Rect)) return;

      const zone = drawnZones.find(z => z.fabricObj === target);
      if (!zone) return;

      zone.coords = {
        x1: Math.round(target.left * PyRatio.x),
        x2: Math.round((target.left + target.width * target.scaleX) * PyRatio.x),
        y1: Math.round(target.top * PyRatio.y),
        y2: Math.round((target.top + target.height * target.scaleY) * PyRatio.y)
      };

      updateZoneList();
    });

  };

  const renderPage = async (pageNum) => {
    const { page, viewport } = await initCanvasDimensions(pageNum);

    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = viewport.width;
    tempCanvas.height = viewport.height;
    const tempCtx = tempCanvas.getContext('2d');

    await page.render({
      canvasContext: tempCtx,
      viewport: viewport
    }).promise;

    const dataUrl = tempCanvas.toDataURL('image/png');
    const img = new Image()

    img.onload = () => {
      const imgObj = new fabric.Image(img, {
        selectable: false,
        centeredRotation: true,
        centeredScaling: true,
        scaleX: 1,
        scaleY: 1,
        perPixelTargetFind: false
      });
      fabricCanvas.add(imgObj);
    }

    img.src = dataUrl;
  };


  const updateZoneList = () => {
    const list = document.getElementById('zone-list');
    list.innerHTML = '';
    drawnZones.forEach((z, index) => {
      const div = document.createElement('div');
      div.className = 'border p-2 mb-4 rounded bg-base-100 shadow relative';

      // 🗑️ Delete button
      const deleteBtn = document.createElement('button');
      deleteBtn.innerHTML = '🗑️';
      deleteBtn.className = 'absolute top-2 right-2 text-red-500 hover:text-red-700 ml-2';
      deleteBtn.onclick = () => {
        fabricCanvas.remove(z.fabricObj);
        drawnZones.splice(index, 1);
        updateZoneList();
      };

      // ✏️ Edit button
      const editBtn = document.createElement('button');
      editBtn.innerHTML = '✏️';
      editBtn.className = 'absolute top-2 right-10 text-blue-500 hover:text-blue-700';
      editBtn.onclick = () => {
        const newLabel = prompt('Nouveau libellé :', z.libelle);
        if (newLabel) {
          drawnZones[index].libelle = newLabel;
          updateZoneList();
        }
      };

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



  const setupDrawing = () => {
    drawingMode = true;

    fabricCanvas.selection = false;

    let rect, isDrawing = false, origX = 0, origY = 0;

    const onMouseDown = (e) => {
      if (!drawingMode) return;
      isDrawing = true;
      const p = fabricCanvas.getPointer(e.e);
      origX = p.x;
      origY = p.y;

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

    const onMouseUp = () => {
      if (!isDrawing || !drawingMode) return;
      isDrawing = false;

      const coords = {
        x1: Math.round((rect.left)* PyRatio.x),
        x2: Math.round((rect.left + rect.width)* PyRatio.x),
        y1: Math.round((rect.top)* PyRatio.y),
        y2: Math.round((rect.top + rect.height)* PyRatio.y),
      };
      const libelle = prompt('Entrez le libellé pour cette zone:');
      if (libelle) {
        drawnZones.push({ page: currentPage, coords, libelle, fabricObj: rect });
        updateZoneList();
      } else {
        fabricCanvas.remove(rect);
      }

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

  const PostZoneBtn = async () => {
    const url = ''; // Symfony route to the controller handling the zones

    // Filter drawn zones to only include those on the current page
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
          'Accept': 'application/json' // Ensure the response is in JSON
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


  await createFabricCanvas(currentPage);
  setupControls();
  await renderPage(currentPage);
});
