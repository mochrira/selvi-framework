<?php

namespace Selvi;

use Selvi\Controller;

class Resource extends Controller {

    protected $modelClass;
    protected $modelAlias;

    function __construct() {
        parent::__construct();
        $this->load($this->modelClass, $this->modelAlias);
    }

    function get() {
        $id = $this->uri->segment(2);
        if($id !== null) {
            $row = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
            if(!$row) {
                response('', 404);
            }

            jsonResponse($row, 200);
        }

        $result = $this->{$this->modelAlias}->result();
        jsonResponse($result, 200);

    }

    function post() {
        $data = json_decode($this->input->raw(), true);
        if($this->{$this->modelAlias}->insert($data)) {
            response('',204);
        } else {
            response('', 500);
        }
    }

    function patch() {
        $data = json_decode($this->input->raw(), true);
        $id = $this->uri->segment(2);
        if($id == null) {
            response('', 400);
        }

        $row = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
        if(!$row) {
            response('', 404);
        }

        if($this->{$this->modelAlias}->update([[$this->{$this->modelAlias}->getPrimary(), $id]], $data)) {
            response('', 204);
        } else {
            response('', 500);
        }
    }

    function delete() {
        $id = $this->uri->segment(2);
        if($id == null) {
            response('', 400);
        }

        $row = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
        if(!$row) {
            response('', 404);
        }

        if($this->{$this->modelAlias}->delete([[$this->{$this->modelAlias}->getPrimary(), $id]])) {
            response('', 204);
        } else {
            response('', 500);
        }
    }
}