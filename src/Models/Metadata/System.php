<?php namespace PHRETS\Models\Metadata;

/**
 * Class System
 * @package PHRETS\Models\Metadata
 * @method string getSystemID
 * @method string getSystemDescription
 * @method string getTimeZoneOffset
 * @method string getComments
 * @method string getVersion
 */
class System extends Base
{
    protected $elements = [
        'SystemID',
        'SystemDescription',
        'TimeZoneOffset',
        'Comments',
        'Version',
    ];

    public function getResources()
    {
        return $this->getSession()->GetResourcesMetadata();
    }
}
