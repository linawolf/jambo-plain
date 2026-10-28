/**
 * Turns every [data-image-slider] element on the page into a Swiper instance.
 * Without JavaScript the markup stays a plain list of figures, which is why the
 * slides are not hidden in CSS.
 */
(function () {
    'use strict';

    function initSliders() {
        document.querySelectorAll('[data-image-slider]').forEach(function (element) {
            if (element.dataset.imageSliderReady) {
                return;
            }
            element.dataset.imageSliderReady = 'true';

            var slideCount = element.querySelectorAll('.swiper-slide').length;

            new window.Swiper(element, {
                slidesPerView: 1,
                spaceBetween: 0,
                loop: slideCount > 1,
                a11y: {
                    enabled: true,
                    // Taken from the markup, so the labels Swiper injects carry
                    // the language of the page rather than a hard-coded English.
                    prevSlideMessage: element.dataset.prevLabel || 'Previous image',
                    nextSlideMessage: element.dataset.nextLabel || 'Next image'
                },
                keyboard: { enabled: true, onlyInViewport: true },
                pagination: {
                    el: element.querySelector('.swiper-pagination'),
                    clickable: true
                },
                navigation: {
                    prevEl: element.querySelector('.swiper-button-prev'),
                    nextEl: element.querySelector('.swiper-button-next')
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSliders);
    } else {
        initSliders();
    }
})();
