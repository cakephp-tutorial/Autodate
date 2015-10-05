<?php
App::uses('AutodateBehavior', 'Autodate.Model.Behavior');
App::uses('AutodateException', 'Autodate.Lib');
/**
 * @todo Metodo automatico load/unload con settings
 **/
class AutodateBehaviorTest extends CakeTestCase {

    public $fixtures = array('core.apple');

    public function setUp() {
        parent::setUp();
        $this->Apple = ClassRegistry::init('Apple');
        $this->loadUnload();
    }

    public function testRetrieve() {
        $expected['Apple']['date'] = '04/01/1951';
        $result = $this->Apple->read('date', 1);
        $this->assertEquals($expected, $result);
    }

    public function testRetrieveAll() {
        $results = $this->Apple->find('all', array('limit' => 3));
        $expected = array(
            array(
        		'Apple' => array(
        			'id' => '1',
        			'apple_id' => '2',
        			'color' => 'Red 1',
        			'name' => 'Red Apple 1',
        			'created' => '2006-11-22 10:38:58',
        			'date' => '04/01/1951',
        			'modified' => '2006-12-01 13:31:26',
        			'mytime' => '22:57:17'
   	            )
           ),
           array(
        		'Apple' => array(
        			'id' => '2',
        			'apple_id' => '1',
        			'color' => 'Bright Red 1',
        			'name' => 'Bright Red Apple',
        			'created' => '2006-11-22 10:43:13',
        			'date' => '01/01/2014',
        			'modified' => '2006-11-30 18:38:10',
        			'mytime' => '22:57:17'
        		)
           ),
           array(
            'Apple' => array(
    			'id' => '3',
    			'apple_id' => '2',
    			'color' => 'blue green',
    			'name' => 'green blue',
    			'created' => '2006-12-25 05:13:36',
    			'date' => '25/12/2006',
    			'modified' => '2006-12-25 05:23:24',
    			'mytime' => '22:57:17'
   		      )
           )
        );
        $this->assertInternalType('array', $results);
        $this->assertTrue(!empty($results));
        $this->assertEquals($expected, $results);
    }

    public function testBeforeAndAfterSave() {

        $recordBefore = $this->Apple->read(null, 1);
        $postData = $recordBefore;
        $postData['Apple']['date'] = '10/10/2010';
        $result = $this->Apple->save($postData);

        $this->assertTrue(!empty($result));
        $this->assertInternalType('array', $result);

        $this->assertEquals($result, $postData);

        $expectedDiff = array('date' => '10/10/2010');

        $diff = Hash::diff($result['Apple'], $recordBefore['Apple']);
        $this->assertEquals($expectedDiff, $diff);
    }
    /**
     * @todo
     **/
    public function testDifferentFormat001Find() {

        $dateFormats = array(
            'd-m-Y' => '04-01-1951',
            'd/m/Y' => '04/01/1951',
            'Y/m/d' => '1951/01/04',
            'Y-m-d' => '1951-01-04',
            'Y-d-m' => '1951-04-01',
            'Y/d/m' => '1951/04/01',
            'm-d-Y' => '01-04-1951',
            'm/d/Y' => '01/04/1951',
            'Ymd'   => '19510104',
            'Ydm'   => '19510401',
        );

        foreach($dateFormats as $dateFormat => $expectedResult) {

            $this->loadUnload($dateFormat);
            $expected['Apple']['date'] = $expectedResult;

            $result = $this->Apple->read('date', 1);

            $this->assertInternalType('array', $result);
            $this->assertTrue(!empty($result));
            $this->assertEquals($expected, $result);
        }
    }

    public function testDifferentFormat001Save() {

        $dateFormats = array(
            'd-m-Y' => '04-01-1952',
            'd/m/Y' => '04/01/1952',
            'Y/m/d' => '1952/01/04',
            'Y-m-d' => '1952-01-04',
            'Y-d-m' => '1952-04-01',
            'Y/d/m' => '1952/04/01',
            'm-d-Y' => '01-04-1952',
            'm/d/Y' => '01/04/1952',
            'Ymd'   => '19520104',
            'Ydm'   => '19520401',
        );

        //-- this record must be resetted at every cycle
        $this->loadUnload();
        $before = $this->Apple->read(null, 1);

        foreach($dateFormats as $dateFormat => $expectedValue) {

            $this->loadUnload($dateFormat);

            $before = $this->Apple->read(null, 1);

            $postData = $before;
            $postData['Apple']['date'] = $expectedValue;

            $result = $this->Apple->save($postData);

            $this->assertInternalType('array', $result);
            $this->assertEquals($postData, $result);

            $expectedDiff = array('date' => $expectedValue);
            $diff = Hash::diff($result['Apple'], $before['Apple']);

            $this->assertEquals($expectedDiff, $diff);

            //-- Save record for next cycle
            $this->Apple->save($before);
        }
    }

    public function testFindWithException() {
        $this->setExpectedException('AutodateException');
        $this->loadUnload('YYY-DD-MM');
    }

    public function testWithWrongDate() {
        $this->loadUnload();
        $postData = $this->Apple->read(null, 1);
        $postData['Apple']['date'] = 'aaaa-bb-cc';

        $result = $this->Apple->save($postData);

        $this->assertInternalType('bool', $result);
        $this->assertFalse($result);
    }

    public function testBeforeFind() {
        $this->loadUnload();
        $result = $this->Apple->find('all', array(
            'conditions' => array(
                'date >' => '22/06/1982',
                'date <' => '01/01/2012',
                'NOT' => array(
                    'date' => NULL
                )
            )
        ));
        $this->assertInternalType('array', $result);
        $this->assertTrue(!empty($result));
    }

    private function loadUnload($dateFormat = 'd/m/Y') {
        $this->Apple->Behaviors->unload('Autodate.Autodate');
        $this->Apple->Behaviors->load('Autodate.Autodate', array('dateformat' => $dateFormat));
        $this->Apple->Behaviors->load('Containable');
    }
}