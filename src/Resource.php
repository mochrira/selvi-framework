<?php

namespace Selvi;
use Selvi\Controller;
use Selvi\Exception;

class Resource extends Controller {

    protected $modelClass;
    protected $modelAlias;

    protected $detailClass;
    protected $detailAlias;
    protected $detailKey = 'detail';

    function __construct($autoloadModel = true) {
        if($autoloadModel == true) {
            $this->loadModel();
        }
    }

    protected function loadModel() {
        $this->load($this->modelClass, $this->modelAlias);
        if($this->detailClass != null && $this->detailAlias != null) {
            $this->load($this->detailClass, $this->detailAlias);
        }
    }

    protected function buildWhere() {
        return [];
    }

    protected function startTransaction() {
        $this->{$this->modelAlias}->getSchema()->startTransaction();
        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->{$this->detailAlias}->getSchema()->startTransaction();
        }
    }

    protected function rollback() {
        $this->{$this->modelAlias}->getSchema()->rollback();
        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->{$this->detailAlias}->getSchema()->rollback();
        }
    }

    protected function commit() {
        $this->{$this->modelAlias}->getSchema()->commit();
        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->{$this->detailAlias}->getSchema()->commit();
        }
    }

    function get() {
        $id = $this->uri->segment(2);
        if($id !== null) {
            $row = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
            if(!$row) {
                Throw new Exception('Invalid id or criteria', $this->modelAlias.'/not-found', 404);
            }

            if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
                $row = (array)$row;
                $row[$this->detailKey] = $this->{$this->detailAlias}->result([
                    [$this->{$this->modelAlias}->getPrimary(), $id]
                ]);
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

    function post() {
        $data = json_decode($this->input->raw(), true);
        if(\method_exists($this, 'validateData')) {
            $data = $this->validateData($data);
        }

        $preparedData = $data;
        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->startTransaction();
            if(isset($preparedData[$this->detailKey])) {
                unset($preparedData[$this->detailKey]);
            }
        }

        try {
            $insert = $this->{$this->modelAlias}->insert($preparedData);
            if(!$insert) {
                Throw new Exception('Failed to insert', $this->modelAlias.'/insert-failed', 500);
            }

            if(\method_exists($this, 'afterInsert')) {
                $object = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $insert]]);
                $this->afterInsert($object);
            }
        } catch(Exception $e) {
            $this->rollback();
            Throw new Exception($e->getMessage(), $this->modelAlias.'/insert-failed', 500);
        }

        if($this->detailAlias != '' && $this->{$this->detailAlias} != null && isset($data[$this->detailKey])) {
            foreach($data[$this->detailKey] as $item) {
                try {
                    $item[$this->{$this->modelAlias}->getPrimary()] = $insert;
                    $insertItem = $this->{$this->detailAlias}->insert($item);
                    if(!$insertItem) {
                        $this->rollback();
                        Throw new Exception('Failed to process detail', $this->modelAlias.'/insert-detail-failed', 500);
                    }
                } catch(Exception $e) {
                    $this->rollback();
                    Throw new Exception($e->getMessage(), $this->modelAlias.'/insert-failed', 500);
                }
            }
        }

        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->commit();
        }
        return jsonResponse([$this->{$this->modelAlias}->getPrimary() => $insert],201);
    }

    function patch() {
        $data = json_decode($this->input->raw(), true);
        if(\method_exists($this, 'validateData')) {
            $data = $this->validateData($data);
        }

        $id = $this->uri->segment(2);
        if($id == null) {
            Throw new Exception('Invalid request', $this->modelAlias.'/invalid-request', 400);
        }

        $row = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
        if(!$row) {
            Throw new Exception('Invalid id or criteria', $this->modelAlias.'/not-found', 404);
        }

        $preparedData = $data;
        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->startTransaction();
            if(isset($preparedData[$this->detailKey])) {
                unset($preparedData[$this->detailKey]);
            }
        }

        try {
            if(!$this->{$this->modelAlias}->update([[$this->{$this->modelAlias}->getPrimary(), $id]], $preparedData)) {
                Throw new Exception('Failed to update', $this->modelAlias.'/update-failed', 500);
            }
            if(\method_exists($this, 'afterUpdate')) {
                $object = $this->{$this->modelAlias}->row([[$this->{$this->modelAlias}->getPrimary(), $id]]);
                $this->afterUpdate($object);
            }
        } catch(Exception $e) {
            $this->rollback();
            Throw new Exception($e->getMessage(), $this->modelAlias.'/insert-failed', 500);
        }
        
        if($this->detailAlias != '' && $this->{$this->detailAlias} != null && isset($data[$this->detailKey])) {
            try {
                if(!$this->{$this->detailAlias}->delete([[$this->{$this->modelAlias}->getPrimary(), $id]])) {
                    $this->rollback();
                    Throw new Exception('Failed to clear existing detail', $this->modelAlias.'/clear-detail-failed', 500);
                }
            } catch(Exception $e) {
                $this->rollback();
                Throw new Exception($e->getMessage(), $this->modelAlias.'/update-failed', 500);
            }

            foreach($data[$this->detailKey] as $item) {
                try {
                    $item[$this->{$this->modelAlias}->getPrimary()] = $id;
                    $insertItem = $this->{$this->detailAlias}->insert($item);
                    if(!$insertItem) {
                        $this->rollback();
                        Throw new Exception('Failed to process detail', $this->modelAlias.'/update-detail-failed', 500);
                    }
                } catch(Exception $e) {
                    $this->rollback();
                    Throw new Exception($e->getMessage(), $this->modelAlias.'/update-failed', 500);
                }
            }
        }

        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->commit();
        }
        return response('', 204);
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

        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->startTransaction();
        }

        try {
            if(!$this->{$this->modelAlias}->delete([[$this->{$this->modelAlias}->getPrimary(), $id]])) {
                $this->rollback();
                Throw new Exception('Failed to delete', $this->modelAlias.'/delete-failed', 500);
            }
            if(\method_exists($this, 'afterDelete')) {
                $this->afterDelete($object);
            }
        } catch(Exception $e) {
            $this->rollback();
            Throw new Exception($e->getMessage(), $this->modelAlias.'/delete-failed', 500);
        }

        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            try {
                if(!$this->{$this->detailAlias}->delete([[$this->{$this->modelAlias}->getPrimary(), $id]])) {
                    $this->rollback();
                    Throw new Exception('Failed to clear existing detail', $this->modelAlias.'/clear-detail-failed', 500);
                }
            } catch(Exception $e) {
                $this->rollback();
                Throw new Exception($e->getMessage(), $this->modelAlias.'/delete-failed', 500);
            }
        }

        if($this->detailAlias != '' && $this->{$this->detailAlias} != null) {
            $this->commit();
        }
        return response('', 204);
    }
}