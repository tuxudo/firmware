<?php $this->view('partials/head'); ?>

<div class="container">
    <div class="row"><span id="firmware_pull_all"></span></div>
    <div class="col-lg-5">
        <div id="GetAllFirmware-Progress" class="progress hide">
            <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%;">
                <span id="Progress-Bar-Percent"></span>
            </div>
        </div>
        <br id="Progress-Space" class="hide">
        <div id="Firmware-Status"></div>
    </div>
</div>  <!-- /container -->

<script>
var firmware_pull_all_running = 0;

$(document).on('appReady', function(e, lang) {

    // Get JSON of admin data
    $.getJSON(appUrl + '/module/firmware/get_admin_data', function (processdata) {

        // Build table
        var firmrows = '<table class="table table-striped table-condensed" id="firmware_status"><tbody>'

        if (processdata['last_update'] > 0){
            var date = new Date(processdata['last_update'] * 1000);
            firmrows = firmrows + '<tr><th>'+i18n.t('firmware.last_cache_update')+'</th><td id="firm_time"><span title="'+moment(date).fromNow()+'">'+moment(date).format('llll')+'</span></td></tr>';
        } else {
            firmrows = firmrows + '<tr><th>'+i18n.t('firmware.last_cache_update')+'</th><td id="firm_time">'+i18n.t('firmware.never')+'</td></tr>';
        }

        if (processdata['source'] == 1){
            firmrows = firmrows + '<tr><th>'+i18n.t('firmware.cache_source')+'</th><td id="firm_source"><a href="https://github.com/hoakleyelc/updates/" target="_blank">'+i18n.t('firmware.github')+'</a></td></tr>';
        } else if (processdata['source'] == 2){
            firmrows = firmrows + '<tr><th>'+i18n.t('firmware.cache_source')+'</th><td id="firm_source">'+i18n.t('firmware.local')+'</td></tr>';
        }

        $('#Firmware-Status').html(firmrows+'</tbody></table>') // Close table framework and assign to HTML ID
    });
    
    // Generate pull all button and header    
    $('#firmware_pull_all').html('<h3 class="col-lg-6" >&nbsp;&nbsp;'+i18n.t('firmware.title_admin')+'&nbsp;&nbsp;<button id="GetAllFirmware" class="btn btn-default btn-xs">'+i18n.t("firmware.pull_in_all")+'</button>&nbsp;&nbsp;<button id="UpdateFirmware" class="btn btn-default btn-xs">'+i18n.t("firmware.update_cache_file")+'</button>&nbsp;<i id="GetAllFirmwareProgess" class="hide fa fa-cog fa-spin" aria-hidden="true"></i></h3>');
    
    // Update cache file function
    $('#UpdateFirmware').click(function (e) {
        // Disable buttons
        $('#GetAllFirmware').addClass('disabled');
        $('#GetAllFirmwareProgess').removeClass('hide');
        $('#UpdateFirmware').addClass('disabled');
        
        $.getJSON(appUrl + '/module/firmware/update_cached_firmware_data', function (processdata) {
            if(processdata['status'] == 1){
                var date = new Date(processdata['timestamp'] * 1000);
                $('#firm_time').html('<span title="'+moment(date).fromNow()+'">'+moment(date).format('llll')+'</span>')
                $('#firm_source').html('<a href="https://github.com/munkireport/firmware/blob/master/firmware_data.yml" target="_blank">'+i18n.t('firmware.update_from_github')+'</a>')
                $('#GetAllFirmware').removeClass('disabled');
                $('#UpdateFirmware').removeClass('disabled');
                $('#GetAllFirmwareProgess').addClass('hide');
                
            } else if(processdata['status'] == 2){
                
                var date = new Date(processdata['timestamp'] * 1000);
                $('#firm_time').html('<span title="'+moment(date).fromNow()+'">'+moment(date).format('llll')+'</span>')
                $('#firm_source').html(i18n.t('firmware.update_from_local'))
                $('#GetAllFirmware').removeClass('disabled');
                $('#UpdateFirmware').removeClass('disabled');
                $('#GetAllFirmwareProgess').addClass('hide');
            }
        });
    });

    firmware_pull_all_running = 0;

    // Process all serials
    $('#GetAllFirmware').click(function (e) {
        // Disable button and unhide progress bar
        $('#GetAllFirmware').html(i18n.t('firmware.processing')+'...');
        $('#Progress-Bar-Percent').text('0%');
        $('#GetAllFirmware-Progress').removeClass('hide');
        $('#Progress-Space').removeClass('hide');
        $('#GetAllFirmware').addClass('disabled');
        $('#UpdateFirmware').addClass('disabled');
        firmware_pull_all_running = 1;

        // Get JSON of all serial numbers
        $.getJSON(appUrl + '/module/firmware/pull_all_firmware_data', function (processdata) {

            // Set count of serial numbers to be processed
            var progressmax = (processdata.length);
            var progessvalue = 0;;
            $('.progress-bar').attr('aria-valuemax', progressmax);

            var serial_index = 0;
            var serial = processdata[0]

            // Process the serial numbers
            process_serial(serial,progessvalue,progressmax,processdata,serial_index)
        });
    });
});

// Process each serial number one at a time
function process_serial(serial,progessvalue,progressmax,processdata,serial_index){

        // Get JSON for each serial number
        request = $.ajax({
        url: appUrl + '/module/firmware/pull_all_firmware_data/'+processdata[serial_index],
        type: "get",
        success: function (obj, resultdata) {

            // Calculate progress bar's percent
            var processpercent = Math.round((((progessvalue+1)/progressmax)*100));
            progessvalue++
            $('.progress-bar').css('width', (processpercent+'%')).attr('aria-valuenow', processpercent);
            $('#Progress-Bar-Percent').text(progessvalue+"/"+progressmax);

            // Cleanup and reset when done processing serials
            if ((progessvalue) == progressmax) {
                // Make button clickable again and hide process bar elements
                $('#GetAllFirmware').html(i18n.t('firmware.pull_in_all'));
                $('#GetAllFirmware').removeClass('disabled');
                $('#UpdateFirmware').removeClass('disabled');
                firmware_pull_all_running = 0;
                $("#Progress-Space").fadeOut(1200, function() {
                    $('#Progress-Space').addClass('hide')
                    var progresselement = document.getElementById('Progress-Space');
                    progresselement.style.display = null;
                    progresselement.style.opacity = null;
                });
                $("#GetAllFirmware-Progress").fadeOut( 1200, function() {
                    $('#GetAllFirmware-Progress').addClass('hide')
                    var progresselement = document.getElementById('GetAllFirmware-Progress');
                    progresselement.style.display = null;
                    progresselement.style.opacity = null;
                    $('.progress-bar').css('width', 0+'%').attr('aria-valuenow', 0);
                });

                return true;
            }

            // Go to the next serial
            serial_index++

            // Get next serial
            serial = processdata[serial_index];

            // Run function again with new serial
            process_serial(serial,progessvalue,progressmax,processdata,serial_index)
        },
        statusCode: {
            500: function() {
                firmware_pull_all_running = 0;
                alert("An internal server occurred. Please refresh the page and try again.");
            }
        }
    });
}

// Warning about leaving page if supported os pull all is running
window.onbeforeunload = function() {
    if (firmware_pull_all_running == 1) {
        return i18n.t('firmware.leave_page_warning');
    } else {
        return;
    }
};
    
</script>

<?php $this->view('partials/foot'); ?>
