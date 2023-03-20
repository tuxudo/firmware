#!/bin/bash

# firmware controller
CTL="${BASEURL}index.php?/module/firmware/"

# Get the scripts in the proper directories
"${CURL[@]}" "${CTL}get_script/firmware" -o "${MUNKIPATH}preflight.d/firmware"

# Check exit status of curl
if [ $? = 0 ]; then
	# Make executable
	/bin/chmod a+x "${MUNKIPATH}preflight.d/firmware"

	# Set preference to include this file in the preflight check
	setreportpref "firmware" "${CACHEPATH}firmware.plist"

else
	echo "Failed to download all required components!"
	/bin/rm -f "${MUNKIPATH}preflight.d/firmware"

	# Signal that we had an error
	ERR=1
fi