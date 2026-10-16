document.addEventListener("DOMContentLoaded", function () {
  const sliders = document.querySelectorAll(".swiper.layout-slider");

  sliders.forEach(function (slider) {
    const swiper = new Swiper(slider, {
      slidesPerView: "auto",
      spaceBetween: 20,
      freeMode: false,
      watchOverflow: true,
      pagination: {
        el: slider.querySelector(".swiper-pagination"),
        clickable: true,
      },
      navigation: {
        nextEl: slider.querySelector(".swiper-button-next"),
        prevEl: slider.querySelector(".swiper-button-prev"),
      },
    });

    // Optional: update after a short delay for dynamic content
    setTimeout(() => swiper.update(), 100);
  });
});
