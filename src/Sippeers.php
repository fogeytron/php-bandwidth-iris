<?php

namespace Iris;

class Sippeers extends RestEntry {
    public function __construct($site) {
        $this->parent = $site;
        parent::_init($site->get_rest_client(), $site->get_relative_namespace());
    }

    public function get($filters = Array()) {
        $sippeers = [];

        $data = parent::get('sippeers');

        if(isset($data['SipPeers']) && isset($data['SipPeers']['SipPeer'])) {
            if($this->is_assoc($data['SipPeers']['SipPeer']))
                $peers = [ $data['SipPeers']['SipPeer'] ];
            else
                $peers = $data['SipPeers']['SipPeer'];

            foreach($peers as $sippeer) {
                $sippeers[] = new Sippeer($this, $sippeer);
            }
        }

        return $sippeers;
    }

    public function sippeer($id) {
        $sipper = new Sippeer($this, array("PeerId" => $id));
        $sipper->get();
        return $sipper;
    }

    public function get_appendix() {
        return '/sippeers';
    }

    public function create($data) {
        $sipper = new Sippeer($this, $data);
        return $sipper;
    }
}

class Sippeer extends RestEntry {
    use BaseModel;

    protected $fields = array(
        "PeerId" => array("type" => "string"),
        "PeerName" => array("type" => "string"),
        "IsDefaultPeer" => array("type" => "string"),
        "ShortMessagingProtocol" => array("type" => "string"),
        "VoiceHosts" => array("type" => "Iris\Hosts"),
        "VoiceHostGroups" => array("type" => "string"),
        "SmsHosts" => array("type" => "Iris\Hosts"),
        "TerminationHosts" => array("type" => "Iris\Hosts")
    );

    public function __construct($parent, $data) {
        $this->PeerId = null;

        if(isset($data)) {
            if(is_object($data) && $data->PeerId)
                $this->PeerId = $data->PeerId;
            if(is_array($data) && isset($data['PeerId']))
                $this->PeerId = $data['PeerId'];
        }
        $this->set_data($data);

        $this->parent = $parent;
        parent::_init($parent->get_rest_client(), $parent->get_relative_namespace());
        $this->tns = null;
    }

    public function get() {
        $data = parent::get($this->get_id());
        $this->set_data($data['SipPeer']);
    }

    public function save() {
        if(isset($this->PeerId))
            parent::put($this->PeerId, "SipPeer", $this->to_array());
        else {
            $header = parent::post(null, "SipPeer", $this->to_array());
            $splitted = split("/", $header['Location']);
            $this->PeerId = end($splitted);
        }
    }

    public function delete() {
        parent::delete($this->get_id());
    }

    public function movetns(Phones $data) {
        $url = sprintf("%s/%s", $this->get_id(), "movetns");
        parent::post($url, "SipPeerTelephoneNumbers", $data);
    }

    public function tns() {
        if(is_null($this->tns))
            $this->tns = new Tns($this);
        return $this->tns;
    }

    private function get_id() {
        if(!isset($this->PeerId))
            throw new \Exception('Id should be provided');
        return $this->PeerId;
    }


    public function get_appendix() {
        return '/'.$this->get_id();
    }
}