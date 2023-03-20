<?php

use munkireport\lib\Request;
use CFPropertyList\CFPropertyList;

/**
 * Firmware module class
 *
 * @package munkireport
 * @author AvB
 **/
class Firmware_controller extends Module_controller
{
    public function __construct()
    {
        // Store module path
        $this->module_path = dirname(__FILE__);
    }

    public function index()
    {
        echo "You've loaded the firmware module!";
    }
    
    public function admin()
    {
        $obj = new View();
        $obj->view('firmware_admin', [], $this->module_path.'/views/');
    }

    /**
     * Get boot_rom_outdated for widget
     *
     * @return void
     * @author tuxudo
     **/
    public function get_boot_rom_outdated()
    {
        $sql = "SELECT COUNT(CASE WHEN boot_rom_outdated IS NOT NULL THEN 1 END) AS count, boot_rom_outdated
                FROM firmware
                LEFT JOIN reportdata USING (serial_number)
                ".get_machine_group_filter()."
                GROUP BY boot_rom_outdated
                ORDER BY count DESC";

        $out = [];
        $queryobj = new Firmware_model;
        foreach ($queryobj->query($sql) as $obj) {
            if ("$obj->count" !== "0") {
                if ($obj->boot_rom_outdated == "1"){
                    $obj->lable = "Yes";
                } else {
                    $obj->lable = "No";
                }
                $out[] = $obj;
            }
        }

        jsonView($out);
    }

    /**
     * Get ibridge_outdated for widget
     *
     * @return void
     * @author tuxudo
     **/
    public function get_ibridge_outdated()
    {
        $sql = "SELECT COUNT(CASE WHEN ibridge_outdated IS NOT NULL THEN 1 END) AS count, ibridge_outdated
                FROM firmware
                LEFT JOIN reportdata USING (serial_number)
                ".get_machine_group_filter()."
                GROUP BY ibridge_outdated
                ORDER BY count DESC";

        $out = [];
        $queryobj = new Firmware_model;
        foreach ($queryobj->query($sql) as $obj) {
            if ("$obj->count" !== "0") {
                if ($obj->ibridge_outdated == "1"){
                    $obj->lable = "Yes";
                } else {
                    $obj->lable = "No";
                }
                $out[] = $obj;
            }
        }

        jsonView($out);
    }
    
    /**
     * Force data pull from firmware GitHub
     *
     * @return void
     * @author tuxudo
     **/
    public function update_cached_firmware_data()
    {
        $queryobj = new Firmware_model();

        // Get YAML from Eclectic Light Company (https://github.com/hoakleyelc/updates) GitHub repo
        $web_request = new Request();
        $options = ['http_errors' => false];
        $apple_silicon_result = (string) $web_request->get('https://raw.githubusercontent.com/hoakleyelc/updates/master/applesilicon.plist', $options);

        // Check if we got results before trying all the plists
        if (strpos($apple_silicon_result, '<key>MacModel</key>') === false ){
            error_log("Unable to fetch new firmware data plist from firmware GitHub!! Using local version instead. ");
            // print_r("Unable to fetch new firmware data plist from GitHub!! Using local version instead. ");
            $yaml_result = file_get_contents(__DIR__ . '/firmware_data.yml');
            $return_status = 2;
            $cache_source = 2;
        } else {
            // print_r("Updating firmware data from GitHub. ");
            $return_status = 1;
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
            // foreach ([$apple_silicon_result] as $firmware_plist) {
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

        // Get the current time
        $current_time = time();
        
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
                'property' => 'last_update ',
            ],[
                'value' => $current_time,
                'timestamp' => $current_time,
            ]
        );
        
        // Send result
        $out = array("status"=>$return_status,"source"=>$cache_source,"timestamp"=>$current_time);
        jsonView($out);
    }

     /**
     * Pull in firmware data for all serial numbers :D
     *
     * @return void
     * @author tuxudo
     **/
    public function pull_all_firmware_data($incoming_serial = '')
    {
        // Check if we are returning a list of all serials or processing a serial
        // Returns either a list of all serial numbers in MunkiReport OR
        // a JSON of what serial number was just ran with the status of the run

        // Remove non-serial number characters
        $incoming_serial = preg_replace("/[^A-Za-z0-9_\-]]/", '', $incoming_serial);

        if ( $incoming_serial == ''){
            // Get all the serial numbers in an object
            $machine = new Firmware_model();
            $filter = get_machine_group_filter();

            $sql = "SELECT machine.serial_number
                        FROM machine
                        LEFT JOIN reportdata USING (serial_number)
                        $filter";

            // Loop through each serial number for processing
            $out = array();
            foreach ($machine->query($sql) as $serialobj) {
                $out[] = $serialobj->serial_number;
            }

            // Send result
            jsonView($out);

        } else {

            // Get machine model data
            $machine = new Firmware_model();
            $sql = "SELECT machine.machine_model, machine.boot_rom_version
                        FROM machine
                        WHERE serial_number = '".$incoming_serial."'";

            $data = [];
            $data["machine_model"] = $machine->query($sql)[0]->machine_model;

            $bootrom_data = $machine->query($sql)[0]->boot_rom_version;
            if (! is_null($bootrom_data)){
                $data["boot_rom_version"] = explode (" ", $machine->query($sql)[0]->boot_rom_version)[0];
            }

            // Get ibrige model data
            $machine = new Firmware_model();
            $sql = "SELECT ibridge.ibridge_version, ibridge.hardware_model
                        FROM ibridge
                        WHERE serial_number = '".$incoming_serial."'";

            $data["serial_number"] = $incoming_serial;
            $data["reprocess"] = true;

            $ibridge_data = $machine->query($sql);
            if (array_key_exists(0, $ibridge_data) && ! is_null($ibridge_data[0]->ibridge_version) && !strpos($ibridge_data[0]->ibridge_version, ',') === false ){
                $data["ibridge_version"] = explode (",", $ibridge_data[0]->ibridge_version)[0];
                $data["hardware_model"] = $ibridge_data[0]->hardware_model;
            }

            // Process the serial in the model
            $firmware_process = new Firmware_model();

            // Send result
            jsonView($firmware_process->process($data));
        }
    }
    
    /**
     * Reprocess serial number
     *
     * @return void
     * @author tuxudo
     **/
    public function recheck_firmware($serial)
    {   
        // Remove non-serial number characters
        $serial = preg_replace("/[^A-Za-z0-9_\-]]/", '', $serial);

        // Process the serial in the model
        $machine = new Firmware_model();
        $sql = "SELECT machine.machine_model, machine.boot_rom_version
                    FROM machine
                        WHERE serial_number = '".$serial."'";

        $data = [];
        $data["machine_model"] = $machine->query($sql)[0]->machine_model;
        $bootrom_data = $machine->query($sql)[0]->boot_rom_version;
        if (! is_null($bootrom_data)){
            $data["boot_rom_version"] = explode (" ", $machine->query($sql)[0]->boot_rom_version)[0];
        }

         // Get ibrige model data
        $machine = new Firmware_model();
        $sql = "SELECT ibridge.ibridge_version, ibridge.hardware_model
                    FROM ibridge
                    WHERE serial_number = '".$serial."'";

        $data["serial_number"] = $serial;
        $data["reprocess"] = true;

        $ibridge_data = $machine->query($sql);
        if (array_key_exists(0, $ibridge_data) && ! is_null($ibridge_data[0]->ibridge_version) && !strpos($ibridge_data[0]->ibridge_version, ',') === false ){
            $data["ibridge_version"] = explode (",", $ibridge_data[0]->ibridge_version)[0];
            $data["hardware_model"] = $ibridge_data[0]->hardware_model;
        }   

        $machine->process($data);

        // Send people back to the client tab once serial is reprocessed
        redirect("clients/detail/$serial#tab_firmware_version-tab");
    }

    /**
     * Return JSON with information for admin page
     *
     * @return void
     * @author tuxudo
     **/
    public function get_admin_data()
    {
        $source = munkireport\models\Cache::select('value')
                        ->where('module', 'firmware')
                        ->where('property', 'source')
                        ->value('value');
        $last_update = munkireport\models\Cache::select('value')
                        ->where('module', 'firmware')
                        ->where('property', 'last_update')
                        ->value('value');

        $out = array('source' => $source,'last_update' => $last_update);
        jsonView($out);
    }

    /**
     * Retrieve data in json format
     *
     **/
    public function get_data($serial_number = '')
    {
        $firmware = new Firmware_model($serial_number);        
        jsonView($firmware->rs);
    }
} // End class Firmware_controller
