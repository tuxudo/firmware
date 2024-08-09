<?php

use CFPropertyList\CFPropertyList;
use munkireport\lib\Request;

class Firmware_model extends \Model
{
    public function __construct($serial = '')
    {
        parent::__construct('id', 'firmware'); // Primary key, tablename
        $this->rs['id'] = '';
        $this->rs['serial_number'] = $serial;
        $this->rs['machine_model'] = null; # Mac14,2
        $this->rs['boot_rom_version'] = null; # 7459.141.1 or 429.0.0.0.0
        $this->rs['boot_rom_latest'] = null; # 7459.141.1 or 429.0.0.0.0
        $this->rs['boot_rom_outdated'] = null; # Int 0/1
        $this->rs['hardware_model'] = null;  # J413AP
        $this->rs['ibridge_version'] = null; # 19.16.16067.0.0,0
        $this->rs['ibridge_latest'] = null; # 19.16.16067.0.0
        $this->rs['ibridge_outdated'] = null; # Int 0/1

        if ($serial) {
            $this->retrieve_record($serial);
        }

        $this->serial_number = $serial;
    }

    // ------------------------------------------------------------------------

    /**
     * Process method, is called by the client
     *
     * @return void
     * @author tuxudo
     **/
    public function process($data)
    {
        // Check if we have data
        if ( ! $data){
            throw new Exception("Error Processing Request: No property list found", 1);
        }

        // Check if we have cached firmware YAML
        $cached_data_time = munkireport\models\Cache::select('value')
                        ->where('module', 'firmware')
                        ->where('property', 'last_update')
                        ->value('value');

        // Get the current time
        $current_time = time();
        
        // Check if we have a null result or a week has passed
        if($cached_data_time == null || ($current_time > ($cached_data_time + 604800))){

            // Get YAML from Eclectic Light Company (https://github.com/hoakleyelc/updates) GitHub repo
            $web_request = new Request();
            $options = ['http_errors' => false];
            $apple_silicon_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/applesilicon.plist', $options);

            // Check if we got results before trying all the plists
            if (strpos($apple_silicon_result, '<key>MacModel</key>') === false ){
                error_log("Unable to fetch new firmware data plist from firmware GitHub!! Using local version instead. ");
                // print_r("Unable to fetch new firmware data plist from GitHub!! Using local version instead. ");
                $yaml_result = file_get_contents(__DIR__ . '/firmware_data.yml');
                $cache_source = 2;
            } else {
                // print_r("Updating firmware data from GitHub. ");
                $cache_source = 1;

                // Get the rest of the firmware plists
                $macbook_pro_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/MacBookPro.plist', $options);
                $macbook_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/MacBook.plist', $options);
                $macbook_air_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/MacBookAir.plist', $options);
                $mac_pro_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/MacPro.plist', $options);
                $mac_mini_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/Macmini.plist', $options);
                $imac_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/iMac.plist', $options);
                $imac_pro_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/iMacPro.plist', $options);

                // Merge plists into single yaml file
                $yaml_result = array();
                foreach ([$apple_silicon_result, $macbook_pro_result, $macbook_result, $macbook_air_result, $mac_pro_result, $mac_mini_result, $imac_result, $imac_pro_result] as $firmware_plist) {

                    // Turn the plists into objects
                    $parser = new CFPropertyList();
                    $parser->parse($firmware_plist, CFPropertyList::FORMAT_XML);
                    $plist = $parser->toArray();

                    // Process each Mac in the firmware plists
                    foreach($plist as $mac_model){
                        $firmware_array = array();

                        if(array_key_exists("EFIversion", $mac_model)) {
                            $firmware_array['boot_rom_latest'] = $mac_model["EFIversion"];
                        }
                        if(array_key_exists("iBootversion", $mac_model)) {
                            $firmware_array['boot_rom_latest'] = $mac_model["iBootversion"];
                        }
                        if(array_key_exists("iBridge", $mac_model)) {
                            $firmware_array['ibridge_latest'] = $mac_model["iBridge"];
                        }
                        $yaml_result[$mac_model["MacModel"]] = $firmware_array;
                    }
                }

                $yaml_result = Symfony\Component\Yaml\Yaml::dump($yaml_result);
            }

            $yaml_data = (object) Symfony\Component\Yaml\Yaml::parse($yaml_result);

            // Save new cache data to the cache table
            munkireport\models\Cache::updateOrCreate(
                [
                    'module' => 'firmware',
                    'property' => 'yaml',
                ],[
                    'value' => $yaml_result,
                    'timestamp' => $current_time,
                ]
            );
            munkireport\models\Cache::updateOrCreate(
                [
                    'module' => 'firmware',
                    'property' => 'source',
                ],[
                    'value' => $cache_source,
                    'timestamp' => $current_time,
                ]
            );
            munkireport\models\Cache::updateOrCreate(
                [
                    'module' => 'firmware',
                    'property' => 'last_update',
                ],[
                    'value' => $current_time,
                    'timestamp' => $current_time,
                ]
            );
        } else {

            // Retrieve cached YAML from database
            $yaml_result = munkireport\models\Cache::select('value')
                            ->where('module', 'firmware')
                            ->where('property', 'yaml')
                            ->value('value');
        }

        // Decode YAML
        $yaml_data = (object) Symfony\Component\Yaml\Yaml::parse($yaml_result);
        $yaml_data = json_decode(json_encode($yaml_data), TRUE);

        // Check if we are processing a plist or not
        if(!is_array($data)){
            $parser = new CFPropertyList();
            $parser->parse($data);
            $plist = $parser->toArray();

            $this->rs['machine_model'] = $plist['machine_model'];
            if (array_key_exists('boot_rom_version', $plist)) {
                $this->rs['boot_rom_version'] = $plist['boot_rom_version'];
            }
            if (array_key_exists('ibridge_version', $plist)) {
                $this->rs['hardware_model'] = $plist['hardware_model'];
                $this->rs['ibridge_version'] = $plist['ibridge_version'];
            }

        } else if($data['reprocess']){
            $this->retrieve_record($data['serial_number']);
            $this->rs['serial_number'] = $data['serial_number'];
            $this->rs['machine_model'] = $data['machine_model'];
            if (array_key_exists('boot_rom_version', $data)) {
                $this->rs['boot_rom_version'] = $data['boot_rom_version'];
            }
            if (array_key_exists('ibridge_version', $data)) {
                $this->rs['hardware_model'] = $data['hardware_model'];
                $this->rs['ibridge_version'] = $data['ibridge_version'];
            }
        }

        // Process machine_model for bootrom_latest and ibridge_latest
        if (array_key_exists($this->rs['machine_model'], $yaml_data)) {

            // Process bootrom_latest
            if (array_key_exists('boot_rom_latest', $yaml_data[$this->rs['machine_model']])) {
                $this->rs['boot_rom_latest'] = $yaml_data[$this->rs['machine_model']]['boot_rom_latest'];

                // Compare to make sure we're running the latest version
                if(! is_null($this->rs['boot_rom_version']) && version_compare($this->rs['boot_rom_version'], $this->rs['boot_rom_latest'], '>=')) {
                    $this->rs['boot_rom_outdated'] = 0;
                } else if (is_null($this->rs['boot_rom_version'])){
                    $this->rs['boot_rom_outdated'] = null;
                } else {
                    $this->rs['boot_rom_outdated'] = 1;
                }
            }

            // Process ibridge_latest
            if (array_key_exists('ibridge_latest', $yaml_data[$this->rs['machine_model']])) {
                $this->rs['ibridge_latest'] = $yaml_data[$this->rs['machine_model']]['ibridge_latest'];

                // Compare to make sure we're running the latest version
                if(! is_null($this->rs['ibridge_version']) && version_compare($this->rs['ibridge_version'], $this->rs['ibridge_latest'], '>=')) {
                    $this->rs['ibridge_outdated'] = 0;
                } else if (is_null($this->rs['ibridge_version'])){
                    $this->rs['ibridge_outdated'] = null;
                } else {
                    $this->rs['ibridge_outdated'] = 1;
                }
            }

        // Else check if we're an Apple Silion Mac and the machine model
        // is not in the array, use the "other" key if it exists
        } else if (preg_match('/^Mac[0-9]/', $this->rs['machine_model']) && array_key_exists("other", $yaml_data)) {

            // Process bootrom_latest
            if (array_key_exists('boot_rom_latest', $yaml_data["other"])) {
                $this->rs['boot_rom_latest'] = $yaml_data["other"]['boot_rom_latest'];

                // Compare to make sure we're running the latest version
                if(! is_null($this->rs['boot_rom_version']) && version_compare($this->rs['boot_rom_version'], $this->rs['boot_rom_latest'], '>=')) {
                    $this->rs['boot_rom_outdated'] = 0;
                } else if (is_null($this->rs['boot_rom_version'])){
                    $this->rs['boot_rom_outdated'] = null;
                } else {
                    $this->rs['boot_rom_outdated'] = 1;
                }
            }

            // Process ibridge_latest
            if (array_key_exists('ibridge_latest', $yaml_data["other"])) {
                $this->rs['ibridge_latest'] = $yaml_data["other"]['ibridge_latest'];

                // Compare to make sure we're running the latest version
                if(! is_null($this->rs['ibridge_version']) && version_compare($this->rs['ibridge_version'], $this->rs['ibridge_latest'], '>=')) {
                    $this->rs['ibridge_outdated'] = 0;
                } else if (is_null($this->rs['ibridge_version'])){
                    $this->rs['ibridge_outdated'] = null;
                } else {
                    $this->rs['ibridge_outdated'] = 1;
                }
            }

        } else {
            // Error out if we cannot locate that machine.
            error_log("Machine model '".$this->rs['machine_model']."' not found in firmware data. ");
        }

        // Save firmware datas
        $this->save();

        // Return something if reprocessing
        if(is_array($data)){
            return true;
        }
    } // End process()
}
