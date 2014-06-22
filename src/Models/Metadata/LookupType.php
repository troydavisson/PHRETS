<?php namespace PHRETS\Models\Metadata;

class LookupType extends Base
{
    protected $elements = [
        'MetadataEntryID',
        'LongValue',
        'ShortValue',
        'Value',
    ];
    protected $attributes = [
        'Version',
        'Date',
        'Resource',
        'Lookup',
    ];
}
