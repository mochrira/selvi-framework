<?php

namespace Selvi;
use Selvi\Controller;

class Resource extends Controller {

    protected $modelClass;
    protected $modelAlias;

    function __construct($autoloadModel = true) {
        if($autoloadModel == true) {
            $this->loadModel();
        }
    }

    protected function loadModel() {
        $this->load($this->modelClass, $this->modelAlias);   
    }

    protected function validateData() {
        return json_decode($this->input->raw(), true);
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
        $data = $this->validateData();
        $insert = $this->{$this->modelAlias}->insert($data);
        if(!$insert) {
            Throw new Exception('Failed to insert', $this->modelAlias.'/insert-failed', 500);
        }
        return jsonResponse([$this->{$this->modelAlias}->getPrimary() => $insert],201);
    }

    function patch() {
        $data = $this->validateData();
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

        if(!$this->{$this->modelAlias}->delete([[$this->{$this->modelAlias}->getPrimary(), $id]])) {
            Throw new Exception('Failed to delete', $this->modelAlias.'/delete-failed', 500);
        }
        return response('', 204);
    }
}