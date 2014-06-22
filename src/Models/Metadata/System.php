<?php namespace PHRETS\Models\Metadata;

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
