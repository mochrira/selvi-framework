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
                Throw new Exception('Invalid id or criteria', $this->modelAlias.'/not-found', 404);
            }
            return jsonResponse($row, 200);
        }

        $result = $this->{$this->modelAlias}->result();
        return jsonResponse($result, 200);
    }

    function post() {
        $data = json_decode($this->input->raw(), true);
        if(!$this->{$this->modelAlias}->insert($data)) {
            Throw new Exception('Failed to insert', $this->modelAlias.'/insert-failed', 500);
        }
        return response('',204);
    }

    function patch() {
        $data = json_decode($this->input->raw(), true);
        $id = $this->uri->segment(2);
        if($id == null) {
            Throw new Exception('Invalid request', $this->modelAlias.'/invalid-request', 400);
        }

        $row = $this->{$this->modelAlias}->row([
            [$this->{$this->modelAlias}->getPrimary(), $id]
        ]);
        if(!$row) {
            Throw new Exception('Invalid id or criteria', $this->modelAlias.'/not-found', 404);
        }

        if(!$this->{$this->modelAlias}->update([[$this->{$this->modelAlias}->getPrimary(), $id]], $data)) {
            Throw new Exception('Failed to update', $this->modelAlias.'/update-failed', 500);
        }
        return response('', 204);
    }

    function delete() {
        $id = $this->uri->segment(2);
        if($id == null) {
            Throw new Exception('Invalid request', $this->modelAlias.'/invalid-request', 400);
        }

        $row = $this->{$this->modelAlias}->row([
            [$this->{$this->modelAlias}->getPrimary(), $id]
        ]);
        if(!$row) {
            Throw new Exception('Invalid id or criteria', $this->modelAlias.'/not-found', 404);
        }

        if($this->{$this->modelAlias}->delete([[$this->{$this->modelAlias}->getPrimary(), $id]])) {
            Throw new Exception('Failed to delete', $this->modelAlias.'/delete-failed', 500);
        }
        return response('', 204);
    }
}