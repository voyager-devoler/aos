<?php

/**
 * В ряде методов работы с базой предполагается, что целевая таблица обладает primary ключем с именем id
 * @abstract
 */
abstract class Model_AbstractStatic {
    protected static $_db = null; // DbSimple_Mysql link to database
    protected $_tablename = null;
    protected static $_instance = null;
    protected $_dbRawArray; // необработанный результат запроса в БД
    protected $_dbRawRowValues; // набор полей, подготовленный для записи строки в БД

    protected function  __construct($object_id)
    {
        $this->getFromDB($object_id);
    }
    
    public static function getInstance()
    {
        if (is_null(static::$_instance))
        {
            static::$_instance = new static;
        }
        return static::$_instance;
    }

    /**
     *
     * @return DbSimple_Mysql 
     */
    protected static function  _db()
    {
        if (is_null(static::$_db)) static::$_db = dbLink::getDB();
        return static::$_db;
    }

    /**
     * Перебирает $_dbRawArray и, если там есть элементы, совпадающие с именами свойств класса - устанавливает значения этих свойств
     * TODO: вообще-то было бы неплохо ограничить перечень доступных для установки данным методом свойст определенным набором,
     * а то мало ли что там из базы прийдет чисто теоретически...
     */
    protected function _assoc2properties ($add_only = false)
    {
        foreach ($this->_dbRawArray as $property=>$value)
        {
            //if (property_exists(get_class($this), $property) && is_null($this->$property)) // только дополняем еще не установленные свойства. пока так
            if (property_exists(get_class($this), $property) && !$add_only)
                $this->$property = $value;
            //if (is_string($value) && $value!=="" and $value!=_($value)) var_dump(self::$_textdomain, get_class($this));
            //else echo ' !'.$property;
        }
    }
    
    public function getFromDB($id)
    {
        $this->_dbRawArray = $this->_db()->selectRow('SELECT * from ?_'.$this->_tablename.' WHERE id=?', $id);
        $this->_assoc2properties();
    }

    /**
     * Устанавливает значение поля в массиве подготовленных к записи в базу полей строки
     * @param string $name
     * @param string|int|float $value
     */
    public function setRowValue ($name, $value, $only_db = false)
    {
        $this->_dbRawRowValues[$name] = $value;
        if (!$only_db)
            $this->$name = $value;
    }

    /**
     * Добавляет несколько значений в массив поготовленных к записи в базу полей строки
     * @param array $values 'column_name'=>'column_value'
     */
    public function setRowValues ($values)
    {
        if (!is_array($this->_dbRawRowValues)) $this->_dbRawRowValues = array();
        $this->_dbRawRowValues = array_merge($this->_dbRawRowValues, $values);
    }

    /**
     * Как бы можно посмотреть, что там у нас к записи подготовлено, т.е. getter на $_dbRawRowValues
     * @param string $name
     * @return string|int|float
     */
    public function getRowValue($name)
    {
        if (array_key_exists($name, $this->_dbRawRowValues)) return $this->_dbRawRowValues[$name];
        else throw new Exception ('Not exists property '.$name);
    }

    /**
     * Выполняет апдейт строки в базе на основе $_dbRawRowValues
     * Также должно быть установлено свойство $_tablename
     * id записи должен либо присутствовать в $_dbRawRowValues, либо в классе должно сужествовать не равное null свойство id
     * @return int|false 
     */
    public function updateRow()
    {
        if (is_null($this->_tablename))
            throw new Exception ('Undefined table name for update row operation');
        $values = $this->_dbRawRowValues;
        if (!isset($values['id']))
            if (!property_exists($this, 'id') || is_null($this->id))
                throw new Exception ('Data array must contain id value for update row');
            else $values['id'] = $this->id;
        $id = $values['id'];
        unset($values['id']);
        return $this->_db()->query('UPDATE ?_'.$this->_tablename.' SET ?a WHERE id=?', $values, $id);
    }

    /**
     * Удаляет строку из базы
     * Должно быть установлено свойство $_tablename
     * @param int|string $id id записи
     * @return int id удаленной записи
     */
    public function deleteRow($id = null)
    {
        if (is_null($this->_tablename))
            throw new Exception ('Undefined table name for delete row operation');
        if (is_null($id) && property_exists($this, 'id'))
                $id = $this->id;
        if (is_null($id)){
            throw new Exception ('Undefined id for delete row operation');}
        $this->_db()->query('DELETE FROM ?_'.$this->_tablename.' WHERE id=?', $id);
        return $id;
    }

    /**
     * Добавляет строку в базу
     * Должно быть установлено свойство $_tablename
     * @return int id добавленной строки (если на primary key стоит autoincrement)
     */
    public function insertRow()
    {
       if (is_null($this->_tablename))
            throw new Exception ('Undefined table name for insert row operation');
       return $this->_db()->query('INSERT INTO ?_'.$this->_tablename.'(?#) VALUES (?a)', array_keys($this->_dbRawRowValues), array_values($this->_dbRawRowValues));
    }

    public function force_save()
    {
        
    }
}
?>
