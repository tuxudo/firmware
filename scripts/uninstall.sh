#!/bin/bash

# Remove firmware script
/bin/rm -f "${MUNKIPATH}preflight.d/firmware"

# Remove firmware.plist file
/bin/rm -f "${CACHEPATH}firmware.plist"
