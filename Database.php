<?php

class Database
{

    protected $server;
    protected $dataBase;
    protected $user;
    protected $password;
    protected $charset;
    protected $connectionId;
    protected $connection;

    private $query = "";
    private $bindables = [];
    private $fieldNames = [];

    /**
     * Constructor
     */
    public function __construct($server, $user, $password, $database)
    {
        $this->server = $server;
        $this->dataBase = $database;
        $this->user = $user;
        $this->password = $password;
        $this->charset = "utf8";
        $this->connection = NULL;
        $this->connectionId = NULL;
        $this->open();
    }

    /**
     * Destructor (?)
     */
    public function __destroy()
    {
        $this->close();
    }

    /**
     * Abre la conexión con la base de datos
     * @return bool Devuelve TRUE si se pudo realizar la conexión o FALSE en caso contrario
     * @access public
     */
    public function open()
    {
        if (!is_null($this->connectionId)) {
            die('ERROR: la base de datos está abierta.');
        }
        $oConni = new mysqli($this->server, $this->user, $this->password, $this->dataBase) or die("Error al conectar.");
        $oConni->set_charset($this->charset);

        if ($oConni->connect_errno) {
            die('Error de conexión ' . $oConni->connect_errno . ': ' . $oConni->connect_error);
        }
        $this->connection = $oConni;
        $this->connectionId = $oConni->thread_id;

        return (true);
    }

    /**
     * Cierra la conexión con la base de datos
     * @return bool Devuelve TRUE si se pudo realizar la desconexión o FALSE en caso contrario
     * @access public
     */
    public function close()
    {
        if (is_null($this->connectionId)) {
            return (false);
        }
        if ($this->connection->kill($this->connectionId)) {
            $this->connection->close();
            $this->connection = NULL;
            $this->connectionId = NULL;
            return (true);
        } else {
            return (false);
        }
    }


    /**
     * @param array
     * @return Database
     */
    public function insert($values)
    {
        $aCampos = [];
        $cadena = [];
        foreach ($values as $key => $value) {
            $aCampos[] = $key;
            $this->bindables[] = $value;
            $cadena[] = '?';
        }

        $this->query = ' (' . implode(',', $aCampos) . ') VALUES (' . implode(',', $cadena) . ')';
        return $this;
    }

    /**
     * @param string
     * @return Database
     */
    public function into($table)
    {
        $this->query = "INSERT INTO " . $table . $this->query;
        return $this;
    }

    /**
     * @param mixed (array|string)
     * @return Database
     */
    public function select($fields)
    {
        $this->query = "SELECT ";
        if (gettype($fields) == "array") {
            $this->query .= implode(",", $fields);
            $this->fieldNames = $fields;
        } else {
            $this->query .= $fields;
            $this->fieldNames = array($fields);
        }

        return $this;
    }

    /**
     * @param string
     * @return Database
     */
    public function from($tabla)
    {
        $this->query .= " FROM " . $tabla;
        return $this;
    }

    /**
     * @param string
     * @return Database
     */
    public function update($table)
    {
        $this->query = "UPDATE " . $table;
        return $this;
    }

    public function set(array $params)
    {
        $this->query .= " SET ";
        foreach ($params as $key => $val) {
            $this->query .= $key . " = ?,";
            $this->bindables[] = $val;
        }
        $this->query = trim($this->query, ",");
        return $this;
    }

    /**
     * @param string
     * @return Database
     */
    public function delete($table)
    {
        $this->query = "DELETE FROM " . $table;
        return $this;
    }

    /**
     * @param array
     * @return Database
     */
    public function where(array $conditions)
    {
        foreach ($conditions as $key => $value) {
            if (strpos($this->query, "WHERE")) $this->query .= " AND ";
            else $this->query .= " WHERE ";
            $this->query .= $key;
            if (gettype($value) == "array") {

                $this->query .= " " . $value[0] . " ?";
                $this->bindables[] = $value[1];
            }
            else {
                $this->query .= "=?";
                $this->bindables[] = $value;
            }
        }
        return $this;
    }

    /**
     * @param array
     * @return Database
     */
    public function orWhere(array $conditions)
    {
        $this->query .= " OR 1=1";
        foreach ($conditions as $key => $value) {
            $this->query .= " AND ";

            $this->query .= $key;
            if (gettype($value) == "array") {

                $this->query .= " " . $value[0] . " ?";
                $this->bindables[] = $value[1];
            }
            else {
                $this->query .= "=?";
                $this->bindables[] = $value;
            }
        }
        return $this;
    }


    /**
     * @param Array(Array, Array)
     * @return Database
     */
    public function join($union)
    {
        reset($union[0]);
        reset($union[1]);
        $tabla1 = key($union[0]);
        $tabla2 = key($union[1]);
        $this->query .= " INNER JOIN $tabla2 ON $tabla1." . $union[0][$tabla1] . "=$tabla2." . $union[1][$tabla2];
        return $this;
    }

    /**
     * @param string
     * @param string
     * @return Database
     */
    public function orderBy($field, $direction = "ASC")
    {
        $this->query .= " ORDER BY " . $field . " " . $direction;
        return $this;
    }

    /**
     * @param string
     * @return Database
     */
    public function groupBy($field)
    {
        $this->query .= " GROUP BY " . $field;
        return $this;
    }

    /**
     * @param int
     * @param int
     * @return Database
     */
    public function limit()
    {
        $params = func_get_args();
        if (count($params) == 1) $this->query .= " LIMIT " . $params[0];
        else $this->query .= " LIMIT " . $params[0] . ", " . $params[1];
        return $this;
    }

    /**
     * @param callback
     * @return callback()
     */
    public function then($callback)
    {
        $stmt = $this->connection->prepare($this->query);

        if (count($this->bindables)) {
            $types = "";
            foreach ($this->bindables as $param) {
                $types .= $this->_determineType($param);
            }
            call_user_func_array(array($stmt, "bind_param"), $this->_refValues(array_merge(array($types), $this->bindables)));
        }
        $stmt->execute();
        $meta = $stmt->result_metadata();


        $fields = $results = array();


        while ($field = $meta->fetch_field()) {
            $var = $field->name;
            $$var = null;
            $fields[$var] = &$$var;
        }


        $fieldCount = count($this->fieldNames);
        call_user_func_array(array($stmt, 'bind_result'), $fields);

        $i = 0;
        while ($stmt->fetch()) {
            $results[$i] = array();
            for ($l = 0; $l < $fieldCount; $l++)
                $results[$i][$this->fieldNames[$l]] = $fields[$this->fieldNames[$l]];
            $i++;
        }

        $stmt->close();
        $this->_setScratch();

        return $callback($results);
    }

    /**
     * @return bool
     */
    public function exec()
    {
        $stmt = $this->connection->prepare($this->query);

        if (count($this->bindables)) {
            $types = "";
            foreach ($this->bindables as $param) {
                $types .= $this->_determineType($param);
            }
            call_user_func_array(array($stmt, "bind_param"), $this->_refValues(array_merge(array($types), $this->bindables)));
        }
        $stmt->execute();
        $this->_setScratch();
        return true;
    }

    /**
     * @return query result
     */
    public function storage()
    {
        return $this->then(function ($data) {
            return ($data);
        });
    }

    /**
     * @param string query
     * @return bool
     */
    public function simpleQuery($query)
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        return true;
    }

    /**
     * @param mixed
     * @return string The joined parameter types.
     */
    private function _determineType($item)
    {
        switch (gettype($item)) {
            case 'NULL':
            case 'string':
                return 's';
                break;
            case 'boolean':
            case 'integer':
                return 'i';
                break;
            case 'blob':
                return 'b';
                break;
            case 'double':
                return 'd';
                break;
        }
        return '';
    }

    /**
     * @param $arr
     * @return array
     */
    private function _refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    /**
     * reset all to start new query
     */
    private function _setScratch()
    {
        $this->query = "";
        $this->bindables = [];
        $this->fieldNames = [];
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @param string $charset
     * @return Database
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @return null
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param null $connection
     * @return Database
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return null
     */
    public function getConnectionId()
    {
        return $this->connectionId;
    }

    /**
     * @param null $connectionId
     * @return Database
     */
    public function setConnectionId($connectionId)
    {
        $this->connectionId = $connectionId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDataBase()
    {
        return $this->dataBase;
    }

    /**
     * @param mixed $dataBase
     * @return Database
     */
    public function setDataBase($dataBase)
    {
        $this->dataBase = $dataBase;
        return $this;
    }

    /**
     * @return string
     */
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return Database
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string $server
     * @return Database
     */
    public function setServer($server)
    {
        $this->server = $server;
        return $this;
    }

    /**
     * @return string
     */
    private function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return Database
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }


}