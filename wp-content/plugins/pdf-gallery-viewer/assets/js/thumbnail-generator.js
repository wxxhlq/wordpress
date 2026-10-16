document.addEventListener("DOMContentLoaded", function () {
  const pdfItems = document.querySelectorAll(".pdf-gallery-item");
  if (!pdfItems.length) return;

  if (typeof window.pdfjsLib === "undefined") {
    console.error("[PDF Gallery] pdfjsLib not available.");
    pdfItems.forEach((item) => {
      const iconContainer = item.querySelector(".pdf-icon");
      if (iconContainer) {
        iconContainer.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:red"></i> PDF non disponibile';
      }
    });
    return;
  }

  pdfjsLib.GlobalWorkerOptions.workerSrc =
    typeof PDFGalleryViewer !== "undefined" && PDFGalleryViewer.workerSrc
      ? PDFGalleryViewer.workerSrc
      : "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

  function renderThumbnail(url, iconContainer) {
    pdfjsLib
      .getDocument(url)
      .promise.then((pdf) => pdf.getPage(1))
      .then((page) => {
        const scale = 0.5;
        const viewport = page.getViewport({ scale });
        const canvas = document.createElement("canvas");
        const context = canvas.getContext("2d");
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        return page
          .render({ canvasContext: context, viewport: viewport })
          .promise.then(() => {
            const img = document.createElement("img");
            img.src = canvas.toDataURL("image/png");
            img.alt = "Anteprima PDF";
            iconContainer.innerHTML = "";
            iconContainer.appendChild(img);
          });
      })
      .catch((err) => {
        console.error("Errore rendering PDF:", err.message);
        iconContainer.innerHTML =
          '<i class="fas fa-exclamation-circle" style="color:#dc3545"></i> Impossibile caricare PDF';
      });
  }

  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          observer.unobserve(entry.target);
          const url = entry.target.getAttribute("data-url");
          const iconContainer = entry.target.querySelector(".pdf-icon");
          if (url && iconContainer) renderThumbnail(url, iconContainer);
        });
      },
      { rootMargin: "200px" }
    );
    pdfItems.forEach((item) => observer.observe(item));
  } else {
    // Fallback for old browsers
    pdfItems.forEach((item) => {
      const url = item.getAttribute("data-url");
      const iconContainer = item.querySelector(".pdf-icon");
      if (url && iconContainer) renderThumbnail(url, iconContainer);
    });
  }
});
