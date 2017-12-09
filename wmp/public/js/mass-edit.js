var MassEdit = {};

MassEdit.settings = {
    'checkboxSelector': '.check-item',
    'checkboxAllSelector': '.check-all',
    'counterSelector': '.selected-counter',
    'idsFieldSelector': '.ids-field',
    'noneSelectedMessage': 'Please select at least one item',
    'massEditUrl': '#'
};

MassEdit.countSelected = function () {
    $(MassEdit.settings.counterSelector)
        .text($(MassEdit.settings.checkboxSelector + ':checked').length);
};

MassEdit.init = function (options) {
    $.extend(MassEdit.settings, options);

    $(MassEdit.settings.checkboxAllSelector).on('click', function () {
        var checked = $(this).prop('checked');
        $(MassEdit.settings.checkboxAllSelector).prop('checked', checked);

        $(MassEdit.settings.checkboxSelector).prop('checked', false);
        $(MassEdit.settings.checkboxSelector + ':visible').prop('checked', checked);
        MassEdit.countSelected();
    });

    $(MassEdit.settings.checkboxSelector).on('click', MassEdit.countSelected);

    $('.tablesorter-sticky-wrapper').append(
        '<a href="' + MassEdit.settings.massEditUrl + '" class="btn btn-default ajax-form mass-edit-sticky" ' +
        'data-form-init="MassEdit.checkSelected" data-form-modal-init="MassEdit.setIds">' +
        '<i class="fa fa-pencil"></i></a>' +
        '<div class="selected-counter sticky-selected-counter">0</div>'
    );
};

MassEdit.checkSelected = function () {
    var inputs = $(MassEdit.settings.checkboxSelector + ':checked');

    if (inputs.length == 0) {
        alert(MassEdit.settings.noneSelectedMessage);
        return false;
    }

    return true;
};

MassEdit.setIds = function () {
    var ids = [];
    $(MassEdit.settings.checkboxSelector + ':checked').each(function () {
        ids.push(this.value);
    });

    $(MassEdit.settings.idsFieldSelector).val(ids);
};
