document.addEventListener("DOMContentLoaded", function () {
  const links = document.querySelectorAll(".pdf-view-link");
  const modal = document.getElementById("pdfModal");
  const modalClose = document.querySelector(".pdf-modal-close");
  const viewer = document.getElementById("pdfModalViewer");
  const prevBtn = document.getElementById("pdfPrevPage");
  const nextBtn = document.getElementById("pdfNextPage");
  const pageInfo = document.getElementById("pdfPageInfo");
  const zoomInBtn = document.getElementById("pdfZoomIn");
  const zoomOutBtn = document.getElementById("pdfZoomOut");
  const downloadLink = document.getElementById("pdfDownload");

let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1.2;
let canvas = null;
let ctx = null;
let pdfUrl = "";
const viewerState = {};


  if (typeof pdfjsLib === "undefined") {
    console.error("pdfjsLib non disponibile");
    return;
  }

  pdfjsLib.GlobalWorkerOptions.workerSrc =
    "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

  function renderPage(pageNumber) {
    pdfDoc.getPage(pageNumber).then((page) => {
      const viewport = page.getViewport({ scale });

      canvas.width = viewport.width;
      canvas.height = viewport.height;

      const renderContext = {
        canvasContext: ctx,
        viewport: viewport,
      };
      page.render(renderContext);

      currentPage = pageNumber;
      pageInfo.textContent = `${currentPage} / ${totalPages}`;
      prevBtn.disabled = currentPage <= 1;
      nextBtn.disabled = currentPage >= totalPages;

      // Salva stato per il PDF attuale
      sessionStorage.setItem(
        `pdf_viewer_state_${pdfUrl}`,
        JSON.stringify({
          page: currentPage,
          zoom: scale,
        })
      );
    });
  }


  links.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      pdfUrl = this.getAttribute("href");
      modal.style.display = "block";
      viewer.innerHTML = "";

      canvas = document.createElement("canvas");
      ctx = canvas.getContext("2d");
      viewer.appendChild(canvas);

      pdfjsLib.getDocument(pdfUrl).promise.then((pdf) => {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        downloadLink.href = pdfUrl;
        scale = 1.2;
        const savedState = sessionStorage.getItem(`pdf_viewer_state_${pdfUrl}`);
        if (savedState) {
          try {
            const parsed = JSON.parse(savedState);
            if (parsed.page) currentPage = parsed.page;
            if (parsed.zoom) scale = parsed.zoom;
          } catch (e) {
            console.warn("Errore lettura stato salvato", e);
          }
        }
        renderPage(currentPage);
      });
    });
  });

  prevBtn.onclick = () => {
    if (currentPage > 1) renderPage(currentPage - 1);
  };
  nextBtn.onclick = () => {
    if (currentPage < totalPages) renderPage(currentPage + 1);
  };
  zoomInBtn.onclick = () => {
    scale += 0.2;
    renderPage(currentPage);
  };
  zoomOutBtn.onclick = () => {
    scale = Math.max(0.4, scale - 0.2);
    renderPage(currentPage);
  };

  modalClose.onclick = () => {
    modal.style.display = "none";
    viewer.innerHTML = "";
    pdfDoc = null;
    currentPage = 1;
    totalPages = 0;
  };

  window.onclick = (event) => {
    if (event.target === modal) {
      modal.style.display = "none";
      viewer.innerHTML = "";
      pdfDoc = null;
      currentPage = 1;
      totalPages = 0;
    }
  };
});
