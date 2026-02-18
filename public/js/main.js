(() => {
  // Smooth scroll para anclas internas
  document.addEventListener("click", (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const id = a.getAttribute("href");
    if (!id || id === "#") return;
    const el = document.querySelector(id);
    if (!el) return;
    e.preventDefault();
    el.scrollIntoView({ behavior: "smooth", block: "start" });
    history.pushState(null, "", id);
  });

  // Bootstrap validation (client-side)
  const forms = document.querySelectorAll(".needs-validation");
  Array.from(forms).forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add("was-validated");
      },
      false
    );
  });

  // Modal para galería (reutiliza el mismo modal en home/galeria)
  const modalEl = document.getElementById("galleryModal");
  const modalImg = document.getElementById("galleryModalImg");
  const modalTitle = document.getElementById("galleryModalTitle");
  let modal = null;

  document.addEventListener("click", (e) => {
    const link = e.target.closest('a[data-gallery="open"]');
    if (!link || !modalEl || !modalImg || !modalTitle) return;

    e.preventDefault();
    const src = link.getAttribute("href");
    const title = link.getAttribute("data-title") || "Imagen";

    modalImg.src = src;
    modalImg.alt = title;
    modalTitle.textContent = title;

    modal = modal || new bootstrap.Modal(modalEl);
    modal.show();
  });
})();

