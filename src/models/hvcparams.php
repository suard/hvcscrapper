<?php namespace Suard\HvcScrapper\Models;

/**
 * Parameter class needed for the main HvcScrapper class.
 *
 * @author Suard Damme <suardd@gmail.com>
 * @license MIT, https://opensource.org/licenses/MIT
 */
class HvcParams
{
    /**
     * The dutch postalcode (1000XX) for finding trash collections
     *
     * @var string
     */
    public $postalcode;

    /**
     * The housenumber for finding trash collections (eg. 10A).
     *
     * @var string
     */
    public $houseno;

    /**
     * Constructor of the HvcParams object.
     *
     * @param string $postalCode, a dutch postalcode
     * @param string $houseno
     * @throws \Exception Exception
     */
    public function __construct($postalCode, $houseno)
    {
        $this->postalcode = strtoupper(trim($postalCode));
        $this->houseno = trim($houseno);

        if(!$this->validatePostalcode())
        {
            throw new \Exception(sprintf('Postalcode % is not valid', $this->postalcode));
        }

        if(!$this->validateHouseNumber())
        {
            throw new \Exception(sprintf('Housenumber % is not valid', $this->houseno));
        }
    }

    /**
     * Checks if a dutch postalcode (1000AA) is valid.
     *
     * @return bool
     */
    private function validatePostalcode()
    {
        if (preg_match('~\A[1-9]\d{3}?[a-zA-Z]{2}\z~', $this->postalcode, $matches))
        {
            return true;
        }

        return false;
    }

    /**
     * Validates a housenumber with a regular expression; 11, 24A are valid,
     * 1 1, 24 A are not valid.
     *
     * @return bool
     */
    private function validateHouseNumber()
    {
        // check if first character is an integer
        if((string)(int)$this->houseno[0] != $this->houseno[0])
        {
            return false;
        }

        // pregmatch that checks alfanumeric
        if (preg_match('/^[a-zA-Z0-9]*$/', $this->houseno, $matches))
        {
            return true;
        }

        return false;
    }
}
