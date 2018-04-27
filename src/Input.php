<?php

final class Input
{
    /**
     * @var string
     */
    private $delimiter = ';';
    /**
     * @var string
     */
    private $rightInputFormat = 'filename dd/mm/yy hh:mm postcode amount';
    /**
     * @var string
     */
    private $pathToVendorFile;
    /**
     * @var DateTime
     */
    private $datetime;
    /**
     * @var string
     */
    private $postcode;
    /**
     * @var integer
     */
    private $covers;
    /**
     * @var DateTime|null
     */
    private $runAsIfDatetime;

    /**
     * Input constructor.
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->assertCorrectNrOfArguments($input);
        $this->validateFileAndSetVendors($input[0]);
        $this->validateAndSetDatetime($input[1], $input[2]);
        $this->validateAndSetPostcode($input[3]);
        $this->validateAndSetCovers($input[4]);
    }

    /**
     * @return string
     */
    public function getSuggestions(): string
    {
        $handle = fopen($this->pathToVendorFile, 'r');
        $suggestions = '';
        $suggestVendor = false;
        $newVendor = true;
        while (($line = fgets($handle)) !== false) {
            if ($line === PHP_EOL) {
                $newVendor = true;
                continue;
            }
            if ($newVendor) {
                $v = explode($this->delimiter, $line);
                $vendorPostcode = $v[1];
                $vendorCanCover = $v[2];
                $canDeliver = $this->getAreaOfPostcode($vendorPostcode) === $this->getAreaOfPostcode($this->postcode);
                $canCover = $vendorCanCover >= $this->covers;
                $suggestVendor = $canDeliver && $canCover;
            } elseif ($suggestVendor) {
                $advanceTimeNeeded = substr(explode($this->delimiter, $line)[2], 0, -2);
                if ($this->withinAdvanceTime($advanceTimeNeeded)) {
                    // Removing the advance time needed
                    $s = explode($this->delimiter, $line);
                    $suggestions .= $s[0] . $this->delimiter . $s[1] . PHP_EOL;
                }
            }
            $newVendor = false;
        }
        fclose($handle);
        return $suggestions;
    }

    /**
     * For debugging purpose only, changes the systems current time
     *
     * @param DateTime $dateTime
     */
    public function setCurrentTime(DateTime $dateTime)
    {
        $this->runAsIfDatetime = $dateTime;
    }

    /**
     * @param $input
     *
     * @throws InvalidArgumentException
     */
    private function assertCorrectNrOfArguments(array $input): void
    {
        $rightNrOfArguments = count(explode(' ', $this->rightInputFormat));
        if ($rightNrOfArguments !== count($input)) {
            throw new InvalidArgumentException(sprintf('Invalid input arguments. Right format is: "%s"', $this->rightInputFormat));
        }
    }

    /**
     * @param string $filename
     *
     * @throws InvalidArgumentException
     */
    private function validateFileAndSetVendors(string $filename): void
    {
        $fullPath = __DIR__ . '/../data/' . $filename;
        if (!file_exists($fullPath)) {
            throw new InvalidArgumentException(sprintf('Could not locate file "%s"', $filename));
        }
        $this->pathToVendorFile = $fullPath;
    }

    /**
     * @param string $date
     * @param string $time
     *
     * @throws InvalidArgumentException
     */
    private function validateAndSetDatetime(string $date, string $time): void
    {
        if (!preg_match('#^(3[01]|[12][0-9]|0[1-9])/(1[0-2]|0[1-9])/[0-9]{2}$#', $date)) {
            throw new InvalidArgumentException('Invalid date given. Must be in format "dd/mm/yy"');
        }
        $da = explode('/', $date);
        $dateYmdFormat = $da[2] . '-' . $da[1] . '-' . $da[0];
        if (!preg_match('#^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$#', $time)) {
            throw new InvalidArgumentException('Invalid time given. Must be in 24h format "hh:mm');
        }
        $this->datetime = new DateTime($dateYmdFormat . ' ' . $time);
    }

    /**
     * @param string $postcode
     *
     * @throws InvalidArgumentException
     */
    private function validateAndSetPostcode(string $postcode): void
    {
        // Based upon https://assets.publishing.service.gov.uk/government/uploads/system/uploads/attachment_data/file/404522/ILRSpecification_2015_16_Appendix_C_Feb2015_v1.pdf
        if (!preg_match('#^(GIR ?0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW])[0-9][ABD-HJLNP-UW-Z]{2})$#', $postcode)) {
            throw new InvalidArgumentException('Invalid postcode given.');
        }
        $this->postcode = $postcode;
    }

    /**
     * @param int $covers
     *
     * @throws InvalidArgumentException
     */
    private function validateAndSetCovers(int $covers): void
    {
        if ($covers < 1) {
            throw new InvalidArgumentException('Covers must be a number higher then zero.');
        }
        $this->covers = $covers;
    }

    /**
     * @param string $postcode
     *
     * @return string
     */
    private function getAreaOfPostcode(string $postcode): string
    {
        return substr($postcode, 0, strcspn($postcode, '0123456789'));
    }

    /**
     * @param int $advance time needed in hours
     *
     * @return bool
     */
    private function withinAdvanceTime(int $advance): bool
    {
        return $this->datetime->getTimestamp() > strtotime($advance . 'hours', $this->getCurrentTimestamp());
    }

    /**
     * @return int
     */
    private function getCurrentTimestamp(): int
    {
        if ($debugTime = $this->runAsIfDatetime) {
            return $debugTime->getTimestamp();
        }
        return time();
    }
}
