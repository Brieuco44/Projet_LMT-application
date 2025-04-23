import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String
    }
    static targets = ["image", "canvas", "pageInfo"];

    connect() {
        this.page = 1;
        this.boxes = [];
        console.log("Stimulus connected"); // Add this
        this.loadPage();
    }


    async loadPage() {
        // get fresh URL for this.page (you’ll want to regenerate in your Symfony controller)
        const imageUrl = this.urlValue.replace(/_page_\d+/, `_page_${String(this.page).padStart(2,'0')}`);
        this.imageTarget.src = imageUrl;

        // wait for <img> to load so we know its natural sizes
        await new Promise(res => this.imageTarget.onload = res);

        // size canvas to match displayed image
        this.canvasTarget.width  = this.imageTarget.clientWidth;
        this.canvasTarget.height = this.imageTarget.clientHeight;

        // compute scale  = PDF‐pixel / CSS‐pixel
        this.scale = this.imageTarget.naturalWidth / this.imageTarget.clientWidth;

        this.pageInfoTarget.textContent = this.page;
        this.clearCanvas();
    }

    prevPage() {
        if (this.page > 1) { this.page--; this.loadPage(); }
    }
    nextPage() {
        this.page++; this.loadPage();
    }

    startDrawZone() {
        this.isDrawing = true;
        this.canvasTarget.style.cursor = "crosshair";
        console.log("Start drawing mode"); // ← check this appears
        this.canvasTarget.addEventListener("mousedown", this.onMouseDown);
        this.canvasTarget.addEventListener("mousemove", this.onMouseMove);
        this.canvasTarget.addEventListener("mouseup",   this.onMouseUp);
    }

    onMouseDown = (e) => {
        if (!this.isDrawing) return;
        const rect = this.canvasTarget.getBoundingClientRect();
        this.startX = e.clientX - rect.left;
        this.startY = e.clientY - rect.top;
        this.ctx = this.canvasTarget.getContext("2d");
        // save a clean snapshot
        this.snapshot = this.ctx.getImageData(0, 0, this.canvasTarget.width, this.canvasTarget.height);
    }

    onMouseMove = (e) => {
        if (this.startX == null) return;
        const rect = this.canvasTarget.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // restore blank
        this.ctx.putImageData(this.snapshot, 0, 0);

        // draw current rectangle
        this.ctx.strokeStyle = "#0f0";
        this.ctx.lineWidth   = 1;
        this.ctx.strokeRect(
            this.startX, this.startY,
            x - this.startX, y - this.startY
        );
    }

    onMouseUp = (e) => {
        const rect = this.canvasTarget.getBoundingClientRect();
        const endX = e.clientX - rect.left;
        const endY = e.clientY - rect.top;

        // restore & freeze final
        this.ctx.putImageData(this.snapshot, 0, 0);
        this.ctx.strokeRect(
            this.startX, this.startY,
            endX - this.startX, endY - this.startY
        );

        // convert back to PDF‐pixel coordinates
        const box = {
            x1: Math.round(this.startX * this.scale),
            y1: Math.round(this.startY * this.scale),
            x2: Math.round(endX   * this.scale),
            y2: Math.round(endY   * this.scale),
        };
        this.boxes.push(box);
        console.log(JSON.stringify(box));
        // e.g. {"x1":405,"y1":342,"x2":2415,"y2":405}

        // clean up
        this.startX = this.startY = null;
        this.isDrawing = false;
        this.canvasTarget.style.cursor = "default";
        this.canvasTarget.removeEventListener("mousedown", this.onMouseDown);
        this.canvasTarget.removeEventListener("mousemove", this.onMouseMove);
        this.canvasTarget.removeEventListener("mouseup",   this.onMouseUp);
    }

    clearCanvas() {
        const ctx = this.canvasTarget.getContext("2d");
        ctx.clearRect(0, 0, this.canvasTarget.width, this.canvasTarget.height);
    }
}
