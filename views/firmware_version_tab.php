<h2>Firmware  <a data-i18n="firmware.recheck" class="btn btn-default btn-xs" href="<?php echo url('module/firmware/recheck_firmware/' . $serial_number);?>"></a></h2>

<div id="firmware-msg" data-i18n="listing.loading" class="col-lg-12 text-center"></div>
	<div id="firmware-view" class="row hide">
		<div class="col-md-3">
			<table class="table table-striped">
				<tr>
					<th data-i18n="firmware.machine_model"></th>
					<td id="firmware-machine_model"></td>
				</tr>
				<tr>
					<th data-i18n="firmware.boot_rom_version"></th>
					<td id="firmware-boot_rom_version"></td>
				</tr>
				<tr id="firmware-boot_rom_latest-view" class="hide">
					<th data-i18n="firmware.boot_rom_latest"></th>
					<td id="firmware-boot_rom_latest"></td>
				</tr>
				<tr id="firmware-boot_rom_outdated-view" class="hide">
					<th data-i18n="firmware.boot_rom_outdated"></th>
					<td id="firmware-boot_rom_outdated"></td>
				</tr>
				<tr id="firmware-hardware_model-view" class="hide">
					<th data-i18n="firmware.hardware_model"></th>
					<td id="firmware-hardware_model"></td>
				</tr>
				<tr id="firmware-ibridge_version-view" class="hide">
					<th data-i18n="firmware.ibridge_version"></th>
					<td id="firmware-ibridge_version"></td>
				</tr>
				<tr id="firmware-ibridge_latest-view" class="hide">
					<th data-i18n="firmware.ibridge_latest"></th>
					<td id="firmware-ibridge_latest"></td>
				</tr>
				<tr id="firmware-ibridge_outdated-view" class="hide">
					<th data-i18n="firmware.ibridge_outdated"></th>
					<td id="firmware-ibridge_outdated"></td>
				</tr>
			</table>
		</div>
	</div>

<script>
$(document).on('appReady', function(e, lang) {

	$('#firmware-cnt').text("");

	// Get firmware data
	$.getJSON( appUrl + '/module/firmware/get_data/' + serialNumber, function( data ) {
		// Check if we have valid data
		if(! data.machine_model){
			$('#firmware-msg').text(i18n.t('no_data'));
		} else {

			// Hide
			$('#firmware-msg').text('');
			$('#firmware-view').removeClass('hide');

			// Add data
			if (data.boot_rom_outdated == "1"){
				$('#firmware-cnt').text("!").addClass('alert-danger');
				$('#firmware-boot_rom_outdated').text(i18n.t('yes')).addClass('alert-danger');
			} else {
				$('#firmware-boot_rom_outdated').text(i18n.t('no'));
			}
			if (data.ibridge_outdated == "1"){
				$('#firmware-cnt').text("!").addClass('alert-danger');
				$('#firmware-ibridge_outdated').text(i18n.t('yes')).addClass('alert-danger');
			} else {
				$('#firmware-ibridge_outdated').text(i18n.t('no'));
			}

			$('#firmware-machine_model').text(data.machine_model);
			$('#firmware-boot_rom_version').text(data.boot_rom_version);

			// Don't unhide blank rows
			if (data.boot_rom_latest !== null){
				$('#firmware-boot_rom_latest-view').removeClass('hide');
				$('#firmware-boot_rom_outdated-view').removeClass('hide');
				$('#firmware-boot_rom_latest').text(data.boot_rom_latest);
			}
			if (data.hardware_model !== null){
				$('#firmware-hardware_model-view').removeClass('hide');
				$('#firmware-hardware_model').text(data.hardware_model);
			}
			if (data.ibridge_version !== null){
				$('#firmware-ibridge_version-view').removeClass('hide');
				$('#firmware-ibridge_version').text(data.ibridge_version);
			}
			if (data.ibridge_latest !== null){
				$('#firmware-ibridge_latest-view').removeClass('hide');
				$('#firmware-ibridge_outdated-view').removeClass('hide');
				$('#firmware-ibridge_latest').text(data.ibridge_latest);
			}
		}
	});
});

</script>