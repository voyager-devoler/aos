<?php
/**
 * Позволяет создавать не singleton объекты
 */
abstract class Model_Abstract extends Model_AbstractStatic {

    // делаем конструктор публичным
    public function  __construct($obj_id)
    {
        parent::__construct($obj_id);
    }
}
?>
