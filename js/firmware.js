
var firmwareboot_rom_outdatedFilter = function(colNumber, d){

    // Look for 'between' statement todo: make generic
    if(d.search.value.match(/^boot_rom_outdated = \d$/))
    {
        // Add column specific search
        d.columns[colNumber].search.value = d.search.value.replace(/.*(\d)$/, '= $1');

        // Clear global search
        d.search.value = '';
    }
}

var firmwareibridge_outdatedFilter = function(colNumber, d){

    // Look for 'between' statement todo: make generic
    if(d.search.value.match(/^ibridge_outdated = \d$/))
    {
        // Add column specific search
        d.columns[colNumber].search.value = d.search.value.replace(/.*(\d)$/, '= $1');

        // Clear global search
        d.search.value = '';
    }
}

var firmware_outdated = function(colNumber, row){
    var col = $('td:eq('+colNumber+')', row),
        colvar = col.text();

    colvar = colvar == '1' ? '<span class="label label-danger">'+i18n.t('yes')+'</span>' :
    (colvar === '0' ? '<span class="label label-success">'+i18n.t('no')+'</span>' : colvar)
    col.html(colvar)
}
