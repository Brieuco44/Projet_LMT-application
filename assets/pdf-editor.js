import { Canvas } from "fabric";
import { Image as FabricImage } from "fabric";
import * as pdfjsLib from "pdfjs-dist";
import workerSrc from "pdfjs-dist/build/pdf.worker.min.js?url";

pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

document.addEventListener("DOMContentLoaded", async () => {
  const container = document.getElementById("pdf-editor");
  const pdfUrl = container?.dataset?.url;
  
  
  if (!container || !pdfUrl) {
    console.error("📄 pdf-editor: container or PDF URL missing.");
    return;
  }
  
  const htmlCanvas = document.createElement("canvas");
  htmlCanvas.id = "pdf-canvas";
  container.appendChild(htmlCanvas);
  
  // const nav = document.createElement("div");
  // nav.innerHTML = `
  // <button id="prevPage">◀</button>
  // <span id="pageInfo">Page 1</span>
  // <button id="nextPage">▶</button>
  // `;
  // container.appendChild(nav);
  
  const ctx = htmlCanvas.getContext("2d");
  let fabricCanvas;
  let currentPage = 1;
  let totalPages = 0;
  let pdf = null;
  
  pdf = await pdfjsLib.getDocument({
    url: pdfUrl,
    disableFontFace: true,
    cMapUrl: "/cmaps/",
    cMapPacked: true,
  }).promise;
  totalPages = pdf.numPages;
  
  const renderPage = async (pageNum) => {
    const page = await pdf.getPage(pageNum);
    const containerWidth = container.clientWidth;
    const unscaledViewport = page.getViewport({ scale: 1 });
    const scale = containerWidth / unscaledViewport.width;
    const viewport = page.getViewport({ scale });
    
    htmlCanvas.width = viewport.width;
    htmlCanvas.height = viewport.height;
    
    const renderContext = {
      canvasContext: ctx,
      viewport: viewport,
    };

    await page.render(renderContext).promise;
    console.log("📄 pdf-editor: page rendered", pageNum);
    const dataUrl = htmlCanvas.toDataURL();

    // Reset Fabric
    // if (fabricCanvas) {
    //   fabricCanvas.dispose();
    // }

    // fabricCanvas = new Canvas(htmlCanvas, {
    //   selection: false
    // });

    // FabricImage.fromURL(dataUrl, img => {
    //   fabricCanvas.setBackgroundImage(img, fabricCanvas.renderAll.bind(fabricCanvas), {
    //     scaleX: fabricCanvas.width / img.width,
    //     scaleY: fabricCanvas.height / img.height
    //   });
    // });

    // setupDrawing(fabricCanvas);
    document.getElementById(
      "pageInfo"
    ).innerText = `Page ${currentPage} / ${totalPages}`;
  };

  renderPage(currentPage);

  // Navigation

  document.getElementById("prevPage").addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      renderPage(currentPage);
    }
  });

  document.getElementById("nextPage").addEventListener("click", () => {
    if (currentPage < totalPages) {
      currentPage++;
      renderPage(currentPage);
    }
  });

  function setupDrawing(canvas) {
    let rect,
      isDrawing = false,
      origX = 0,
      origY = 0;

    canvas.on("mouse:down", (e) => {
      isDrawing = true;
      const pointer = canvas.getPointer(e.e);
      origX = pointer.x;
      origY = pointer.y;

      rect = new fabric.Rect({
        left: origX,
        top: origY,
        width: 0,
        height: 0,
        fill: "rgba(255, 0, 0, 0.3)",
        stroke: "red",
        strokeWidth: 1,
        selectable: false,
        evented: false,
      });

      canvas.add(rect);
    });

    canvas.on("mouse:move", (e) => {
      if (!isDrawing) return;

      const pointer = canvas.getPointer(e.e);
      rect.set({
        width: Math.abs(origX - pointer.x),
        height: Math.abs(origY - pointer.y),
        left: Math.min(origX, pointer.x),
        top: Math.min(origY, pointer.y),
      });

      canvas.renderAll();
    });

    canvas.on("mouse:up", () => {
      isDrawing = false;

      const coords = {
        x1: Math.round(rect.left),
        y1: Math.round(rect.top),
        x2: Math.round(rect.left + rect.width),
        y2: Math.round(rect.top + rect.height),
      };

      console.log("🧊 Zone dessinée :", coords);
    });
  }
});
