function init() {
    // Place code that we need to run on every page load here

    // Prevent UniformJS from breaking our stuff
    $(document).uniform.restore();

    // Instantiate sidebar
    $('.has-sidebar').sidebarize();

    // Apply Select2
    $('form select').select2({ allowClear: true });

    // Apply Select2 multiselect
    $('form input.multiselect').each(function() {
        $(this).select2({ tags: $(this).val() });
    });

    // Apply bootstrapSwitch
    $('.switch:not(.has-switch)').bootstrapSwitch();

    // Destroy Select2 where it's not necessary
    $('#default_channel').select2('destroy');

    // Activate a form tab
    $('li.tab.active a').each(function() {
        var paneId = $(this).attr('href');
        $(paneId).addClass('active');
    });

    // Toogle accordion icon
    $('.accordion').on('show hide', function(e) {
        $(e.target).siblings('.accordion-heading').find('.accordion-toggle i').toggleClass('fa-icon-collapse-alt fa-icon-expand-alt');
    });

    $('.remove-attribute').each(function() {
        var target = $(this).parent().find('input:not([type="hidden"]):not([class*=select2]), select, textarea').first();
        $(this).insertAfter(target).css('margin-left', 20).attr('tabIndex', -1);
    });

    // Add form update listener
    $('form[data-updated]').each(function() {
        new FormUpdateListener($(this).attr('id'), $(this).data('updated'));
    });

    // Instantiate the tree
    $('[data-tree]').each(function() {
        switch ($(this).attr('data-tree')) {
            case 'associate':
                Pim.tree.associate($(this).attr('id'));
                break;
            case 'view':
                Pim.tree.view($(this).attr('id'));
                break;
            default:
                break;
        }
    });

}

$(function() {
    'use strict';

    // Execute the init function on page load
    init();
});

// Listener for form update events (used in product edit form)
var FormUpdateListener = function(formId, message) {
    var self = this;
    this.updated = false;

    this.formUpdated = function() {
        this.updated = true;
        $('#updated').show();
        $('form#' + formId).off('change', this.formUpdated);
        $('form#' + formId + ' ins.jstree-checkbox').off('click', this.formUpdated);

        // This will not work with backbone navigation
        $(window).on('beforeunload', function() {
            if (self.updated) {
                return message;
            }
        });
        $('form#' + formId + ' button[type="submit"]').on('click', function() {
            self.updated = false;
        });
    };

    $('form#' + formId).on('change', this.formUpdated);
    $('form#' + formId + ' ins.jstree-checkbox').on('click', this.formUpdated);
};
