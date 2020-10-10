<?php

namespace Selvi;
use Selvi\Controller;
use Selvi\Exception;
use Selvi\Route;

class Resource extends Controller {

    protected $modelClass;
    protected $modelAlias;

    function __construct($autoloadModel = true) {
        if($autoloadModel == true) {
            $this->loadModel();
        }
    }

    function loadModel() {
        $this->load($this->modelClass, $this->modelAlias);
    }

    function buildWhere() {
        return [];
    }

    function get() {
        $id = $this->uri->segment(2);
        if($id !== null) {
            $where = $this->buildWhere();
            $where[] = [$this->{$this->modelAlias}->getPrimary(), $id];
            $row = $this->{$this->modelAlias}->row($where);
            if(!$row) {
                Throw new Exception('Invalid id or criteria', $this->modelAlias.'/not-found', 404);
            }
            return jsonResponse($row, 200);
        }

        $order = [];
        $sort = $this->input->get('sort');
        if($sort !== null) {
            $a = explode(',', $sort);
            foreach($a as $b) {
                $c = explode(':', $b);
                $order[$c[0]] = $c[1];
            }
        }

        $limit = $this->input->get('limit') ?? -1;
        $offset = $this->input->get('offset') ?? 0;
        $result = $this->{$this->modelAlias}->result($this->buildWhere(), $this->input->get('search'), $order, $limit, $offset);
        return jsonResponse($result, 200);
    }

    function validateData($data, $object = null) {
        return $data;
    }

    function afterInsert($object, &$response = NULL) {
        // do nothing
        // you can replace this function anytime
    }

    function post() {
        try {
            $data = $this->validateData(json_decode($this->input->raw(), true));
            $insert = $this->{$this->modelAlias}->insert($data);
            if(!$insert) {
                Throw new Exception('Failed to insert', $this->modelAlias.'/insert-failed', 500);
            }
            $object = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $insert]]);
            $response = jsonResponse([$this->{$this->modelAlias}->getPrimary() => $insert], 201);
            $this->afterInsert($object, $response);
        } catch(Exception $e) {
            Throw new Exception($e->getMessage(), $this->modelAlias.'/insert-failed', 500);
        }
        return $response;
    }

    function afterUpdate($object, &$response = NULL) {
        // do nothing
        // you can replace this function anytime
    }

    function patch() {
        $id = $this->uri->segment(2);
        if($id == null) {
            Throw new Exception('Invalid request', $this->modelAlias.'/invalid-request', 400);
        }

        $object = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
        if(!$object) {
            Throw new Exception('Invalid id or criteria', $this->modelAlias.'/not-found', 404);
        }

        try {
            $data = $this->validateData(json_decode($this->input->raw(), true), $object);
            $this->{$this->modelAlias}->update([[$this->{$this->modelAlias}->getPrimary(), $id]], $data);
            $object = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
            $response = response('', 204);
            $this->afterUpdate($object, $response);
        } catch(Exception $e) {
            Throw new Exception($e->getMessage(), $this->modelAlias.'/update-failed', 500);
        }
        
        return $response;
    }

    function afterDelete($object, &$response = NULL) {
        // do nothing
        // you can replace this function anytime
    }

    function delete() {
        $id = $this->uri->segment(2);
        if($id == null) {
            Throw new Exception('Invalid request', $this->modelAlias.'/invalid-request', 400);
        }

        $object = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
        if(!$object) {
            Throw new Exception('Invalid id or criteria', $this->modelAlias.'/not-found', 404);
        }

        try {
            $data = $this->validateData(json_decode($this->input->raw(), true), $object);
            if(!$this->{$this->modelAlias}->delete([[$this->{$this->modelAlias}->getPrimary(), $id]])) {
                $this->rollback();
                Throw new Exception('Failed to delete', $this->modelAlias.'/delete-failed', 500);
            }
            $object = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
            $response = response('', 204);
            $this->afterDelete($object, $response);
        } catch(Exception $e) {
            Throw new Exception($e->getMessage(), $this->modelAlias.'/delete-failed', 500);
        }
        return $response;
    }
}