# Simple file reader

## Assumptions
 * Advance time is always given in hours

## How to use
Simply call the index file with the following parameters in this order:
filename dd/mm/yy hh:mm postcode amount

### Example:
`$ php cli/index.php vendors.txt 24/10/15 11:00 NW43QB 20`

### PHPUnit
`$ ./vendor/bin/phpunit --bootstrap vendor/autoload.php tests --testdox`
