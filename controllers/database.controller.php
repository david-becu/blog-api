<?php
abstract class DatabaseController
{
    public function __construct($params)
    {
        $id = array_shift($params);
        $this->action = null;

        if (isset($id) && !ctype_digit($id)) {
            return $this;
        }

        //
        $request_body = file_get_contents('php://input');
        $this->body = $request_body ? json_decode($request_body, true) : null;
        //

        $this->table = lcfirst(str_replace("Controller", "", get_called_class()));

        if ($_SERVER['REQUEST_METHOD'] == "GET" && !isset($id)) {
            $this->action = $this->getAll();
        }
        if ($_SERVER['REQUEST_METHOD'] == "GET" && isset($id)) {
            $this->action = $this->getOne($id);
        }
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $this->action = $this->create();
        }
        if ($_SERVER['REQUEST_METHOD'] == "PUT" && isset($id)) {
            $this->action = $this->update($id);
        }
        if ($_SERVER['REQUEST_METHOD'] == "PATCH" && isset($id)) {
            $this->action = $this->softDelete($id);
        }
        if ($_SERVER['REQUEST_METHOD'] == "DELETE" && isset($id)) {
            $this->action = $this->hardDelete($id);
        }

        // Routes avec les relations
        if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($id)) {
            if ($id == 0) { //POST /table/0
                $this->action = $this->getAllWith($this->body["with"]);
            }
            if ($id > 0) //POST /table/:id
                $this->action = $this->getOneWith($id, $this->body["with"]);
        }
    }

    public abstract function affectDataToRow(&$row, $sub_rows);

    public function getAll()
    {
        $dbs = new DatabaseService($this->table);
        $rows = $dbs->selectAll();
        return $rows;
    }

    function getAllWith($with)
    {
        $rows = $this->getAll();
        $sub_rows = [];
        foreach ($with as $table) {
            $dbs = new DatabaseService($table);
            $table_rows = $dbs->selectAll();
            $sub_rows[$table] = $table_rows;
        }
        foreach ($rows as $row) {
            $this->affectDataToRow($row, $sub_rows);
        }
        return $rows;
    }

    public function getOne($id)
    {
        $dbs = new DatabaseService($this->table);
        $row = $dbs->selectOne($id);
        return $row;
    }


    function getOneWith($id, $with)
    {
        $row = $this->getOne($id);
        foreach($with as $table) {
            $dbs = new DatabaseService($table);
            $table_rows = $dbs->selectAll();
            $sub_rows[$table] = $table_rows;
        }
        $this->affectDataToRow($row, $sub_rows);
        return $row;
    }

    public function create()
    {
        return "Insert a new row in table $this->table with values : " .
            urldecode(http_build_query($this->body, '', ', '));
    }
    public function update($id)
    {
        return "Update row with id = $id in table $this->table with values : " .
            urldecode(http_build_query($this->body, '', ', '));
    }
    public function softDelete($id)
    {
        return "Delete (soft) row with id = $id in table $this->table";
    }
    public function hardDelete($id)
    {
        return "Delete (hard) row with id = $id in table $this->table";
    }
}
