/* UIEIMS global UI behaviors */
(function ($) {
    'use strict';

    // Sidebar toggle (mobile)
    $(document).on('click', '#sidebarToggle', function () {
        $('#uieSidebar').toggleClass('show');
    });
    $(document).on('click', function (e) {
        if ($(window).width() < 992 &&
            !$(e.target).closest('#uieSidebar, #sidebarToggle').length) {
            $('#uieSidebar').removeClass('show');
        }
    });

    // Dependent dropdown helpers
    // <select data-depends="#regionSelect" data-endpoint="ajax/divisions-by-region">
    window.uieLoadOptions = function ($select, url, placeholder, selected) {
        $select.prop('disabled', true).empty()
            .append($('<option>').val('').text(placeholder || 'Loading...'));
        return $.getJSON(url).done(function (rows) {
            $select.empty().append($('<option>').val('').text('-- Select --'));
            $.each(rows, function (i, r) {
                var $o = $('<option>').val(r.id).text(r.school_id ? r.school_id + ' - ' + r.name : r.name);
                if (selected && String(r.id) === String(selected)) $o.prop('selected', true);
                $select.append($o);
            });
        }).always(function () {
            $select.prop('disabled', false);
        });
    };

    $(document).on('change', '[data-loads]', function () {
        var $src = $(this);
        var target = $src.data('loads');            // css selector of dependent select
        var urlTpl = $src.data('endpoint');          // e.g. siteUrl + 'ajax/divisions-by-region/'
        var $target = $(target);
        if (!$target.length) return;
        if (!$src.val()) {
            $target.empty().append($('<option>').val('').text('-- Select --')).trigger('change');
            return;
        }
        uieLoadOptions($target, urlTpl + encodeURIComponent($src.val()));
    });

    // Auto-submit filter forms on select change
    $(document).on('change', 'form.filter-form select.auto-submit', function () {
        $(this).closest('form').submit();
    });

    // Confirm on dangerous actions
    $(document).on('submit', 'form[data-confirm]', function (e) {
        if (!window.confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });
})(jQuery);
