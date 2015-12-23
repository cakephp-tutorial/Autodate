<?php
App::uses('ModelBehavior', 'Model');
App::uses('AutodateException', 'Autodate.Lib');
/**
 * @todo strtotime might confuse dates (eg: Y-d-m / Y-m-d) always pass same format
 * @todo beforeValidate
 * @todo Custom Field to convert (Model.field format)
 * @todo Cache table structure
 * @todo Pass models list to setup to avoid checking every model
 * @todo List of date formats accepted
 * @todo Check virtual fields
 * @todo Refactory array_filter and getColumnTypes() in a single method
 **/
class AutodateBehavior extends ModelBehavior {
     /**
     * @type Array
     **/
    private $defaults = array(
        'dateformat' => 'd/m/Y',
    );
    /**
     * @type Array
     **/
    private $allowedFormats = array(
        'd-m-Y',
        'd/m/Y',
        'Y/m/d',
        'Y-m-d',
        'Y-d-m',
        'Y/d/m',
        'm-d-Y',
        'm/d/Y',
        'Ymd',
        'Ydm',
    );
    /**
     * @type Array
     **/
    private $columntypes = array('date');
    /**
     * @todo
     * @throws Exception
     **/
    public function setup(Model $model, $settings = array()) {
        $this->settings = array_merge($this->defaults, $settings);
        if (!in_array($this->settings['dateformat'], $this->allowedFormats)) {
            throw new AutodateException(__('Date format not allowed. Allowed Formats: %s', implode(', ', $this->allowedFormats)));
        }
    }
    /**
     * Before save convert data to sqlformat
     *
     * @return True
     **/
    public function beforeSave(Model $model, $options = array()) {
        parent::beforeSave($model, $options);

        $columnTypes = $model->getColumnTypes();

        $fields = array_filter($columnTypes, array($this, 'valueIsDate'));
        foreach ($fields as $field => $value) {
            if (!array_key_exists($field, $model->data[$model->alias])) continue;
            $date = $this->dateObject($model->data[$model->alias][$field]);
            if (!$date) {
                return false;
            }
            $model->data[$model->alias][$field] = $this->ObjectToSQL($date);
        }
        return true;
    }
    /**
     * Before find we need to convert date fields to sqlformat
     *
     * @todo   Optimize cycle, really poor implementation here
     * @return Array
     **/
    public function beforeFind(Model $model, $query = array() ) {
        parent::beforeFind($model, $query);

        if ($model->findQueryType == 'count') return $query;

        //-- if conditions exists
        if (isset($query['conditions']) && is_array($query['conditions'])) {
            $columnTypes = $model->getColumnTypes();
            $fields = array_filter($columnTypes, array($this, 'valueIsDate'));

            //-- Get all conditions keys
            $conditionsKeys  = array_keys($query['conditions']);
            $walked          = array();

            foreach($conditionsKeys as $conKey) {
                $cond = explode(' ', $conKey);
                $cond[0] = str_replace($model->name.'.', null, $cond[0]);
                if (!array_key_exists($cond[0], $fields)) {
                    continue;
                }
                $values = $query['conditions'][$conKey];
                if (is_array($values) ) {
                    foreach($values as $key => $date) {
                        $tmpdate = $this->dateObject($date);
                        $query['conditions'][$conKey][$key] = $this->ObjectToSQL($tmpdate);
                    }
                    $walked[$conKey] = $query['conditions'][$conKey];
                }
                elseif(is_string($values)) {
                  $curObj = $this->dateObject($values);
                  $walked[$conKey] = $this->ObjectToSQL($curObj);
                }
            }
            $query['conditions'] = array_merge($query['conditions'], $walked);
        }
        return $query;
    }
    /**
     * AfterFind
     **/
    public function afterFind(Model $model, $results = array(), $primary = false) {
        parent::afterFind($model, $results, $primary);

        if ($model->findQueryType == 'count') return $results;

        $curObj = $model->alias;
        foreach ($results as $key => $value) {

            $tmpkeys = array_keys($value);

            foreach($tmpkeys as $curmodel) {

                $curObj = ($curmodel != $model->alias) ? $model->{$curmodel} : $model;

                $columnTypes = $curObj->getColumnTypes();
                $fields = array_filter($columnTypes, array($this, 'valueIsDate'));
                /**
                 * @todo Array walk might be a solution for deeper associations?
                 **/
                foreach($fields as $field => $fieldvalue) {

                    $curvalues  = $results[$key][$curmodel];
                    $dimensions = Hash::dimensions($curvalues);

                    if($dimensions <= 1) {
                        if (!array_key_exists($field, $results[$key][$curmodel])) continue;
                        $results[$key][$curmodel][$field] = $this->formatDate($results[$key][$curmodel][$field]);
                        continue;
                    }
                }
            }
        }
        return $results;
    }
    /**
     * AfterSave
     **/
    public function afterSave(Model $model, $created = false, $options = array()) {
        parent::afterSave($model, $created, $options);

        $columnTypes = $model->getColumnTypes();
        $fields = array_filter($columnTypes, array($this, 'valueIsDate'));

        foreach($fields as $field => $value) {
            if (!array_key_exists($field, $model->data[$model->alias])) continue;
            $model->data[$model->alias][$field] = $this->formatDate($model->data[$model->alias][$field]);
        }
    }
    /**
     * @todo   Add extra date Format
     * @return Array|False
     **/
    private function dateObject( $date) {
        /**
         * @todo do we need a default here?
         **/
        switch( $this->settings['dateformat'] ) {

            case 'd-m-Y':
            case 'd/m/Y':
                list( $d, $m, $y ) = preg_split( '/[-\.\/ ]/', $date );
             break;

            case 'Y/m/d':
            case 'Y-m-d':
                list( $y, $m, $d ) = preg_split( '/[-\.\/ ]/', $date );
             break;

            case 'Y-d-m':
            case 'Y/d/m':
                list( $y, $d, $m ) = preg_split( '/[-\.\/ ]/', $date );
             break;

            case 'm-d-Y':
            case 'm/d/Y':
                list( $m, $d, $y ) = preg_split( '/[-\.\/ ]/', $date );
             break;

            case 'Ymd':
                $y = substr( $date, 0, 4 );
                $m = substr( $date, 4, 2 );
                $d = substr( $date, 6, 2 );
             break;

            case 'Ydm':
                $y = substr( $date, 0, 4 );
                $d = substr( $date, 4, 2 );
                $m = substr( $date, 6, 2 );
             break;
        }
        $y = intval($y);
        $m = intval($m);
        $d = intval($d);

        if (!checkdate( $m, $d, $y )) {
            return false;
        }

        return array(
            'day' => $d,
            'month' => $m,
            'year' => $y
        );
    }
    /**
     * @return String
     **/
    private function ObjectToSQL(Array $date) {
        return $date['year'].'-'.$date['month'].'-'.$date['day'];
    }
    /**
     * @return Bool
     **/
    private function valueIsDate($value) {
        return (in_array($value, $this->columntypes));
    }
    /**
     * @return Date
     **/
    private function formatDate($date) {
        return date($this->settings['dateformat'], strtotime($date) );
    }
}