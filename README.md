# Autodate V 1.0.1 #

A CakePHP Plugin that will auto convert date fields from SQL format to a chosen date format (eg: d/m/Y).
Will auto convert the date before saving to database, before find (to match conditions on db), after find and after update

## Requirements ##

* CakePHP 2
* PHP 5.2

## Compatibility ##

* V 1.x - CakePHP 2

## Usage ##

```php
/**
 * Attach to AppModel to auto convert all date fields
 * or attach it to a single model
 **/
class AppModel extends Model {
    public $actsAs = array(
        'Autodate.Autodate' => array('dateformat' => 'd/m/Y')
    );
}
```

#### Available date Formats ####

* 'd-m-Y'
* 'd/m/Y'
* 'Y/m/d'
* 'Y-m-d'
* 'Y-d-m'
* 'Y/d/m'
* 'm-d-Y'
* 'm/d/Y'
* 'Ymd'
* 'Ydm'

## Installation ##

### Using composer ###

```
{
    "require": {
        "cakephp-tutorial/autodate": "1.0.0"
    }
}
```

### Manually Installation ###

* Download from github https://github.com/cakephp-tutorial/Autodate
* copy in app/Plugin/Autodate
* enable in app/Config/bootstrap.php
```php
CakePlugin::load('Autodate');
```