"use strict";
/* ThemeVariables */
var ns = 700, ms = 500;
/* Functions */

function youtubeResize() {
    $('.youtube-video').height($(this).width() / (560 / 315));
};

function videoPause() {
    $("iframe").each(function() {
        $(this)[0].contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*')
    });
};

function scrollWidth() {
    var div = document.createElement('div');
    div.style.overflowY = 'scroll';
    div.style.width = '50px';
    div.style.height = '50px';
    div.style.visibility = 'hidden';
    document.body.appendChild(div);
    var s = div.offsetWidth - div.clientWidth;
    document.body.removeChild(div);
    return s;
};
// document.ready
$(document).ready(function() {
    // mobileNav
    $('#mobileButt').on('click', function() {
        $('body').toggleClass('mobile-menu-open');
    });
    // close mobileNav
    $('body').click(function(e) {
        if ($(e.target).closest('#mobileButt').length == 0) {
            $('body').removeClass('mobile-menu-open');
        }
    });
    // smoothScroll
    smoothScroll.init({
        selector: '.scroll',
        selectorHeader: '#header',
        easing: 'easeInOutCubic',
        speed: 800,
        offset: -25
    });
    // popups
    $('.popup').popup({
        transition: 'all 0.3s',
        scrolllock: true
    });
    // ajaxForms
    var forms = $('form.lead-form');
    forms.on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');
        $btn.prop('disabled', true);
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function(resp) {
            if (resp && resp.ok) {
                $form.trigger('reset');
                $('#sender').popup('show');
            } else {
                alert((resp && resp.error) ? resp.error : 'Не удалось отправить заявку.');
            }
        }).fail(function(xhr) {
            var msg = 'Не удалось отправить заявку.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            alert(msg);
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
    $('form').not('.lead-form').ajaxForm(function() {
        $(this).trigger('reset').popup('hide');
        $('#sender').popup('show');
    });
    // maskedInput
    $('.masked').mask('+7 (999) 999-99-99');
    // timers
    var timer = $('#timer'), timerDate = timer.data('timer');
    timer.countdown(timerDate, function(event) {
        $(this).html(event.strftime('' +
            '<strong>%D</strong>д ' +
            '<span class="pin">:</span> ' +
            '<strong>%H</strong>ч ' +
            '<span class="pin">:</span> ' +
            '<strong>%M</strong>м ' +
            '<span class="pin">:</span> ' +
            '<strong>%S</strong>с'
        ));
    })
    .on('update.countdown', function() {
        $(this).toggleClass('flash');
    })
    .countdown('start');
});
// window.load
$(window).on('load', function() {
    // preloader
    var $loader = $('#preloader'),
        $spin = $loader.find('.spinner');
    $spin.fadeOut();
    $loader.delay(200).fadeOut('slow');
});
// window.load-scroll
$(window).on('load scroll', function() {
    // fixed header
    var scrollTop = $(this).scrollTop(),
        head = $('#header'),
        headTop = head.offset().top;
    if (scrollTop > 0) head.addClass('fixed');
    else head.removeClass('fixed');
});
// window.load-resize
$(window).on('load resize', function() {
    // hacks
    if (($(window).innerWidth() + scrollWidth()) <= ns) $('body').addClass('m' + ns);
    else $('body').removeClass('m' + ns);
    if (($(window).innerWidth() + scrollWidth()) <= ms) $('body').addClass('m' + ms);
    else $('body').removeClass('m' + ms);
});
// window.resize
$(window).on("resize", function() {});