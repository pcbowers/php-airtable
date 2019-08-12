<?php
namespace pcbowers\Airtable;

class Airtable {
    const API_URL = "https://api.airtable.com/v0/"; //API URL
    private $api_key; //Your Airtable API Key
    private $base_id; //Your Base ID
    private $log = []; //Log of Errors and Successes of each Curl Request
    private $log_num = 0; //Current # of logs in the log

    public function __construct($config) {
        if(is_array($config)) {
            $this->setApiKey($config["api_key"]);
            $this->setBaseId($config["base_id"]);
        }
    }

    private function addToLog($function_name=false, $type=false, $mesg=false) { //add custom messages to log
        $this->log_num ++;
        if(!$type) $type = "SUCCESS";
        if(!$mesg) $mesg = "Curl requested sucessfully";
        if(!$function_name) $function_name = "'Function'";
        else $function_name = "'".$function_name."'";
        $function_name .= " ".date('l jS \of F Y h:i:s A')." Log ".$this->log_num;
        $this->log[$function_name] = array($type => $mesg);
    }

    private function checkError($data, $function_name=false, $success_type=false, $success_mesg=false) { //check for curl errors
        if(array_key_exists("error", $data)) {
            $this->addToLog($function_name, $data["error"]["type"], $data["error"]["message"]);
            return true;
        }

        $this->addToLog($function_name, $success_type, $success_mesg);
        return false;
    }

    private function setApiKey($key) { //set the API key
        $this->api_key = $key;
    }

    private function setBaseId($id) { //set the Base ID
        $this->base_id = $id;
    }

    private function getApiUrl($request) { //create the API URL
        $request = str_replace(' ', '%20', $request); //make sure there are no spaces
        return self::API_URL.$this->getBaseId()."/".$request; //append the request to the url
    }

    private function getCurl($table, $type, $params, $id=0, $data=[], $destructive=false) {
        $curl = curl_init(); //begin the curl session
        $request = $table; //add the table to the request url
        $data["typecast"] = true; //allow typecasting

        switch($type) {
            case "list": //listRecords
                if(!empty($params)) $request .= "?".http_build_query($params);
                break;
            case "retrieve": //retreiveRecords
                $request .= "/".$id;
                break;
            case "create": //createRecord
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POST, count($data));

                if(!empty($data["fields"])) {
                    $request .= "?".http_build_query($data);
                    $data = json_encode($data);
                } else $data = '{"fields":{}}';

                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

                break;
            case "update": //updateRecord
                if(!$destructive) curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                else curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT'); //always does destructive
                curl_setopt($curl,CURLOPT_POST, count($data));

                $request .= "/".$id;
                if(!empty($data["fields"])) {
                    $request .= "?".http_build_query($data);
                    $data = json_encode($data);
                } else $data = '{"fields":{}}';

                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

                break;
            case "delete": //deleteRecord
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

                $request .= "/".$id;
                break;
        }

        $headers = array( //curl Headers
            'Content-Type: application/json',
            sprintf('Authorization: Bearer %s', $this->api_key)
        );

        curl_setopt($curl, CURLOPT_URL, $this->getApiUrl($request));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        return $curl;
    }

    public function getApiKey() { //return ApiKey
        return $this->api_key;
    }

    public function getBaseId() { //return BaseId
        return $this->base_id;
    }

    public function getLastLog() { //return the last log
        return array_slice($this->log, -1, true);
    }

    public function getLog() { //return the entire log
        return $this->log;
    }

    public function listRecords($table, $params=[]) { //list records
        $offset = 0;
        $results = [];
        $output = [];

        //allow user to stop offset checking
        $checkOffset = true;
        if(array_key_exists("checkOffset", $params)) {
            $checkOffset = $params["checkOffset"];
            unset($params["checkOffset"]);
        }

        do {
            if($offset) $params["offset"] = $output["offset"]; //add offset parameter if necessary
            else unset($params["offset"]);

            $curl = $this->getCurl($table, "list", $params); //begin Curl Request
            $output = json_decode(curl_exec($curl), true); //execute Curl Request
            curl_close($curl); //end Curl Request

            if($this->checkError($output, "listRecord", "SUCCESS", "Records listed successfully")) return false;

            $results = array_merge($results, $output["records"]);
        } while($offset = array_key_exists("offset", $output) && $checkOffset); //if there are pages, search those too

        return $results;
    }

    public function retrieveRecord($table, $user_id) {
        $curl = $this->getCurl($table, "retrieve", [], $user_id); //begin Curl Request
        $results = json_decode(curl_exec($curl), true); //execute Curl Request
        curl_close($curl); //end Curl Request

        if($this->checkError($results, "retrieveRecord", "SUCCESS", "Record retrieved succesfully")) return false;
        return $results;
    }

    public function createRecord($table, $data=[]) {
        $data = array('fields' => $data);

        $curl = $this->getCurl($table, "create", [], 0, $data); //begin Curl Request
        $results = json_decode(curl_exec($curl), true); //execute Curl Request
        curl_close($curl); //end Curl Request

        if($this->checkError($results, "createRecord", "SUCCESS", "Record created successfully")) return false;

        return $results;
    }

    public function updateRecord($table, $id, $data=[], $destructive=false) {
        $data = array("fields" => $data);

        $current_data = array('fields' => $data["fields"]);
        if($destructive === "false") { //special patch that allows removal of fields if left empty
            $current_data = $this->retrieveRecord($table, $id);
            if($current_data && !empty($current_data)) { //merges data if available
                foreach($data["fields"] as $key => $value) {
                    if(isset($current_data["fields"][$key])) {
                        if(empty($value)) unset($current_data["fields"][$key]);
                        else $current_data["fields"][$key] = $data["fields"][$key];
                    }
                }
                $destructive = true; //allows removal of fields
                $current_data = array('fields' => $current_data["fields"]);
            }
        }

        $curl = $this->getCurl($table, "update", [], $id, $current_data, $destructive); //begin Curl Request
        $results = json_decode(curl_exec($curl), true); //execute Curl Request
        curl_close($curl); //end Curl Request

        if($this->checkError($results, "updateRecord", "SUCCESS", "Record updated successfully")) return false;

        return $results;
    }

    public function deleteRecord($table, $id) {
        $curl = $this->getCurl($table, "delete", [], $id); //begin Curl Request
        $results = json_decode(curl_exec($curl), true); //execute Curl Request
        curl_close($curl); //end Curl Request

        if($this->checkError($results, "deleteRecord", "SUCCESS", "Record deleted successfully")) return false;

        return $results;
    }

}
?>
