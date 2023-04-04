<?php

define("BLOCK_SIZE", 8192);
define("API_URL", "https://smsgateapi.sms-sluzba.cz/apixml30/");
define("API_SENDER", "sender");
define("API_RECEIVER", "receiver");
define("API_CONFIRMER", "confirm");
define("WITH_DR_REQUEST", 20);
define("WITHOUT_DR_REQUEST", 0);
define("DEFAULT_DR_REQUEST", WITHOUT_DR_REQUEST);


class ApiXml30
{
    private $login, $password, $smsgateapi_url, $params, $encoding;

    public function __construct($login, $password, $encoding = "UTF-8")
    {
        $this->login = $login;
        $this->password = $password;
        $this->encoding = $encoding;
        $this->params = array();
    }

    public function confirm_message($params)
    {
        $query_string = http_build_query($params);
        $handle = fopen($this->get_url(API_CONFIRMER) . "&" . $query_string, 'rb', false);
        return $this->send_request($handle);
    }

    public function get_incoming_messages($types = array())
    {
        $query_string = http_build_query($types);
        $handle = fopen($this->get_url(API_SENDER) . "&" . $query_string, 'rb', false);
        return $this->send_request($handle);
    }

    public function send_message($recipient, $text, $send_at = null, $dr_request = null)
    {
        if ($dr_request == null) {
            $dr_request = DEFAULT_DR_REQUEST;
        }
        if ($send_at == null) {
            $send_at = date('YmdHis');
        }
        $xml = "<outgoing_message><dr_request>" . $dr_request . "</dr_request><send_at>" . $send_at . "</send_at><text>" . $text . "</text><recipient>" . $recipient . "</recipient></outgoing_message>";
        return $this->send_xml_request($xml);
    }


    private function get_params_for_xml_request($data)
    {
        $params = array('http' => array(
            'method' => 'POST',
            'content' => $data,
            'header' => 'Content-type: text/xml'
        ));
        return stream_context_create($params);
    }

    private function send_xml_request($data)
    {
        $handle = fopen($this->get_url(API_RECEIVER), 'rb', false, $this->get_params_for_xml_request($data));
        return $this->send_request($handle);
    }

    private function send_request($handle)
    {
        $contents = '';
        if (!$handle) {
            return false;
        }
        while (!feof($handle)) {
            $contents .= fread($handle, BLOCK_SIZE);
        }
        fclose($handle);
        return $contents;
    }

    private function get_url($type)
    {
        return API_URL . $type . "?login=" . $this->login . "&password=" . $this->password;
    }


}

?>
