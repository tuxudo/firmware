Firmware Module
==============

Lists the latest and current firmware versions for a Mac.

Firmware data is updated by module once a week from Eclectic Light Company `updates` GitHub page ([https://github.com/hoakleyelc/updates](https://github.com/hoakleyelc/updates)). Can be manually updated from admin page. Module will use data contained within module if unable to access firmware data on GitHub.

 
Remarks
---

* The client triggers the server to do a lookup once a week
* Admin page provides ability to process all machines at once


Table Schema
---
* machine_model (int) Machine ID
* boot_rom_version (int) Current boot ROM version
* boot_rom_latest (string) Latest version of boot ROM
* boot_rom_outdated (int) 0/1 for if boot ROM is outdated
* hardware_model (int) Hardware model of iBridge
* ibridge_version (int) Current version of iBridge firmware
* ibridge_latest (int) Latest version of iBridge firmware
* ibridge_outdated (int) 0/1 for if iBridge is outdated
