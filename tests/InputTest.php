<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    const LOC_FILE = 0;
    const LOC_DATE = 1;
    const LOC_TIME = 2;
    const LOC_POSTCODE = 3;
    const LOC_COVERS = 4;
    /**
     * @var array
     */
    private $validInput = [
        'vendors.txt',
        '24/10/15',
        '11:00',
        'NW43QB',
        20,
    ];

    public function testValidInput(): void
    {
        try {
            new Input($this->validInput);
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->assertEquals('', $e->getMessage());
        }
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testToFewArguments(): void
    {
        new Input(['vendors.txt']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testToManyArguments(): void
    {
        $input = array_merge($this->validInput, ['foo']);
        new Input($input);
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidFilename(): void
    {
        $input = $this->validInput;
        $input[self::LOC_FILE] = 'this file does not exist';
        new Input($input);
    }

    /**
     *
     * @dataProvider invalidDatesData
     *
     * @param $date
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalidDate($date): void
    {
        $input = $this->validInput;
        $input[self::LOC_DATE] = $date;
        new Input($input);
    }

    /**
     * @return array
     */
    public function invalidDatesData(): array
    {
        return [
            'yyyy-mm-yy' => ['2038-02-28'],
            'yy-mm-dd'   => ['38-02-28'],
            'dd-mm-yy'   => ['28-02-38'],
            'dd/mm/yyyy' => ['28/02/2038'],
            'timestamp'  => [1524955537],
            'string'     => ['foo'],
        ];
    }

    /**
     *
     * @dataProvider invalidTimesData
     *
     * @param $time
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalidTime($time): void
    {
        $input = $this->validInput;
        $input[self::LOC_TIME] = $time;
        new Input($input);
    }

    /**
     * @return array
     */
    public function invalidTimesData(): array
    {
        return [
            'hh:mm:ss' => ['10:30:50'],
            '24h+'     => ['24:00'],
            '60m+'     => ['12:60'],
            'string'   => ['foo'],
        ];
    }

    /**
     *
     * @dataProvider invalidPostcodesData
     *
     * @param $postcode
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalidPostcode($postcode): void
    {
        $input = $this->validInput;
        $input[self::LOC_POSTCODE] = $postcode;
        new Input($input);
    }

    /**
     * @return array
     */
    public function invalidPostcodesData(): array
    {
        return [
            'lowercase'       => ['nw43qb'],
            'contains spaces' => ['NW4 3QB'],
        ];
    }

    /**
     *
     * @dataProvider invalidCoversData
     *
     * @param $covers
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalidCovers($covers): void
    {
        $input = $this->validInput;
        $input[self::LOC_COVERS] = $covers;
        new Input($input);
    }

    /**
     * @return array
     */
    public function invalidCoversData(): array
    {
        return [
            'negative' => [-1],
            'zero'     => [0],
        ];
    }

    /**
     * @dataProvider getSuggestionsData
     *
     * @param string $postcode
     * @param string $date
     * @param string $time
     * @param int    $covers
     * @param string $expected
     */
    public function testGetSuggestions(string $postcode, string $date, string $time, int $covers, string $expected): void
    {
        $input = $this->validInput;
        $input[self::LOC_POSTCODE] = $postcode;
        $input[self::LOC_DATE] = $date;
        $input[self::LOC_TIME] = $time;
        $input[self::LOC_COVERS] = $covers;

        $data = new Input($input);
        $data->setCurrentTime(New DateTime('2015-10-20 00:00'));
        $this->assertEquals($expected, $data->getSuggestions());
    }

    /**
     * @return array
     */
    public function getSuggestionsData(): array
    {
        return [
            'No matching area'                   => [
                'postcode' => 'W69AX',
                'date'     => '24/10/15',
                'time'     => '11:00',
                'covers'   => 1,
                'expect'   => '',
            ],
            'Matching area'                      => [
                'postcode' => 'NW43QB',
                'date'     => '24/10/15',
                'time'     => '11:00',
                'covers'   => 1,
                'expect'   => "Premium meat selection;\nBreakfast;gluten,eggs\n",
            ],
            'Not enough notice given'            => [
                'postcode' => 'NW43QB',
                'date'     => '20/10/15',
                'time'     => '00:00',
                'covers'   => 1,
                'expect'   => '',
            ],
            'Enough notice for some items'       => [
                'postcode' => 'NW43QB',
                'date'     => '21/10/15',
                'time'     => '11:00',
                'covers'   => 1,
                'expect'   => "Breakfast;gluten,eggs\n",
            ],
            'Can exactly cover the requested amount' => [
                'postcode' => 'NW43QB',
                'date'     => '21/10/15',
                'time'     => '11:00',
                'covers'   => 40,
                'expect'   => "Breakfast;gluten,eggs\n",
            ],
            'Can not cover the requested amount' => [
                'postcode' => 'NW43QB',
                'date'     => '21/10/15',
                'time'     => '11:00',
                'covers'   => 50,
                'expect'   => '',
            ],
        ];
    }
}
